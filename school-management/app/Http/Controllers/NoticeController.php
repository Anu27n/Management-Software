<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Models\NotificationSetting;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $canManageNotices = $user->hasPermission('notices.manage');
        $query = Notice::with(['author', 'schoolClass']);

        if ($canManageNotices && $request->filled('target_audience')) {
            $query->where('target_audience', $request->target_audience);
        }

        if (!$canManageNotices) {
            $query->where('is_published', true)
                ->whereDate('publish_date', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now());
                })
                ->where(function ($q) use ($user) {
                    $q->where('target_audience', 'all');

                    if ($user->isParent()) {
                        $q->orWhere('target_audience', 'parents');
                    }

                    if ($user->isStudent()) {
                        $q->orWhere('target_audience', 'students');
                    }
                });

            if ($user->isParent()) {
                $classIds = Student::where('parent_user_id', $user->id)->pluck('class_id')->unique()->filter();
                if ($classIds->isNotEmpty()) {
                    $query->where(function ($q) use ($classIds) {
                        $q->whereNull('class_id')->orWhereIn('class_id', $classIds);
                    });
                } else {
                    $query->whereNull('class_id');
                }
            }

            if ($user->isStudent()) {
                $classIds = Student::where('email', $user->email)->pluck('class_id')->unique()->filter();
                if ($classIds->isNotEmpty()) {
                    $query->where(function ($q) use ($classIds) {
                        $q->whereNull('class_id')->orWhereIn('class_id', $classIds);
                    });
                } else {
                    $query->whereNull('class_id');
                }
            }
        }

        $notices = $query->latest()->paginate(20);
        return view('notices.index', compact('notices'));
    }

    public function create()
    {
        $this->ensureCanManageNotices();
        $classes = SchoolClass::all();
        return view('notices.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $this->ensureCanManageNotices();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_audience' => 'required|in:all,teachers,parents,students',
            'class_id' => 'nullable|exists:classes,id',
            'publish_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:publish_date',
            'attachment' => 'nullable|file|max:10240',
            'is_published' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_published'] = $request->has('is_published');

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('notices', 'public');
        }

        $notice = Notice::create($validated);

        if ($notice->is_published) {
            $this->sendNoticeEmails($notice);
        }

        return redirect()->route('notices.index')->with('success', 'Notice created.');
    }

    public function show(Notice $notice)
    {
        $user = auth()->user();

        if (!$user->hasPermission('notices.manage')) {
            abort_unless($this->canUserViewNotice($notice), 403, 'Unauthorized.');
        }

        $notice->load(['author', 'schoolClass']);
        return view('notices.show', compact('notice'));
    }

    public function edit(Notice $notice)
    {
        $this->ensureCanManageNotices();
        $classes = SchoolClass::all();
        return view('notices.edit', compact('notice', 'classes'));
    }

    public function update(Request $request, Notice $notice)
    {
        $this->ensureCanManageNotices();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_audience' => 'required|in:all,teachers,parents,students',
            'class_id' => 'nullable|exists:classes,id',
            'publish_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:publish_date',
            'attachment' => 'nullable|file|max:10240',
            'is_published' => 'boolean',
        ]);

        $validated['is_published'] = $request->has('is_published');

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('notices', 'public');
        }

        $notice->update($validated);
        return redirect()->route('notices.index')->with('success', 'Notice updated.');
    }

    public function destroy(Notice $notice)
    {
        $this->ensureCanManageNotices();
        $notice->delete();
        return redirect()->route('notices.index')->with('success', 'Notice deleted.');
    }

    private function ensureCanManageNotices(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->hasPermission('notices.manage'), 403, 'Unauthorized.');
    }

    private function canUserViewNotice(Notice $notice): bool
    {
        $user = auth()->user();

        if (!$notice->is_published || !$notice->publish_date?->lte(now())) {
            return false;
        }

        $audienceAllowed = $notice->target_audience === 'all'
            || ($user->isParent() && $notice->target_audience === 'parents')
            || ($user->isStudent() && $notice->target_audience === 'students');

        if (!$audienceAllowed) {
            return false;
        }

        if (!$notice->class_id) {
            return true;
        }

        if ($user->isParent()) {
            return Student::where('parent_user_id', $user->id)->where('class_id', $notice->class_id)->exists();
        }

        if ($user->isStudent()) {
            return Student::where('email', $user->email)->where('class_id', $notice->class_id)->exists();
        }

        return false;
    }

    private function sendNoticeEmails(Notice $notice): void
    {
        $settings = NotificationSetting::first();
        if (!$settings || !$settings->mail_enabled) {
            return;
        }

        try {
            if ($settings->mail_host && $settings->mail_port && $settings->mail_username) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $settings->mail_host,
                    'mail.mailers.smtp.port' => (int) $settings->mail_port,
                    'mail.mailers.smtp.encryption' => $settings->mail_encryption,
                    'mail.mailers.smtp.username' => $settings->mail_username,
                    'mail.mailers.smtp.password' => $settings->mail_password,
                    'mail.from.address' => $settings->mail_from_address ?: config('mail.from.address'),
                    'mail.from.name' => $settings->mail_from_name ?: config('mail.from.name'),
                ]);
            }

            $emails = collect();

            if (in_array($notice->target_audience, ['all', 'teachers'], true)) {
                $emails = $emails->merge(
                    User::where('role', 'teacher')->where('is_active', true)->whereNotNull('email')->pluck('email')
                );
            }

            if (in_array($notice->target_audience, ['all', 'parents'], true)) {
                $parentQuery = User::where('role', 'parent')->where('is_active', true)->whereNotNull('email');
                if ($notice->class_id) {
                    $parentQuery->whereHas('students', function ($q) use ($notice) {
                        $q->where('class_id', $notice->class_id);
                    });
                }
                $emails = $emails->merge($parentQuery->pluck('email'));
            }

            if (in_array($notice->target_audience, ['all', 'students'], true)) {
                $studentUserQuery = User::where('role', 'student')->where('is_active', true)->whereNotNull('email');
                if ($notice->class_id) {
                    $studentUserQuery->whereIn('email', Student::where('class_id', $notice->class_id)->pluck('email'));
                }
                $emails = $emails->merge($studentUserQuery->pluck('email'));

                $studentProfileEmails = Student::whereNotNull('email');
                if ($notice->class_id) {
                    $studentProfileEmails->where('class_id', $notice->class_id);
                }
                $emails = $emails->merge($studentProfileEmails->pluck('email'));
            }

            $emails = $emails
                ->filter(fn ($email) => !empty($email))
                ->map(fn ($email) => strtolower(trim((string) $email)))
                ->unique()
                ->values();

            if ($emails->isEmpty()) {
                return;
            }

            foreach ($emails as $email) {
                Mail::raw(
                    "New notice from School Management System\n\nTitle: {$notice->title}\n\n" . strip_tags($notice->content),
                    function ($message) use ($email, $notice) {
                        $message->to($email)->subject('New Notice: ' . $notice->title);
                    }
                );
            }
        } catch (\Throwable $e) {
            // Do not block notice creation if email dispatch fails.
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\NotificationSetting;
use App\Models\PaymentGatewaySetting;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    public function siteSettings()
    {
        $settings = SiteSetting::current();

        return view('settings.site-settings', compact('settings'));
    }

    public function updateSiteSettings(Request $request)
    {
        $settings = SiteSetting::current();

        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'contact_number' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|max:2048',
            'favicon' => 'nullable|image|max:1024',
            'border_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_fill_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'title_bar_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'title_text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'school_name_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'page_text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('site-settings', 'public');
        }

        if ($request->hasFile('favicon')) {
            if ($settings->favicon_path) {
                Storage::disk('public')->delete($settings->favicon_path);
            }

            $validated['favicon_path'] = $request->file('favicon')->store('site-settings', 'public');
        }

        $settings->update([
            'school_name' => $validated['school_name'],
            'address' => $validated['address'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'logo_path' => $validated['logo_path'] ?? $settings->logo_path,
            'favicon_path' => $validated['favicon_path'] ?? $settings->favicon_path,
            'border_color' => $validated['border_color'] ?? '#7a4a00',
            'header_fill_color' => $validated['header_fill_color'] ?? '#e8d5a3',
            'title_bar_color' => $validated['title_bar_color'] ?? '#b8860b',
            'title_text_color' => $validated['title_text_color'] ?? '#ffffff',
            'school_name_color' => $validated['school_name_color'] ?? '#8b0000',
            'page_text_color' => $validated['page_text_color'] ?? '#1a0a00',
        ]);

        return redirect()->route('settings.site')->with('success', 'Site settings updated.');
    }

    public function logoAsset(): StreamedResponse
    {
        $settings = SiteSetting::current();
        abort_unless($settings->logo_path && Storage::disk('public')->exists($settings->logo_path), 404);

        return Storage::disk('public')->response($settings->logo_path);
    }

    public function faviconAsset(): StreamedResponse
    {
        $settings = SiteSetting::current();
        abort_unless($settings->favicon_path && Storage::disk('public')->exists($settings->favicon_path), 404);

        return Storage::disk('public')->response($settings->favicon_path);
    }

    public function classes()
    {
        $classes = SchoolClass::withCount(['sections', 'students'])->get();
        return view('settings.classes', compact('classes'));
    }

    public function storeClass(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'numeric_name' => 'nullable|string|max:10',
        ]);

        SchoolClass::create($validated);
        return redirect()->route('settings.classes')->with('success', 'Class created.');
    }

    public function destroyClass(SchoolClass $class)
    {
        $class->delete();
        return redirect()->route('settings.classes')->with('success', 'Class deleted.');
    }

    public function sections()
    {
        $sections = Section::with('schoolClass')->get();
        $classes = SchoolClass::all();
        return view('settings.sections', compact('sections', 'classes'));
    }

    public function storeSection(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'name' => 'required|string|max:255',
        ]);

        Section::create($validated);
        return redirect()->route('settings.sections')->with('success', 'Section created.');
    }

    public function destroySection(Section $section)
    {
        $section->delete();
        return redirect()->route('settings.sections')->with('success', 'Section deleted.');
    }

    public function subjects()
    {
        $subjects = Subject::with('schoolClass')->get();
        $classes = SchoolClass::all();
        return view('settings.subjects', compact('subjects', 'classes'));
    }

    public function storeSubject(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'class_id' => 'required|exists:classes,id',
        ]);

        Subject::create($validated);
        return redirect()->route('settings.subjects')->with('success', 'Subject created.');
    }

    public function destroySubject(Subject $subject)
    {
        $subject->delete();
        return redirect()->route('settings.subjects')->with('success', 'Subject deleted.');
    }

    public function academicYears()
    {
        $academicYears = AcademicYear::latest()->get();
        return view('settings.academic-years', compact('academicYears'));
    }

    public function storeAcademicYear(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        if ($validated['is_active']) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        AcademicYear::create($validated);
        return redirect()->route('settings.academic-years')->with('success', 'Academic year created.');
    }

    public function destroyAcademicYear(AcademicYear $year)
    {
        $year->delete();
        return redirect()->route('settings.academic-years')->with('success', 'Academic year deleted.');
    }

    public function notifications()
    {
        $settings = NotificationSetting::firstOrCreate([], [
            'mail_enabled' => false,
            'sms_enabled' => false,
        ]);

        return view('settings.notifications', compact('settings'));
    }

    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'mail_from_name' => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|in:tls,ssl,none',
            'sms_provider' => 'nullable|string|max:255',
            'sms_sender_id' => 'nullable|string|max:50',
            'sms_api_key' => 'nullable|string|max:255',
            'sms_api_secret' => 'nullable|string|max:255',
        ]);

        $settings = NotificationSetting::firstOrCreate([]);
        $settings->update([
            ...$validated,
            'mail_enabled' => $request->boolean('mail_enabled'),
            'mail_encryption' => $request->input('mail_encryption') === 'none' ? null : $request->input('mail_encryption'),
            'sms_enabled' => $request->boolean('sms_enabled'),
        ]);

        return redirect()->route('settings.notifications')->with('success', 'Notification settings updated.');
    }

    public function paymentGateway()
    {
        $settings = PaymentGatewaySetting::firstOrCreate(
            ['provider' => 'razorpay'],
            [
                'display_name' => 'Razorpay',
                'is_enabled' => false,
                'test_mode' => true,
                'currency' => 'INR',
            ]
        );

        return view('settings.payment-gateway', compact('settings'));
    }

    public function updatePaymentGateway(Request $request)
    {
        $settings = PaymentGatewaySetting::firstOrCreate(['provider' => 'razorpay']);
        $requiresCredentials = $request->boolean('is_enabled')
            && (blank($settings->key_id) || blank($settings->key_secret));

        $validated = $request->validate([
            'is_enabled' => 'required|boolean',
            'test_mode' => 'required|boolean',
            'display_name' => 'required|string|max:255',
            'key_id' => ['nullable', 'string', 'max:255', Rule::requiredIf($requiresCredentials)],
            'key_secret' => ['nullable', 'string', 'max:255', Rule::requiredIf($requiresCredentials)],
            'webhook_secret' => 'nullable|string|max:255',
            'currency' => 'required|string|size:3',
            'description' => 'nullable|string|max:500',
        ]);

        $settings->update([
            'display_name' => $validated['display_name'],
            'is_enabled' => $request->boolean('is_enabled'),
            'test_mode' => $request->boolean('test_mode'),
            'key_id' => $validated['key_id'] ?? $settings->key_id,
            'key_secret' => $validated['key_secret'] ?? $settings->key_secret,
            'webhook_secret' => $validated['webhook_secret'] ?? $settings->webhook_secret,
            'currency' => strtoupper($validated['currency']),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('settings.payment-gateway')
            ->with('success', 'Payment gateway settings updated.');
    }
}

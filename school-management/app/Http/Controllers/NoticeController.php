<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $query = Notice::with(['author', 'schoolClass']);

        if ($request->filled('target_audience')) {
            $query->where('target_audience', $request->target_audience);
        }

        $notices = $query->latest()->paginate(20);
        return view('notices.index', compact('notices'));
    }

    public function create()
    {
        $classes = SchoolClass::all();
        return view('notices.create', compact('classes'));
    }

    public function store(Request $request)
    {
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

        Notice::create($validated);
        return redirect()->route('notices.index')->with('success', 'Notice created.');
    }

    public function show(Notice $notice)
    {
        $notice->load(['author', 'schoolClass']);
        return view('notices.show', compact('notice'));
    }

    public function edit(Notice $notice)
    {
        $classes = SchoolClass::all();
        return view('notices.edit', compact('notice', 'classes'));
    }

    public function update(Request $request, Notice $notice)
    {
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
        $notice->delete();
        return redirect()->route('notices.index')->with('success', 'Notice deleted.');
    }
}

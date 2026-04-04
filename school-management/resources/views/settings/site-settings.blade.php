@extends('layouts.app')
@section('title', 'Site Settings')
@section('page-title', 'Site Settings')

@section('content')
<div class="card table-card">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-globe2 me-1"></i>Site Settings</h6>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            Save the school branding and contact information used in the software and on the report card PDF.
        </div>

        <form action="{{ route('settings.site.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">School Name</label>
                    <input type="text" name="school_name" class="form-control" value="{{ old('school_name', $settings->school_name) }}" placeholder="Enter school name">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number', $settings->contact_number) }}" placeholder="Phone / mobile">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email', $settings->contact_email) }}" placeholder="school@example.com">
                </div>
                <div class="col-12">
                    <label class="form-label">School Address</label>
                    <textarea name="address" rows="3" class="form-control" placeholder="Enter school address">{{ old('address', $settings->address) }}</textarea>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">School Logo</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    @if($settings->logo_path)
                        <div class="mt-2 small text-muted">Current logo:</div>
                        <img src="{{ $settings->logo_url }}" alt="School logo" class="border rounded p-1 bg-white" style="max-height: 72px; width: auto;">
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">Favicon</label>
                    <input type="file" name="favicon" class="form-control" accept="image/*">
                    @if($settings->favicon_path)
                        <div class="mt-2 small text-muted">Current favicon:</div>
                        <img src="{{ $settings->favicon_url }}" alt="Favicon" class="border rounded p-1 bg-white" style="max-height: 40px; width: auto;">
                    @endif
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3">Report Card Colors</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Border Color</label>
                    <input type="color" name="border_color" class="form-control form-control-color" value="{{ old('border_color', $settings->border_color ?? '#7a4a00') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Header Fill Color</label>
                    <input type="color" name="header_fill_color" class="form-control form-control-color" value="{{ old('header_fill_color', $settings->header_fill_color ?? '#e8d5a3') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Title Bar Color</label>
                    <input type="color" name="title_bar_color" class="form-control form-control-color" value="{{ old('title_bar_color', $settings->title_bar_color ?? '#b8860b') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Title Text Color</label>
                    <input type="color" name="title_text_color" class="form-control form-control-color" value="{{ old('title_text_color', $settings->title_text_color ?? '#ffffff') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">School Name Color</label>
                    <input type="color" name="school_name_color" class="form-control form-control-color" value="{{ old('school_name_color', $settings->school_name_color ?? '#8b0000') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Page Text Color</label>
                    <input type="color" name="page_text_color" class="form-control form-control-color" value="{{ old('page_text_color', $settings->page_text_color ?? '#1a0a00') }}">
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Site Settings</button>
        </form>
    </div>
</div>
@endsection

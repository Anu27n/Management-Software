@extends('layouts.app')
@section('title', 'Notification Settings')
@section('page-title', 'Email & SMS Notifications')

@section('content')
<div class="card table-card">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-bell me-1"></i>Notification Configuration</h6>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            Save your email and SMS provider credentials here. These settings are used by system notification features.
        </div>

        <form action="{{ route('settings.notifications.update') }}" method="POST">
            @csrf

            <h6 class="fw-semibold text-primary mb-3">Email (SMTP)</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Enable Email</label>
                    <select name="mail_enabled" class="form-select">
                        <option value="1" {{ $settings->mail_enabled ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ !$settings->mail_enabled ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Name</label>
                    <input type="text" name="mail_from_name" class="form-control" value="{{ old('mail_from_name', $settings->mail_from_name) }}" placeholder="School Management">
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Email</label>
                    <input type="email" name="mail_from_address" class="form-control" value="{{ old('mail_from_address', $settings->mail_from_address) }}" placeholder="noreply@yourschool.com">
                </div>
                <div class="col-md-3">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" name="mail_host" class="form-control" value="{{ old('mail_host', $settings->mail_host) }}" placeholder="smtp.gmail.com">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Port</label>
                    <input type="number" name="mail_port" class="form-control" value="{{ old('mail_port', $settings->mail_port) }}" placeholder="587">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Username</label>
                    <input type="text" name="mail_username" class="form-control" value="{{ old('mail_username', $settings->mail_username) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Password</label>
                    <input type="text" name="mail_password" class="form-control" value="{{ old('mail_password', $settings->mail_password) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Encryption</label>
                    <select name="mail_encryption" class="form-select">
                        <option value="none" {{ empty($settings->mail_encryption) ? 'selected' : '' }}>None</option>
                        <option value="tls" {{ $settings->mail_encryption === 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ $settings->mail_encryption === 'ssl' ? 'selected' : '' }}>SSL</option>
                    </select>
                </div>
            </div>

            <h6 class="fw-semibold text-primary mb-3">SMS Gateway</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Enable SMS</label>
                    <select name="sms_enabled" class="form-select">
                        <option value="1" {{ $settings->sms_enabled ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ !$settings->sms_enabled ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Provider Name</label>
                    <input type="text" name="sms_provider" class="form-control" value="{{ old('sms_provider', $settings->sms_provider) }}" placeholder="Twilio / MSG91 / Fast2SMS">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sender ID</label>
                    <input type="text" name="sms_sender_id" class="form-control" value="{{ old('sms_sender_id', $settings->sms_sender_id) }}" placeholder="SCHOOL">
                </div>
                <div class="col-md-3">
                    <label class="form-label">API Key</label>
                    <input type="text" name="sms_api_key" class="form-control" value="{{ old('sms_api_key', $settings->sms_api_key) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">API Secret / Token</label>
                    <input type="text" name="sms_api_secret" class="form-control" value="{{ old('sms_api_secret', $settings->sms_api_secret) }}">
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Notification Settings</button>
        </form>
    </div>
</div>
@endsection

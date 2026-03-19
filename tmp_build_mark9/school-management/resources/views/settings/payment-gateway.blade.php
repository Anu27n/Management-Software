@extends('layouts.app')
@section('title', 'Payment Gateway Settings')
@section('page-title', 'Fee Payment Gateway')

@section('content')
@php
    $selectedEnabled = (int) old('is_enabled', (int) $settings->is_enabled);
    $selectedTestMode = (int) old('test_mode', (int) $settings->test_mode);
@endphp

<div class="card table-card">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-credit-card-2-front me-1"></i>Razorpay Configuration</h6>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-4">
            Configure Razorpay credentials to accept online fee payments. Use test keys in test mode before switching to live mode.
        </div>

        <form action="{{ route('settings.payment-gateway.update') }}" method="POST" class="row g-3">
            @csrf

            <div class="col-md-3">
                <label class="form-label">Enable Razorpay</label>
                <select name="is_enabled" class="form-select" required>
                    <option value="1" {{ $selectedEnabled === 1 ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ $selectedEnabled === 0 ? 'selected' : '' }}>No</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Test Mode</label>
                <select name="test_mode" class="form-select" required>
                    <option value="1" {{ $selectedTestMode === 1 ? 'selected' : '' }}>Enabled</option>
                    <option value="0" {{ $selectedTestMode === 0 ? 'selected' : '' }}>Disabled</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Display Name</label>
                <input type="text" name="display_name" class="form-control" value="{{ old('display_name', $settings->display_name ?? 'Razorpay') }}" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Currency</label>
                <input type="text" name="currency" class="form-control text-uppercase" maxlength="3" value="{{ old('currency', $settings->currency ?? 'INR') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Key ID</label>
                <input type="text" name="key_id" class="form-control" value="{{ old('key_id', $settings->key_id) }}" placeholder="rzp_test_xxxxx">
            </div>

            <div class="col-md-6">
                <label class="form-label">Key Secret</label>
                <input type="text" name="key_secret" class="form-control" value="{{ old('key_secret', $settings->key_secret) }}" placeholder="Enter Razorpay secret key">
            </div>

            <div class="col-md-6">
                <label class="form-label">Webhook Secret (Optional)</label>
                <input type="text" name="webhook_secret" class="form-control" value="{{ old('webhook_secret', $settings->webhook_secret) }}" placeholder="Webhook signature secret">
            </div>

            <div class="col-md-6">
                <label class="form-label">Description (Optional)</label>
                <textarea name="description" rows="2" class="form-control" placeholder="Shown internally for gateway notes">{{ old('description', $settings->description) }}</textarea>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Payment Settings</button>
                <a href="{{ route('fees.payments') }}" class="btn btn-outline-secondary">Back to Fee Payments</a>
            </div>
        </form>
    </div>
</div>
@endsection

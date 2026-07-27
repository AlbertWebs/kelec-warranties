@extends('layouts.admin')
@section('title', 'Settings')
@section('content')
<h1 class="mb-4 text-2xl font-bold">System Settings</h1>
<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
@csrf @method('PUT')
<section class="rounded-xl border bg-white p-4 shadow-sm">
<h2 class="font-semibold">General</h2>
<div class="mt-3 grid gap-3 md:grid-cols-2">
<input name="company_name" value="{{ old('company_name', $settings['company_name'] ?? 'K-Elec') }}" class="rounded-lg border-slate-300" placeholder="Company name" required>
<input name="support_phone" value="{{ old('support_phone', $settings['support_phone'] ?? '') }}" class="rounded-lg border-slate-300" placeholder="Support phone">
<input name="support_email" value="{{ old('support_email', $settings['support_email'] ?? '') }}" class="rounded-lg border-slate-300" placeholder="Support email">
<input name="application_url" value="{{ old('application_url', $settings['application_url'] ?? url('/')) }}" class="rounded-lg border-slate-300" placeholder="Application URL">
<input name="default_timezone" value="{{ old('default_timezone', $settings['default_timezone'] ?? 'Africa/Nairobi') }}" class="rounded-lg border-slate-300" required>
<input name="default_date_format" value="{{ old('default_date_format', $settings['default_date_format'] ?? 'd M Y') }}" class="rounded-lg border-slate-300" required>
</div>
</section>
<section class="rounded-xl border bg-white p-4 shadow-sm">
<h2 class="font-semibold">Warranty</h2>
<div class="mt-3 grid gap-3 md:grid-cols-2">
<input type="number" name="default_warranty_months" value="{{ old('default_warranty_months', $settings['default_warranty_months'] ?? 12) }}" class="rounded-lg border-slate-300" required>
<input type="number" name="registration_grace_days" value="{{ old('registration_grace_days', $settings['registration_grace_days'] ?? 30) }}" class="rounded-lg border-slate-300" required>
<input name="warranty_reference_prefix" value="{{ old('warranty_reference_prefix', $settings['warranty_reference_prefix'] ?? 'KEL-WTY') }}" class="rounded-lg border-slate-300" required>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_manual_verification" value="1" @checked(old('allow_manual_verification', $settings['allow_manual_verification'] ?? true))> Allow manual verification</label>
</div>
</section>
<section class="rounded-xl border bg-white p-4 shadow-sm">
<h2 class="font-semibold">Privacy &amp; legal</h2>
<p class="mt-2 text-sm text-slate-600">Edit the full Privacy Policy and Warranty Terms on the dedicated Legal Pages screen (supports Markdown).</p>
<a href="{{ route('admin.legal.edit') }}" class="mt-3 inline-flex rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">Open Legal Pages editor</a>
<div class="mt-4 grid gap-3 md:grid-cols-2">
<input name="privacy_policy_url" value="{{ old('privacy_policy_url', $settings['privacy_policy_url'] ?? '') }}" class="rounded-lg border-slate-300" placeholder="Privacy Policy URL">
<input name="warranty_terms_url" value="{{ old('warranty_terms_url', $settings['warranty_terms_url'] ?? '') }}" class="rounded-lg border-slate-300" placeholder="Warranty Terms URL">
</div>
</section>
<section class="rounded-xl border bg-white p-4 shadow-sm">
<h2 class="font-semibold">Odoo</h2>
<div class="mt-3 grid gap-3 md:grid-cols-2">
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="odoo_enabled" value="1" @checked(old('odoo_enabled', $settings['odoo_enabled'] ?? false))> Enabled</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="odoo_mock_mode" value="1" @checked(old('odoo_mock_mode', $settings['odoo_mock_mode'] ?? true))> Mock mode</label>
<input name="odoo_base_url" value="{{ old('odoo_base_url', $settings['odoo_base_url'] ?? '') }}" class="rounded-lg border-slate-300" placeholder="Base URL">
<input name="odoo_database" value="{{ old('odoo_database', $settings['odoo_database'] ?? '') }}" class="rounded-lg border-slate-300" placeholder="Database">
<input name="odoo_username" value="{{ old('odoo_username', $settings['odoo_username'] ?? '') }}" class="rounded-lg border-slate-300" placeholder="Username">
<input type="password" name="odoo_api_key" value="" class="rounded-lg border-slate-300" placeholder="API key (leave blank to keep)">
<input type="number" name="odoo_timeout" value="{{ old('odoo_timeout', $settings['odoo_timeout'] ?? 15) }}" class="rounded-lg border-slate-300">
</div>
</section>
<section class="rounded-xl border bg-white p-4 shadow-sm">
<h2 class="font-semibold">SMS</h2>
<div class="mt-3 grid gap-3 md:grid-cols-2">
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="sms_enabled" value="1" @checked(old('sms_enabled', $settings['sms_enabled'] ?? false))> Enabled</label>
<input name="sms_endpoint" value="{{ old('sms_endpoint', $settings['sms_endpoint'] ?? '') }}" class="rounded-lg border-slate-300" placeholder="Endpoint URL">
<select name="sms_http_method" class="rounded-lg border-slate-300"><option value="POST" @selected(($settings['sms_http_method'] ?? 'POST') === 'POST')>POST</option><option value="GET" @selected(($settings['sms_http_method'] ?? '') === 'GET')>GET</option></select>
<input type="password" name="sms_api_key" class="rounded-lg border-slate-300" placeholder="API key (leave blank to keep)">
<input name="sms_sender_id" value="{{ old('sms_sender_id', $settings['sms_sender_id'] ?? '') }}" class="rounded-lg border-slate-300" placeholder="Sender ID">
<input name="sms_auth_header" value="{{ old('sms_auth_header', $settings['sms_auth_header'] ?? 'Authorization') }}" class="rounded-lg border-slate-300">
<input name="sms_phone_param" value="{{ old('sms_phone_param', $settings['sms_phone_param'] ?? 'to') }}" class="rounded-lg border-slate-300">
<input name="sms_message_param" value="{{ old('sms_message_param', $settings['sms_message_param'] ?? 'message') }}" class="rounded-lg border-slate-300">
<input type="number" name="sms_timeout" value="{{ old('sms_timeout', $settings['sms_timeout'] ?? 15) }}" class="rounded-lg border-slate-300">
</div>
</section>
<section class="rounded-xl border bg-white p-4 shadow-sm">
<h2 class="font-semibold">Email</h2>
<div class="mt-3 grid gap-3 md:grid-cols-2">
<input name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address'] ?? '') }}" class="rounded-lg border-slate-300" placeholder="From address">
<input name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name'] ?? 'K-Elec Warranties') }}" class="rounded-lg border-slate-300" placeholder="From name">
</div>
<p class="mt-2 text-sm text-slate-500">SMTP host/port/username/password are configured via environment variables for AWS SES.</p>
</section>
<button class="rounded-lg bg-red-700 px-4 py-2 text-white">Save settings</button>
</form>
@endsection

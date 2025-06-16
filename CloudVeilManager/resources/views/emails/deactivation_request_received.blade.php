<strong>Hello {{ $user->name }},</strong>
<p>This is a notification from {{ config('app.company_name') }}.  We have received a deactivation request from your device: {{ $deactivationRequest->device_id }}</p>
<p>Please reply to this email with your contact information to confirm your deactivation request.</p>

<p>Best Regards</p>
<p><strong>{{ config('app.company_name') }} Support</strong></p>

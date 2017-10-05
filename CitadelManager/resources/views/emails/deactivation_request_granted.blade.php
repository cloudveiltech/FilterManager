<strong>Hello {{ $user->name }},</strong>
<p>This is a notification from {{ config('app.company_name') }}.  We have received and granted a deactivation request from your device: {{ $deactivationRequest->device_id }}</p>
<p>Press deactivate again to disable the filter.</p>

<p>Best Regards</p>
<p><strong>{{ config('app.company_name') }} Support</strong></p>

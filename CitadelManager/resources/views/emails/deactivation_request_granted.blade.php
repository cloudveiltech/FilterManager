<strong>Hello {{ $user->name }},</strong>
<p>This is a notification from {{ config('app.company_name') }}.  We have received and granted a deactivation request from your device: {{ $deactivationRequest->device_id }}</p>
<p>Press deactivate again to disable the filter.
    @if($isMacOs)
        CloudVeil will be deactivated until the computer is restarted TWICE, then the Cloudveil for Mac app is opened and the requested permissions are granted.
    @else
        CloudVeil will be deactivated until the next system restart, or until the Cloudveil app is opened again.
    @endif
</p>

<p>Best Regards</p>
<p><strong>{{ config('app.company_name') }} Support</strong></p>

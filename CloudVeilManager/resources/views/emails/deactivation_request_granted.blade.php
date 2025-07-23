<strong>Hello {{ $user->name }},</strong>
<p>This is a notification from {{ config('app.company_name') }}.  We have received and granted a deactivation request from your device: {{ $deactivationRequest->device_id }}</p>
@if ( $platform == 'WIN')
    <p>Press deactivate again to disable the filter. CloudVeil will be deactivated until the next system restart, or until the Cloudveil app is opened again</p>
@elseif ( $platform == 'OSX')
    <p>Press deactivate again to disable the filter. Put in your password when prompted and the filter will be disabled.</p>
    <p>To reactivate the filter:</p>
    <ol>
        <li>Open the CloudVeil for Mac app</li>
        <li>Log in with your account email and the word <i>password</i> as the password.</li>
        <li>Click “Open Security Preferences” in the System Extension Blocked popup window.</li>
        <li>Click the lock to make changes.</li>
        <li>Enter your computer password and click Unlock.</li>
        <li>Click the Allow button to allow CloudVeil for Mac to make changes to the computer.</li>
        <li>Click the Allow button again to allow CloudVeil for Mac to add a VPN configuration.</li>
    </ol>
    <p>Your filter should be running now.</p>
@endif

<p>Best Regards</p>
<p><strong>{{ config('app.company_name') }} Support</strong></p>

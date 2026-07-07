<strong>Hello,</strong>
<p>This is a notification from {{ config('app.company_name') }}. We have received an activation request from you.</p>
<p>Enter this code to complete the sign in process.<br/>

<p>
    <h3>{{ wordwrap($code, 1, ' ', true)}}</h3>
</p>

<p>The code will expire in {{ \App\TwoFactorCode::EXPIRATION_INTERVAL_HR }} hour.<br/>

<p>Best Regards</p>

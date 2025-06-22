@component('mail::message')
# Hello {{ $user->first_name }},
Welcome to MU Connect!

Please verify your email address to activate your account.

@component('mail::button', ['url' => $verificationUrl])
Verify Email
@endcomponent

If you did not create an account, no further action is required.

Thanks,
{{ config('app.name') }}
@endcomponent

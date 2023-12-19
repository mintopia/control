<x-mail::message>
    # Verify Email Address

    Hi {{ $email->user->nickname }},

    This email address has been added to your account, but before it can be used, we need you to verify it.

    Please follow the link below to verify the email address.

    <x-mail::button :url="$url">
        Verify Now
    </x-mail::button>

    You can also enter the code **{{ $email->verification_code }}** at {{ route('emails.verify', $email->id) }}.

    This code will expire at {{ $email->getVerificationExpiry()->format('d M Y H:i') }}.

    Thanks,<br>
    @setting('name')
</x-mail::message>

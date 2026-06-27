<?php

namespace App\Rules;

use App\Services\Shared\Security\RecaptchaVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class Recaptcha implements ValidationRule
{
    public function __construct(
        private ?RecaptchaVerifier $verifier = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $verifier = $this->verifier ?? app(RecaptchaVerifier::class);
        $token = is_string($value) ? $value : null;

        if (! $verifier->verify($token)) {
            $fail('La verificación reCAPTCHA ha fallado.');
        }
    }
}

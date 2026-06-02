<?php

namespace App\Services\Security;

use ReCaptcha\ReCaptcha as GoogleReCaptcha;

final class RecaptchaVerifier
{
    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $recaptcha = new GoogleReCaptcha(config('services.recaptcha.secret'));

        return $recaptcha->verify($token, $remoteIp ?? request()->ip())->isSuccess();
    }
}

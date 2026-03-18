<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use ReCaptcha\ReCaptcha as GoogleReCaptcha;

class Recaptcha implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return false;
        }

        $recaptcha = new GoogleReCaptcha(config('services.recaptcha.secret'));
        $resp = $recaptcha->verify($value, request()->ip());

        return $resp->isSuccess();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'La verificación reCAPTCHA ha fallado.';
    }
}

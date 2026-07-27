<?php

declare(strict_types=1);

namespace App\Services;

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

/** Numeric 5-digit captcha, with the expected phrase held in the session. */
class CaptchaService
{
    private const SESSION_KEY = 'captcha_phrase';

    /** Returns the captcha as an inline base64 image. */
    public function generate(): string
    {
        $phraseBuilder = new PhraseBuilder(5, '0123456789');
        $builder = new CaptchaBuilder(null, $phraseBuilder);

        $builder->setIgnoreAllEffects(true);
        $builder->setDistortion(false);
        $builder->setInterpolation(false);
        $builder->setMaxBehindLines(0);
        $builder->setMaxFrontLines(0);
        $builder->setBackgroundColor(255, 255, 255);
        $builder->setTextColor(31, 41, 55);

        $builder->build(160, 50);

        session()->put(self::SESSION_KEY, $builder->getPhrase());

        return $builder->inline();
    }

    public function check(string $input): bool
    {
        $expected = session()->pull(self::SESSION_KEY);

        return is_string($expected) && trim($input) === $expected;
    }
}

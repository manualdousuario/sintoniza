<?php

declare(strict_types=1);

namespace Sintoniza\Service;

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Josantonius\Session\Session;

class CaptchaService
{
    private const SESSION_KEY = 'captcha_phrase';

    public function __construct(private Session $session) {}

    public function generate(): string
    {
        $this->ensureSessionStarted();

        $phraseBuilder = new PhraseBuilder(5, '0123456789');
        $builder       = new CaptchaBuilder(null, $phraseBuilder);

        $builder->setIgnoreAllEffects(true);
        $builder->setDistortion(false);
        $builder->setInterpolation(false);
        $builder->setMaxBehindLines(0);
        $builder->setMaxFrontLines(0);
        $builder->setBackgroundColor(255, 255, 255);
        $builder->setTextColor(31, 41, 55);

        $builder->build(160, 50);

        $this->session->set(self::SESSION_KEY, $builder->getPhrase());

        return $builder->inline();
    }

    public function check(string $input): bool
    {
        $this->ensureSessionStarted();

        $expected = $this->session->pull(self::SESSION_KEY);

        return is_string($expected) && trim($input) === $expected;
    }

    private function ensureSessionStarted(): void
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }
    }
}

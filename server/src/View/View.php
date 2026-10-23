<?php

declare(strict_types=1);

namespace App\View;

use DateTimeImmutable;
use DateTimeZone;

/** Рендер PHP-шаблонов из templates/ с хелперами экранирования и форматирования */
final class View
{
    public function __construct(
        private readonly string $templatesDir,
        private readonly DateTimeZone $timezone,
    ) {
    }

    /** @param array<string, mixed> $vars */
    public function render(string $template, array $vars = []): string
    {
        $file = $this->templatesDir . '/' . $template . '.php';
        $view = $this;

        return (static function () use ($file, $vars, $view): string {
            extract($vars, EXTR_SKIP);
            ob_start();
            try {
                require $file;

                return (string) ob_get_clean();
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
        })();
    }

    /** Экранирование для HTML — весь вывод данных в шаблонах идёт через него */
    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /** 1234567.8 -> "1 234 568 ₽" */
    public function money(mixed $value): string
    {
        return number_format((float) $value, 0, ',', "\u{00A0}") . "\u{00A0}₽";
    }

    public function number(mixed $value): string
    {
        return number_format((float) $value, 0, ',', "\u{00A0}");
    }

    public function dateTime(string $value): string
    {
        return (new DateTimeImmutable($value))->setTimezone($this->timezone)->format('d.m.Y, H:i');
    }
}

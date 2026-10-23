<?php

declare(strict_types=1);

namespace App\Application;

final readonly class ValidationResult
{
    /**
     * @param list<array{path: string, message: string}> $issues
     */
    private function __construct(
        public ?ApplicationInput $input,
        public array $issues,
    ) {
    }

    public static function valid(ApplicationInput $input): self
    {
        return new self($input, []);
    }

    /** @param list<array{path: string, message: string}> $issues */
    public static function invalid(array $issues): self
    {
        return new self(null, $issues);
    }

    public function isValid(): bool
    {
        return $this->input !== null;
    }
}

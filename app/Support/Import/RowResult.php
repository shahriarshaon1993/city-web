<?php

namespace App\Support\Import;

/**
 * The verdict on one row: the outcome, a plain-words reason for a skip or a failure, and the cleaned
 * values a definition needs to write it.
 */
final readonly class RowResult
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public RowOutcome $outcome,
        public ?string $message = null,
        public array $data = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function create(array $data = []): self
    {
        return new self(RowOutcome::Create, null, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function update(array $data = []): self
    {
        return new self(RowOutcome::Update, null, $data);
    }

    public static function skip(string $message): self
    {
        return new self(RowOutcome::Skip, $message);
    }

    public static function fail(string $message): self
    {
        return new self(RowOutcome::Fail, $message);
    }

    public function isFailure(): bool
    {
        return $this->outcome === RowOutcome::Fail;
    }

    public function writes(): bool
    {
        return $this->outcome === RowOutcome::Create || $this->outcome === RowOutcome::Update;
    }
}

<?php

namespace Redberry\LaravelCloudSdk\Data\Instances;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class ManagedQueueFailedJobData extends Data
{
    public function __construct(
        public string $id,
        public ?string $name,
        public ?string $queue,
        public ?CarbonImmutable $failedAt,
        public ?CarbonImmutable $startedAt,
        public string $attempts,
        public string $exception,
        public ?CarbonImmutable $retriedAt,
        public ?CarbonImmutable $retryReservedUntil,
    ) {}

    public static function fromResponse(array $attributes, string $id): self
    {
        return new self(
            id: $id,
            name: $attributes['name'] ?? null,
            queue: $attributes['queue'] ?? null,
            failedAt: isset($attributes['failed_at'])
                ? CarbonImmutable::parse($attributes['failed_at'])
                : null,
            startedAt: isset($attributes['started_at'])
                ? CarbonImmutable::parse($attributes['started_at'])
                : null,
            attempts: $attributes['attempts'],
            exception: $attributes['exception'],
            retriedAt: isset($attributes['retried_at'])
                ? CarbonImmutable::parse($attributes['retried_at'])
                : null,
            retryReservedUntil: isset($attributes['retry_reserved_until'])
                ? CarbonImmutable::parse($attributes['retry_reserved_until'])
                : null,
        );
    }
}

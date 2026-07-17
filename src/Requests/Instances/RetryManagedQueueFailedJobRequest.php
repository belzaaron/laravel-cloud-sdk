<?php

namespace Redberry\LaravelCloudSdk\Requests\Instances;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class RetryManagedQueueFailedJobRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        private string $instanceId,
        private string $jobId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/instances/{$this->instanceId}/failed-jobs/{$this->jobId}/retry";
    }
}

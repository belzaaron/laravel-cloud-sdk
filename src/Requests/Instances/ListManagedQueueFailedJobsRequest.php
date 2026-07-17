<?php

namespace Redberry\LaravelCloudSdk\Requests\Instances;

use Redberry\LaravelCloudSdk\Data\Instances\ManagedQueueFailedJobData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;

class ListManagedQueueFailedJobsRequest extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(private string $instanceId) {}

    public function resolveEndpoint(): string
    {
        return "/instances/{$this->instanceId}/failed-jobs";
    }

    /**
     * @return ManagedQueueFailedJobData[]
     */
    public function createDtoFromResponse(Response $response): array
    {
        return array_map(
            fn (array $item) => ManagedQueueFailedJobData::fromResponse($item['attributes'], $item['id']),
            $response->json('data')
        );
    }
}

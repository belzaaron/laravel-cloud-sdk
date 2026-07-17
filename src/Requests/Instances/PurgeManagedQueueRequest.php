<?php

namespace Redberry\LaravelCloudSdk\Requests\Instances;

use Redberry\LaravelCloudSdk\Data\Instances\InstanceData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class PurgeManagedQueueRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(private string $instanceId) {}

    public function resolveEndpoint(): string
    {
        return "/instances/{$this->instanceId}/purge";
    }

    public function createDtoFromResponse(Response $response): InstanceData
    {
        $data = $response->json('data');

        return InstanceData::fromResponse($data['attributes'], $data['id']);
    }
}

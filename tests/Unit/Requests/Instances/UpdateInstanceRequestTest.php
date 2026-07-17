<?php

use Redberry\LaravelCloudSdk\Connectors\LaravelCloudConnector;
use Redberry\LaravelCloudSdk\Data\Instances\InstanceData;
use Redberry\LaravelCloudSdk\Data\Instances\UpdateInstanceData;
use Redberry\LaravelCloudSdk\Enums\InstanceScalingType;
use Redberry\LaravelCloudSdk\Enums\InstanceSize;
use Redberry\LaravelCloudSdk\Exceptions\ValidationException;
use Redberry\LaravelCloudSdk\Requests\Applications\ListApplicationsRequest;
use Redberry\LaravelCloudSdk\Requests\Environments\ListEnvironmentsRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\ListInstancesRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\UpdateInstanceRequest;
use Redberry\LaravelCloudSdk\Tests\Fixtures\LaravelCloudFixture;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

it('resolves the endpoint correctly', function () {
    $data = new UpdateInstanceData(name: 'updated-worker');
    $request = new UpdateInstanceRequest('inst-123', $data);

    expect($request->resolveEndpoint())->toBe('/instances/inst-123');
});

it('has the correct HTTP method', function () {
    $data = new UpdateInstanceData(name: 'updated-worker');
    $request = new UpdateInstanceRequest('inst-123', $data);

    expect($request->getMethod())->toBe(Method::PATCH);
});

it('sends correct body with all optional fields', function () {
    $data = new UpdateInstanceData(
        name: 'updated-worker',
        size: InstanceSize::FlexM2vcpu2gb,
        scalingType: InstanceScalingType::Custom,
        maxReplicas: 10,
        minReplicas: 2,
        usesSleepMode: false,
        sleepTimeout: 30,
        usesScheduler: true,
        usesOctane: false,
        usesInertiaSsr: false,
        scalingCpuThresholdPercentage: 75,
        scalingMemoryThresholdPercentage: null,
        visibilityTimeout: 60,
        pollingInterval: 20,
        shutdownTimeout: 30,
        sleepWithApp: false,
    );
    $request = new UpdateInstanceRequest('inst-123', $data);
    $body = $request->body()->all();

    expect($body['name'])->toBe('updated-worker');
    expect($body['size'])->toBe('flex.m-2vcpu-2gb');
    expect($body['scaling_type'])->toBe('custom');
    expect($body['max_replicas'])->toBe(10);
    expect($body['min_replicas'])->toBe(2);
    expect($body['uses_sleep_mode'])->toBeFalse();
    expect($body['sleep_timeout'])->toBe(30);
    expect($body['uses_scheduler'])->toBeTrue();
    expect($body['uses_octane'])->toBeFalse();
    expect($body['uses_inertia_ssr'])->toBeFalse();
    expect($body['scaling_cpu_threshold_percentage'])->toBe(75);
    expect($body['scaling_memory_threshold_percentage'])->toBeNull();
    expect($body['visibility_timeout'])->toBe(60);
    expect($body['polling_interval'])->toBe(20);
    expect($body['shutdown_timeout'])->toBe(30);
    expect($body['sleep_with_app'])->toBeFalse();
});

it('excludes unset optional fields from body', function () {
    $data = new UpdateInstanceData(name: 'updated-worker');
    $request = new UpdateInstanceRequest('inst-123', $data);
    $body = $request->body()->all();

    expect($body)->toHaveKey('name');
    expect($body)->not->toHaveKey('size');
    expect($body)->not->toHaveKey('scaling_type');
    expect($body)->not->toHaveKey('uses_scheduler');
    expect($body)->not->toHaveKey('uses_octane');
    expect($body)->not->toHaveKey('scaling_cpu_threshold_percentage');
    expect($body)->not->toHaveKey('visibility_timeout');
    expect($body)->not->toHaveKey('polling_interval');
    expect($body)->not->toHaveKey('shutdown_timeout');
    expect($body)->not->toHaveKey('sleep_with_app');
});

it('throws validation errors for invalid managed queue fields when updating an instance', function () {
    Saloon::fake([
        UpdateInstanceRequest::class => MockResponse::make([
            'message' => 'The given data was invalid.',
            'errors' => [
                'polling_interval' => ['The polling interval must be at least 1.'],
            ],
        ], 422),
    ]);

    $data = new UpdateInstanceData(pollingInterval: 0);

    $connector = new LaravelCloudConnector(config('laravel-cloud-sdk.token'));

    try {
        $connector->send(new UpdateInstanceRequest('inst-123', $data))->throw();
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('polling_interval');

        throw $e;
    }
})->throws(ValidationException::class);

it('updates an instance and returns InstanceData', function () {
    Saloon::fake([
        ListApplicationsRequest::class => new LaravelCloudFixture('applications/list'),
    ]);

    $connector = new LaravelCloudConnector(config('laravel-cloud-sdk.token'));
    $firstApplication = $connector->send(new ListApplicationsRequest)->dtoOrFail()[0];

    Saloon::fake([
        ListEnvironmentsRequest::class => new LaravelCloudFixture('environments/list'),
    ]);

    $firstEnvironment = $connector->send(new ListEnvironmentsRequest($firstApplication->id))->dtoOrFail()[0];

    Saloon::fake([
        ListInstancesRequest::class => new LaravelCloudFixture('instances/list'),
    ]);

    $firstInstance = $connector->send(new ListInstancesRequest($firstEnvironment->id))->dtoOrFail()[0];

    Saloon::fake([
        UpdateInstanceRequest::class => new LaravelCloudFixture('instances/update'),
    ]);

    $data = new UpdateInstanceData(
        name: 'updated-worker',
        size: InstanceSize::FlexM2vcpu2gb,
        scalingType: InstanceScalingType::Custom,
        maxReplicas: 5,
        minReplicas: 1,
        usesSleepMode: false,
        sleepTimeout: 30,
        usesScheduler: true,
        usesOctane: false,
        usesInertiaSsr: false,
    );
    $response = $connector->send(new UpdateInstanceRequest($firstInstance->id, $data));

    Saloon::assertSent(UpdateInstanceRequest::class);

    $dto = $response->dtoOrFail();
    expect($dto)->toBeInstanceOf(InstanceData::class);
});

<?php

use Redberry\LaravelCloudSdk\Connectors\LaravelCloudConnector;
use Redberry\LaravelCloudSdk\Data\BackgroundProcesses\BackgroundProcessData;
use Redberry\LaravelCloudSdk\Data\Instances\CreateInstanceData;
use Redberry\LaravelCloudSdk\Data\Instances\InstanceData;
use Redberry\LaravelCloudSdk\Enums\DaemonType;
use Redberry\LaravelCloudSdk\Enums\InstanceScalingType;
use Redberry\LaravelCloudSdk\Enums\InstanceSize;
use Redberry\LaravelCloudSdk\Enums\InstanceType;
use Redberry\LaravelCloudSdk\Exceptions\ValidationException;
use Redberry\LaravelCloudSdk\Requests\Applications\ListApplicationsRequest;
use Redberry\LaravelCloudSdk\Requests\Environments\ListEnvironmentsRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\CreateInstanceRequest;
use Redberry\LaravelCloudSdk\Tests\Fixtures\LaravelCloudFixture;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

it('resolves the endpoint correctly', function () {
    $data = new CreateInstanceData(
        name: 'test-worker',
        type: InstanceType::Service,
        size: InstanceSize::FlexM1vcpu1gb,
        scalingType: InstanceScalingType::None,
        maxReplicas: 1,
        minReplicas: 1,
    );
    $request = new CreateInstanceRequest('env-123', $data);

    expect($request->resolveEndpoint())->toBe('/environments/env-123/instances');
});

it('has the correct HTTP method', function () {
    $data = new CreateInstanceData(
        name: 'test-worker',
        type: InstanceType::Service,
        size: InstanceSize::FlexM1vcpu1gb,
        scalingType: InstanceScalingType::None,
        maxReplicas: 1,
        minReplicas: 1,
    );
    $request = new CreateInstanceRequest('env-123', $data);

    expect($request->getMethod())->toBe(Method::POST);
});

it('implements HasBody', function () {
    $data = new CreateInstanceData(
        name: 'test-worker',
        type: InstanceType::Service,
        size: InstanceSize::FlexM1vcpu1gb,
        scalingType: InstanceScalingType::None,
        maxReplicas: 1,
        minReplicas: 1,
    );
    $request = new CreateInstanceRequest('env-123', $data);

    expect($request)->toBeInstanceOf(HasBody::class);
});

it('sends correct body with all optional fields', function () {
    $data = new CreateInstanceData(
        name: 'test-worker',
        type: InstanceType::Service,
        size: InstanceSize::FlexM1vcpu1gb,
        scalingType: InstanceScalingType::Custom,
        maxReplicas: 5,
        minReplicas: 1,
        usesScheduler: true,
        scalingCpuThresholdPercentage: 70,
        scalingMemoryThresholdPercentage: 80,
    );
    $request = new CreateInstanceRequest('env-123', $data);
    $body = $request->body()->all();

    expect($body['name'])->toBe('test-worker');
    expect($body['type'])->toBe('service');
    expect($body['size'])->toBe('flex.m-1vcpu-1gb');
    expect($body['scaling_type'])->toBe('custom');
    expect($body['max_replicas'])->toBe(5);
    expect($body['min_replicas'])->toBe(1);
    expect($body['uses_scheduler'])->toBeTrue();
    expect($body['scaling_cpu_threshold_percentage'])->toBe(70);
    expect($body['scaling_memory_threshold_percentage'])->toBe(80);
});

it('excludes unset optional fields from body', function () {
    $data = new CreateInstanceData(
        name: 'test-worker',
        type: InstanceType::Service,
        size: InstanceSize::FlexM1vcpu1gb,
        scalingType: InstanceScalingType::None,
        maxReplicas: 1,
        minReplicas: 1,
    );
    $request = new CreateInstanceRequest('env-123', $data);
    $body = $request->body()->all();

    expect($body)->not->toHaveKey('uses_scheduler');
    expect($body)->not->toHaveKey('scaling_cpu_threshold_percentage');
    expect($body)->not->toHaveKey('scaling_memory_threshold_percentage');
    expect($body)->not->toHaveKey('background_processes');
});

it('sends managed queue fields in body', function () {
    $data = new CreateInstanceData(
        name: 'orders',
        type: InstanceType::ManagedQueue,
        size: InstanceSize::FlexM1vcpu1gb,
        scalingType: InstanceScalingType::Custom,
        maxReplicas: 5,
        minReplicas: 0,
        visibilityTimeout: 60,
        pollingInterval: 20,
        shutdownTimeout: 30,
        sleepWithApp: false,
    );
    $request = new CreateInstanceRequest('env-123', $data);
    $body = $request->body()->all();

    expect($body['type'])->toBe('managed_queue');
    expect($body['visibility_timeout'])->toBe(60);
    expect($body['polling_interval'])->toBe(20);
    expect($body['shutdown_timeout'])->toBe(30);
    expect($body['sleep_with_app'])->toBeFalse();
});

it('throws validation errors for invalid managed queue fields when creating an instance', function () {
    Saloon::fake([
        CreateInstanceRequest::class => MockResponse::make([
            'message' => 'The given data was invalid.',
            'errors' => [
                'visibility_timeout' => ['The visibility timeout must be at least 1.'],
            ],
        ], 422),
    ]);

    $data = new CreateInstanceData(
        name: 'orders',
        type: InstanceType::ManagedQueue,
        size: InstanceSize::FlexM1vcpu1gb,
        scalingType: InstanceScalingType::Custom,
        maxReplicas: 5,
        minReplicas: 0,
        visibilityTimeout: 0,
    );

    $connector = new LaravelCloudConnector(config('laravel-cloud-sdk.token'));

    try {
        $connector->send(new CreateInstanceRequest('env-123', $data))->throw();
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('visibility_timeout');

        throw $e;
    }
})->throws(ValidationException::class);

it('creates an instance and returns InstanceData', function () {
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
        CreateInstanceRequest::class => new LaravelCloudFixture('instances/create'),
    ]);

    $data = new CreateInstanceData(
        name: 'test-worker',
        type: InstanceType::Service,
        size: InstanceSize::FlexM1vcpu1gb,
        scalingType: InstanceScalingType::Custom,
        maxReplicas: 5,
        minReplicas: 1,
        usesScheduler: true,
        backgroundProcesses: [
            new BackgroundProcessData(
                type: DaemonType::Custom,
                processes: 1,
                command: 'php artisan queue:work',
            ),
        ],
    );
    $response = $connector->send(new CreateInstanceRequest($firstEnvironment->id, $data));

    Saloon::assertSent(CreateInstanceRequest::class);

    $dto = $response->dtoOrFail();
    expect($dto)->toBeInstanceOf(InstanceData::class);
});

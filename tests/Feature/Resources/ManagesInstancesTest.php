<?php

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Redberry\LaravelCloudSdk\Data\BackgroundProcesses\BackgroundProcessData;
use Redberry\LaravelCloudSdk\Data\Environments\EnvironmentData;
use Redberry\LaravelCloudSdk\Data\Instances\CreateInstanceData;
use Redberry\LaravelCloudSdk\Data\Instances\InstanceData;
use Redberry\LaravelCloudSdk\Data\Instances\InstanceSizeData;
use Redberry\LaravelCloudSdk\Data\Instances\UpdateInstanceData;
use Redberry\LaravelCloudSdk\Enums\InstanceScalingType;
use Redberry\LaravelCloudSdk\Enums\InstanceSize;
use Redberry\LaravelCloudSdk\Enums\InstanceType;
use Redberry\LaravelCloudSdk\LaravelCloud;
use Redberry\LaravelCloudSdk\Requests\Instances\CreateInstanceRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\DeleteInstanceRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\GetInstanceRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\ListInstanceSizesRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\ListInstancesRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\UpdateInstanceRequest;
use Redberry\LaravelCloudSdk\Tests\Fixtures\LaravelCloudFixture;
use Saloon\Laravel\Facades\Saloon;

it('lists instances for an environment', function () {
    Saloon::fake([
        ListInstancesRequest::class => new LaravelCloudFixture('instances/list'),
    ]);

    $result = (new LaravelCloud('token'))->instances('env-a15fd671-0b6a-401a-84bf-14105ce69023');

    expect($result)->toBeInstanceOf(LazyCollection::class);

    $first = $result->first();
    expect($first)->toBeInstanceOf(InstanceData::class);
    expect($first->environment)->toBeInstanceOf(EnvironmentData::class);
    expect($first->backgroundProcesses)->toBeArray();
    expect($first->backgroundProcesses)->each->toBeInstanceOf(BackgroundProcessData::class);

    Saloon::assertSent(ListInstancesRequest::class);
});

it('retrieves a single instance by id', function () {
    Saloon::fake([
        GetInstanceRequest::class => new LaravelCloudFixture('instances/get'),
    ]);

    $result = (new LaravelCloud('token'))->instance('inst-a15fd671-14f8-489f-b552-886c7ffa09dd');

    Saloon::assertSent(GetInstanceRequest::class);
    expect($result)->toBeInstanceOf(InstanceData::class);
    expect($result->id)->toBe('inst-a15fd671-14f8-489f-b552-886c7ffa09dd');
    expect($result->name)->toBe('App');
    expect($result->environment)->toBeInstanceOf(EnvironmentData::class);
    expect($result->backgroundProcesses)->toHaveCount(2);
    expect($result->backgroundProcesses)->each->toBeInstanceOf(BackgroundProcessData::class);
});

it('creates an instance with named params', function () {
    Saloon::fake([
        CreateInstanceRequest::class => new LaravelCloudFixture('instances/create'),
    ]);

    $result = (new LaravelCloud('token'))->createInstance(
        environmentId: 'env-a14fe550-4e39-4ff2-8016-a20e4d32a996',
        name: 'App',
        type: InstanceType::App,
        size: InstanceSize::FlexG1vcpu512mb,
        scalingType: InstanceScalingType::None,
        maxReplicas: 1,
        minReplicas: 1,
    );

    Saloon::assertSent(CreateInstanceRequest::class);
    expect($result)->toBeInstanceOf(InstanceData::class);
});

it('creates an instance with string enums', function () {
    Saloon::fake([
        CreateInstanceRequest::class => new LaravelCloudFixture('instances/create'),
    ]);

    $result = (new LaravelCloud('token'))->createInstance(
        environmentId: 'env-a14fe550-4e39-4ff2-8016-a20e4d32a996',
        name: 'App',
        type: 'app',
        size: 'flex.g-1vcpu-512mb',
        scalingType: 'none',
        maxReplicas: 1,
        minReplicas: 1,
    );

    Saloon::assertSent(CreateInstanceRequest::class);
    expect($result)->toBeInstanceOf(InstanceData::class);
});

it('preserves the positional background processes parameter when creating an instance', function () {
    Saloon::fake([
        CreateInstanceRequest::class => new LaravelCloudFixture('instances/create'),
    ]);

    $result = (new LaravelCloud('token'))->createInstance(
        'env-a14fe550-4e39-4ff2-8016-a20e4d32a996',
        'App',
        InstanceType::App,
        InstanceSize::FlexG1vcpu512mb,
        InstanceScalingType::None,
        1,
        1,
        false,
        null,
        null,
        [],
    );

    Saloon::assertSent(CreateInstanceRequest::class);
    expect($result)->toBeInstanceOf(InstanceData::class);
});

it('creates an instance via createInstanceWith()', function () {
    Saloon::fake([
        CreateInstanceRequest::class => new LaravelCloudFixture('instances/create'),
    ]);

    $result = (new LaravelCloud('token'))->createInstanceWith(
        'env-a14fe550-4e39-4ff2-8016-a20e4d32a996',
        new CreateInstanceData(
            name: 'App',
            type: InstanceType::App,
            size: InstanceSize::FlexG1vcpu512mb,
            scalingType: InstanceScalingType::None,
            maxReplicas: 1,
            minReplicas: 1,
        ),
    );

    Saloon::assertSent(CreateInstanceRequest::class);
    expect($result)->toBeInstanceOf(InstanceData::class);
});

it('updates an instance with named params', function () {
    Saloon::fake([
        UpdateInstanceRequest::class => new LaravelCloudFixture('instances/update'),
    ]);

    $result = (new LaravelCloud('token'))->updateInstance(
        'inst-a14fe550-5c7b-4986-9a0d-d0ab1dcda9ba',
        size: InstanceSize::FlexG1vcpu512mb,
    );

    Saloon::assertSent(UpdateInstanceRequest::class);
    expect($result)->toBeInstanceOf(InstanceData::class);
});

it('updates an instance via updateInstanceWith()', function () {
    Saloon::fake([
        UpdateInstanceRequest::class => new LaravelCloudFixture('instances/update'),
    ]);

    $result = (new LaravelCloud('token'))->updateInstanceWith(
        'inst-a14fe550-5c7b-4986-9a0d-d0ab1dcda9ba',
        new UpdateInstanceData(size: InstanceSize::FlexG1vcpu512mb),
    );

    Saloon::assertSent(UpdateInstanceRequest::class);
    expect($result)->toBeInstanceOf(InstanceData::class);
});

it('lists available instance sizes', function () {
    Saloon::fake([
        ListInstanceSizesRequest::class => new LaravelCloudFixture('instances/sizes'),
    ]);

    $result = (new LaravelCloud('token'))->instanceSizes();

    Saloon::assertSent(ListInstanceSizesRequest::class);
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->first())->toBeInstanceOf(InstanceSizeData::class);
});

it('deletes an instance', function () {
    Saloon::fake([
        DeleteInstanceRequest::class => new LaravelCloudFixture('instances/delete'),
    ]);

    (new LaravelCloud('token'))->deleteInstance('inst-a14fe550-5c7b-4986-9a0d-d0ab1dcda9ba');

    Saloon::assertSent(DeleteInstanceRequest::class);
});

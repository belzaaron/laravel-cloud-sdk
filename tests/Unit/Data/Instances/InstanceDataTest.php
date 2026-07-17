<?php

use Carbon\CarbonImmutable;
use Redberry\LaravelCloudSdk\Data\BackgroundProcesses\BackgroundProcessData;
use Redberry\LaravelCloudSdk\Data\Instances\InstanceData;
use Redberry\LaravelCloudSdk\Enums\DaemonType;
use Redberry\LaravelCloudSdk\Enums\InstanceScalingType;
use Redberry\LaravelCloudSdk\Enums\InstanceSize;
use Redberry\LaravelCloudSdk\Enums\InstanceType;
use Redberry\LaravelCloudSdk\Enums\ManagedQueueStatus;

it('builds from response attributes', function () {
    $data = InstanceData::fromResponse([
        'name' => 'my-service',
        'type' => 'service',
        'size' => 'flex.m-1vcpu-1gb',
        'scaling_type' => 'none',
        'min_replicas' => 1,
        'max_replicas' => 1,
        'uses_scheduler' => false,
        'scaling_cpu_threshold_percentage' => null,
        'scaling_memory_threshold_percentage' => null,
        'created_at' => '2024-01-15T10:00:00Z',
    ], 'instance-abc');

    expect($data->id)->toBe('instance-abc');
    expect($data->name)->toBe('my-service');
    expect($data->type)->toBe(InstanceType::Service);
    expect($data->size)->toBe(InstanceSize::FlexM1vcpu1gb);
    expect($data->scalingType)->toBe(InstanceScalingType::None);
    expect($data->minReplicas)->toBe(1);
    expect($data->maxReplicas)->toBe(1);
    expect($data->usesScheduler)->toBeFalse();
    expect($data->scalingCpuThresholdPercentage)->toBeNull();
    expect($data->scalingMemoryThresholdPercentage)->toBeNull();
    expect($data->backgroundProcesses)->toBe([]);
    expect($data->createdAt)->toBeInstanceOf(CarbonImmutable::class);
    expect($data->createdAt->toDateString())->toBe('2024-01-15');
});

it('builds with scaling thresholds', function () {
    $data = InstanceData::fromResponse([
        'name' => 'my-app',
        'type' => 'app',
        'size' => 'flex.m-2vcpu-2gb',
        'scaling_type' => 'custom',
        'min_replicas' => 2,
        'max_replicas' => 10,
        'uses_scheduler' => true,
        'scaling_cpu_threshold_percentage' => 75,
        'scaling_memory_threshold_percentage' => 80,
    ], 'instance-xyz');

    expect($data->scalingType)->toBe(InstanceScalingType::Custom);
    expect($data->scalingCpuThresholdPercentage)->toBe(75);
    expect($data->scalingMemoryThresholdPercentage)->toBe(80);
    expect($data->usesScheduler)->toBeTrue();
    expect($data->createdAt)->toBeNull();
});

it('accepts background processes', function () {
    $bgProcess = BackgroundProcessData::fromResponse([
        'type' => 'worker',
        'processes' => 2,
    ], 'bp-1');

    $data = InstanceData::fromResponse([
        'name' => 'my-worker',
        'type' => 'queue',
        'size' => 'flex.m-1vcpu-1gb',
        'scaling_type' => 'none',
        'min_replicas' => 1,
        'max_replicas' => 1,
        'uses_scheduler' => false,
    ], 'instance-bg');

    $data->backgroundProcesses = [$bgProcess];

    expect($data->backgroundProcesses)->toHaveCount(1);
    expect($data->backgroundProcesses[0])->toBeInstanceOf(BackgroundProcessData::class);
    expect($data->backgroundProcesses[0]->type)->toBe(DaemonType::Worker);
});

it('preserves existing positional optional parameters', function () {
    $backgroundProcesses = [
        new BackgroundProcessData(type: DaemonType::Worker, processes: 1),
    ];
    $createdAt = CarbonImmutable::parse('2024-01-15T10:00:00Z');

    $data = new InstanceData(
        'instance-abc',
        'my-service',
        InstanceType::Service,
        InstanceSize::FlexM1vcpu1gb,
        InstanceScalingType::None,
        1,
        1,
        false,
        null,
        null,
        $backgroundProcesses,
        $createdAt,
    );

    expect($data->backgroundProcesses)->toBe($backgroundProcesses);
    expect($data->createdAt)->toBe($createdAt);
    expect($data->queueStatus)->toBeNull();
});

it('hydrates an unknown managed queue status', function () {
    $data = InstanceData::fromResponse([
        'name' => 'orders',
        'type' => 'managed_queue',
        'size' => 'flex.m-1vcpu-1gb',
        'scaling_type' => 'custom',
        'min_replicas' => 0,
        'max_replicas' => 5,
        'uses_scheduler' => false,
        'queue_status' => 'unknown',
    ], 'instance-queue');

    expect($data->queueStatus)->toBe(ManagedQueueStatus::Unknown);
});

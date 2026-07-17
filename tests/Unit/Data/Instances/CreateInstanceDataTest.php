<?php

use Redberry\LaravelCloudSdk\Data\BackgroundProcesses\BackgroundProcessData;
use Redberry\LaravelCloudSdk\Data\Instances\CreateInstanceData;
use Redberry\LaravelCloudSdk\Enums\DaemonType;
use Redberry\LaravelCloudSdk\Enums\InstanceScalingType;
use Redberry\LaravelCloudSdk\Enums\InstanceSize;
use Redberry\LaravelCloudSdk\Enums\InstanceType;
use Spatie\LaravelData\Optional;

it('requires name, type, size, scalingType, maxReplicas, minReplicas', function () {
    $data = new CreateInstanceData(
        name: 'test-worker',
        type: InstanceType::Service,
        size: InstanceSize::FlexM1vcpu1gb,
        scalingType: InstanceScalingType::None,
        maxReplicas: 1,
        minReplicas: 1,
    );

    $array = $data->toArray();

    expect($array['name'])->toBe('test-worker');
    expect($array['type'])->toBe('service');
    expect($array['size'])->toBe('flex.m-1vcpu-1gb');
    expect($array['scaling_type'])->toBe('none');
    expect($array['max_replicas'])->toBe(1);
    expect($array['min_replicas'])->toBe(1);
});

it('defaults optional fields to Optional', function () {
    $data = new CreateInstanceData(
        name: 'test-worker',
        type: InstanceType::Service,
        size: InstanceSize::FlexM1vcpu1gb,
        scalingType: InstanceScalingType::None,
        maxReplicas: 1,
        minReplicas: 1,
    );

    expect($data->usesScheduler)->toBeInstanceOf(Optional::class);
    expect($data->scalingCpuThresholdPercentage)->toBeInstanceOf(Optional::class);
    expect($data->scalingMemoryThresholdPercentage)->toBeInstanceOf(Optional::class);
    expect($data->backgroundProcesses)->toBeInstanceOf(Optional::class);
});

it('serializes optional fields when set and excludes unset ones', function () {
    $data = new CreateInstanceData(
        name: 'test-worker',
        type: InstanceType::Service,
        size: InstanceSize::FlexM1vcpu1gb,
        scalingType: InstanceScalingType::Custom,
        maxReplicas: 5,
        minReplicas: 1,
        usesScheduler: true,
        scalingCpuThresholdPercentage: 70,
    );

    $array = $data->toArray();

    expect($array['uses_scheduler'])->toBeTrue();
    expect($array['scaling_cpu_threshold_percentage'])->toBe(70);
    expect($array)->not->toHaveKey('scaling_memory_threshold_percentage');
    expect($array)->not->toHaveKey('background_processes');
});

it('serializes background_processes when set', function () {
    $data = new CreateInstanceData(
        name: 'test-worker',
        type: InstanceType::Service,
        size: InstanceSize::FlexM1vcpu1gb,
        scalingType: InstanceScalingType::None,
        maxReplicas: 1,
        minReplicas: 1,
        backgroundProcesses: [
            new BackgroundProcessData(
                type: DaemonType::Custom,
                processes: 1,
                command: 'php artisan queue:work',
            ),
        ],
    );

    $array = $data->toArray();

    expect($array['background_processes'])->toBeArray();
    expect($array['background_processes'][0]['type'])->toBe('custom');
    expect($array['background_processes'][0]['processes'])->toBe(1);
    expect($array['background_processes'][0]['command'])->toBe('php artisan queue:work');
});

it('preserves the positional background processes parameter', function () {
    $backgroundProcesses = [
        new BackgroundProcessData(
            type: DaemonType::Custom,
            processes: 1,
            command: 'php artisan queue:work',
        ),
    ];

    $data = new CreateInstanceData(
        'test-worker',
        InstanceType::Service,
        InstanceSize::FlexM1vcpu1gb,
        InstanceScalingType::None,
        1,
        1,
        true,
        null,
        null,
        $backgroundProcesses,
    );

    expect($data->backgroundProcesses)->toBe($backgroundProcesses);
    expect($data->visibilityTimeout)->toBeInstanceOf(Optional::class);
});

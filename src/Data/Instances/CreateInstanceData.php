<?php

namespace Redberry\LaravelCloudSdk\Data\Instances;

use Redberry\LaravelCloudSdk\Data\BackgroundProcesses\BackgroundProcessData;
use Redberry\LaravelCloudSdk\Enums\InstanceScalingType;
use Redberry\LaravelCloudSdk\Enums\InstanceSize;
use Redberry\LaravelCloudSdk\Enums\InstanceType;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapName(SnakeCaseMapper::class)]
class CreateInstanceData extends Data
{
    /**
     * @param  BackgroundProcessData[]|Optional  $backgroundProcesses
     */
    public function __construct(
        public string $name,
        public string|InstanceType $type,
        public string|InstanceSize $size,
        public string|InstanceScalingType $scalingType,
        public int $maxReplicas,
        public int $minReplicas,
        public bool|Optional $usesScheduler = new Optional,
        public int|null|Optional $scalingCpuThresholdPercentage = new Optional,
        public int|null|Optional $scalingMemoryThresholdPercentage = new Optional,
        public array|Optional $backgroundProcesses = new Optional,
        public int|null|Optional $visibilityTimeout = new Optional,
        public int|null|Optional $pollingInterval = new Optional,
        public int|null|Optional $shutdownTimeout = new Optional,
        public bool|null|Optional $sleepWithApp = new Optional,
    ) {}
}

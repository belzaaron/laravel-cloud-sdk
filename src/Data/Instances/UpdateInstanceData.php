<?php

namespace Redberry\LaravelCloudSdk\Data\Instances;

use Redberry\LaravelCloudSdk\Enums\InstanceScalingType;
use Redberry\LaravelCloudSdk\Enums\InstanceSize;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapName(SnakeCaseMapper::class)]
class UpdateInstanceData extends Data
{
    public function __construct(
        public string|Optional $name = new Optional,
        public string|InstanceSize|Optional $size = new Optional,
        public string|InstanceScalingType|Optional $scalingType = new Optional,
        public int|Optional $maxReplicas = new Optional,
        public int|Optional $minReplicas = new Optional,
        public bool|Optional $usesSleepMode = new Optional,
        public int|Optional $sleepTimeout = new Optional,
        public bool|Optional $usesScheduler = new Optional,
        public bool|Optional $usesOctane = new Optional,
        public bool|Optional $usesInertiaSsr = new Optional,
        public int|null|Optional $scalingCpuThresholdPercentage = new Optional,
        public int|null|Optional $scalingMemoryThresholdPercentage = new Optional,
        public int|null|Optional $visibilityTimeout = new Optional,
        public int|null|Optional $pollingInterval = new Optional,
        public int|null|Optional $shutdownTimeout = new Optional,
        public bool|null|Optional $sleepWithApp = new Optional,
    ) {}
}

<?php

namespace Redberry\LaravelCloudSdk\Resources;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Redberry\LaravelCloudSdk\Data\Instances\CreateInstanceData;
use Redberry\LaravelCloudSdk\Data\Instances\InstanceData;
use Redberry\LaravelCloudSdk\Data\Instances\ManagedQueueFailedJobData;
use Redberry\LaravelCloudSdk\Data\Instances\UpdateInstanceData;
use Redberry\LaravelCloudSdk\Enums\InstanceScalingType;
use Redberry\LaravelCloudSdk\Enums\InstanceSize;
use Redberry\LaravelCloudSdk\Enums\InstanceType;
use Redberry\LaravelCloudSdk\Requests\Instances\CreateInstanceRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\DeleteInstanceRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\DeleteManagedQueueFailedJobRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\GetInstanceRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\ListInstanceSizesRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\ListInstancesRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\ListManagedQueueFailedJobsRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\PauseManagedQueueRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\PurgeManagedQueueRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\ResumeManagedQueueRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\RetryManagedQueueFailedJobRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\SetDefaultManagedQueueRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\UpdateInstanceRequest;
use Spatie\LaravelData\Optional;

trait ManagesInstances
{
    /**
     * @return LazyCollection<int, InstanceData>
     */
    public function instances(string $environmentId): LazyCollection
    {
        return $this->connector->paginate(new ListInstancesRequest($environmentId))->collect();
    }

    public function instance(string $id): InstanceData
    {
        return $this->connector->send(new GetInstanceRequest($id))->dtoOrFail();
    }

    public function createInstance(
        string $environmentId,
        string $name,
        string|InstanceType $type,
        string|InstanceSize $size,
        string|InstanceScalingType $scalingType,
        int $maxReplicas,
        int $minReplicas,
        bool|Optional $usesScheduler = new Optional,
        int|null|Optional $scalingCpuThresholdPercentage = new Optional,
        int|null|Optional $scalingMemoryThresholdPercentage = new Optional,
        array|Optional $backgroundProcesses = new Optional,
        int|null|Optional $visibilityTimeout = new Optional,
        int|null|Optional $pollingInterval = new Optional,
        int|null|Optional $shutdownTimeout = new Optional,
        bool|null|Optional $sleepWithApp = new Optional,
    ): InstanceData {
        return $this->createInstanceWith($environmentId, new CreateInstanceData(
            name: $name,
            type: $type,
            size: $size,
            scalingType: $scalingType,
            maxReplicas: $maxReplicas,
            minReplicas: $minReplicas,
            usesScheduler: $usesScheduler,
            scalingCpuThresholdPercentage: $scalingCpuThresholdPercentage,
            scalingMemoryThresholdPercentage: $scalingMemoryThresholdPercentage,
            visibilityTimeout: $visibilityTimeout,
            pollingInterval: $pollingInterval,
            shutdownTimeout: $shutdownTimeout,
            sleepWithApp: $sleepWithApp,
            backgroundProcesses: $backgroundProcesses,
        ));
    }

    public function createInstanceWith(string $environmentId, CreateInstanceData $data): InstanceData
    {
        return $this->connector->send(new CreateInstanceRequest($environmentId, $data))->dtoOrFail();
    }

    public function updateInstance(
        string $id,
        string|Optional $name = new Optional,
        string|InstanceSize|Optional $size = new Optional,
        string|InstanceScalingType|Optional $scalingType = new Optional,
        int|Optional $maxReplicas = new Optional,
        int|Optional $minReplicas = new Optional,
        bool|Optional $usesSleepMode = new Optional,
        int|Optional $sleepTimeout = new Optional,
        bool|Optional $usesScheduler = new Optional,
        bool|Optional $usesOctane = new Optional,
        bool|Optional $usesInertiaSsr = new Optional,
        int|null|Optional $scalingCpuThresholdPercentage = new Optional,
        int|null|Optional $scalingMemoryThresholdPercentage = new Optional,
        int|null|Optional $visibilityTimeout = new Optional,
        int|null|Optional $pollingInterval = new Optional,
        int|null|Optional $shutdownTimeout = new Optional,
        bool|null|Optional $sleepWithApp = new Optional,
    ): InstanceData {
        return $this->updateInstanceWith($id, new UpdateInstanceData(
            name: $name,
            size: $size,
            scalingType: $scalingType,
            maxReplicas: $maxReplicas,
            minReplicas: $minReplicas,
            usesSleepMode: $usesSleepMode,
            sleepTimeout: $sleepTimeout,
            usesScheduler: $usesScheduler,
            usesOctane: $usesOctane,
            usesInertiaSsr: $usesInertiaSsr,
            scalingCpuThresholdPercentage: $scalingCpuThresholdPercentage,
            scalingMemoryThresholdPercentage: $scalingMemoryThresholdPercentage,
            visibilityTimeout: $visibilityTimeout,
            pollingInterval: $pollingInterval,
            shutdownTimeout: $shutdownTimeout,
            sleepWithApp: $sleepWithApp,
        ));
    }

    public function updateInstanceWith(string $id, UpdateInstanceData $data): InstanceData
    {
        return $this->connector->send(new UpdateInstanceRequest($id, $data))->dtoOrFail();
    }

    public function instanceSizes(): Collection
    {
        return $this->connector->send(new ListInstanceSizesRequest)->dtoOrFail();
    }

    public function pauseManagedQueue(string $id): InstanceData
    {
        return $this->connector->send(new PauseManagedQueueRequest($id))->dtoOrFail();
    }

    public function resumeManagedQueue(string $id): InstanceData
    {
        return $this->connector->send(new ResumeManagedQueueRequest($id))->dtoOrFail();
    }

    public function purgeManagedQueue(string $id): InstanceData
    {
        return $this->connector->send(new PurgeManagedQueueRequest($id))->dtoOrFail();
    }

    public function setDefaultManagedQueue(string $id): InstanceData
    {
        return $this->connector->send(new SetDefaultManagedQueueRequest($id))->dtoOrFail();
    }

    /**
     * @return LazyCollection<int, ManagedQueueFailedJobData>
     */
    public function managedQueueFailedJobs(string $id): LazyCollection
    {
        return $this->connector->paginate(new ListManagedQueueFailedJobsRequest($id))->collect();
    }

    public function retryManagedQueueFailedJob(string $id, string $jobId): void
    {
        $this->connector->send(new RetryManagedQueueFailedJobRequest($id, $jobId))->throw();
    }

    public function deleteManagedQueueFailedJob(string $id, string $jobId): void
    {
        $this->connector->send(new DeleteManagedQueueFailedJobRequest($id, $jobId))->throw();
    }

    public function deleteInstance(string $id): void
    {
        $this->connector->send(new DeleteInstanceRequest($id))->throw();
    }
}

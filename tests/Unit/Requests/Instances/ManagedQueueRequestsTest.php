<?php

use Illuminate\Support\LazyCollection;
use Redberry\LaravelCloudSdk\Connectors\LaravelCloudConnector;
use Redberry\LaravelCloudSdk\Data\Instances\InstanceData;
use Redberry\LaravelCloudSdk\Data\Instances\ManagedQueueFailedJobData;
use Redberry\LaravelCloudSdk\Enums\InstanceType;
use Redberry\LaravelCloudSdk\Enums\ManagedQueueStatus;
use Redberry\LaravelCloudSdk\LaravelCloud;
use Redberry\LaravelCloudSdk\Requests\Instances\DeleteManagedQueueFailedJobRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\ListManagedQueueFailedJobsRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\PauseManagedQueueRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\PurgeManagedQueueRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\ResumeManagedQueueRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\RetryManagedQueueFailedJobRequest;
use Redberry\LaravelCloudSdk\Requests\Instances\SetDefaultManagedQueueRequest;
use Redberry\LaravelCloudSdk\Tests\Fixtures\LaravelCloudFixture;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\Statuses\NotFoundException;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Saloon\PaginationPlugin\Contracts\Paginatable;

it('resolves managed queue action endpoints correctly', function (string $requestClass, string $endpoint) {
    $request = new $requestClass('inst-123');

    expect($request->resolveEndpoint())->toBe($endpoint);
    expect($request->getMethod())->toBe(Method::POST);
})->with([
    [PauseManagedQueueRequest::class, '/instances/inst-123/pause'],
    [ResumeManagedQueueRequest::class, '/instances/inst-123/resume'],
    [PurgeManagedQueueRequest::class, '/instances/inst-123/purge'],
    [SetDefaultManagedQueueRequest::class, '/instances/inst-123/default'],
]);

it('hydrates managed queue instance fields from action responses', function () {
    Saloon::fake([
        PauseManagedQueueRequest::class => new LaravelCloudFixture('instances/managed-queue-pause'),
    ]);

    $connector = new LaravelCloudConnector(config('laravel-cloud-sdk.token'));
    $response = $connector->send(new PauseManagedQueueRequest('inst-9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d'));

    Saloon::assertSent(PauseManagedQueueRequest::class);

    $dto = $response->dtoOrFail();
    expect($dto)->toBeInstanceOf(InstanceData::class);
    expect($dto->type)->toBe(InstanceType::ManagedQueue);
    expect($dto->queueStatus)->toBe(ManagedQueueStatus::Available);
    expect($dto->paused)->toBeTrue();
    expect($dto->visibilityTimeout)->toBe(60);
    expect($dto->pollingInterval)->toBe(20);
});

it('lists managed queue failed jobs', function () {
    Saloon::fake([
        ListManagedQueueFailedJobsRequest::class => new LaravelCloudFixture('instances/managed-queue-failed-jobs'),
    ]);

    $request = new ListManagedQueueFailedJobsRequest('inst-123');

    expect($request->resolveEndpoint())->toBe('/instances/inst-123/failed-jobs');
    expect($request->getMethod())->toBe(Method::GET);
    expect($request)->toBeInstanceOf(Paginatable::class);

    $connector = new LaravelCloudConnector(config('laravel-cloud-sdk.token'));
    $response = $connector->send($request);

    $dto = $response->dtoOrFail();
    expect($dto)->toBeArray();
    expect($dto[0])->toBeInstanceOf(ManagedQueueFailedJobData::class);
    expect($dto[0]->name)->toBe('App\\Jobs\\ProcessOrder');
    expect($dto[0]->queue)->toBe('default');
    expect($dto[0]->failedAt)->not->toBeNull();
    expect($dto[0]->startedAt)->not->toBeNull();
    expect($dto[0]->attempts)->toBe('3');
    expect($dto[0]->retriedAt)->toBeNull();
    expect($dto[0]->retryReservedUntil)->toBeNull();
});

it('paginates managed queue failed jobs through the resource method', function () {
    Saloon::fake([
        ListManagedQueueFailedJobsRequest::class => new LaravelCloudFixture('instances/managed-queue-failed-jobs'),
    ]);

    $cloud = new LaravelCloud(config('laravel-cloud-sdk.token'));
    $jobs = $cloud->managedQueueFailedJobs('inst-123');

    expect($jobs)->toBeInstanceOf(LazyCollection::class);

    $job = $jobs->first();

    Saloon::assertSent(ListManagedQueueFailedJobsRequest::class);
    expect($job)->toBeInstanceOf(ManagedQueueFailedJobData::class);
    expect($job->name)->toBe('App\\Jobs\\ProcessOrder');
});

it('resolves failed job mutation endpoints correctly', function () {
    $retry = new RetryManagedQueueFailedJobRequest('inst-123', 'job-123');
    $delete = new DeleteManagedQueueFailedJobRequest('inst-123', 'job-123');

    expect($retry->resolveEndpoint())->toBe('/instances/inst-123/failed-jobs/job-123/retry');
    expect($retry->getMethod())->toBe(Method::POST);
    expect($delete->resolveEndpoint())->toBe('/instances/inst-123/failed-jobs/job-123');
    expect($delete->getMethod())->toBe(Method::DELETE);
});

it('handles failed job retry and delete responses', function () {
    Saloon::fake([
        RetryManagedQueueFailedJobRequest::class => new LaravelCloudFixture('instances/managed-queue-retry-failed-job'),
        DeleteManagedQueueFailedJobRequest::class => new LaravelCloudFixture('instances/managed-queue-delete-failed-job'),
    ]);

    $connector = new LaravelCloudConnector(config('laravel-cloud-sdk.token'));

    expect($connector->send(new RetryManagedQueueFailedJobRequest('inst-123', 'job-123'))->status())->toBe(202);
    expect($connector->send(new DeleteManagedQueueFailedJobRequest('inst-123', 'job-123'))->status())->toBe(204);
});

it('throws not found for invalid managed queue instance ids', function (string $requestClass) {
    Saloon::fake([
        $requestClass => MockResponse::make([], 404),
    ]);

    $connector = new LaravelCloudConnector(config('laravel-cloud-sdk.token'));

    $connector->send(new $requestClass('invalid-instance-id'))->throw();
})->with([
    PauseManagedQueueRequest::class,
    ResumeManagedQueueRequest::class,
    PurgeManagedQueueRequest::class,
    SetDefaultManagedQueueRequest::class,
    ListManagedQueueFailedJobsRequest::class,
])->throws(NotFoundException::class);

it('throws not found for failed job mutations with invalid managed queue instance ids', function (string $requestClass) {
    Saloon::fake([
        $requestClass => MockResponse::make([], 404),
    ]);

    $connector = new LaravelCloudConnector(config('laravel-cloud-sdk.token'));

    $connector->send(new $requestClass('invalid-instance-id', 'job-123'))->throw();
})->with([
    [RetryManagedQueueFailedJobRequest::class],
    [DeleteManagedQueueFailedJobRequest::class],
])->throws(NotFoundException::class);

it('throws not found for invalid managed queue failed job ids', function (string $requestClass) {
    Saloon::fake([
        $requestClass => MockResponse::make([], 404),
    ]);

    $connector = new LaravelCloudConnector(config('laravel-cloud-sdk.token'));

    $connector->send(new $requestClass('inst-123', 'invalid-job-id'))->throw();
})->with([
    [RetryManagedQueueFailedJobRequest::class],
    [DeleteManagedQueueFailedJobRequest::class],
])->throws(NotFoundException::class);

# Laravel Cloud SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/redberry/laravel-cloud-sdk.svg?style=flat-square)](https://packagist.org/packages/redberry/laravel-cloud-sdk)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/RedberryProducts/laravel-cloud-sdk/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/RedberryProducts/laravel-cloud-sdk/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Code Coverage](https://img.shields.io/codecov/c/github/RedberryProducts/laravel-cloud-sdk?style=flat-square&logo=codecov)](https://codecov.io/gh/RedberryProducts/laravel-cloud-sdk)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/RedberryProducts/laravel-cloud-sdk/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/RedberryProducts/laravel-cloud-sdk/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/redberry/laravel-cloud-sdk.svg?style=flat-square)](https://packagist.org/packages/redberry/laravel-cloud-sdk)

A fluent, expressive PHP SDK for the [Laravel Cloud](https://cloud.laravel.com) API. Manage your applications, environments, databases, caches, object storage, and more - directly from your Laravel application.

> This is a community-maintained SDK. It is not officially affiliated with, endorsed by, or supported by Laravel. "Laravel" and "Laravel Cloud" are trademarks of their respective owners.

## Requirements

| Package version | PHP       | Laravel              |
|-----------------|-----------|----------------------|
| 1.x             | 8.3, 8.4  | 11.x, 12.x, 13.x     |

## Installation

You may install the Laravel Cloud SDK via Composer:

```bash
composer require redberry/laravel-cloud-sdk
```

The package will automatically register its service provider.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=cloud-sdk-config
```

This will create a `config/laravel-cloud-sdk.php` configuration file. You should add your Laravel Cloud API token to your `.env` file:

```env
LARAVEL_CLOUD_TOKEN=your-api-token
```

You may generate an API token from your Laravel Cloud account settings.

## Usage

### Creating a Client

The most common way to interact with the SDK is through the `LaravelCloud` facade, which automatically uses the API token from your configuration:

```php
use Redberry\LaravelCloudSdk\Facades\LaravelCloud;

$applications = LaravelCloud::applications();
```

If you need to provide a token at runtime - for example, when managing multiple Cloud organizations - you may use the `forToken` method:

```php
$cloud = LaravelCloud::forToken($user->cloud_token);
$applications = $cloud->applications();
```

You may also instantiate the client directly:

```php
use Redberry\LaravelCloudSdk\LaravelCloud;

$cloud = new LaravelCloud($token);
```

### Enums

The SDK ships with enums for all known API values - regions, PHP versions, database types, statuses, and more. Enums provide type safety and IDE autocompletion, but they are never required. Every method that accepts an enum also accepts a plain string:

```php
use Redberry\LaravelCloudSdk\Enums\CloudRegion;
use Redberry\LaravelCloudSdk\Enums\SourceControlProvider;

// Using enums (recommended)
LaravelCloud::createApplication(
    repository: 'my-org/my-repo',
    name: 'my-app',
    region: CloudRegion::UsEast1,
    sourceControlProviderType: SourceControlProvider::Github,
);

// Using strings - equally valid
LaravelCloud::createApplication(
    repository: 'my-org/my-repo',
    name: 'my-app',
    region: 'us-east-1',
    sourceControlProviderType: 'github',
);
```

Response objects return enum instances for known values and fall back to raw strings for values the SDK doesn't yet recognize. This means the SDK never breaks when Laravel Cloud adds new options:

```php
$app = LaravelCloud::application('app-123');
$app->region; // CloudRegion::UsEast1

$newRegionApp = LaravelCloud::application('app-456');
$newRegionApp->region; // "ap-northeast-1" (new region the SDK doesn't know about yet - still works)
```

### Pagination

Methods that return lists of resources use automatic pagination behind the scenes. The SDK returns `LazyCollection` instances that fetch pages on demand as you iterate, so you never need to manage pagination manually:

```php
// Iterates through all pages automatically
$applications = LaravelCloud::applications();

foreach ($applications as $application) {
    echo $application->name;
}

// Standard collection operations work seamlessly
$names = LaravelCloud::applications()->map(fn ($app) => $app->name);
$production = LaravelCloud::environments($appId)->filter(fn ($env) => $env->name === 'production');
```

Because results are lazy, only the pages you actually consume are fetched from the API. If you only need the first result, only one API call is made.

### Response Data Objects

Every API response is automatically converted into a strongly-typed data object. You never work with raw arrays or JSON - every property is typed, every enum is resolved, and your IDE can autocomplete every field:

```php
$environment = LaravelCloud::environment($environmentId);

$environment->name;             // "production"
$environment->status;           // EnvironmentStatus::Running
$environment->phpMajorVersion;  // PhpVersion::V8_4
$environment->createdAt;        // CarbonImmutable instance
```

Methods that return lists yield `LazyCollection` instances where each item is the same typed data object:

```php
$environments = LaravelCloud::environments($applicationId);

$environments->each(function (EnvironmentData $environment) {
    echo $environment->name;
});
```

Each resource section below includes a **Response** toggle documenting all available data object properties.

### Relationships

When you retrieve resources - whether a single resource or a list - the SDK automatically loads their related resources. Related data is available as typed properties directly on the response object, with no extra API calls needed:

```php
$environment = LaravelCloud::environment($environmentId);

$environment->application;          // ApplicationData
$environment->instances;            // InstanceData[]
$environment->currentDeployment;    // ?DeploymentData
$environment->database;             // ?DatabaseData
$environment->cache;                // ?CacheData

// Relationships are also loaded on list results
$environments = LaravelCloud::environments($applicationId);

$environments->each(function (EnvironmentData $env) {
    echo $env->application->name;           // loaded automatically
    echo count($env->instances) . ' instances';
});

$application = LaravelCloud::application($applicationId);

$application->organization;         // ?OrganizationData
$application->environments;         // EnvironmentData[]
```

Singular relationships return the related data object or `null` if not set. Array relationships return an array of data objects (empty array if none exist).

Each resource section below documents available relationship properties in its **Response** toggle.

### Creating & Updating Resources

Create and update methods use named parameters for a clean, readable API:

```php
$application = LaravelCloud::createApplication(
    repository: 'my-org/my-repo',
    name: 'my-application',
    region: CloudRegion::UsEast1,
    sourceControlProviderType: SourceControlProvider::Github,
);

$application = LaravelCloud::updateApplication($applicationId,
    name: 'new-name',
    slackChannel: '#deployments',
);
```

For updates, only pass the fields you want to change - all other fields remain unchanged.

Every create and update method also has a corresponding `*With` variant that accepts a data object directly. This is useful when you want to build the payload programmatically or reuse it across calls:

```php
use Redberry\LaravelCloudSdk\Data\Applications\CreateApplicationData;

$application = LaravelCloud::createApplicationWith(
    new CreateApplicationData(
        repository: 'my-org/my-repo',
        name: 'my-application',
        region: CloudRegion::UsEast1,
        sourceControlProviderType: SourceControlProvider::Github,
    )
);
```

Each resource section below documents all available parameters for its create and update methods.

### Deleting Resources

Delete methods accept a resource ID:

```php
LaravelCloud::deleteApplication($applicationId);
LaravelCloud::deleteEnvironment($environmentId);
LaravelCloud::deleteDomain($domainId);
```

## About Redberry

This package is built and maintained by [Redberry](https://redberry.international), one of the few Official Premier Laravel Partner agencies worldwide. With 250+ Laravel projects shipped across 20+ countries, a 200-person team, and over a decade in the Laravel ecosystem, Redberry has helped startups, SMEs, and publicly traded enterprises in regulated industries build SaaS platforms, custom web applications, APIs, and more. [Learn about our Laravel development services](https://redberry.international/laravel-development/).

## Table of Contents

- [Applications](#applications)
- [Environments](#environments)
- [Deployments](#deployments)
- [Instances](#instances)
- [Background Processes](#background-processes)
- [Commands](#commands)
- [Domains](#domains)
- [Database Clusters](#database-clusters)
- [Database Snapshots](#database-snapshots)
- [Databases](#databases)
- [Caches](#caches)
- [Object Storage Buckets](#object-storage-buckets)
- [Bucket Keys](#bucket-keys)
- [Websocket Clusters](#websocket-clusters)
- [Websocket Applications](#websocket-applications)
- [Dedicated Clusters](#dedicated-clusters)
- [Organization](#organization)
- [Regions](#regions)
- [IP Addresses](#ip-addresses)
- [Error Handling](#error-handling)
- [Testing](#testing)

## Applications

Applications are the top-level organizational unit in Laravel Cloud. Each application represents a single Laravel project connected to a source control repository and deployed to a specific region. An application contains one or more [environments](#environments) and serves as the parent for all deployment infrastructure.

### Listing Applications

```php
$applications = LaravelCloud::applications();
```

### Retrieving an Application

```php
$application = LaravelCloud::application($applicationId);
```

### Creating an Application

```php
$application = LaravelCloud::createApplication(
    repository: 'my-org/my-repo',
    name: 'my-application',
    region: CloudRegion::UsEast1,
    sourceControlProviderType: SourceControlProvider::Github,
);
```

### Updating an Application

```php
$application = LaravelCloud::updateApplication($applicationId,
    name: 'new-name',
    slackChannel: '#deployments',
);
```

### Deleting an Application

```php
LaravelCloud::deleteApplication($applicationId);
```

> **Warning:** Deleting an application removes all of its environments. Shared resources like databases, caches, and buckets are not deleted.

### Application Avatar

You may upload or remove an application's avatar image:

```php
$application = LaravelCloud::uploadApplicationAvatar($applicationId, avatarPath: '/path/to/avatar.png');

LaravelCloud::deleteApplicationAvatar($applicationId);
```

<details>
<summary>Available parameters for <code>createApplication</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `repository` | `string` | Yes | The full repository path (e.g., `my-org/my-repo`). |
| `name` | `string` | Yes | The display name of the application. |
| `region` | `string\|CloudRegion` | Yes | The AWS region to deploy to (e.g., `us-east-1`). |
| `sourceControlProviderType` | `string\|SourceControlProvider` | Yes | The source control provider: `github`, `gitlab`, or `bitbucket`. |
| `clusterId` | `?string` | No | The compute cluster ID. If omitted, one is assigned automatically. |

</details>

<details>
<summary>Available parameters for <code>updateApplication</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `sourceControlProviderType` | `string\|SourceControlProvider` | No | Change the source control provider. |
| `name` | `string` | No | Update the display name. |
| `slug` | `string` | No | Update the URL-friendly slug. |
| `defaultEnvironmentId` | `string` | No | Set the default environment ID. |
| `repository` | `string` | No | Change the connected repository. |
| `slackChannel` | `?string` | No | Set a Slack channel for notifications. Pass `null` to remove. |

</details>

<details>
<summary>Response: <code>ApplicationData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The application ID. |
| `name` | `string` | The display name. |
| `slug` | `string` | The URL-friendly slug. |
| `region` | `string\|CloudRegion` | The AWS region (e.g., `us-east-1`). |
| `slackChannel` | `?string` | The Slack notification channel. |
| `avatarUrl` | `?string` | URL of the application avatar. |
| `repository` | `?ApplicationRepositoryData` | Repository info: `fullName` (string), `defaultBranch` (string). |
| `createdAt` | `?CarbonImmutable` | When the application was created. |
| `organization` | `?OrganizationData` | The parent organization. |
| `environments` | `EnvironmentData[]` | All environments belonging to this application. |
| `defaultEnvironment` | `?EnvironmentData` | The default environment. |

</details>

## Environments

Environments represent isolated deployment configurations within an application. Each environment has its own compute instances, resource attachments, environment variables, domains, and deployment settings.

A typical setup might include a **production** environment with larger, non-hibernating instances and a dedicated database cluster, alongside **dev**, **staging**, and **UAT** environments with smaller instances sharing a single database cluster. You can also spin up short-lived environments for feature testing or pull request previews.

When an environment is attached to resources (databases, caches, object storage), Laravel Cloud automatically injects the necessary environment variables - `DB_HOST`, `REDIS_HOST`, `FILESYSTEM_DISK`, and so on.

### Listing Environments

```php
$environments = LaravelCloud::environments($applicationId);
```

### Retrieving an Environment

```php
$environment = LaravelCloud::environment($environmentId);
```

### Creating an Environment

```php
$environment = LaravelCloud::createEnvironment($applicationId,
    name: 'staging',
    branch: 'develop',
);
```

### Updating an Environment

Only pass the fields you want to change. All other fields remain unchanged:

```php
use Redberry\LaravelCloudSdk\Enums\PhpVersion;
use Redberry\LaravelCloudSdk\Enums\NodeVersion;

$environment = LaravelCloud::updateEnvironment($environmentId,
    phpVersion: PhpVersion::V8_4,
    nodeVersion: NodeVersion::V22,
    usesPushToDeploy: true,
    buildCommand: 'npm run build',
    deployCommand: 'php artisan migrate --force',
);
```

### Starting & Stopping an Environment

```php
// Start - optionally force a redeploy
$deployment = LaravelCloud::startEnvironment($environmentId, redeploy: true);

// Stop
$environment = LaravelCloud::stopEnvironment($environmentId);
```

### Deleting an Environment

```php
LaravelCloud::deleteEnvironment($environmentId);
```

### Environment Variables

You may set environment variables using the `EnvironmentVariableMethod::Append` strategy (add or update specific keys) or the `EnvironmentVariableMethod::Set` strategy (replace all variables entirely). Each variable is an array with `key` and `value` entries:

```php
use Redberry\LaravelCloudSdk\Enums\EnvironmentVariableMethod;

// Add or update specific variables
$environment = LaravelCloud::setEnvironmentVariables($environmentId,
    method: EnvironmentVariableMethod::Append,
    variables: [
        ['key' => 'APP_DEBUG', 'value' => 'false'],
        ['key' => 'CACHE_DRIVER', 'value' => 'redis'],
    ],
);

// Remove specific variables by key
$environment = LaravelCloud::deleteEnvironmentVariables($environmentId,
    keys: ['APP_DEBUG', 'CACHE_DRIVER'],
);
```

### Environment Metrics

Retrieve CPU, memory, and HTTP metrics for an environment:

```php
use Redberry\LaravelCloudSdk\Enums\MetricPeriod;

$metrics = LaravelCloud::environmentMetrics($environmentId);

// With a specific time period
$metrics = LaravelCloud::environmentMetrics($environmentId, period: MetricPeriod::SevenDays);
```

Available periods: `6h`, `24h`, `3d`, `7d`, `30d`.

### Environment Logs

Query application logs with time range filters:

```php
use Redberry\LaravelCloudSdk\Enums\LogFilterType;

$logs = LaravelCloud::environmentLogs(
    $environmentId,
    from: '2025-01-01T00:00:00Z',
    to: '2025-01-02T00:00:00Z',
    searchQuery: 'Exception',
    type: LogFilterType::Application,
);

foreach ($logs as $entry) {
    echo "[{$entry->level}] {$entry->message}";
}
```

Log results are automatically paginated using cursor-based pagination and returned as a `LazyCollection`. You may filter by type: `All`, `Application`, or `Access`.

<details>
<summary>Available parameters for <code>createEnvironment</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | Yes | The environment name (e.g., `staging`, `production`). |
| `branch` | `string` | Yes | The Git branch to deploy from. |
| `clusterId` | `?string` | No | The compute cluster ID. If omitted, one is assigned automatically. |

</details>

<details>
<summary>Available parameters for <code>updateEnvironment</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | No | Update the environment's display name. |
| `slug` | `string` | No | Update the URL-friendly slug. |
| `color` | `string\|EnvironmentColor` | No | The color label for the dashboard. |
| `branch` | `string` | No | Change the Git branch. |
| `phpVersion` | `string\|PhpVersion` | No | The PHP version (`8.2`, `8.3`, `8.4`, `8.5`). Requires redeployment. |
| `nodeVersion` | `string\|NodeVersion` | No | The Node.js version (`20`, `22`, `24`). Requires redeployment. |
| `buildCommand` | `?string` | No | Command to run during build (e.g., `npm run build`). Pass `null` to remove. |
| `deployCommand` | `?string` | No | Command to run before activation (e.g., `php artisan migrate --force`). Pass `null` to remove. |
| `usesPushToDeploy` | `bool` | No | Whether pushing to the branch triggers deployment. |
| `usesDeployHook` | `bool` | No | Whether the deploy hook is enabled. |
| `usesOctane` | `bool` | No | Whether Laravel Octane is enabled. |
| `usesVanityDomain` | `bool` | No | Whether the free `laravel.cloud` subdomain is active. |
| `timeout` | `int` | No | Request timeout in seconds. |
| `sleepTimeout` | `int` | No | Seconds of inactivity before hibernation (Flex only). |
| `shutdownTimeout` | `int` | No | Seconds to wait for graceful shutdown. |
| `usesPurgeEdgeCacheOnDeploy` | `bool` | No | Whether to purge edge cache after deployment. |
| `nightwatchToken` | `?string` | No | The Nightwatch monitoring token. Pass `null` to remove. |
| `cacheStrategy` | `string\|CacheStrategy` | No | Edge cache strategy. |
| `responseHeadersFrame` | `string\|ResponseHeadersFrame` | No | The `X-Frame-Options` header value. |
| `responseHeadersContentType` | `string\|ResponseHeadersContentType` | No | The `X-Content-Type-Options` header value. |
| `responseHeadersRobotsTag` | `string\|ResponseHeadersRobotsTag` | No | The `X-Robots-Tag` header value. |
| `responseHeadersHsts` | `?HstsData` | No | HSTS configuration object. Fields: `maxAge` (?int), `includeSubdomains` (bool, required), `preload` (bool, required). Pass `null` to disable. |
| `filesystemKeys` | `?array` | No | Array of `FilesystemKeyData` objects for object storage. Each requires: `id` (string), `disk` (string), `isDefaultDisk` (bool). Pass `null` to detach all. |
| `firewallRateLimitLevel` | `?string\|FirewallRateLimitLevel` | No | Cloudflare rate limiting level. Pass `null` to disable. |
| `firewallUnderAttackMode` | `bool` | No | Whether Cloudflare's "Under Attack" mode is enabled. |
| `databaseSchemaId` | `?string` | No | The database ID to attach. Pass `null` to detach. |
| `cacheId` | `?string` | No | The cache ID to attach. Pass `null` to detach. |
| `websocketApplicationId` | `?string` | No | The websocket application ID to attach. Pass `null` to detach. |

</details>

<details>
<summary>Response: <code>EnvironmentData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The environment ID. |
| `name` | `string` | The environment name. |
| `slug` | `string` | The URL-friendly slug. |
| `status` | `string\|EnvironmentStatus` | Current status: `deploying`, `running`, `hibernating`, `stopped`. |
| `phpMajorVersion` | `string\|PhpVersion` | The PHP version: `8.2`, `8.3`, `8.4`, `8.5`. |
| `nodeVersion` | `string\|NodeVersion` | The Node.js version: `20`, `22`, `24`. |
| `vanityDomain` | `string` | The free `laravel.cloud` subdomain. |
| `createdFromAutomation` | `bool` | Whether this environment was created by an automation. |
| `usesOctane` | `bool` | Whether Laravel Octane is enabled. |
| `usesHibernation` | `bool` | Whether hibernation is enabled. |
| `usesPushToDeploy` | `bool` | Whether push-to-deploy is enabled. |
| `usesDeployHook` | `bool` | Whether the deploy hook is enabled. |
| `buildCommand` | `?string` | The build command. |
| `deployCommand` | `?string` | The deploy command. |
| `environmentVariables` | `EnvironmentVariableData[]` | Array of environment variables. Each has `key` (string) and `value` (string). |
| `networkSettings` | `NetworkSettingsData` | Network configuration: `cacheStrategy` (string), `responseHeadersFrame` (string), `responseHeadersContentType` (string), `responseHeadersRobotsTag` (string), `responseHeadersHsts` (HstsData), `firewallRateLimitLevel` (?string), `firewallUnderAttackMode` (bool). |
| `createdAt` | `?CarbonImmutable` | When the environment was created. |
| `application` | `?ApplicationData` | The parent application. |
| `branch` | `?BranchData` | The Git branch. Has `id` and `name` properties. |
| `deployments` | `DeploymentData[]` | All deployments for this environment. |
| `currentDeployment` | `?DeploymentData` | The currently active deployment. |
| `primaryDomain` | `?DomainData` | The primary domain. |
| `instances` | `InstanceData[]` | All compute instances. |
| `database` | `?DatabaseData` | The attached database. |
| `cache` | `?CacheData` | The attached cache. |
| `buckets` | `BucketData[]` | Attached object storage buckets. |
| `websocketApplication` | `?WebsocketApplicationData` | The attached websocket application. |

</details>

## Deployments

Deployments represent individual releases of your application. Each deployment captures a snapshot of your code at a specific commit, builds it, and rolls it out to the environment's instances.

### Listing Deployments

```php
$deployments = LaravelCloud::deployments($environmentId);
```

### Retrieving a Deployment

```php
$deployment = LaravelCloud::deployment($deploymentId);
```

### Creating a Deployment

```php
$deployment = LaravelCloud::deploy($environmentId);
```

### Deployment Logs

Retrieve the build and deploy logs for a specific deployment:

```php
$logs = LaravelCloud::deploymentLogs($deploymentId);

// Build phase
foreach ($logs->build->steps as $step) {
    echo "{$step->step}: {$step->status} ({$step->durationMs}ms)\n";
    echo $step->output;
}

// Deploy phase
foreach ($logs->deploy->steps as $step) {
    echo "{$step->step}: {$step->status}\n";
}
```

<details>
<summary>Response: <code>DeploymentData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The deployment ID. |
| `status` | `string\|DeploymentStatus` | Current status: `pending`, `build.running`, `build.succeeded`, `build.failed`, `deployment.running`, `deployment.succeeded`, `deployment.failed`, `cancelled`, `failed`. |
| `branchName` | `?string` | The Git branch that was deployed. |
| `commitHash` | `?string` | The commit SHA. |
| `commitMessage` | `?string` | The commit message. |
| `commitAuthor` | `?string` | The commit author. |
| `failureReason` | `?string` | Description of why the deployment failed. |
| `phpMajorVersion` | `string\|PhpVersion\|null` | The PHP version used: `8.2`, `8.3`, `8.4`, `8.5`. |
| `buildCommand` | `?string` | The build command used. |
| `nodeVersion` | `string\|NodeVersion\|null` | The Node.js version used: `20`, `22`, `24`. |
| `usesOctane` | `bool` | Whether Octane was enabled. |
| `usesHibernation` | `bool` | Whether hibernation was enabled. |
| `startedAt` | `?CarbonImmutable` | When the deployment started. |
| `finishedAt` | `?CarbonImmutable` | When the deployment finished. |
| `createdAt` | `?CarbonImmutable` | When the deployment was created. |
| `environment` | `?EnvironmentData` | The parent environment. |
| `initiator` | `?UserData` | The user who initiated the deployment. Has `id` and `name` properties. |

</details>

## Instances

Instances are the compute infrastructure that runs your application code. Every environment has at least one **App** instance that serves HTTP traffic, and may optionally have **Worker** or **Managed Queue** instances for background processing.

Instances come in two performance classes: **Flex** (lightweight, cost-efficient, supports hibernation) and **Pro** (larger sizes for sustained production workloads). Scaling can be disabled, set to custom min/max replicas, or left unlimited for automatic horizontal scaling.

### Listing Instances

```php
$instances = LaravelCloud::instances($environmentId);
```

### Retrieving an Instance

```php
$instance = LaravelCloud::instance($instanceId);
```

### Creating an Instance

```php
use Redberry\LaravelCloudSdk\Enums\InstanceType;
use Redberry\LaravelCloudSdk\Enums\InstanceSize;
use Redberry\LaravelCloudSdk\Enums\InstanceScalingType;

$instance = LaravelCloud::createInstance($environmentId,
    name: 'web',
    type: InstanceType::App,
    size: InstanceSize::FlexG2vcpu1gb,
    scalingType: InstanceScalingType::Custom,
    maxReplicas: 5,
    minReplicas: 1,
);
```

### Updating an Instance

```php
$instance = LaravelCloud::updateInstance($instanceId,
    size: InstanceSize::FlexM8vcpu8gb,
);
```

### Deleting an Instance

```php
LaravelCloud::deleteInstance($instanceId);
```

### Listing Available Instance Sizes

```php
$sizes = LaravelCloud::instanceSizes();
```

### Managed Queues

Managed queues are queue-focused instances that Laravel Cloud runs and supervises for you. You can create them with queue-specific timeout settings, pause or resume processing, purge pending jobs, set the default queue instance, and inspect failed jobs:

```php
use Redberry\LaravelCloudSdk\Enums\InstanceType;
use Redberry\LaravelCloudSdk\Enums\InstanceSize;
use Redberry\LaravelCloudSdk\Enums\InstanceScalingType;

$queue = LaravelCloud::createInstance($environmentId,
    name: 'default-queue',
    type: InstanceType::ManagedQueue,
    size: InstanceSize::FlexG1vcpu512mb,
    scalingType: InstanceScalingType::None,
    maxReplicas: 1,
    minReplicas: 1,
    visibilityTimeout: 60,
    pollingInterval: 20,
    shutdownTimeout: 30,
    sleepWithApp: true,
);

LaravelCloud::pauseManagedQueue($queue->id);
LaravelCloud::resumeManagedQueue($queue->id);
LaravelCloud::purgeManagedQueue($queue->id);
LaravelCloud::setDefaultManagedQueue($queue->id);

$failedJobs = LaravelCloud::managedQueueFailedJobs($queue->id);

foreach ($failedJobs as $job) {
    LaravelCloud::retryManagedQueueFailedJob($queue->id, $job->id);
    LaravelCloud::deleteManagedQueueFailedJob($queue->id, $job->id);
}
```

<details>
<summary>Available parameters for <code>createInstance</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | Yes | The instance name (e.g., `web`, `worker`). |
| `type` | `string\|InstanceType` | Yes | The instance type: `app`, `service`, `queue`, `serverless_queue`, or `managed_queue`. |
| `size` | `string\|InstanceSize` | Yes | The compute size (e.g., `FlexM2vcpu2gb`, `ProG2vcpu4gb`). |
| `scalingType` | `string\|InstanceScalingType` | Yes | Scaling strategy: `none`, `custom`, or `auto`. |
| `maxReplicas` | `int` | Yes | The maximum number of replicas when using `custom` scaling. |
| `minReplicas` | `int` | Yes | The minimum number of replicas when using `custom` scaling. |
| `usesScheduler` | `bool` | No | Whether this instance runs the task scheduler. |
| `scalingCpuThresholdPercentage` | `?int` | No | CPU usage percentage threshold that triggers scaling. |
| `scalingMemoryThresholdPercentage` | `?int` | No | Memory usage percentage threshold that triggers scaling. |
| `backgroundProcesses` | `array` | No | Array of `BackgroundProcessConfigData` objects. All fields are optional: `connection` (string), `queue` (string), `tries` (int), `backoff` (int), `sleep` (int), `rest` (int), `timeout` (int), `force` (bool). |
| `visibilityTimeout` | `?int` | No | Managed queue visibility timeout in seconds. |
| `pollingInterval` | `?int` | No | Managed queue polling interval in seconds. |
| `shutdownTimeout` | `?int` | No | Managed queue graceful shutdown timeout in seconds. |
| `sleepWithApp` | `?bool` | No | Whether the managed queue sleeps with the app instance. |

</details>

<details>
<summary>Available parameters for <code>updateInstance</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | No | Update the instance name. |
| `size` | `string\|InstanceSize` | No | Change the compute size. |
| `scalingType` | `string\|InstanceScalingType` | No | Change the scaling strategy. |
| `maxReplicas` | `int` | No | Update maximum replicas. |
| `minReplicas` | `int` | No | Update minimum replicas. |
| `usesSleepMode` | `bool` | No | Whether the instance hibernates when idle (Flex only). |
| `sleepTimeout` | `int` | No | Seconds of inactivity before hibernation. |
| `usesScheduler` | `bool` | No | Whether this instance runs the task scheduler. |
| `usesOctane` | `bool` | No | Whether Laravel Octane is enabled. |
| `usesInertiaSsr` | `bool` | No | Whether Inertia SSR is enabled. |
| `scalingCpuThresholdPercentage` | `?int` | No | CPU threshold for scaling. |
| `scalingMemoryThresholdPercentage` | `?int` | No | Memory threshold for scaling. |
| `visibilityTimeout` | `?int` | No | Update managed queue visibility timeout in seconds. |
| `pollingInterval` | `?int` | No | Update managed queue polling interval in seconds. |
| `shutdownTimeout` | `?int` | No | Update managed queue graceful shutdown timeout in seconds. |
| `sleepWithApp` | `?bool` | No | Update whether the managed queue sleeps with the app instance. |

</details>

<details>
<summary>Response: <code>InstanceData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The instance ID. |
| `name` | `string` | The instance name. |
| `type` | `string\|InstanceType` | `app`, `service`, `queue`, `serverless_queue`, or `managed_queue`. |
| `size` | `string\|InstanceSize` | The compute size (e.g., `flex-small`, `pro-medium`). |
| `scalingType` | `string\|InstanceScalingType` | `none`, `custom`, or `auto`. |
| `minReplicas` | `int` | Minimum number of replicas. |
| `maxReplicas` | `int` | Maximum number of replicas. |
| `usesScheduler` | `bool` | Whether the task scheduler runs on this instance. |
| `scalingCpuThresholdPercentage` | `?int` | CPU threshold for scaling. |
| `scalingMemoryThresholdPercentage` | `?int` | Memory threshold for scaling. |
| `backgroundProcesses` | `BackgroundProcessData[]` | Background processes attached to this instance. |
| `createdAt` | `?CarbonImmutable` | When the instance was created. |
| `environment` | `?EnvironmentData` | The parent environment. |
| `queueStatus` | `string\|ManagedQueueStatus\|null` | Managed queue status: `creating`, `available`, `updating`, `deleting`, `deleted`, or `unknown`. |
| `paused` | `?bool` | Whether the managed queue is paused. |
| `isDefault` | `?bool` | Whether this is the environment's default managed queue. |
| `visibilityTimeout` | `?int` | Managed queue visibility timeout in seconds. |
| `pollingInterval` | `?int` | Managed queue polling interval in seconds. |
| `shutdownTimeout` | `?int` | Managed queue graceful shutdown timeout in seconds. |
| `sleepWithApp` | `?bool` | Whether the managed queue sleeps with the app instance. |

</details>

<details>
<summary>Response: <code>ManagedQueueFailedJobData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The failed job ID. |
| `name` | `?string` | The job class or display name. |
| `queue` | `?string` | The queue the job was pulled from. |
| `failedAt` | `?CarbonImmutable` | When the job failed. |
| `startedAt` | `?CarbonImmutable` | When the job started processing. |
| `attempts` | `string` | Number of attempts recorded by Laravel Cloud. |
| `exception` | `string` | The exception message or trace for the failed job. |
| `retriedAt` | `?CarbonImmutable` | When the job was last retried. |
| `retryReservedUntil` | `?CarbonImmutable` | When the retry reservation expires. |

</details>

<details>
<summary>Response: <code>InstanceSizeData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The size identifier (e.g., `flex-small`). |
| `name` | `string` | The size name. |
| `label` | `string` | Human-readable label. |
| `description` | `string` | Description of the size tier. |
| `cpuType` | `string` | The CPU architecture type. |
| `computeClass` | `string` | The compute class (e.g., `flex`, `pro`). |
| `cpuCount` | `int` | Number of CPU cores. |
| `memoryMib` | `int` | Memory in MiB. |

</details>

## Background Processes

Background processes are long-running daemons attached to an instance - typically queue workers or custom commands. Each process defines a daemon type, the number of concurrent processes, and optionally a custom command or queue configuration.

### Listing Background Processes

```php
$processes = LaravelCloud::backgroundProcesses($instanceId);
```

### Retrieving a Background Process

```php
$process = LaravelCloud::backgroundProcess($processId);
```

### Creating a Background Process

```php
use Redberry\LaravelCloudSdk\Enums\DaemonType;
use Redberry\LaravelCloudSdk\Data\BackgroundProcesses\BackgroundProcessConfigData;

// A queue worker
$process = LaravelCloud::createBackgroundProcess($instanceId,
    type: DaemonType::Worker,
    processes: 3,
    config: new BackgroundProcessConfigData(
        connection: 'redis',
        queue: 'default,emails',
        tries: 3,
        timeout: 60,
    ),
);

// A custom daemon
$process = LaravelCloud::createBackgroundProcess($instanceId,
    type: DaemonType::Custom,
    processes: 1,
    command: 'php artisan horizon',
);
```

### Updating a Background Process

```php
$process = LaravelCloud::updateBackgroundProcess($processId,
    processes: 5,
);
```

### Deleting a Background Process

```php
LaravelCloud::deleteBackgroundProcess($processId);
```

<details>
<summary>Available parameters for <code>createBackgroundProcess</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `type` | `string\|DaemonType` | Yes | The daemon type: `worker` or `custom`. |
| `processes` | `int` | Yes | Number of concurrent processes to run. |
| `command` | `?string` | No | The command to execute (required for `custom` type). |
| `config` | `?BackgroundProcessConfigData` | No | Queue worker configuration (for `worker` type). All fields are optional: `connection` (string), `queue` (string), `tries` (int), `backoff` (int), `sleep` (int), `rest` (int), `timeout` (int), `force` (bool). |

</details>

<details>
<summary>Available parameters for <code>updateBackgroundProcess</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `type` | `string\|DaemonType` | No | Change the daemon type: `worker` or `custom`. |
| `processes` | `int` | No | Update the number of concurrent processes. |
| `command` | `?string` | No | Change the command. Pass `null` to remove. |
| `config` | `?BackgroundProcessConfigData` | No | Update queue worker configuration. All fields are optional: `connection` (string), `queue` (string), `tries` (int), `backoff` (int), `sleep` (int), `rest` (int), `timeout` (int), `force` (bool). |

</details>

<details>
<summary>Response: <code>BackgroundProcessData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The process ID. |
| `type` | `string\|DaemonType` | `worker` or `custom`. |
| `processes` | `int` | Number of concurrent processes. |
| `command` | `?string` | The custom command, if applicable. |
| `config` | `?BackgroundProcessConfigData` | Queue worker configuration. |
| `strategyType` | `string\|DaemonStrategyType` | Scaling strategy: `none`, `growth_rate`, or `queue_size`. |
| `strategyThreshold` | `?int` | The threshold for the scaling strategy. |
| `createdAt` | `?CarbonImmutable` | When the process was created. |
| `instance` | `?InstanceData` | The parent instance. |

</details>

## Commands

Commands let you execute one-off Artisan or shell commands against a running environment - useful for migrations, cache clearing, or debugging.

### Listing Commands

```php
$commands = LaravelCloud::commands($environmentId);
```

### Retrieving a Command

```php
$command = LaravelCloud::command($commandId);
```

### Running a Command

```php
$command = LaravelCloud::runCommand($environmentId, command: 'php artisan migrate --force');
```

<details>
<summary>Response: <code>CommandData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The command ID. |
| `command` | `string` | The command that was executed. |
| `output` | `?string` | The command output. |
| `status` | `string\|CommandStatus` | Current status: `pending`, `command.created`, `command.running`, `command.success`, `command.failure`. |
| `exitCode` | `?int` | The process exit code. |
| `failureReason` | `?string` | Description of why the command failed. |
| `startedAt` | `?CarbonImmutable` | When the command started executing. |
| `finishedAt` | `?CarbonImmutable` | When the command finished. |
| `createdAt` | `?CarbonImmutable` | When the command was created. |
| `environment` | `?EnvironmentData` | The parent environment. |
| `deployment` | `?DeploymentData` | The associated deployment. |
| `initiator` | `?UserData` | The user who ran the command. Has `id` and `name` properties. |

</details>

## Domains

Every environment receives a free `laravel.cloud` subdomain after its first deployment. For production, you can attach custom domains with automatic SSL provisioning and renewal.

Custom domains support root domains, www variants, and wildcards. Domain verification can happen in real-time (point DNS first) or via pre-verification (verify ownership before switching traffic).

### Listing Domains

```php
$domains = LaravelCloud::domains($environmentId);
```

### Retrieving a Domain

```php
$domain = LaravelCloud::domain($domainId);
```

### Creating a Domain

```php
use Redberry\LaravelCloudSdk\Enums\DomainRedirect;
use Redberry\LaravelCloudSdk\Enums\DomainVerificationMethod;

$domain = LaravelCloud::createDomain($environmentId,
    name: 'example.com',
    wwwRedirect: DomainRedirect::WwwToRoot,
    verificationMethod: DomainVerificationMethod::RealTime,
);
```

<details>
<summary>Available parameters for <code>createDomain</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | Yes | The domain name (e.g., `example.com`). |
| `wwwRedirect` | `string\|DomainRedirect` | Yes | Redirect behavior: `DomainRedirect::RootToWww` or `DomainRedirect::WwwToRoot`. |
| `verificationMethod` | `string\|DomainVerificationMethod` | Yes | Verification: `DomainVerificationMethod::RealTime` or `DomainVerificationMethod::PreVerification`. |
| `cloudflareStrategy` | `string\|DomainCloudflareStrategy` | No | Only needed if your domain is already proxied through Cloudflare. |
| `wildcardEnabled` | `?bool` | No | Whether to enable wildcard subdomains. |
| `allowDowntime` | `?bool` | No | Whether a brief interruption is acceptable during DNS switchover. |

</details>

### Updating a Domain

```php
$domain = LaravelCloud::updateDomain($domainId,
    verificationMethod: DomainVerificationMethod::PreVerification,
);
```

<details>
<summary>Available parameters for <code>updateDomain</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `verificationMethod` | `string\|DomainVerificationMethod` | Yes | `DomainVerificationMethod::RealTime` or `DomainVerificationMethod::PreVerification`. |

</details>

### Verifying a Domain

```php
$domain = LaravelCloud::verifyDomain($domainId);
```

### Deleting a Domain

```php
LaravelCloud::deleteDomain($domainId);
```

<details>
<summary>Response: <code>DomainData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The domain ID. |
| `name` | `string` | The domain name. |
| `type` | `string\|DomainType` | `root`, `www`, or `wildcard`. |
| `hostnameStatus` | `string\|DomainStatus` | Hostname verification status: `pending`, `verified`, `failed`, `disabled`. |
| `sslStatus` | `string\|DomainStatus` | SSL certificate status: `pending`, `verified`, `failed`, `disabled`. |
| `originStatus` | `string\|DomainStatus` | Origin routing status: `pending`, `verified`, `failed`, `disabled`. |
| `redirect` | `string\|DomainRedirect\|null` | The redirect behavior: `root_to_www` or `www_to_root`, if configured. |
| `cloudflareStrategy` | `string\|DomainCloudflareStrategy\|null` | The Cloudflare strategy: `none`, `dns`, or `dns_proxy`, if applicable. |
| `downtime` | `?bool` | Whether downtime was allowed during setup. |
| `wildcardEnabled` | `bool` | Whether wildcard subdomains are enabled. |
| `actionRequired` | `?string` | Description of any action needed from the user. |
| `dnsRecords` | `array` | DNS records that need to be configured. |
| `lastVerifiedAt` | `?CarbonImmutable` | When the domain was last verified. |
| `createdAt` | `?CarbonImmutable` | When the domain was created. |
| `environment` | `?EnvironmentData` | The parent environment. |

</details>

## Database Clusters

A database cluster is the managed infrastructure that hosts one or more logical databases. You create a cluster once, then create individual databases within it for each environment.

This is one of the most important cost-optimization concepts in Laravel Cloud: **non-production environments can share a single database cluster** by each having their own database within it, while production typically would get a dedicated cluster.

Laravel Cloud supports **Laravel MySQL** (managed MySQL 8.0/8.4) and **Neon Serverless Postgres** (serverless PostgreSQL 16/17/18). **AWS RDS** (MySQL 8 or PostgreSQL 18) is also available for [Laravel Private Cloud](https://cloud.laravel.com) customers.

### Listing Database Clusters

```php
$clusters = LaravelCloud::databaseClusters();
```

### Retrieving a Database Cluster

```php
$cluster = LaravelCloud::databaseCluster($clusterId);
```

### Creating a Database Cluster

```php
use Redberry\LaravelCloudSdk\Enums\DatabaseType;
use Redberry\LaravelCloudSdk\Enums\DatabaseClusterSize;
use Redberry\LaravelCloudSdk\Data\DatabaseClusters\LaravelMysqlConfigData;

$cluster = LaravelCloud::createDatabaseCluster(
    name: 'production-db',
    type: DatabaseType::LaravelMysql84,
    region: CloudRegion::UsEast1,
    config: new LaravelMysqlConfigData(
        size: DatabaseClusterSize::Flex1vcpu1gb,
        storage: 10,
        isPublic: false,
        usesScheduledSnapshots: true,
        retentionDays: 7,
        maintenanceWindow: null,
    ),
);
```

### Updating a Database Cluster

```php
$cluster = LaravelCloud::updateDatabaseCluster($clusterId,
    config: new LaravelMysqlConfigData(
        size: DatabaseClusterSize::Flex1vcpu2gb,
        storage: 20,
        isPublic: false,
        usesScheduledSnapshots: true,
        retentionDays: 14,
        maintenanceWindow: null,
    ),
);
```

### Deleting a Database Cluster

```php
LaravelCloud::deleteDatabaseCluster($clusterId);
```

### Database Cluster Metrics

Metrics accept the same `MetricPeriod` options as [environment metrics](#environment-metrics):

```php
$metrics = LaravelCloud::databaseClusterMetrics($clusterId);
$metrics = LaravelCloud::databaseClusterMetrics($clusterId, period: MetricPeriod::SevenDays);
```

### Listing Available Database Types

```php
$types = LaravelCloud::databaseTypes();
```

<details>
<summary>Available parameters for <code>createDatabaseCluster</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | Yes | The cluster name. |
| `type` | `string\|DatabaseType` | Yes | The database engine. Use `databaseTypes()` to list all options. |
| `region` | `string\|CloudRegion` | Yes | The region. Must match attached environments. |
| `config` | `NeonServerlessPostgresConfigData\|LaravelMysqlConfigData\|AwsRdsConfigData` | Yes | Engine-specific configuration object. Must match the selected `type`. See below. |
| `clusterId` | `?int` | No | Optional cluster placement hint. |

**`LaravelMysqlConfigData`** - `size` (string\|DatabaseClusterSize, required), `storage` (int, required), `isPublic` (bool, required), `usesScheduledSnapshots` (bool, required), `retentionDays` (int, required), `maintenanceWindow` (?string).

**`NeonServerlessPostgresConfigData`** - `cuMin` (float\|NeonServerlessPostgresComputeUnit, required), `cuMax` (float\|NeonServerlessPostgresComputeUnit, required), `suspendSeconds` (int, required), `retentionDays` (int, required).

**`AwsRdsConfigData`** - `size` (string\|DatabaseClusterSize, required), `storage` (int, required), `isPublic` (bool, required), `usesPitr` (bool, required), `retentionDays` (int, required), `deploymentOption` (string\|DeploymentOption, required), `maintenanceWindow` (?string), `readReplicas` (?int).

</details>

<details>
<summary>Available parameters for <code>updateDatabaseCluster</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `config` | `NeonServerlessPostgresConfigData\|LaravelMysqlConfigData\|AwsRdsConfigData` | Yes | Engine-specific configuration. Must match the cluster's database type. See [createDatabaseCluster](#creating-a-database-cluster) for config field details. |

</details>

<details>
<summary>Response: <code>DatabaseClusterData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The cluster ID. |
| `name` | `string` | The cluster name. |
| `type` | `string\|DatabaseType` | The database engine: `laravel_mysql_84`, `laravel_mysql_8`, `aws_rds_mysql_8`, `aws_rds_postgres_18`, `neon_serverless_postgres_18`, `neon_serverless_postgres_17`, `neon_serverless_postgres_16`. |
| `status` | `string\|DatabaseStatus` | Current status: `creating`, `updating`, `available`, `restoring`, `restore_failed`, `archiving`, `archived`, `deleting`, `deleted`, etc. |
| `region` | `string\|CloudRegion` | The region. |
| `config` | `NeonServerlessPostgresConfigData\|LaravelMysqlConfigData\|AwsRdsConfigData` | Engine-specific configuration. See [createDatabaseCluster](#creating-a-database-cluster) for field details. |
| `connection` | `DatabaseConnectionData` | Connection details: `hostname` (string), `port` (int), `protocol` (string\|DatabaseProtocol: `mysql` or `postgres`), `driver` (string\|DatabaseDriver: `mysql` or `pgsql`), `username` (string), `password` (string). |
| `createdAt` | `?CarbonImmutable` | When the cluster was created. |
| `databases` | `DatabaseData[]` | All databases within this cluster. |

</details>

## Database Snapshots

Snapshots provide point-in-time backups of your database cluster. You can create manual snapshots, list existing ones, and restore a cluster from a snapshot or a specific point in time.

### Creating a Snapshot

```php
$snapshot = LaravelCloud::createDatabaseSnapshot($clusterId,
    name: 'pre-migration-backup',
    description: 'Before running v2 migrations',
);
```

### Listing Snapshots

```php
$snapshots = LaravelCloud::databaseSnapshots($clusterId);
```

### Retrieving a Snapshot

```php
$snapshot = LaravelCloud::databaseSnapshot($snapshotId);
```

### Restoring From a Snapshot

Restoring creates a **new** database cluster from the snapshot data:

```php
// Restore from a specific snapshot
$cluster = LaravelCloud::restoreDatabaseCluster($clusterId,
    name: 'restored-cluster',
    databaseSnapshotId: $snapshotId,
);

// Restore to a specific point in time
$cluster = LaravelCloud::restoreDatabaseCluster($clusterId,
    name: 'restored-cluster',
    restoreTime: '2025-06-15T14:30:00Z',
);
```

### Deleting a Snapshot

```php
LaravelCloud::deleteDatabaseSnapshot($snapshotId);
```

<details>
<summary>Available parameters for <code>createDatabaseSnapshot</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | Yes | The snapshot name. |
| `description` | `?string` | No | An optional description for the snapshot. |

</details>

<details>
<summary>Available parameters for <code>restoreDatabaseCluster</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | Yes | The name for the new cluster created from the restore. |
| `restoreTime` | `?string` | No | ISO 8601 timestamp for point-in-time restore. Mutually exclusive with `databaseSnapshotId`. |
| `databaseSnapshotId` | `?string` | No | The snapshot ID to restore from. Mutually exclusive with `restoreTime`. |

</details>

<details>
<summary>Response: <code>DatabaseSnapshotData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The snapshot ID. |
| `name` | `string` | The snapshot name. |
| `description` | `?string` | The snapshot description. |
| `type` | `string\|DatabaseSnapshotType` | `manual` or `scheduled`. |
| `status` | `string\|DatabaseSnapshotStatus` | Current status: `pending`, `creating`, `available`, `failed`, `deleting`. |
| `storageBytes` | `?int` | The snapshot size in bytes. |
| `pitrEnabled` | `?bool` | Whether point-in-time recovery is enabled. |
| `pitrEndsAt` | `?CarbonImmutable` | When the PITR window expires. |
| `completedAt` | `?CarbonImmutable` | When the snapshot completed. |
| `createdAt` | `?CarbonImmutable` | When the snapshot was created. |
| `databaseCluster` | `?DatabaseClusterData` | The parent database cluster. |

</details>

## Databases

Databases are the logical schemas within a [database cluster](#database-clusters). When you attach a database to an environment, Laravel Cloud automatically injects the connection credentials. This separation allows you to share one cluster across dev, staging, and UAT environments - each with their own isolated database.

### Listing Databases

```php
$databases = LaravelCloud::databases($clusterId);
```

### Retrieving a Database

```php
$database = LaravelCloud::database($clusterId, $databaseId);
```

### Creating a Database

```php
$database = LaravelCloud::createDatabase($clusterId,
    name: 'my_database',
);
```

### Deleting a Database

```php
LaravelCloud::deleteDatabase($clusterId, $databaseId);
```

<details>
<summary>Response: <code>DatabaseData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The database ID. |
| `name` | `string` | The database name. |
| `createdAt` | `?CarbonImmutable` | When the database was created. |
| `databaseCluster` | `?DatabaseClusterData` | The parent database cluster. |
| `environments` | `EnvironmentData[]` | Environments attached to this database. |

</details>

## Caches

Caches are managed Redis-compatible stores that serve as your application's cache, queue backend, or session store. Caches can be shared across environments - each receives a unique prefix to prevent collisions.

Two types are available: **Laravel Valkey** (managed, Redis-compatible) and **Upstash Redis** (serverless). When attached to an environment, Laravel Cloud injects `CACHE_STORE`, `REDIS_HOST`, and `REDIS_PASSWORD` automatically.

### Listing Caches

```php
$caches = LaravelCloud::caches();
```

### Retrieving a Cache

```php
$cache = LaravelCloud::cache($cacheId);
```

### Creating a Cache

```php
use Redberry\LaravelCloudSdk\Enums\CacheType;
use Redberry\LaravelCloudSdk\Enums\CacheSize;

$cache = LaravelCloud::createCache(
    type: CacheType::LaravelValkey,
    name: 'production-cache',
    region: CloudRegion::UsEast1,
    size: CacheSize::Flex256mb,
    autoUpgradeEnabled: true,
    isPublic: false,
);
```

### Updating a Cache

```php
$cache = LaravelCloud::updateCache($cacheId,
    size: CacheSize::Flex512mb,
);
```

### Deleting a Cache

```php
LaravelCloud::deleteCache($cacheId);
```

### Cache Metrics

Metrics accept the same `MetricPeriod` options as [environment metrics](#environment-metrics):

```php
$metrics = LaravelCloud::cacheMetrics($cacheId);
$metrics = LaravelCloud::cacheMetrics($cacheId, period: MetricPeriod::TwentyFourHours);
```

### Listing Available Cache Types

```php
$types = LaravelCloud::cacheTypes();
```

<details>
<summary>Available parameters for <code>createCache</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `type` | `string\|CacheType` | Yes | The cache engine: `laravel_valkey` or `upstash_redis`. |
| `name` | `string` | Yes | The cache instance name. |
| `region` | `string\|CloudRegion` | Yes | The region. Must match attached environments. |
| `size` | `string\|CacheSize` | Yes | The storage size. Use `cacheTypes()` to see options. |
| `autoUpgradeEnabled` | `bool` | Yes | Whether the cache automatically upgrades in size when needed. |
| `isPublic` | `bool` | Yes | Whether the cache is accessible from the open internet outside the Laravel Cloud network. |
| `evictionPolicy` | `?string\|EvictionPolicy` | No | Key eviction policy (Laravel Valkey only). |

</details>

<details>
<summary>Available parameters for <code>updateCache</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | No | Update the cache name. |
| `size` | `string\|CacheSize` | No | Change the storage size. |
| `autoUpgradeEnabled` | `bool` | No | Toggle automatic size upgrades. |
| `isPublic` | `bool` | No | Toggle public internet access. |
| `evictionPolicy` | `?string\|EvictionPolicy` | No | Change the eviction policy. |

</details>

<details>
<summary>Response: <code>CacheData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The cache ID. |
| `name` | `string` | The cache name. |
| `type` | `string\|CacheType` | `laravel_valkey` or `upstash_redis`. |
| `status` | `string\|CacheStatus` | Current status: `creating`, `updating`, `available`, `deleting`, `deleted`. |
| `region` | `string\|CloudRegion` | The region. |
| `size` | `string\|CacheSize` | The storage size. Upstash: `250mb`, `1gb`, `2.5gb`, `5gb`, `12gb`, `50gb`, `100gb`, `500gb`. Valkey Pro: `valkey-pro.250mb`, `valkey-pro.1gb`, `valkey-pro.2.5gb`, `valkey-pro.5gb`, `valkey-pro.12gb`, `valkey-pro.25gb`, `valkey-pro.50gb`. |
| `autoUpgradeEnabled` | `bool` | Whether automatic size upgrades are enabled. |
| `isPublic` | `bool` | Whether the cache is publicly accessible. |
| `connection` | `CacheConnectionData` | Connection details: `hostname` (?string), `port` (?int), `protocol` (string\|CacheProtocol: `redis`), `username` (?string), `password` (?string). |
| `createdAt` | `?CarbonImmutable` | When the cache was created. |

</details>

## Object Storage Buckets

Buckets provide S3-compatible file storage powered by Cloudflare R2, integrated directly with Laravel's `Storage` facade. When a bucket is attached to an environment, Laravel Cloud injects the necessary AWS S3-compatible credentials and `FILESYSTEM_DISK` configuration automatically.

Buckets can be **private** (files are inaccessible to the public, though you can generate temporary URLs via `Storage::temporaryUrl`) or **public** (files are accessible via internet-facing URLs). You may also choose a **jurisdiction** (`default` or `eu`) for data residency compliance.

A common pattern is to create **one private bucket** for user uploads and sensitive files, and **one public bucket** for assets that need to be served directly. Non-production environments can share a single bucket with different [keys](#bucket-keys) for access isolation - each key provides scoped credentials, so staging and QA can use the same bucket without interfering with each other.

### Listing Buckets

```php
$buckets = LaravelCloud::buckets();
```

### Retrieving a Bucket

```php
$bucket = LaravelCloud::bucket($bucketId);
```

### Creating a Bucket

```php
use Redberry\LaravelCloudSdk\Enums\BucketVisibility;
use Redberry\LaravelCloudSdk\Enums\BucketJurisdiction;
use Redberry\LaravelCloudSdk\Enums\KeyPermission;

$bucket = LaravelCloud::createBucket(
    name: 'media-storage',
    visibility: BucketVisibility::Private,
    jurisdiction: BucketJurisdiction::Eu,
    keyName: 'default-key',
    keyPermission: KeyPermission::ReadWrite,
);
```

### Updating a Bucket

```php
$bucket = LaravelCloud::updateBucket($bucketId,
    name: 'new-bucket-name',
);
```

### Deleting a Bucket

```php
LaravelCloud::deleteBucket($bucketId);
```

<details>
<summary>Available parameters for <code>createBucket</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | Yes | The bucket name. |
| `visibility` | `string\|BucketVisibility` | Yes | `private` or `public`. |
| `jurisdiction` | `string\|BucketJurisdiction` | Yes | `default` (global) or `eu` (EU-only). |
| `keyName` | `string` | Yes | Name for the initial access key. |
| `keyPermission` | `string\|KeyPermission` | Yes | `read_write` or `read_only`. |
| `allowedOrigins` | `?array` | No | CORS allowed origins. |

</details>

<details>
<summary>Available parameters for <code>updateBucket</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | No | Update the bucket name. |
| `visibility` | `string\|BucketVisibility` | No | Change visibility: `private` or `public`. |
| `allowedOrigins` | `?array` | No | Update CORS allowed origins. Pass `null` to remove. |

</details>

<details>
<summary>Response: <code>BucketData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The bucket ID. |
| `name` | `string` | The bucket name. |
| `type` | `string\|BucketType` | The storage backend (e.g., `cloudflare_r2`). |
| `status` | `string\|BucketStatus` | Current status: `creating`, `updating`, `available`, `deleting`, `deleted`. |
| `visibility` | `string\|BucketVisibility` | `private` or `public`. |
| `jurisdiction` | `string\|BucketJurisdiction` | `default` or `eu`. |
| `endpoint` | `?string` | The S3-compatible endpoint URL. |
| `url` | `?string` | The public URL for public buckets. |
| `allowedOrigins` | `?array` | CORS allowed origins. |
| `createdAt` | `?CarbonImmutable` | When the bucket was created. |
| `keys` | `BucketKeyData[]` | All access keys for this bucket. |

</details>

## Bucket Keys

Bucket keys are scoped credentials that control access to a specific bucket. Each key has a permission level - **read-write** or **read-only**. You can create multiple keys with different permissions to control access granularity - for example, giving your application full read-write access while providing a reporting service with read-only credentials.

### Listing Bucket Keys

```php
$keys = LaravelCloud::bucketKeys($bucketId);
```

### Retrieving a Bucket Key

```php
$key = LaravelCloud::bucketKey($keyId);
```

### Creating a Bucket Key

```php
$key = LaravelCloud::createBucketKey($bucketId,
    name: 'upload-key',
    permission: KeyPermission::ReadWrite,
);
```

### Updating a Bucket Key

```php
$key = LaravelCloud::updateBucketKey($keyId, name: 'new-key-name');
```

### Deleting a Bucket Key

```php
LaravelCloud::deleteBucketKey($keyId);
```

<details>
<summary>Response: <code>BucketKeyData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The key ID. |
| `name` | `string` | The key name. |
| `permission` | `string\|KeyPermission` | `read_write` or `read_only`. |
| `accessKeyId` | `?string` | The S3-compatible access key ID. Only returned on creation. |
| `accessKeySecret` | `?string` | The S3-compatible secret key. Only returned on creation. |
| `createdAt` | `?CarbonImmutable` | When the key was created. |
| `bucket` | `?BucketData` | The parent bucket. |

</details>

## Websocket Clusters

Websocket clusters provide fully managed WebSocket infrastructure powered by [Laravel Reverb](https://reverb.laravel.com). Each cluster is created in a specific region with a maximum concurrent connection capacity that is shared across its websocket applications. When a websocket application is attached to an environment, Laravel Cloud automatically injects the necessary Reverb connection credentials.

### Listing Websocket Clusters

```php
$clusters = LaravelCloud::websocketClusters();
```

### Retrieving a Websocket Cluster

```php
$cluster = LaravelCloud::websocketCluster($clusterId);
```

### Creating a Websocket Cluster

```php
use Redberry\LaravelCloudSdk\Enums\WebsocketServerType;
use Redberry\LaravelCloudSdk\Enums\WebsocketMaxConnections;

$cluster = LaravelCloud::createWebsocketCluster(
    name: 'production-ws',
    type: WebsocketServerType::Reverb,
    region: CloudRegion::UsEast1,
    maxConnections: WebsocketMaxConnections::V500,
);
```

### Updating a Websocket Cluster

```php
$cluster = LaravelCloud::updateWebsocketCluster($clusterId,
    maxConnections: WebsocketMaxConnections::V2000,
);
```

### Deleting a Websocket Cluster

```php
LaravelCloud::deleteWebsocketCluster($clusterId);
```

### Websocket Cluster Metrics

Metrics accept the same `MetricPeriod` options as [environment metrics](#environment-metrics):

```php
$metrics = LaravelCloud::websocketClusterMetrics($clusterId);
$metrics = LaravelCloud::websocketClusterMetrics($clusterId, period: MetricPeriod::TwentyFourHours);
```

<details>
<summary>Available parameters for <code>createWebsocketCluster</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | Yes | The cluster name. |
| `type` | `string\|WebsocketServerType` | Yes | Currently only `reverb`. |
| `region` | `string\|CloudRegion` | Yes | The region. |
| `maxConnections` | `string\|WebsocketMaxConnections` | Yes | Maximum concurrent connections: `100`, `200`, `500`, `2000`, `5000`, or `10000`. |

</details>

<details>
<summary>Available parameters for <code>updateWebsocketCluster</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | No | Update the cluster name. |
| `maxConnections` | `string\|WebsocketMaxConnections` | No | Change the maximum concurrent connections. |

</details>

<details>
<summary>Response: <code>WebsocketClusterData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The cluster ID. |
| `name` | `string` | The cluster name. |
| `type` | `string\|WebsocketServerType` | The server type: `reverb`. |
| `region` | `string\|CloudRegion` | The region. |
| `status` | `string\|WebsocketStatus` | Current status: `creating`, `updating`, `available`, `deleting`, `deleted`. |
| `maxConnections` | `string\|WebsocketMaxConnections` | Maximum concurrent connections: `100`, `200`, `500`, `2000`, `5000`, or `10000`. |
| `connectionDistributionStrategy` | `string\|WebsocketConnectionDistributionStrategy` | How connections are distributed: `evenly` or `custom`. |
| `hostname` | `string` | The WebSocket server hostname for client connections. |
| `createdAt` | `?CarbonImmutable` | When the cluster was created. |
| `applications` | `WebsocketApplicationData[]` | All applications within this cluster. |

</details>

## Websocket Applications

Websocket applications are logical partitions within a websocket cluster. Each cluster creates a default `main` application on provisioning. Additional applications distribute the cluster's connection capacity across different projects.

### Listing Websocket Applications

```php
$applications = LaravelCloud::websocketApplications($clusterId);
```

### Retrieving a Websocket Application

```php
$application = LaravelCloud::websocketApplication($applicationId);
```

### Creating a Websocket Application

```php
$application = LaravelCloud::createWebsocketApplication($clusterId,
    name: 'my-ws-app',
);
```

### Updating a Websocket Application

```php
$application = LaravelCloud::updateWebsocketApplication($applicationId,
    name: 'new-ws-app-name',
);
```

### Deleting a Websocket Application

```php
LaravelCloud::deleteWebsocketApplication($applicationId);
```

### Websocket Application Metrics

Metrics accept the same `MetricPeriod` options as [environment metrics](#environment-metrics):

```php
$metrics = LaravelCloud::websocketApplicationMetrics($applicationId);
$metrics = LaravelCloud::websocketApplicationMetrics($applicationId, period: MetricPeriod::SevenDays);
```

<details>
<summary>Available parameters for <code>createWebsocketApplication</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | Yes | The application name. |
| `pingInterval` | `int` | No | Seconds between keep-alive pings. |
| `activityTimeout` | `int` | No | Seconds of inactivity before a connection is stale. |
| `allowedOrigins` | `?array` | No | Allowed WebSocket origins. Pass `null` for all. |

</details>

<details>
<summary>Available parameters for <code>updateWebsocketApplication</code></summary>

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | No | Update the application name. |
| `pingInterval` | `int` | No | Change seconds between keep-alive pings. |
| `activityTimeout` | `int` | No | Change seconds of inactivity before a connection is stale. |
| `allowedOrigins` | `?array` | No | Update allowed origins. Pass `null` for all. |

</details>

<details>
<summary>Response: <code>WebsocketApplicationData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The websocket application ID. |
| `name` | `string` | The application name. |
| `appId` | `string` | The Reverb application ID for client-side configuration. |
| `allowedOrigins` | `array` | Allowed WebSocket origins. |
| `pingInterval` | `int` | Seconds between keep-alive pings. |
| `activityTimeout` | `int` | Seconds of inactivity before a connection is stale. |
| `maxMessageSize` | `int` | Maximum message size in bytes. |
| `maxConnections` | `int` | Maximum concurrent connections for this application. |
| `key` | `string` | The Reverb app key for client-side configuration. |
| `secret` | `string` | The Reverb app secret for server-side verification. |
| `createdAt` | `?CarbonImmutable` | When the application was created. |
| `websocketCluster` | `?WebsocketClusterData` | The parent websocket cluster. |

</details>

## Dedicated Clusters

Dedicated clusters provide isolated compute infrastructure for your resources. You may filter by region, resource type, or status:

```php
use Redberry\LaravelCloudSdk\Enums\ClusterType;
use Redberry\LaravelCloudSdk\Enums\ClusterStatus;

// List all dedicated clusters
$clusters = LaravelCloud::dedicatedClusters();

// Filter by type and region
$clusters = LaravelCloud::dedicatedClusters(
    region: CloudRegion::UsEast1,
    type: ClusterType::Applications,
    status: ClusterStatus::Active,
);
```

Available cluster types: `Applications`, `MysqlDatabases`, `RdsDatabases`, `RedisCaches`, `ReverbWebsocketServers`.

<details>
<summary>Response: <code>DedicatedClusterData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The cluster ID. |
| `name` | `string` | The cluster name. |
| `region` | `string\|CloudRegion` | The region. |
| `type` | `string\|ClusterType` | The resource type: `applications`, `mysql-databases`, `rds-databases`, `redis-caches`, or `reverb-websocket-servers`. |
| `tenancyType` | `string\|TenancyType` | `shared` or `dedicated`. |
| `status` | `string\|ClusterStatus` | Current status: `draft`, `active`, `maintenance`, or `inactive`. |
| `createdAt` | `?CarbonImmutable` | When the cluster was created. |

</details>

## Organization

Retrieve the organization associated with the current API token:

```php
$org = LaravelCloud::organization();

$org->id;   // "org-abc123"
$org->name; // "My Team"
$org->slug; // "my-team"
```

<details>
<summary>Response: <code>OrganizationData</code></summary>

| Property | Type | Description |
|---|---|---|
| `id` | `string` | The organization ID. |
| `name` | `string` | The organization name. |
| `slug` | `string` | The URL-friendly slug. |

</details>

## Regions

Regions determine where your infrastructure is physically located. For optimal performance, your application and all its resources should be in the same region.

```php
$regions = LaravelCloud::regions();
```

<details>
<summary>Response: <code>RegionData</code></summary>

| Property | Type | Description |
|---|---|---|
| `region` | `string\|CloudRegion` | The region identifier. |
| `label` | `string` | Human-readable region name. |
| `flag` | `string` | The flag emoji for the region's country. |

</details>

## IP Addresses

Retrieve the IP addresses used by Laravel Cloud, optionally filtered by region. This is useful for configuring firewall allowlists on external services:

```php
// All regions
$addresses = LaravelCloud::ipAddresses();

// Specific region
$addresses = LaravelCloud::ipAddresses(region: CloudRegion::UsEast1);
```

<details>
<summary>Response: <code>IpAddressData</code></summary>

| Property | Type | Description |
|---|---|---|
| `region` | `string\|CloudRegion` | The region these addresses belong to. |
| `ipv4` | `array` | List of IPv4 addresses. |
| `ipv6` | `array` | List of IPv6 addresses. |

</details>

## Error Handling

The SDK throws specific exceptions for common API errors:

```php
use Redberry\LaravelCloudSdk\Exceptions\ValidationException;
use Redberry\LaravelCloudSdk\Exceptions\RateLimitException;
use Redberry\LaravelCloudSdk\Exceptions\HtmlResponseException;

try {
    LaravelCloud::createApplication(...);
} catch (ValidationException $e) {
    // Invalid request data (422)
    $errors = $e->errors();
} catch (RateLimitException $e) {
    // Too many requests (429)
    $retryAfter = $e->retryAfter();
}
```

All other HTTP errors (401, 404, 500, etc.) throw Saloon's default `RequestException`, which you may catch for general error handling:

```php
use Saloon\Exceptions\Request\RequestException;

try {
    $app = LaravelCloud::application('app-nonexistent');
} catch (RequestException $e) {
    $e->getResponse()->status(); // 404
}
```

The SDK also detects unexpected HTML responses from the API (which can occur during maintenance or when an endpoint URL is incorrect) and throws an `HtmlResponseException` with a descriptive message instead of failing silently or returning unparseable data.

## Testing

The SDK is built on [Saloon](https://docs.saloon.dev), which provides a built-in mechanism for faking HTTP requests during tests. You should use Saloon's `MockResponse` to prevent real API calls and assert that the correct requests are being sent.

### Faking Responses

The Laravel Cloud API follows the [JSON:API](https://jsonapi.org) specification. Each resource is returned with an `id`, `type`, and `attributes` object inside `data`. Related resources are sideloaded in a top-level `included` array and linked via the `relationships` object.

The SDK handles all of this automatically - you just need to structure your mock responses to match. Here's a basic example without relationships:

```php
use Redberry\LaravelCloudSdk\Requests\Applications\ListApplicationsRequest;
use Saloon\Laravel\Facades\Saloon;
use Saloon\Http\Faking\MockResponse;

Saloon::fake([
    ListApplicationsRequest::class => MockResponse::make([
        'data' => [
            [
                'id' => 'app-123',
                'type' => 'applications',
                'attributes' => [
                    'name' => 'my-app',
                    'slug' => 'my-app',
                    'region' => 'us-east-1',
                    'slack_channel' => null,
                    'avatar_url' => null,
                    'repository' => null,
                    'created_at' => '2025-01-01T00:00:00Z',
                ],
            ],
        ],
        'included' => [],
    ], 200),
]);

$applications = LaravelCloud::applications();

expect($applications)->toHaveCount(1);
expect($applications->first()->name)->toBe('my-app');
expect($applications->first()->organization)->toBeNull(); // no included data

Saloon::assertSent(ListApplicationsRequest::class);
```

### Faking Responses with Relationships

To test relationship hydration, add a `relationships` object to each resource in `data` and provide the related resources in the `included` array. The SDK will automatically match them by `type` and `id`:

```php
use Redberry\LaravelCloudSdk\Requests\Applications\GetApplicationRequest;
use Saloon\Laravel\Facades\Saloon;
use Saloon\Http\Faking\MockResponse;

Saloon::fake([
    GetApplicationRequest::class => MockResponse::make([
        'data' => [
            'id' => 'app-123',
            'type' => 'applications',
            'attributes' => [
                'name' => 'my-app',
                'slug' => 'my-app',
                'region' => 'us-east-1',
                'slack_channel' => null,
                'avatar_url' => null,
                'repository' => null,
                'created_at' => '2025-01-01T00:00:00Z',
            ],
            'relationships' => [
                'organization' => [
                    'data' => ['type' => 'organizations', 'id' => 'org-456'],
                ],
                'environments' => [
                    'data' => [
                        ['type' => 'environments', 'id' => 'env-789'],
                    ],
                ],
            ],
        ],
        'included' => [
            [
                'id' => 'org-456',
                'type' => 'organizations',
                'attributes' => [
                    'name' => 'My Team',
                    'slug' => 'my-team',
                ],
            ],
            [
                'id' => 'env-789',
                'type' => 'environments',
                'attributes' => [
                    'name' => 'production',
                    'slug' => 'production',
                    'status' => 'running',
                    // ... other attributes
                ],
            ],
        ],
    ], 200),
]);

$app = LaravelCloud::application('app-123');

expect($app->name)->toBe('my-app');
expect($app->organization->name)->toBe('My Team');
expect($app->environments)->toHaveCount(1);
expect($app->environments[0]->name)->toBe('production');
```

### Preventing Stray Requests

To ensure no real API calls leak through during your test suite, you may call `preventStrayRequests`. Any unfaked request will throw an exception:

```php
// In your Pest.php
Saloon::preventStrayRequests();
```

### Faking Error Responses

```php
use Saloon\Exceptions\Request\RequestException;
use Redberry\LaravelCloudSdk\Requests\Applications\GetApplicationRequest;

Saloon::fake([
    GetApplicationRequest::class => MockResponse::make([], 404),
]);

LaravelCloud::application('app-nonexistent'); // throws RequestException
```

For more details on request faking, assertions, and recording, refer to the [Saloon testing documentation](https://docs.saloon.dev).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Gaga Darsalia](https://github.com/rayredberry)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

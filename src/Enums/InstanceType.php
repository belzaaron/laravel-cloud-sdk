<?php

namespace Redberry\LaravelCloudSdk\Enums;

enum InstanceType: string
{
    case App = 'app';
    case Service = 'service';
    case Queue = 'queue';
    case ServerlessQueue = 'serverless_queue';
    case ManagedQueue = 'managed_queue';
}

<?php

namespace Redberry\LaravelCloudSdk\Enums;

enum ManagedQueueStatus: string
{
    case Creating = 'creating';
    case Available = 'available';
    case Updating = 'updating';
    case Deleting = 'deleting';
    case Deleted = 'deleted';
    case Unknown = 'unknown';
}

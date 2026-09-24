<?php

namespace App\Services;

use App\Models\StoredFile;
use DateTimeInterface;

interface DeletionNotificationPublisher
{
    public function publish(StoredFile $file, string $source, DateTimeInterface $deletedAt): void;
}

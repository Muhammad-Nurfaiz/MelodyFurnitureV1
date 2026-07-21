<?php

namespace App\Console\Commands;
use App\Services\Media\TemporaryMediaService;
use Illuminate\Console\Command;

class CleanupTemporaryMedia extends Command
{
    protected $signature = 'temporary-media:cleanup';

    protected $description = 'Delete expired temporary uploaded media';

    public function __construct(
        protected TemporaryMediaService $temporaryMediaService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $deleted = $this->temporaryMediaService->cleanupExpired();

        $this->info("Deleted {$deleted} expired temporary media.");

        return self::SUCCESS;
    }
}

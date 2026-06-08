<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

/**
 * Runs the Tojiktelecom org-structure import on the queue worker instead of the
 * caller's process. The heavy work (API fetch + full tree reconciliation in one
 * transaction) lives in the `org:import` command; this job simply invokes it in
 * `--sync` mode from the background so the dispatcher returns immediately.
 */
class SyncOrgStructure implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * The import can take a while (API timeout + retries + a large transaction),
     * so give it generous headroom — this overrides the worker's --timeout.
     */
    public int $timeout = 1800;

    /**
     * Never auto-retry a partial run: a re-dispatch is idempotent, a mid-flight
     * retry is not.
     */
    public int $tries = 1;

    public function __construct(
        public bool $api = false,
        public ?string $file = null,
        public bool $fresh = false,
    ) {}

    /**
     * Hold the unique lock for the whole run so an overlapping manual and nightly
     * dispatch cannot import concurrently.
     */
    public function uniqueFor(): int
    {
        return $this->timeout;
    }

    public function handle(): void
    {
        $options = ['--sync' => true];

        if ($this->api) {
            $options['--api'] = true;
        }
        if ($this->file !== null) {
            $options['--file'] = $this->file;
        }
        if ($this->fresh) {
            $options['--fresh'] = true;
        }

        Artisan::call('org:import', $options);
    }
}

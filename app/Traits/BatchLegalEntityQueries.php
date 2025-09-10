<?php

declare(strict_types=1);

namespace App\Traits;

use stdClass;
use Exception;
use App\Models\User;
use App\Models\LegalEntity;
use Illuminate\Bus\BatchRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

/**
 * Trait for querying batches by legal_entity_id
 *
 */
trait BatchLegalEntityQueries
{
    /**
     * Find all batches for a specific legal entity
     *
     * @param int $legalEntityId
     * @param int $limit
     * @param string $orderBy
     * @return Collection<stdClass>
     */
    protected function findBatchesByLegalEntity(int $legalEntityId, int $limit = 50, string $orderBy = 'desc'): Collection
    {
        return DB::table('job_batches')
            ->where('legal_entity_id', $legalEntityId)
            ->orderBy('created_at', $orderBy)
            ->limit($limit)
            ->get();
    }

    /**
     * Find failed batches by legal entity ID
     *
     * @param int $legalEntityId
     * @param string $orderBy
     * @param int $limit
     *
     * @return Collection<stdClass>
     */
    protected function findFailedBatchesByLegalEntity(int $legalEntityId, string $orderBy = 'desc'): Collection
    {
        return DB::table('job_batches')
            ->where('legal_entity_id', $legalEntityId)
            ->where('failed_jobs', '>', 0)
            ->orderBy('cancelled_at', $orderBy)
            ->get();
    }

    /**
     * Find running (not finished, not cancelled) batches by legal entity ID
     *
     * @param int $legalEntityId
     * @param int $limit
     *
     * @return Collection<stdClass>
     */
    protected function findRunningBatchesByLegalEntity(int $legalEntityId): Collection
    {
        return DB::table('job_batches')
            ->where('legal_entity_id', $legalEntityId)
            ->whereNull('finished_at')
            ->whereNull('cancelled_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }


    /**
     * Check if legal entity has any running batches
     *
     * @param int $legalEntityId
     * @return bool
     */
    protected function hasRunningBatchesForLegalEntity(int $legalEntityId): bool
    {
        return DB::table('job_batches')
            ->where('legal_entity_id', $legalEntityId)
            ->whereNull('finished_at')
            ->whereNull('cancelled_at')
            ->exists();
    }

    /**
     * Restart a failed batch by creating a new batch with pending jobs
     *
     * @param User $user The user context for the new batch execution
     * @param stdClass $batch The failed batch record from job_batches table
     * @param string $token Encrypted authentication token for API requests
     * @param LegalEntity $legalEntity The legal entity context for the batch
     * @param array $pendingJobs Array of job instances to be re-dispatched
     *
     * @return void
     *
     * @throws Exception If batch creation or deletion fails
     */
    protected function restartFailedBatch(User $user, stdClass $batch, string $token, LegalEntity $legalEntity, array $pendingJobs): void
    {
        $newBatch = Bus::batch($pendingJobs)
            ->name($batch->name)
            ->withOption('legal_entity_id', $legalEntity->id)
            ->withOption('token', $token) // Here token is encrypted
            ->withOption('user', $user)
            ->onQueue('sync')
            ->dispatch();

        echo 'Dispatched new batch: ' . $newBatch->name . ' id: ' . $newBatch->id . PHP_EOL;

        // Delete the old failed batch to prevent clutter
        app(BatchRepository::class)->delete($batch->id);

        echo 'Deleted old failed batch: ' . $batch->name . ' id: ' . $batch->id . PHP_EOL;
    }

    /**
     * Extract pending jobs from failed batch by recreating job instances from failed_jobs table
     *
     * @param stdClass $batch The batch record from job_batches table
     *
     * @return array Array of unserialized job instances ready for re-dispatch
     */
    protected function extractPendingJobsFromBatches(stdClass $batch): array
    {
        $pendingJobs = [];

        $failedJobsIds = json_decode($batch->failed_job_ids, true) ?? [];

        if (empty($failedJobsIds)) {
            return [];
        }

        $jobs = DB::table('failed_jobs')
            ->whereIn('uuid', $failedJobsIds)
            ->get();

        foreach ($jobs as $job) {
            if (!isset($job->payload)) {
                continue;
            }

            $payload = json_decode($job->payload, true);

            if (isset($payload['data']['command'])) {
                $pendingJobs[] = unserialize($payload['data']['command']);
            }

            // Remove the job from failed_jobs table to prevent reprocessing
            DB::table('failed_jobs')->where('id', $job->id)->delete();
        }

        return $pendingJobs;
    }
}

<?php

namespace App\Services;

use App\Models\OdooProductSyncFailure;
use App\Models\OdooProductSyncRun;
use App\Models\Product;
use App\Services\Odoo\OdooProductService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductSyncService
{
    public function __construct(protected OdooProductService $odooProductService) {}

    public function queueSync(string $syncType = 'full', ?int $startedBy = null): OdooProductSyncRun
    {
        $run = OdooProductSyncRun::create([
            'sync_type' => $syncType,
            'status' => 'pending',
            'started_by' => $startedBy,
            'started_at' => now(),
            'metadata' => ['queued_at' => now()->toIso8601String()],
        ]);

        \App\Jobs\SyncOdooProducts::dispatch($run->id);

        return $run;
    }

    public function runSync(int $runId): OdooProductSyncRun
    {
        /** @var OdooProductSyncRun $run */
        $run = OdooProductSyncRun::query()->findOrFail($runId);
        $run->update([
            'status' => 'running',
            'started_at' => $run->started_at ?? now(),
            'error_message' => null,
        ]);

        $offset = 0;
        $limit = 100;
        $processed = 0;
        $created = 0;
        $updated = 0;
        $failed = 0;
        $total = 0;
        $domain = $this->domainForSync($run);

        try {
            do {
                $batch = $this->odooProductService->fetchProductsBatch($offset, $limit, $domain);
                $count = count($batch);
                $total += $count;

                foreach ($batch as $payload) {
                    $processed++;
                    $externalId = (string) ($payload['id'] ?? '');
                    try {
                        DB::transaction(function () use ($payload, $externalId, &$created, &$updated): void {
                            $existing = Product::query()->where('odoo_id', $externalId)->exists();
                            $this->odooProductService->upsertProductFromOdoo($payload);
                            if ($existing) {
                                $updated++;
                            } else {
                                $created++;
                            }
                        });
                    } catch (Throwable $e) {
                        $failed++;
                        OdooProductSyncFailure::create([
                            'sync_run_id' => $run->id,
                            'external_id' => $externalId !== '' ? $externalId : null,
                            'identifier' => (string) ($payload['default_code'] ?? $payload['barcode'] ?? $payload['name'] ?? ''),
                            'error_message' => $e->getMessage(),
                            'status' => 'pending',
                            'payload' => $payload,
                            'last_attempt_at' => now(),
                        ]);
                    }
                }

                $offset += $limit;

                $run->update([
                    'total_records' => $total,
                    'processed_records' => $processed,
                    'created_records' => $created,
                    'updated_records' => $updated,
                    'failed_records' => $failed,
                ]);
            } while ($count === $limit);

            $run->update([
                'status' => $failed > 0 ? 'completed_with_errors' : 'completed',
                'completed_at' => now(),
                'metadata' => array_merge($run->metadata ?? [], [
                    'sync_domain' => $domain,
                    'batch_size' => $limit,
                ]),
            ]);
        } catch (Throwable $e) {
            Log::error('Odoo product sync failed', ['run_id' => $run->id, 'error' => $e->getMessage()]);
            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }

        return $run->fresh();
    }

    public function retryFailure(OdooProductSyncFailure $failure): void
    {
        try {
            $payload = $failure->payload ?? [];
            if (! is_array($payload) || ! isset($payload['id'])) {
                throw new \RuntimeException('Invalid failure payload for retry.');
            }

            $product = $this->odooProductService->fetchSingleProduct((int) $payload['id']);
            if (! $product) {
                throw new \RuntimeException('Product no longer available in Odoo.');
            }

            $this->odooProductService->upsertProductFromOdoo($product);
            $failure->update([
                'status' => 'resolved',
                'retry_count' => $failure->retry_count + 1,
                'last_attempt_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            $failure->update([
                'status' => 'pending',
                'retry_count' => $failure->retry_count + 1,
                'last_attempt_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<int, array<int, string>>
     */
    protected function domainForSync(OdooProductSyncRun $run): array
    {
        if ($run->sync_type !== 'incremental') {
            return [];
        }

        $lastCompleted = OdooProductSyncRun::query()
            ->whereIn('status', ['completed', 'completed_with_errors'])
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->first();

        if (! $lastCompleted?->completed_at) {
            return [];
        }

        return [['write_date', '>=', $lastCompleted->completed_at->subMinutes(1)->format('Y-m-d H:i:s')]];
    }
}


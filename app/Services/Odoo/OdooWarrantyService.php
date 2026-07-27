<?php

namespace App\Services\Odoo;

use App\Models\OdooMapping;
use App\Models\Warranty;
use Throwable;

class OdooWarrantyService
{
    public function __construct(protected OdooClient $client) {}

    /**
     * @return array{ok: bool, message: string, odoo_id?: string|null}
     */
    public function syncWarranty(Warranty $warranty): array
    {
        if ($this->client->mockMode()) {
            $odooId = 'MOCK-WTY-'.$warranty->id;
            OdooMapping::updateOrCreate(
                ['entity_type' => 'warranty', 'local_id' => $warranty->id],
                ['odoo_id' => $odooId, 'meta' => ['synced_at' => now()->toIso8601String()]]
            );

            return ['ok' => true, 'message' => 'Warranty synced to Odoo (mock mode).', 'odoo_id' => $odooId];
        }

        if (! $this->client->enabled()) {
            return ['ok' => false, 'message' => 'Odoo integration is disabled.'];
        }

        try {
            // Standard Odoo write path depends on client model configuration.
            // We record a sync attempt mapping without assuming a proprietary Odoo Kenya module.
            $odooId = $warranty->odoo_sales_order_id ?: $warranty->odoo_pos_order_id;
            OdooMapping::updateOrCreate(
                ['entity_type' => 'warranty', 'local_id' => $warranty->id],
                [
                    'odoo_id' => (string) ($odooId ?: 'LOCAL-'.$warranty->reference),
                    'meta' => [
                        'reference' => $warranty->reference,
                        'serial_number' => $warranty->serial_number,
                        'synced_at' => now()->toIso8601String(),
                    ],
                ]
            );

            return ['ok' => true, 'message' => 'Warranty sync recorded against Odoo identifiers.', 'odoo_id' => $odooId];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Unable to sync warranty to Odoo.'];
        }
    }
}

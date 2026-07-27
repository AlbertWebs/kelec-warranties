<?php

namespace App\Services\Odoo;

class OdooSalesService
{
    public function __construct(protected OdooClient $client) {}

    public function enabled(): bool
    {
        return $this->client->enabled() || $this->client->mockMode();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentBrandShopSales(int $limit = 20): array
    {
        if ($this->client->mockMode()) {
            return [
                [
                    'serial_number' => 'POSMOCK'.now()->format('His'),
                    'branch_name' => 'Sarin',
                    'odoo_pos_order_id' => 'POS-'.now()->timestamp,
                    'full_name' => 'POS Walk-in Customer',
                    'mobile_number' => '0712345699',
                    'email' => null,
                    'product_name' => 'K-Elec Cooker 1000',
                    'product_model' => 'KE-1000',
                    'purchase_date' => now()->toDateString(),
                    'invoice_number' => 'POS-INV-'.now()->format('Ymd'),
                    'marketing_consent' => false,
                ],
            ];
        }

        // Live POS order retrieval requires client-confirmed model/field mappings.
        return [];
    }
}

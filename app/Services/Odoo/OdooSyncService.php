<?php

namespace App\Services\Odoo;

class OdooSyncService
{
    public function __construct(protected OdooClient $client) {}

    /**
     * @return array{ok: bool, message: string}
     */
    public function testConnection(): array
    {
        return $this->client->testConnection();
    }
}

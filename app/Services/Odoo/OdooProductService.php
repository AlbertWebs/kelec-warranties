<?php

namespace App\Services\Odoo;

class OdooProductService
{
    public function __construct(protected OdooClient $client) {}

    /**
     * @return array{found: bool, product?: array<string, mixed>, sale?: array<string, mixed>, customer?: array<string, mixed>, message: string}
     */
    public function lookupBySerial(string $serialNumber): array
    {
        return $this->client->validateSerial($serialNumber);
    }
}

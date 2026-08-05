<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Odoo\OdooClient;
use App\Services\SettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OdooConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_connection_fails_when_credentials_are_missing_even_in_mock_mode(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('odoo_mock_mode', true, 'odoo', 'boolean');
        $settings->set('odoo_enabled', false, 'odoo', 'boolean');
        $settings->set('odoo_base_url', '', 'odoo', 'string');
        $settings->set('odoo_database', '', 'odoo', 'string');
        $settings->set('odoo_username', '', 'odoo', 'string');
        $settings->set('odoo_api_key', '', 'odoo', 'string');

        $result = app(OdooClient::class)->testConnection();

        $this->assertFalse($result['ok']);
        $this->assertSame('error', $result['flash']);
        $this->assertStringContainsString('Missing:', $result['message']);
        $this->assertStringContainsString('username', $result['message']);
    }

    public function test_connection_fails_when_odoo_rejects_authentication(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('odoo_mock_mode', false, 'odoo', 'boolean');
        $settings->set('odoo_enabled', true, 'odoo', 'boolean');
        $settings->set('odoo_base_url', 'https://odoo.example.test', 'odoo', 'string');
        $settings->set('odoo_database', 'kelec', 'odoo', 'string');
        $settings->set('odoo_username', 'api', 'odoo', 'string');
        $settings->set('odoo_api_key', 'bad-key', 'odoo', 'string');

        Http::fake([
            'https://odoo.example.test/jsonrpc' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => false,
            ], 200),
        ]);

        $admin = User::where('email', 'admin@kelec.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.odoo.test'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_connection_succeeds_only_when_odoo_returns_a_uid(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('odoo_mock_mode', false, 'odoo', 'boolean');
        $settings->set('odoo_enabled', true, 'odoo', 'boolean');
        $settings->set('odoo_base_url', 'https://odoo.example.test', 'odoo', 'string');
        $settings->set('odoo_database', 'kelec', 'odoo', 'string');
        $settings->set('odoo_username', 'api', 'odoo', 'string');
        $settings->set('odoo_api_key', 'good-key', 'odoo', 'string');

        Http::fake([
            'https://odoo.example.test/jsonrpc' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => 42,
            ], 200),
        ]);

        $admin = User::where('email', 'admin@kelec.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.odoo.test'))
            ->assertRedirect()
            ->assertSessionHas('success');
    }
}

<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\NotificationChannel;
use App\Enums\RegistrationSource;
use App\Enums\WarrantyStatus;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\IntegrationFailure;
use App\Models\NotificationLog;
use App\Models\OdooSyncLog;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseSource;
use App\Models\User;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use App\Models\WarrantyNote;
use App\Models\WarrantyStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoDataService
{
    /**
     * @return array{customers: int, warranties: int, claims: int, logs: int}
     */
    public function seed(): array
    {
        $admin = User::query()->where('email', 'admin@kelec.test')->first()
            ?? User::query()->first();
        $support = User::query()->where('email', 'support@kelec.test')->first() ?? $admin;

        $sources = PurchaseSource::query()->orderBy('sort_order')->get();
        $dealers = Dealer::query()->get();
        $products = Product::query()->with('category')->where('is_active', true)->get();

        if ($products->isEmpty() || $sources->isEmpty()) {
            throw new \RuntimeException('Base catalog is missing. Run `php artisan db:seed` first.');
        }

        // Extra catalog rows for richer UI (idempotent)
        $cooling = ProductCategory::query()->firstOrCreate(
            ['code' => 'DEMO-COOL'],
            ['name' => 'Demo Cooling', 'slug' => 'demo-cooling', 'default_warranty_months' => 24, 'is_active' => true]
        );
        $laundry = ProductCategory::query()->firstOrCreate(
            ['code' => 'DEMO-LAUNDRY'],
            ['name' => 'Demo Laundry', 'slug' => 'demo-laundry', 'default_warranty_months' => 12, 'is_active' => true]
        );

        Product::query()->updateOrCreate(
            ['sku' => 'DEMO-FRIDGE-300'],
            [
                'product_category_id' => $cooling->id,
                'name' => 'K-Elec FrostFree 300',
                'model' => 'KF-300',
                'brand' => 'K-Elec',
                'default_warranty_months' => 24,
                'is_active' => true,
            ]
        );
        Product::query()->updateOrCreate(
            ['sku' => 'DEMO-WASHER-8'],
            [
                'product_category_id' => $laundry->id,
                'name' => 'K-Elec WashPro 8kg',
                'model' => 'KW-800',
                'brand' => 'K-Elec',
                'default_warranty_months' => 12,
                'is_active' => true,
            ]
        );

        Dealer::query()->updateOrCreate(
            ['dealer_code' => 'WESTLANDS'],
            [
                'name' => 'Westlands Brand Shop',
                'contact_person' => 'Branch Manager',
                'mobile_number' => '0700000003',
                'county' => 'Nairobi',
                'town' => 'Westlands',
                'physical_location' => 'Westlands',
                'is_active' => true,
                'is_authorised' => true,
            ]
        );

        $products = Product::query()->with('category')->where('is_active', true)->get();
        $dealers = Dealer::query()->get();

        $customerSpecs = [
            ['full_name' => 'Amina Otieno', 'mobile' => '0712001001', 'email' => 'amina.otieno@example.com', 'password' => 'password', 'town' => 'Westlands'],
            ['full_name' => 'Brian Kamau', 'mobile' => '0712001002', 'email' => 'brian.kamau@example.com', 'password' => null, 'town' => 'CBD'],
            ['full_name' => 'Catherine Wanjiku', 'mobile' => '0712001003', 'email' => 'catherine.w@example.com', 'password' => 'password', 'town' => 'Syokimau'],
            ['full_name' => 'David Mwangi', 'mobile' => '0712001004', 'email' => 'david.mwangi@example.com', 'password' => null, 'town' => 'Kilimani'],
            ['full_name' => 'Esther Njeri', 'mobile' => '0712001005', 'email' => 'esther.njeri@example.com', 'password' => 'password', 'town' => 'Thika'],
            ['full_name' => 'Test Customer', 'mobile' => '0711000000', 'email' => 'customer@kelec.test', 'password' => 'password', 'town' => 'Nairobi'],
        ];

        $customers = collect();
        foreach ($customerSpecs as $spec) {
            $normalized = '254'.substr($spec['mobile'], 1);
            $attributes = [
                'full_name' => $spec['full_name'],
                'mobile_number' => $spec['mobile'],
                'email' => $spec['email'],
                'county' => 'Nairobi',
                'town' => $spec['town'],
                'marketing_consent' => true,
                'marketing_consent_at' => now()->subDays(3),
            ];
            if ($spec['password']) {
                $attributes['password'] = $spec['password'];
            }

            $customers->push(Customer::query()->updateOrCreate(
                ['mobile_normalized' => $normalized],
                $attributes
            ));
        }

        $statusSets = [
            WarrantyStatus::Active,
            WarrantyStatus::Active,
            WarrantyStatus::Active,
            WarrantyStatus::PendingVerification,
            WarrantyStatus::UnderReview,
            WarrantyStatus::Rejected,
            WarrantyStatus::Expired,
            WarrantyStatus::Submitted,
        ];

        $warranties = collect();
        foreach ($statusSets as $index => $status) {
            $customer = $customers[$index % $customers->count()];
            $product = $products[$index % $products->count()];
            $source = $sources[$index % $sources->count()];
            $dealer = $dealers[$index % $dealers->count()];
            $start = now()->subMonths($index + 1)->startOfDay();
            $months = $product->default_warranty_months ?: 12;

            $warranty = Warranty::query()->updateOrCreate(
                ['reference' => 'KEL-WTY-DEMO-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'customer_id' => $customer->id,
                    'product_id' => $product->id,
                    'product_category_id' => $product->product_category_id,
                    'purchase_source_id' => $source->id,
                    'dealer_id' => $source->code === 'brand_shop' ? $dealer->id : null,
                    'product_name' => $product->name,
                    'product_model' => $product->model,
                    'serial_number' => 'DEMO-SN-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                    'branch_name' => $dealer->physical_location ?? $dealer->name,
                    'purchase_date' => $start->toDateString(),
                    'registration_date' => $start->copy()->addDays(2),
                    'warranty_start_date' => $start->toDateString(),
                    'warranty_expiry_date' => $status === WarrantyStatus::Expired
                        ? $start->copy()->addMonths(6)->toDateString()
                        : $start->copy()->addMonths($months)->toDateString(),
                    'warranty_duration_months' => $months,
                    'status' => $status,
                    'registration_source' => $index % 2 === 0 ? RegistrationSource::PublicPortal : RegistrationSource::OdooPos,
                    'marketing_consent' => true,
                    'privacy_accepted' => true,
                    'consent_timestamp' => $start->copy()->addDays(2),
                    'consent_source' => 'public_portal',
                    'odoo_validated' => $status === WarrantyStatus::Active,
                    'requires_manual_verification' => in_array($status, [WarrantyStatus::PendingVerification, WarrantyStatus::UnderReview], true),
                    'rejection_reason' => $status === WarrantyStatus::Rejected ? 'Purchase proof could not be verified.' : null,
                    'approved_by' => $status === WarrantyStatus::Active ? $admin?->id : null,
                    'approved_at' => $status === WarrantyStatus::Active ? $start->copy()->addDays(3) : null,
                    'customer_notes' => 'Demo warranty for UI review.',
                ]
            );

            WarrantyStatusHistory::query()->updateOrCreate(
                [
                    'warranty_id' => $warranty->id,
                    'to_status' => $status,
                ],
                [
                    'from_status' => WarrantyStatus::Submitted,
                    'changed_by' => $admin?->id,
                    'reason' => 'Demo status history',
                ]
            );

            WarrantyNote::query()->updateOrCreate(
                [
                    'warranty_id' => $warranty->id,
                    'body' => 'Demo internal note for '.$warranty->reference,
                ],
                [
                    'user_id' => $support?->id ?? $admin?->id,
                    'is_internal' => true,
                ]
            );

            NotificationLog::query()->updateOrCreate(
                [
                    'warranty_id' => $warranty->id,
                    'notification_type' => 'warranty_status_update',
                    'channel' => NotificationChannel::Sms,
                ],
                [
                    'customer_id' => $customer->id,
                    'recipient' => $customer->mobile_number,
                    'message' => 'Demo SMS for '.$warranty->reference,
                    'status' => $index % 3 === 0 ? 'failed' : 'sent',
                    'sent_at' => $index % 3 === 0 ? null : now()->subHours($index + 1),
                    'failed_at' => $index % 3 === 0 ? now()->subHours($index + 1) : null,
                    'retry_count' => $index % 3 === 0 ? 1 : 0,
                ]
            );

            $warranties->push($warranty);
        }

        $claimCount = 0;
        $activeWarranties = $warranties->where('status', WarrantyStatus::Active)->values()->take(3);
        foreach ($activeWarranties as $i => $warranty) {
            WarrantyClaim::query()->updateOrCreate(
                ['reference' => 'CLM-DEMO-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'customer_id' => $warranty->customer_id,
                    'warranty_id' => $warranty->id,
                    'subject' => ['Not cooling properly', 'Unusual noise', 'Display flickering'][$i] ?? 'Service request',
                    'description' => 'Demo claim description for UI review of claim workflows.',
                    'status' => [ClaimStatus::Submitted, ClaimStatus::InReview, ClaimStatus::Resolved][$i] ?? ClaimStatus::Submitted,
                    'customer_notes' => 'Customer reported the issue after 2 weeks of use.',
                    'admin_notes' => $i === 2 ? 'Resolved under warranty — replacement part fitted.' : null,
                ]
            );
            $claimCount++;
        }

        foreach (['authenticate', 'validate_serial', 'sync_warranty'] as $i => $action) {
            OdooSyncLog::query()->updateOrCreate(
                ['request_reference' => 'DEMO-'.$action],
                [
                    'endpoint' => 'https://odoo.example.test/jsonrpc',
                    'action' => $action,
                    'response_status' => $i === 2 ? 500 : 200,
                    'error_message' => $i === 2 ? 'Demo simulated timeout' : null,
                    'status' => $i === 2 ? 'failed' : 'success',
                    'meta' => ['demo' => true],
                ]
            );
        }

        $failedWarranty = $warranties->firstWhere('status', WarrantyStatus::PendingVerification);
        if ($failedWarranty) {
            IntegrationFailure::query()->updateOrCreate(
                [
                    'integration' => 'odoo',
                    'action' => 'validate_serial',
                    'warranty_id' => $failedWarranty->id,
                ],
                [
                    'error_message' => 'Demo integration failure for UI.',
                    'retry_count' => 2,
                    'status' => 'pending',
                    'next_retry_at' => now()->addHour(),
                    'payload' => ['serial' => $failedWarranty->serial_number],
                ]
            );
        }

        foreach (['warranty.approved', 'customer.updated', 'settings.updated'] as $i => $action) {
            AuditLog::query()->updateOrCreate(
                [
                    'action' => $action,
                    'user_agent' => 'DemoDataSeeder',
                ],
                [
                    'user_id' => $admin?->id,
                    'entity_type' => $i === 0 ? Warranty::class : ($i === 1 ? Customer::class : 'SystemSetting'),
                    'entity_id' => $i === 0 ? $warranties->first()?->id : ($i === 1 ? $customers->first()?->id : null),
                    'previous_values' => ['demo' => 'before'],
                    'new_values' => ['demo' => 'after'],
                    'ip_address' => '127.0.0.1',
                ]
            );
        }

        return [
            'customers' => $customers->count(),
            'warranties' => $warranties->count(),
            'claims' => $claimCount,
            'logs' => OdooSyncLog::query()->where('request_reference', 'like', 'DEMO-%')->count()
                + NotificationLog::query()->where('message', 'like', 'Demo SMS%')->count()
                + AuditLog::query()->where('user_agent', 'DemoDataSeeder')->count(),
        ];
    }

    /**
     * @return array{tables: list<string>}
     */
    public function wipe(): array
    {
        $tables = [
            'warranty_claims',
            'warranty_notes',
            'warranty_documents',
            'warranty_status_histories',
            'warranty_consents',
            'warranty_information_requests',
            'notification_logs',
            'public_access_tokens',
            'integration_failures',
            'odoo_sync_logs',
            'odoo_mappings',
            'audit_logs',
            'warranties',
            'customers',
        ];

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                    if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                        // Keep SQLite simple; MySQL can reset AI
                        try {
                            DB::statement('ALTER TABLE '.$table.' AUTO_INCREMENT = 1');
                        } catch (\Throwable) {
                            // ignore
                        }
                    }
                }
            }

            // Remove demo-only catalog extras (keep core seeded catalog)
            Product::query()->whereIn('sku', ['DEMO-FRIDGE-300', 'DEMO-WASHER-8'])->forceDelete();
            ProductCategory::query()->whereIn('code', ['DEMO-COOL', 'DEMO-LAUNDRY'])->forceDelete();
            Dealer::query()->where('dealer_code', 'WESTLANDS')->forceDelete();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return ['tables' => $tables];
    }
}

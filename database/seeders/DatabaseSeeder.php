<?php

namespace Database\Seeders;

use App\Enums\NotificationChannel;
use App\Enums\PurchaseSourceType;
use App\Models\Dealer;
use App\Models\NotificationTemplate;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseSource;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WarrantyRule;
use App\Support\LegalContentDefaults;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'warranties.view',
            'warranties.update',
            'warranties.approve',
            'warranties.reject',
            'warranties.delete',
            'warranties.export',
            'warranties.resend_notification',
            'customers.view',
            'customers.update',
            'products.view',
            'products.manage',
            'dealers.view',
            'dealers.manage',
            'purchase_sources.manage',
            'odoo.view',
            'odoo.manage',
            'notifications.view',
            'reports.view',
            'users.manage',
            'roles.manage',
            'audit_logs.view',
            'settings.manage',
            'claims.view',
            'claims.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $superAdmin = Role::findOrCreate('super_admin');
        $warrantyAdmin = Role::findOrCreate('warranty_admin');
        $support = Role::findOrCreate('customer_support');

        $superAdmin->syncPermissions(Permission::all());

        $warrantyAdmin->syncPermissions([
            'warranties.view',
            'warranties.update',
            'warranties.approve',
            'warranties.reject',
            'warranties.export',
            'warranties.resend_notification',
            'customers.view',
            'customers.update',
            'products.view',
            'dealers.view',
            'odoo.view',
            'notifications.view',
            'reports.view',
            'claims.view',
            'claims.manage',
        ]);

        $support->syncPermissions([
            'warranties.view',
            'warranties.resend_notification',
            'customers.view',
            'products.view',
            'dealers.view',
            'notifications.view',
            'claims.view',
            'claims.manage',
        ]);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@kelec.test'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['super_admin']);

        User::query()->updateOrCreate(
            ['email' => 'warranty@kelec.test'],
            [
                'name' => 'Warranty Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        )->syncRoles(['warranty_admin']);

        User::query()->updateOrCreate(
            ['email' => 'support@kelec.test'],
            [
                'name' => 'Customer Support',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        )->syncRoles(['customer_support']);

        $sources = [
            ['name' => 'Brand Shop', 'code' => 'brand_shop', 'type' => PurchaseSourceType::BrandShop, 'requires_branch' => true, 'sort_order' => 1],
            ['name' => 'Dealer', 'code' => 'dealer', 'type' => PurchaseSourceType::Dealer, 'requires_dealer' => true, 'sort_order' => 2],
            ['name' => 'Jumia', 'code' => 'jumia', 'type' => PurchaseSourceType::Jumia, 'sort_order' => 3],
            ['name' => 'Kilimall', 'code' => 'kilimall', 'type' => PurchaseSourceType::Kilimall, 'sort_order' => 4],
            ['name' => 'Other authorised seller', 'code' => 'other', 'type' => PurchaseSourceType::Other, 'sort_order' => 5],
        ];

        foreach ($sources as $source) {
            PurchaseSource::query()->updateOrCreate(
                ['code' => $source['code']],
                [
                    'name' => $source['name'],
                    'type' => $source['type'],
                    'requires_dealer' => $source['requires_dealer'] ?? false,
                    'requires_branch' => $source['requires_branch'] ?? false,
                    'sort_order' => $source['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        Dealer::query()->updateOrCreate(
            ['dealer_code' => 'SARIN'],
            [
                'name' => 'K-Elec Brand Shop Sarin',
                'contact_person' => 'Branch Manager',
                'mobile_number' => '0700000001',
                'county' => 'Nairobi',
                'town' => 'Nairobi',
                'physical_location' => 'Sarin',
                'is_active' => true,
                'is_authorised' => true,
            ]
        );

        Dealer::query()->updateOrCreate(
            ['dealer_code' => 'CBD'],
            [
                'name' => 'K-Elec Brand Shop CBD',
                'contact_person' => 'Branch Manager',
                'mobile_number' => '0700000002',
                'county' => 'Nairobi',
                'town' => 'Nairobi',
                'physical_location' => 'CBD',
                'is_active' => true,
                'is_authorised' => true,
            ]
        );

        $cooking = ProductCategory::query()->updateOrCreate(
            ['slug' => 'cooking'],
            [
                'name' => 'Cooking',
                'code' => 'COOK',
                'default_warranty_months' => 12,
                'is_active' => true,
            ]
        );

        $cooling = ProductCategory::query()->updateOrCreate(
            ['slug' => 'cooling'],
            [
                'name' => 'Cooling',
                'code' => 'COOL',
                'default_warranty_months' => 24,
                'is_active' => true,
            ]
        );

        Product::query()->updateOrCreate(
            ['sku' => 'KE-COOK-1000'],
            [
                'product_category_id' => $cooking->id,
                'name' => 'K-Elec Cooker 1000',
                'product_code' => 'COOK1000',
                'model' => 'KE-1000',
                'brand' => 'K-Elec',
                'default_warranty_months' => 12,
                'registration_grace_days' => 30,
                'odoo_product_id' => 'MOCK-P-100',
                'is_active' => true,
                'serial_tracking_enabled' => true,
                'manual_verification_allowed' => true,
                'receipt_required' => false,
                'is_odoo_managed' => false,
            ]
        );

        Product::query()->updateOrCreate(
            ['sku' => 'KE-COOL-2000'],
            [
                'product_category_id' => $cooling->id,
                'name' => 'K-Elec Cooler 2000',
                'product_code' => 'COOL2000',
                'model' => 'KE-2000',
                'brand' => 'K-Elec',
                'default_warranty_months' => 24,
                'registration_grace_days' => 45,
                'odoo_product_id' => 'MOCK-P-200',
                'is_active' => true,
                'serial_tracking_enabled' => true,
                'manual_verification_allowed' => true,
                'receipt_required' => false,
                'is_odoo_managed' => false,
            ]
        );

        WarrantyRule::query()->updateOrCreate(
            ['name' => 'Default warranty rule'],
            [
                'warranty_duration_months' => 12,
                'registration_grace_days' => 30,
                'eligible_purchase_sources' => ['brand_shop', 'dealer', 'jumia', 'kilimall', 'other'],
                'receipt_required' => false,
                'serial_validation_mandatory' => false,
                'manual_verification_allowed' => true,
                'start_date_method' => 'purchase_date',
                'is_active' => true,
            ]
        );

        $templates = [
            ['key' => 'warranty_activated', 'name' => 'Warranty Activated', 'subject' => 'Your K-Elec warranty {{warranty_reference}} is active'],
            ['key' => 'warranty_pending_verification', 'name' => 'Pending Verification', 'subject' => 'Warranty {{warranty_reference}} pending verification'],
            ['key' => 'warranty_rejected', 'name' => 'Warranty Rejected', 'subject' => 'Warranty {{warranty_reference}} update'],
            ['key' => 'warranty_lookup', 'name' => 'Warranty Lookup Details', 'subject' => 'Your K-Elec warranty details'],
            ['key' => 'pos_warranty_registered', 'name' => 'POS Warranty Registered', 'subject' => 'Your Brand Shop warranty {{warranty_reference}}'],
            ['key' => 'customer_details_completion', 'name' => 'Complete Registration', 'subject' => 'Complete your K-Elec warranty details'],
            ['key' => 'consent_request', 'name' => 'Marketing Consent Request', 'subject' => 'Optional K-Elec marketing preference'],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::query()->updateOrCreate(
                ['key' => $template['key']],
                [
                    'name' => $template['name'],
                    'channel' => NotificationChannel::Both,
                    'subject' => $template['subject'],
                    'email_body' => "Hello {{customer_name}},\n\nWarranty {{warranty_reference}} for {{product_name}} ({{serial_number}}) is {{warranty_status}}.\nStart: {{warranty_start_date}}\nExpiry: {{warranty_expiry_date}}\nLookup: {{lookup_link}}\n\nSupport: {{support_phone}}",
                    'sms_body' => 'K-Elec: Warranty {{warranty_reference}} for {{product_name}} is {{warranty_status}}. Expiry {{warranty_expiry_date}}.',
                    'is_active' => true,
                ]
            );
        }

        $settings = [
            ['company_name', 'K-Elec', 'general', 'string'],
            ['support_phone', '+254700000000', 'general', 'string'],
            ['support_email', 'support@kelec.test', 'general', 'string'],
            ['application_url', url('/'), 'general', 'string'],
            ['default_timezone', 'Africa/Nairobi', 'general', 'string'],
            ['default_date_format', 'd M Y', 'general', 'string'],
            ['default_warranty_months', '12', 'warranty', 'integer'],
            ['registration_grace_days', '30', 'warranty', 'integer'],
            ['warranty_reference_prefix', 'KEL-WTY', 'warranty', 'string'],
            ['allow_manual_verification', '1', 'warranty', 'boolean'],
            ['privacy_policy_url', url('/privacy-policy'), 'privacy', 'string'],
            ['warranty_terms_url', url('/warranty-terms'), 'privacy', 'string'],
            ['privacy_policy_content', LegalContentDefaults::privacyPolicy(), 'privacy', 'string'],
            ['warranty_terms_content', LegalContentDefaults::warrantyTerms(), 'privacy', 'string'],
            ['odoo_enabled', '0', 'odoo', 'boolean'],
            ['odoo_mock_mode', '1', 'odoo', 'boolean'],
            ['odoo_timeout', '15', 'odoo', 'integer'],
            ['sms_enabled', '0', 'sms', 'boolean'],
            ['sms_http_method', 'POST', 'sms', 'string'],
            ['sms_auth_header', 'Authorization', 'sms', 'string'],
            ['sms_phone_param', 'to', 'sms', 'string'],
            ['sms_message_param', 'message', 'sms', 'string'],
            ['sms_timeout', '15', 'sms', 'integer'],
            ['mail_from_address', 'warranties@kelec.test', 'email', 'string'],
            ['mail_from_name', 'K-Elec Warranties', 'email', 'string'],
            ['pos_brand_shop_branches', 'Sarin,CBD', 'odoo', 'string'],
        ];

        foreach ($settings as [$key, $value, $group, $type]) {
            SystemSetting::setValue($key, $value, $group, $type, false);
        }
    }
}

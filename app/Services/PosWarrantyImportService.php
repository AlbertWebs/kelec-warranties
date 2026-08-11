<?php

namespace App\Services;

use App\Enums\ConsentType;
use App\Enums\RegistrationSource;
use App\Enums\WarrantyStatus;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\PublicAccessToken;
use App\Models\PurchaseSource;
use App\Models\Warranty;
use App\Models\WarrantyConsent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosWarrantyImportService
{
    public function __construct(
        protected CustomerService $customerService,
        protected PhoneNumberService $phoneNumberService,
        protected WarrantyReferenceGenerator $referenceGenerator,
        protected WarrantyEligibilityService $eligibilityService,
        protected WarrantyStatusService $statusService,
        protected NotificationDispatcher $notificationDispatcher,
        protected AuditLogger $auditLogger,
        protected SettingsService $settingsService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function import(array $payload): Warranty
    {
        return DB::transaction(function () use ($payload) {
            $serial = strtoupper(trim((string) ($payload['serial_number'] ?? '')));
            $posOrderId = (string) ($payload['odoo_pos_order_id'] ?? $payload['pos_order_id'] ?? '');

            if ($serial === '') {
                throw new \InvalidArgumentException('Serial number is required for POS warranty import.');
            }

            if ($existing = $this->eligibilityService->findActiveBySerial($serial)) {
                return $existing;
            }

            if ($posOrderId !== '') {
                $byPos = Warranty::query()->where('odoo_pos_order_id', $posOrderId)->first();
                if ($byPos) {
                    return $byPos;
                }
            }

            $branch = trim((string) ($payload['branch_name'] ?? $payload['branch'] ?? ''));
            $this->assertBrandShopBranch($branch);

            $product = null;
            if (! empty($payload['product_id'])) {
                $product = Product::find($payload['product_id']);
            } elseif (! empty($payload['odoo_product_id'])) {
                $product = Product::where('odoo_product_id', $payload['odoo_product_id'])->first();
            } elseif (! empty($payload['sku'])) {
                $product = Product::where('sku', $payload['sku'])->first();
            }

            $purchaseSource = PurchaseSource::query()->where('code', 'brand_shop')->first();
            $dealer = Dealer::query()
                ->where('is_active', true)
                ->where(function ($q) use ($branch) {
                    $q->where('physical_location', 'like', '%'.$branch.'%')
                        ->orWhere('dealer_code', strtoupper($branch))
                        ->orWhere('name', 'like', '%'.$branch.'%');
                })
                ->first();

            $mobile = $payload['mobile_number'] ?? $payload['customer_mobile'] ?? null;
            $fullName = trim((string) ($payload['full_name'] ?? $payload['customer_name'] ?? ''));
            $incompleteCustomer = blank($mobile) || blank($fullName);

            if ($incompleteCustomer) {
                $customer = Customer::create([
                    'full_name' => $fullName !== '' ? $fullName : 'POS Customer',
                    'mobile_number' => $mobile ?: 'provisional',
                    'mobile_normalized' => $mobile
                        ? $this->phoneNumberService->normalize($mobile)
                        : 'provisional-'.uniqid(),
                    'email' => $payload['email'] ?? null,
                    'odoo_customer_id' => $payload['odoo_customer_id'] ?? null,
                    'marketing_consent' => false,
                ]);
            } else {
                $customer = $this->customerService->findOrCreate([
                    'full_name' => $fullName,
                    'mobile_number' => $mobile,
                    'email' => $payload['email'] ?? null,
                    'odoo_customer_id' => $payload['odoo_customer_id'] ?? null,
                    'marketing_consent' => false,
                ]);
            }

            // Never auto-grant marketing consent from POS.
            if (! empty($payload['marketing_consent']) && filter_var($payload['marketing_consent'], FILTER_VALIDATE_BOOLEAN)) {
                $this->recordPosMarketingConsent($customer, true, $posOrderId);
            }

            $purchaseDate = ! empty($payload['purchase_date'])
                ? now()->parse($payload['purchase_date'])->startOfDay()
                : null;

            $eligibility = $this->eligibilityService->evaluate(
                $product,
                $purchaseDate,
                true,
                false,
                'brand_shop'
            );

            $provisional = $incompleteCustomer;
            $status = $provisional ? WarrantyStatus::PendingVerification : WarrantyStatus::Active;

            $warranty = Warranty::create([
                'reference' => $this->referenceGenerator->generate(),
                'customer_id' => $customer->id,
                'product_id' => $product?->id,
                'product_category_id' => $product?->product_category_id,
                'purchase_source_id' => $purchaseSource?->id,
                'dealer_id' => $dealer?->id,
                'product_name' => $payload['product_name'] ?? $product?->name,
                'product_model' => $payload['product_model'] ?? $product?->model,
                'serial_number' => $serial,
                'branch_name' => $branch,
                'purchase_date' => $purchaseDate?->toDateString(),
                'registration_date' => now(),
                'warranty_start_date' => $status === WarrantyStatus::Active ? $eligibility['start_date'] : null,
                'warranty_expiry_date' => $status === WarrantyStatus::Active ? $eligibility['expiry_date'] : null,
                'warranty_duration_months' => $eligibility['duration_months'] ?? 12,
                'status' => WarrantyStatus::Submitted,
                'eligibility_result' => $eligibility['result'] ?? 'pos_import',
                'odoo_customer_id' => $payload['odoo_customer_id'] ?? null,
                'odoo_product_id' => $payload['odoo_product_id'] ?? $product?->odoo_product_id,
                'odoo_serial_id' => $payload['odoo_serial_id'] ?? null,
                'odoo_pos_order_id' => $posOrderId ?: null,
                'invoice_number' => $payload['invoice_number'] ?? null,
                'registration_source' => RegistrationSource::OdooPos,
                'marketing_consent' => (bool) $customer->marketing_consent,
                'privacy_accepted' => true,
                'consent_timestamp' => now(),
                'consent_source' => 'odoo_pos',
                'odoo_validated' => true,
                'odoo_validation_message' => 'Imported from Odoo POS',
                'requires_manual_verification' => $provisional,
                'customer_notes' => $provisional ? 'Provisional POS warranty awaiting customer details completion.' : null,
            ]);

            $this->statusService->transition(
                $warranty,
                $status,
                null,
                $provisional ? 'Provisional POS warranty created' : 'Automatically activated from Brand Shop POS'
            );

            if ($status === WarrantyStatus::Active) {
                $warranty->update(['approved_at' => now()]);
            }

            $this->auditLogger->log('pos_warranty_imported', $warranty, null, [
                'branch' => $branch,
                'pos_order_id' => $posOrderId,
                'provisional' => $provisional,
            ]);

            if ($provisional) {
                $token = PublicAccessToken::issue('complete_registration', $customer, $warranty, 21);
                $this->notificationDispatcher->sendWarrantyNotification(
                    $warranty->fresh(['customer', 'product']),
                    'customer_details_completion'
                );
                // Store completion URL in customer notes for admin visibility.
                $warranty->update([
                    'customer_notes' => trim(($warranty->customer_notes ?? '')."\nCompletion link: ".url('/complete-registration/'.$token->token)),
                ]);
            } else {
                $this->notificationDispatcher->sendWarrantyNotification(
                    $warranty->fresh(['customer', 'product']),
                    'pos_warranty_registered'
                );
            }

            // Marketing consent is always optional and separate from warranty activation.
            if (empty($payload['marketing_consent']) || ! filter_var($payload['marketing_consent'], FILTER_VALIDATE_BOOLEAN)) {
                $this->sendMarketingConsentRequest($customer, $warranty);
            }

            return $warranty->fresh(['customer', 'product', 'purchaseSource', 'dealer']);
        });
    }

    public function sendMarketingConsentRequest(Customer $customer, ?Warranty $warranty = null): PublicAccessToken
    {
        $token = PublicAccessToken::issue('marketing_consent', $customer, $warranty, 30);
        $link = url('/consent/'.$token->token);

        // Marketing preference is optional — email only; do not spend SMS credits.
        $this->notificationDispatcher->sendCustomMessage(
            $customer,
            $warranty,
            'consent_request',
            'K-Elec marketing preference',
            "Hello {$customer->full_name},\n\nWould you like to receive marketing updates from K-Elec? Manage your preference here (optional):\n{$link}\n\nYour warranty remains active regardless of this choice.",
            "K-Elec: Optional marketing preference link {$link}",
            allowSms: false,
        );

        return $token;
    }

    protected function recordPosMarketingConsent(Customer $customer, bool $granted, ?string $posOrderId): void
    {
        $customer->update([
            'marketing_consent' => $granted,
            'marketing_consent_at' => $granted ? now() : null,
        ]);

        WarrantyConsent::create([
            'customer_id' => $customer->id,
            'consent_type' => ConsentType::Marketing,
            'granted' => $granted,
            'source' => 'odoo_pos',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'consented_at' => now(),
        ]);
    }

    protected function assertBrandShopBranch(string $branch): void
    {
        $allowed = collect(explode(',', (string) $this->settingsService->get('pos_brand_shop_branches', 'Sarin,CBD')))
            ->map(fn ($item) => Str::lower(trim($item)))
            ->filter()
            ->all();

        if ($branch === '' || ! in_array(Str::lower($branch), $allowed, true)) {
            throw new \InvalidArgumentException('POS branch is not configured for automated Brand Shop warranty registration.');
        }
    }
}

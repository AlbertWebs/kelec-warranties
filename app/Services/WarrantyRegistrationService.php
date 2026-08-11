<?php

namespace App\Services;

use App\Enums\ConsentType;
use App\Enums\RegistrationSource;
use App\Enums\WarrantyStatus;
use App\Jobs\SyncWarrantyToOdoo;
use App\Models\Customer;
use App\Models\IntegrationFailure;
use App\Models\Product;
use App\Models\PurchaseSource;
use App\Models\Warranty;
use App\Models\WarrantyConsent;
use App\Models\WarrantyDocument;
use App\Services\Odoo\OdooProductService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class WarrantyRegistrationService
{
    public function __construct(
        protected PhoneNumberService $phoneNumberService,
        protected WarrantyReferenceGenerator $referenceGenerator,
        protected WarrantyEligibilityService $eligibilityService,
        protected WarrantyStatusService $statusService,
        protected CustomerService $customerService,
        protected DocumentStorageService $documentStorageService,
        protected OdooProductService $odooProductService,
        protected AuditLogger $auditLogger,
        protected NotificationDispatcher $notificationDispatcher,
    ) {}

    /**
     * @return array{status: string, warranty?: Warranty, message: string, odoo?: array<string, mixed>}
     */
    public function checkSerial(string $serialNumber): array
    {
        $originalSerial = trim($serialNumber);
        $serialNumber = strtoupper($originalSerial);
        $existing = $this->eligibilityService->findActiveBySerial($serialNumber);

        if ($existing) {
            $reference = $existing->reference ?: 'on file';

            return [
                'status' => 'existing_active',
                'warranty' => $existing,
                'message' => "This serial number already has a warranty ({$reference}). Use Warranty Lookup with the registered mobile number to view it.",
            ];
        }

        $localProduct = $this->findLocalProductBySerial($originalSerial);
        $odoo = null;

        try {
            // Always ask Odoo for lot/POS sale details when possible, even if the product is cached locally.
            $odoo = $this->odooProductService->lookupBySerial($originalSerial !== '' ? $originalSerial : $serialNumber);
        } catch (Throwable $e) {
            if (! $localProduct) {
                IntegrationFailure::create([
                    'integration' => 'odoo',
                    'action' => 'validate_serial',
                    'error_message' => $e->getMessage(),
                    'status' => 'pending',
                    'payload' => ['serial_number' => $serialNumber],
                    'next_retry_at' => now()->addMinutes(15),
                ]);

                return [
                    'status' => 'odoo_unavailable',
                    'message' => 'We are currently unable to verify the product automatically. Your registration can still be saved and will be reviewed by the K-Elec team.',
                    'odoo' => ['found' => false, 'error' => true],
                ];
            }
        }

        if ($localProduct) {
            $odooProduct = is_array($odoo['product'] ?? null) ? $odoo['product'] : [];
            $odooSale = is_array($odoo['sale'] ?? null) ? $odoo['sale'] : [];
            $odooCustomer = is_array($odoo['customer'] ?? null) ? $odoo['customer'] : [];

            $model = $localProduct->model
                ?: $localProduct->default_code
                ?: $localProduct->sku
                ?: ($odooProduct['model'] ?? null)
                ?: $localProduct->customerFacingName();

            $hasSaleDetails = ($odooSale['sale_status'] ?? null) === 'sold'
                || filled($odooSale['odoo_pos_order_id'] ?? null)
                || filled($odooSale['purchase_date'] ?? null)
                || filled($odooSale['invoice_number'] ?? null);
            $inStock = ($odooSale['sale_status'] ?? null) === 'in_stock';

            $message = 'Serial number validated successfully.';
            if ($inStock) {
                $branch = $odooSale['branch_name'] ?? null;
                $message = 'Product found in stock'
                    .($branch ? ' at '.$branch : '')
                    .'. This unit was transferred internally and is not sold yet. Place of purchase is prefilled from the current branch. Add purchase date and invoice after the customer buys it.';
            } elseif (is_array($odoo) && ! $hasSaleDetails) {
                $message = 'Product found. Sale details were not automatically retrieved from Odoo, so please confirm purchase information.';
            }

            return [
                'status' => 'found_local',
                'message' => $message,
                'odoo' => [
                    'source' => 'local',
                    'found' => true,
                    'product' => [
                        'id' => $localProduct->id,
                        'odoo_product_id' => $localProduct->odoo_product_id ?: $localProduct->odoo_id ?: ($odooProduct['odoo_product_id'] ?? null),
                        'name' => $localProduct->customerFacingName() ?: ($odooProduct['name'] ?? null),
                        'model' => $model,
                        'category_id' => $localProduct->product_category_id ?: ($odooProduct['category_id'] ?? null),
                    ],
                    'sale' => [
                        'purchase_date' => $odooSale['purchase_date'] ?? null,
                        'invoice_number' => $odooSale['invoice_number'] ?? null,
                        'branch_name' => $odooSale['branch_name'] ?? null,
                        'odoo_pos_order_id' => $odooSale['odoo_pos_order_id'] ?? null,
                        'sale_status' => $odooSale['sale_status'] ?? ($hasSaleDetails ? 'sold' : null),
                        'current_location' => $odooSale['current_location'] ?? null,
                    ],
                    'customer' => $odooCustomer,
                ],
            ];
        }

        if (! ($odoo['found'] ?? false)) {
            return [
                'status' => 'not_found',
                'message' => 'We could not automatically locate this serial number in our sales records. You can continue with the registration, and the K-Elec team will verify the product.',
                'odoo' => $odoo ?? ['found' => false],
            ];
        }

        return [
            'status' => 'found',
            'message' => $odoo['message'] ?? 'Serial number validated successfully.',
            'odoo' => $odoo,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(array $data, ?UploadedFile $receipt = null): Warranty
    {
        return DB::transaction(function () use ($data, $receipt) {
            $serialNumber = strtoupper(trim($data['serial_number']));
            $existing = $this->eligibilityService->findActiveBySerial($serialNumber);
            if ($existing) {
                abort(422, 'An active warranty already exists for this serial number.');
            }

            $serialCheck = $this->checkSerial($serialNumber);
            $odoo = $serialCheck['odoo'] ?? [];
            $serialFound = in_array(($serialCheck['status'] ?? null), ['found', 'found_local'], true);
            $odooUnavailable = ($serialCheck['status'] ?? null) === 'odoo_unavailable';

            $product = null;
            if (! empty($data['product_id'])) {
                $product = Product::find($data['product_id']);
            } elseif (! empty($odoo['product']['id'])) {
                $product = Product::find($odoo['product']['id']);
            } elseif (! empty($odoo['product']['odoo_product_id'])) {
                $product = Product::where('odoo_product_id', $odoo['product']['odoo_product_id'])->first();
            }

            $purchaseSource = ! empty($data['purchase_source_id'])
                ? PurchaseSource::find($data['purchase_source_id'])
                : null;

            $eligibility = $this->eligibilityService->evaluate(
                $product,
                ! empty($data['purchase_date']) ? now()->parse($data['purchase_date']) : (! empty($odoo['sale']['purchase_date']) ? now()->parse($odoo['sale']['purchase_date']) : null),
                $serialFound,
                false,
                $purchaseSource?->code
            );

            $customer = $this->customerService->findOrCreate([
                'full_name' => $data['full_name'],
                'mobile_number' => $data['mobile_number'],
                'email' => $data['email'] ?? null,
                'county' => $data['county'] ?? null,
                'town' => $data['town'] ?? null,
                'marketing_consent' => (bool) ($data['marketing_consent'] ?? false),
                'odoo_customer_id' => $odoo['customer']['odoo_customer_id'] ?? null,
            ]);

            $requiresManual = $eligibility['requires_manual_verification'] || $odooUnavailable || ! $serialFound;
            $status = $requiresManual ? WarrantyStatus::PendingVerification : WarrantyStatus::Active;

            $warranty = Warranty::create([
                'reference' => $this->referenceGenerator->generate(),
                'customer_id' => $customer->id,
                'product_id' => $product?->id,
                'product_category_id' => $product?->product_category_id ?? ($data['product_category_id'] ?? null),
                'purchase_source_id' => $purchaseSource?->id,
                'dealer_id' => $data['dealer_id'] ?? null,
                'product_name' => $data['product_name'] ?? $odoo['product']['name'] ?? $product?->name,
                'product_model' => $data['product_model'] ?? $odoo['product']['model'] ?? $product?->model,
                'serial_number' => $serialNumber,
                'branch_name' => $data['branch_name'] ?? $odoo['sale']['branch_name'] ?? null,
                'purchase_date' => $data['purchase_date'] ?? $odoo['sale']['purchase_date'] ?? null,
                'registration_date' => now(),
                'warranty_start_date' => $status === WarrantyStatus::Active ? $eligibility['start_date'] : null,
                'warranty_expiry_date' => $status === WarrantyStatus::Active ? $eligibility['expiry_date'] : null,
                'warranty_duration_months' => $eligibility['duration_months'],
                'status' => WarrantyStatus::Submitted,
                'eligibility_result' => $eligibility['result'],
                'odoo_customer_id' => $odoo['customer']['odoo_customer_id'] ?? null,
                'odoo_product_id' => $odoo['product']['odoo_product_id'] ?? null,
                'odoo_serial_id' => $odoo['product']['odoo_serial_id'] ?? null,
                'odoo_pos_order_id' => $odoo['sale']['odoo_pos_order_id'] ?? null,
                'invoice_number' => $data['invoice_number'] ?? $odoo['sale']['invoice_number'] ?? null,
                'registration_source' => RegistrationSource::PublicPortal,
                'marketing_consent' => (bool) ($data['marketing_consent'] ?? false),
                'privacy_accepted' => (bool) ($data['privacy_accepted'] ?? false),
                'consent_timestamp' => now(),
                'consent_source' => 'public_portal',
                'consent_ip' => request()->ip(),
                'odoo_validated' => $serialFound,
                'odoo_validation_message' => $serialCheck['message'] ?? null,
                'requires_manual_verification' => $requiresManual,
            ]);

            if ($receipt) {
                $stored = $this->documentStorageService->storeReceipt($receipt);
                $warranty->update([
                    'receipt_path' => $stored['path'],
                    'receipt_original_name' => $stored['original_name'],
                ]);
                WarrantyDocument::create([
                    'warranty_id' => $warranty->id,
                    'type' => 'receipt',
                    'disk' => $stored['disk'],
                    'path' => $stored['path'],
                    'original_name' => $stored['original_name'],
                    'mime_type' => $stored['mime_type'],
                    'size' => $stored['size'],
                ]);
            }

            $this->storeConsents($warranty, $customer, (bool) ($data['privacy_accepted'] ?? false), (bool) ($data['marketing_consent'] ?? false));

            $this->statusService->transition(
                $warranty,
                $status,
                null,
                $requiresManual ? 'Awaiting manual verification' : 'Automatically activated after successful validation'
            );

            if ($status === WarrantyStatus::Active) {
                $warranty->update([
                    'approved_at' => now(),
                ]);
                SyncWarrantyToOdoo::dispatch($warranty->id);
            }

            $this->auditLogger->log('warranty_created', $warranty, null, $warranty->toArray());

            $this->notificationDispatcher->sendWarrantyNotification(
                $warranty->fresh(['customer', 'product']),
                $requiresManual ? 'warranty_pending_verification' : 'warranty_activated'
            );

            return $warranty->fresh(['customer', 'product', 'purchaseSource', 'dealer']);
        });
    }

    protected function storeConsents(Warranty $warranty, Customer $customer, bool $privacy, bool $marketing): void
    {
        $common = [
            'warranty_id' => $warranty->id,
            'customer_id' => $customer->id,
            'source' => 'public_portal',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'consented_at' => now(),
        ];

        WarrantyConsent::create($common + [
            'consent_type' => ConsentType::Privacy,
            'granted' => $privacy,
        ]);

        WarrantyConsent::create($common + [
            'consent_type' => ConsentType::WarrantyTerms,
            'granted' => $privacy,
        ]);

        WarrantyConsent::create($common + [
            'consent_type' => ConsentType::Marketing,
            'granted' => $marketing,
        ]);
    }

    protected function findLocalProductBySerial(string $serialNumber): ?Product
    {
        $candidates = array_values(array_unique(array_filter([
            trim($serialNumber),
            strtoupper(trim($serialNumber)),
            strtolower(trim($serialNumber)),
        ], fn (string $value) => $value !== '')));

        if ($candidates === []) {
            return null;
        }

        // Only match true unit serials stored on products — never SKU/model/barcode catalog codes.
        return Product::query()
            ->whereIn('serial_number', $candidates)
            ->first();
    }
}

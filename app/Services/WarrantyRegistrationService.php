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
            return [
                'status' => 'existing_active',
                'warranty' => $existing,
                'message' => 'This product already has an active warranty. Enter the registered mobile number to view the warranty details.',
            ];
        }

        $localProduct = $this->findLocalProductBySerial($originalSerial);
        if ($localProduct) {
            return [
                'status' => 'found_local',
                'message' => 'Serial number validated successfully.',
                'odoo' => [
                    'source' => 'local',
                    'found' => true,
                    'product' => [
                        'id' => $localProduct->id,
                        'odoo_product_id' => $localProduct->odoo_product_id ?: $localProduct->odoo_id,
                        'name' => $localProduct->customerFacingName(),
                        'model' => $localProduct->model ?: $localProduct->default_code ?: $localProduct->sku,
                        'category_id' => $localProduct->product_category_id,
                    ],
                    'sale' => [],
                    'customer' => [],
                ],
            ];
        }

        try {
            // Prefer the original casing for Odoo lookups; internal warranty keys stay uppercased.
            $odoo = $this->odooProductService->lookupBySerial($originalSerial !== '' ? $originalSerial : $serialNumber);

            if (! ($odoo['found'] ?? false)) {
                return [
                    'status' => 'not_found',
                    'message' => 'We could not automatically locate this serial number in our sales records. You can continue with the registration, and the K-Elec team will verify the product.',
                    'odoo' => $odoo,
                ];
            }

            return [
                'status' => 'found',
                'message' => 'Serial number validated successfully.',
                'odoo' => $odoo,
            ];
        } catch (Throwable $e) {
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

        return Product::query()
            ->where(function ($query) use ($candidates) {
                $query->whereIn('serial_number', $candidates)
                    ->orWhereIn('barcode', $candidates)
                    ->orWhereIn('default_code', $candidates)
                    ->orWhereIn('sku', $candidates)
                    ->orWhereIn('product_code', $candidates)
                    ->orWhereIn('odoo_id', $candidates)
                    ->orWhereIn('odoo_product_id', $candidates)
                    ->orWhereIn('model', $candidates);
            })
            ->first();
    }
}

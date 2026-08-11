<?php

namespace App\Support;

class LegalContentDefaults
{
    public static function privacyPolicy(): string
    {
        return <<<'MD'
## 1. Who we are

K-Elec (“we”, “us”) operates this warranty portal to help customers register and manage warranties for K-Elec appliances purchased in Kenya.

**Contact:** support channels published in this portal (phone and email in Admin → Settings).

## 2. What this policy covers

This Privacy Policy explains how we collect, use, store, and share personal data when you:

- register a product warranty
- look up an existing warranty
- download or view a warranty certificate
- respond to a consent or registration-completion link
- contact us about a warranty matter

## 3. Data we collect

Depending on how you use the portal, we may collect:

- **Identity & contact:** full name, mobile number, email address
- **Purchase details:** serial number, product, purchase date, dealer or purchase source, receipt or proof of purchase
- **Warranty records:** reference number, status, start/end dates, verification notes
- **Consent records:** privacy acceptance, optional marketing preference, timestamps and related tokens
- **Technical data:** IP address, browser type, and basic request logs needed for security and abuse prevention

We do not ask for payment card details through this warranty portal.

## 4. Why we use your data

We process personal data to:

- validate purchases and activate warranties
- issue warranty references and certificates
- contact you about registration status, verification, or support
- prevent fraud, duplicate serial misuse, and system abuse
- meet legal, audit, and product-safety obligations
- improve portal reliability and customer support

Optional marketing messages are sent **only** if you give separate marketing consent. Warranty processing does not depend on marketing consent.

## 5. Legal basis

We process warranty data because it is necessary to provide the warranty service you request and to pursue our legitimate interests in authenticating products and supporting customers. Where required, we rely on your consent (for example privacy acceptance at registration and optional marketing).

## 6. Sharing your data

We may share data with:

- **Internal K-Elec teams** handling warranties, customer support, and operations
- **Odoo / ERP systems** used for product, sales, or warranty sync (when enabled)
- **SMS and email providers** used to deliver transactional notifications
- **Authorised service partners** when needed to assess a warranty claim
- **Regulators or law enforcement** when legally required

We do not sell your personal data.

## 7. How long we keep data

Warranty and related customer records are retained for the life of the warranty and a reasonable period afterwards for support, dispute handling, and audit. Consent and notification logs may be kept to demonstrate compliance. When data is no longer needed, we delete or anonymise it where practical.

## 8. Security

We use access controls, authenticated admin accounts, encrypted transport (HTTPS in production), and operational logging to protect warranty data. No method of transmission or storage is completely secure; please keep your warranty reference and mobile number confidential.

## 9. Your choices and rights

Subject to applicable Kenyan data protection law, you may request access to, correction of, or deletion of personal data we hold about you, or withdraw marketing consent at any time. Warranty-related processing may still be required for an active warranty.

To exercise these rights, contact K-Elec support using the details on this portal.

## 10. Children

This portal is intended for adult customers and authorised purchasers. If you believe a child’s data was submitted in error, contact us so we can review and remove it where appropriate.

## 11. Updates

We may update this Privacy Policy from time to time. The version published on this page is the current policy. Material changes will be reflected here; continued use of the portal after updates constitutes notice of the revised policy for ongoing warranty services.

## 12. Contact

Questions about privacy or this policy: use the support phone or email shown in the portal footer and settings, or visit [k-elec.co.ke](https://k-elec.co.ke/).
MD;
    }

    public static function warrantyTerms(): string
    {
        return <<<'MD'
## 1. Coverage

K-Elec warranties cover manufacturing defects for the configured duration from the **purchase date**, not the registration date, subject to product-specific terms and valid registration.

## 2. Registration

Warranties should be registered with accurate customer, product, and purchase details. Proof of purchase may be required for verification. Late registrations may be accepted within any published grace period, subject to review.

## 3. Exclusions

Wear and tear, misuse, unauthorised repairs, damage from power irregularities, and consumable parts are typically excluded unless product terms state otherwise.

## 4. Claims

Contact K-Elec support with your warranty reference, serial number, and a description of the issue. We may inspect the product or request additional information before approving a remedy.

## 5. Changes

K-Elec may update these terms. The version published on this page applies to new registrations from the date of publication.
MD;
    }
}

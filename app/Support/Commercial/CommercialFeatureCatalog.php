<?php

namespace App\Support\Commercial;

final class CommercialFeatureCatalog
{
    public const MULTI_BRANCH = 'multi_branch';

    public const BILLING = 'billing';

    public const INVENTORY = 'inventory';

    public const REFERRAL_LABS = 'referral_labs';

    public const PATIENT_PORTAL = 'patient_portal';

    public const CLINICIAN_PORTAL = 'clinician_portal';

    public const NOTIFICATIONS = 'notifications';

    public const ANALYZER_INTEGRATION = 'analyzer_integration';

    public const INTEROPERABILITY = 'interoperability';

    public const ADVANCED_ANALYTICS = 'advanced_analytics';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::MULTI_BRANCH => 'Multi-branch and collection centres',
            self::BILLING => 'Billing, invoices and payments',
            self::INVENTORY => 'Inventory and reagent management',
            self::REFERRAL_LABS => 'Referral laboratories',
            self::PATIENT_PORTAL => 'Patient portal',
            self::CLINICIAN_PORTAL => 'Clinician portal',
            self::NOTIFICATIONS => 'Notifications',
            self::ANALYZER_INTEGRATION => 'Analyzer integration',
            self::INTEROPERABILITY => 'HL7, ASTM and FHIR interoperability',
            self::ADVANCED_ANALYTICS => 'Advanced analytics',
        ];
    }

    /**
     * Features implemented and validated by the current commercial pack.
     *
     * SaaS plan and subscription administration is the entitlement platform
     * itself and therefore does not require a tenant-facing feature key.
     *
     * @return array<int, string>
     */
    public static function enabledByDefault(): array
    {
        return [
            self::MULTI_BRANCH,
            self::BILLING,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::labels());
    }
}

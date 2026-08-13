<?php

namespace Tests\Support\Tenant;

use App\Models\ClinicianPortalAccount;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\ReferringClinician;
use App\Models\Tenant;
use App\Services\Authentication\TenantSwitcher;
use Closure;
use Illuminate\Support\Str;
use Tests\Support\Authentication\TenantAuthenticationTestCase;

abstract class PortalAuthenticationTestCase extends TenantAuthenticationTestCase
{
    /**
     * @return array<string, string>
     */
    protected function portalHeaders(
        string $token,
        string $domain
    ): array {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-Domain' => $domain,
        ];
    }

    protected function runInPortalTenant(
        Tenant $tenant,
        Closure $callback
    ): mixed {
        app(TenantSwitcher::class)->switch($tenant);

        try {
            return $callback();
        } finally {
            $this->restoreLandlordContext();
        }
    }

    /**
     * @return array{
     *     patient: Patient,
     *     account: PatientPortalAccount,
     *     email: string,
     *     password: string
     * }
     */
    protected function createPatientPortalIdentity(
        Tenant $tenant,
        ?string $email = null,
        string $password = 'PortalPassword123!',
        array $accountOverrides = []
    ): array {
        return $this->runInPortalTenant(
            $tenant,
            function () use (
                $email,
                $password,
                $accountOverrides
            ): array {
                $suffix = strtolower(Str::random(10));

                $patient = Patient::query()->create([
                    'first_name' => 'Portal',
                    'last_name' => 'Patient',
                    'date_of_birth' => '1990-01-01',
                    'gender' => 'female',
                    'email' => 'clinical-'.$suffix.'@example.test',
                    'phone' => '+2348000000000',
                    'address' => 'Portal test address',
                    'medical_record_number' => 'MRN-'.$suffix,
                    'notes' => null,
                    'is_active' => true,
                ]);

                $loginEmail = $email
                    ?? 'patient-'.$suffix.'@example.test';

                $account = PatientPortalAccount::query()->create(
                    array_merge(
                        [
                            'patient_id' => $patient->getKey(),
                            'email' => $loginEmail,
                            'password' => $password,
                            'email_verified_at' => now(),
                            'activated_at' => now(),
                            'last_login_at' => null,
                            'is_active' => true,
                            'created_by' => null,
                        ],
                        $accountOverrides
                    )
                );

                return [
                    'patient' => $patient,
                    'account' => $account,
                    'email' => strtolower($loginEmail),
                    'password' => $password,
                ];
            }
        );
    }

    /**
     * @return array{
     *     clinician: ReferringClinician,
     *     account: ClinicianPortalAccount,
     *     email: string,
     *     password: string
     * }
     */
    protected function createClinicianPortalIdentity(
        Tenant $tenant,
        ?string $email = null,
        string $password = 'PortalPassword123!',
        array $accountOverrides = []
    ): array {
        return $this->runInPortalTenant(
            $tenant,
            function () use (
                $email,
                $password,
                $accountOverrides
            ): array {
                $suffix = strtolower(Str::random(10));

                $clinician = ReferringClinician::query()->create([
                    'name' => 'Dr Portal Clinician '.$suffix,
                    'email' => 'directory-'.$suffix.'@example.test',
                    'phone' => '+2348111111111',
                    'organisation' => 'Portal Test Clinic',
                    'registration_number' => 'MDCN-'.$suffix,
                    'specialty' => 'General Practice',
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                ]);

                $loginEmail = $email
                    ?? 'clinician-'.$suffix.'@example.test';

                $account = ClinicianPortalAccount::query()->create(
                    array_merge(
                        [
                            'referring_clinician_id' => (
                                $clinician->getKey()
                            ),
                            'email' => $loginEmail,
                            'password' => $password,
                            'email_verified_at' => now(),
                            'activated_at' => now(),
                            'last_login_at' => null,
                            'is_active' => true,
                            'created_by' => null,
                        ],
                        $accountOverrides
                    )
                );

                return [
                    'clinician' => $clinician,
                    'account' => $account,
                    'email' => strtolower($loginEmail),
                    'password' => $password,
                ];
            }
        );
    }
}

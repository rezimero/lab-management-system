<?php

namespace Tests\Feature\Tenant\Profile;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Tenant\ProfileManagementTestCase;

class TenantProfileContractTest extends
    ProfileManagementTestCase
{
    public function test_administrator_can_view_and_update_profile(): void
    {
        $registration = $this->registerTestTenant(
            'profile-contract'
        );

        $headers = $this->tenantHeaders(
            $registration['token'],
            $registration['domain']
        );

        $this
            ->withHeaders($headers)
            ->getJson('/api/tenant/profile')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.lab_name',
                $registration['tenant']->name
            )
            ->assertJsonPath(
                'data.logo_url',
                null
            )
            ->assertJsonMissingPath(
                'data.tenant_id'
            );

        $payload = $this->profilePayload(
            'Rezhel Diagnostic Centre'
        );

        $this->resetProfileAuthentication();

        $this
            ->withHeaders($headers)
            ->putJson(
                '/api/tenant/profile',
                $payload
            )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Laboratory profile updated successfully.'
            )
            ->assertJsonPath(
                'data.lab_name',
                'Rezhel Diagnostic Centre'
            )
            ->assertJsonPath(
                'data.director_name',
                'Dr Ada Okafor'
            )
            ->assertJsonPath(
                'data.director_qualification',
                'MBBS, FMCPath'
            )
            ->assertJsonPath(
                'data.director_registration_number',
                'MDCN-12345'
            )
            ->assertJsonPath(
                'data.signatory_designation',
                'Consultant Pathologist'
            )
            ->assertJsonPath(
                'data.accreditation_details',
                'ISO 15189 accredited laboratory.'
            )
            ->assertJsonPath(
                'data.full_address',
                '12 Laboratory Road, Lagos, Lagos, Nigeria'
            );

        $stored = $this->storedTenantProfile(
            $registration['tenant']
        );

        $this->assertSame(
            'Rezhel Diagnostic Centre',
            $stored->lab_name
        );

        $this->assertSame(
            'Dr Ada Okafor',
            $stored->director_name
        );
    }

    public function test_non_administrator_cannot_manage_profile(): void
    {
        $registration = $this->registerTestTenant(
            'profile-forbidden'
        );

        $headers = $this->headersForProfileRole(
            $registration['tenant'],
            $registration['domain'],
            'receptionist'
        );

        $this
            ->withHeaders($headers)
            ->getJson('/api/tenant/profile')
            ->assertForbidden();

        $this->resetProfileAuthentication();

        $this
            ->withHeaders($headers)
            ->putJson(
                '/api/tenant/profile',
                $this->profilePayload(
                    'Forbidden Laboratory'
                )
            )
            ->assertForbidden();

        $this->resetProfileAuthentication();

        $this
            ->withHeaders($headers)
            ->post(
                '/api/tenant/profile/logo',
                [
                    'file' => $this->fakePng(
                        'forbidden.png'
                    ),
                ]
            )
            ->assertForbidden();
    }

    public function test_administrator_can_upload_profile_media(): void
    {
        Storage::fake('public');

        $registration = $this->registerTestTenant(
            'profile-media'
        );

        $headers = $this->tenantHeaders(
            $registration['token'],
            $registration['domain']
        );

        $logoResponse = $this
            ->withHeaders($headers)
            ->post(
                '/api/tenant/profile/logo',
                [
                    'file' => $this->fakePng(
                        'laboratory-logo.png'
                    ),
                ]
            );

        $logoResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertIsString(
            $logoResponse->json(
                'data.logo_url'
            )
        );

        $this->resetProfileAuthentication();

        $faviconResponse = $this
            ->withHeaders($headers)
            ->post(
                '/api/tenant/profile/favicon',
                [
                    'file' => $this->fakePng(
                        'favicon.png'
                    ),
                ]
            );

        $faviconResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertIsString(
            $faviconResponse->json(
                'data.favicon_url'
            )
        );

        $this->resetProfileAuthentication();

        $signatureResponse = $this
            ->withHeaders($headers)
            ->post(
                '/api/tenant/profile/director-signature',
                [
                    'file' => $this->fakePng(
                        'director-signature.png'
                    ),
                ]
            );

        $signatureResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertIsString(
            $signatureResponse->json(
                'data.director_signature_url'
            )
        );

        $profile = $this->storedTenantProfile(
            $registration['tenant']
        );

        $this->assertTrue(
            $profile->hasMedia('logo')
        );

        $this->assertTrue(
            $profile->hasMedia('favicon')
        );

        $this->assertTrue(
            $profile->hasMedia(
                'director_signature'
            )
        );

        $logo = $profile->getFirstMedia(
            'logo'
        );

        $this->assertNotNull($logo);

        $this->assertSame(
            $registration['tenant']->id,
            $logo->tenant_id
        );

        $this->assertSame(
            'laboratory-logo.png',
            $logo->original_filename
        );

        $this->assertSame(
            sprintf(
                'tenant-profile/%s/logo',
                $profile->getKey()
            ),
            $logo->directory
        );

        $this->assertSame(
            $logo->directory.'/'.$logo->filename,
            $logo->path
        );

        $this->assertNotSame(
            '',
            $logo->uuid
        );

        $this->assertSame(
            64,
            strlen((string) $logo->hash)
        );
    }

    public function test_profile_is_tenant_isolated(): void
    {
        $first = $this->registerTestTenant(
            'profile-isolation-a'
        );

        $second = $this->registerTestTenant(
            'profile-isolation-b'
        );

        $firstHeaders = $this->tenantHeaders(
            $first['token'],
            $first['domain']
        );

        $secondHeaders = $this->tenantHeaders(
            $second['token'],
            $second['domain']
        );

        $this->resetProfileAuthentication();

        $this
            ->withHeaders($firstHeaders)
            ->putJson(
                '/api/tenant/profile',
                $this->profilePayload(
                    'Tenant A Laboratory'
                )
            )
            ->assertOk();

        $this->resetProfileAuthentication();

        $this
            ->withHeaders($secondHeaders)
            ->putJson(
                '/api/tenant/profile',
                $this->profilePayload(
                    'Tenant B Laboratory'
                )
            )
            ->assertOk();

        $this->resetProfileAuthentication();

        $this
            ->withHeaders($firstHeaders)
            ->getJson('/api/tenant/profile')
            ->assertOk()
            ->assertJsonPath(
                'data.lab_name',
                'Tenant A Laboratory'
            );

        $this->resetProfileAuthentication();

        $this
            ->withHeaders($secondHeaders)
            ->getJson('/api/tenant/profile')
            ->assertOk()
            ->assertJsonPath(
                'data.lab_name',
                'Tenant B Laboratory'
            );
    }

    /**
     * Create a valid PNG upload without requiring the GD extension.
     */
    private function fakePng(
        string $name
    ): UploadedFile {
        $content = base64_decode(
            (
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB'
                .'CAQAAAC1HAwCAAAAC0lEQVR42mNk+A8A'
                .'AQUBAScY42YAAAAASUVORK5CYII='
            ),
            true
        );

        $this->assertIsString($content);

        return UploadedFile::fake()
            ->createWithContent(
                $name,
                $content
            );
    }

    /**
     * Return a complete valid profile payload.
     *
     * @return array<string, mixed>
     */
    private function profilePayload(
        string $labName
    ): array {
        return [
            'lab_name' => $labName,
            'tagline' => 'Trusted laboratory care',
            'primary_color' => '#1976D2',
            'secondary_color' => '#424242',
            'accent_color' => '#2E7D32',
            'email' => 'laboratory@example.test',
            'phone' => '+2348012345678',
            'website' => 'https://laboratory.example.test',
            'address' => '12 Laboratory Road',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria',
            'license_number' => 'LAB-LIC-1001',
            'registration_number' => 'LAB-REG-1001',
            'tin' => 'TIN-1001',
            'report_footer' => (
                'This report must be interpreted by a clinician.'
            ),
            'director_name' => 'Dr Ada Okafor',
            'director_qualification' => 'MBBS, FMCPath',
            'director_registration_number' => 'MDCN-12345',
            'signatory_designation' => 'Consultant Pathologist',
            'accreditation_details' => (
                'ISO 15189 accredited laboratory.'
            ),
            'invoice_footer' => 'Thank you for your business.',
            'bank_name' => 'Test Bank',
            'account_name' => $labName,
            'account_number' => '0123456789',
        ];
    }
}
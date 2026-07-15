<?php

namespace Tests\Support\Authentication;

use Tests\Support\Authentication\Concerns\ManagesTestTenants;

abstract class TenantAuthenticationTestCase extends AuthenticationTestCase
{
    use ManagesTestTenants;

    protected function tearDown(): void
    {
        try {
            $this->cleanupTestTenants();
        } finally {
            parent::tearDown();
        }
    }
}
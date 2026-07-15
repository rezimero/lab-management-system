<?php

namespace Tests\Support\Authentication;

use Tests\Support\Authentication\Concerns\ManagesLandlordTestTenants;
use Tests\Support\Authentication\Concerns\ManagesTestLandlordUsers;

abstract class LandlordAuthenticationTestCase extends AuthenticationTestCase
{
    use ManagesLandlordTestTenants;
    use ManagesTestLandlordUsers;

    protected const PASSWORD = 'Password123!';

    protected function tearDown(): void
    {
        try {
            $this->restoreLandlordContext();
            $this->cleanupLandlordTestTenants();
            $this->cleanupTestLandlordUsers();
            $this->clearAuthenticationState();
        } finally {
            parent::tearDown();
        }
    }
}
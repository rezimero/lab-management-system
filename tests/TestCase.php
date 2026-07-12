<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSafeTestingEnvironment();
    }

    /**
     * Prevent automated tests from connecting to the real landlord database.
     */
    private function assertSafeTestingEnvironment(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException(
                'Automated tests may run only in the testing environment.'
            );
        }

        $configuredDatabase = (string) config(
            'database.connections.landlord.database'
        );

        if ($configuredDatabase !== 'lab_saas_landlord_test') {
            throw new RuntimeException(
                sprintf(
                    'Unsafe configured landlord database [%s]. '
                    .'Expected [lab_saas_landlord_test].',
                    $configuredDatabase
                )
            );
        }

        $actualDatabase = DB::connection('landlord')
            ->getDatabaseName();

        if ($actualDatabase !== 'lab_saas_landlord_test') {
            throw new RuntimeException(
                sprintf(
                    'Unsafe active landlord database [%s]. '
                    .'Expected [lab_saas_landlord_test].',
                    $actualDatabase
                )
            );
        }
    }
}
<?php

namespace Tests\Unit;

use App\Services\Tenant\TestResult\CanonicalClinicalPayloadHasher;
use PHPUnit\Framework\TestCase;

class CanonicalClinicalPayloadHasherTest extends TestCase
{
    public function test_associative_keys_are_sorted_recursively(): void
    {
        $hasher = new CanonicalClinicalPayloadHasher;

        $first = [
            'result_uuid' => 'result-1',
            'effective_version' => 2,
            'results_data' => [
                [
                    'value' => '14.2',
                    'name' => 'Haemoglobin',
                    'metadata' => [
                        'zeta' => true,
                        'alpha' => 1.0,
                    ],
                ],
            ],
            'notes' => 'Verified / confirmé',
        ];

        $second = [
            'notes' => 'Verified / confirmé',
            'results_data' => [
                [
                    'metadata' => [
                        'alpha' => 1.0,
                        'zeta' => true,
                    ],
                    'name' => 'Haemoglobin',
                    'value' => '14.2',
                ],
            ],
            'effective_version' => 2,
            'result_uuid' => 'result-1',
        ];

        $this->assertSame(
            $hasher->encode($first),
            $hasher->encode($second)
        );
        $this->assertSame(
            hash('sha256', $hasher->encode($first)),
            hash('sha256', $hasher->encode($second))
        );
        $this->assertStringContainsString(
            'confirmé',
            $hasher->encode($first)
        );
        $this->assertStringContainsString(
            'Verified / confirmé',
            $hasher->encode($first)
        );
        $this->assertStringContainsString(
            '1.0',
            $hasher->encode($first)
        );
    }

    public function test_indexed_array_order_is_preserved(): void
    {
        $hasher = new CanonicalClinicalPayloadHasher;

        $first = $hasher->encode([
            ['name' => 'A'],
            ['name' => 'B'],
        ]);

        $second = $hasher->encode([
            ['name' => 'B'],
            ['name' => 'A'],
        ]);

        $this->assertNotSame($first, $second);
    }
}

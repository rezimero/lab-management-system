<?php

namespace App\Services\Tenant\TestResult;

use App\Models\LaboratoryOrderItem;
use App\Models\TestResult;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TestResultSubmissionValidator
{
    /**
     * Require complete, unique result parameters.
     */
    public function validate(
        TestResult $result,
        LaboratoryOrderItem $item
    ): void {
        $rows = $result->results_data ?? [];

        if ($rows === []) {
            throw ValidationException::withMessages([
                'results_data' => [
                    'At least one result parameter is required before submission.',
                ],
            ]);
        }

        $submittedNames = [];

        foreach ($rows as $index => $row) {
            $name = trim(
                (string) ($row['name'] ?? '')
            );

            $value = trim(
                (string) ($row['value'] ?? '')
            );

            $this->assertNameExists(
                $name,
                $index
            );

            $this->assertValueExists(
                $name,
                $value,
                $index
            );

            $normalizedName = Str::lower($name);

            if (isset($submittedNames[$normalizedName])) {
                throw ValidationException::withMessages([
                    'results_data' => [
                        sprintf(
                            'The result parameter %s was submitted more than once.',
                            $name
                        ),
                    ],
                ]);
            }

            $submittedNames[$normalizedName] = true;
        }

        $this->assertExpectedParametersExist(
            $submittedNames,
            $item->parameters_snapshot ?? []
        );
    }

    /**
     * Require a parameter name.
     */
    private function assertNameExists(
        string $name,
        int $index
    ): void {
        if ($name !== '') {
            return;
        }

        throw ValidationException::withMessages([
            "results_data.$index.name" => [
                'Every result parameter requires a name.',
            ],
        ]);
    }

    /**
     * Require a parameter value.
     */
    private function assertValueExists(
        string $name,
        string $value,
        int $index
    ): void {
        if ($value !== '') {
            return;
        }

        throw ValidationException::withMessages([
            "results_data.$index.value" => [
                sprintf(
                    'A result value is required for %s.',
                    $name
                ),
            ],
        ]);
    }

    /**
     * Require all parameters from the order snapshot.
     *
     * @param array<string, bool> $submittedNames
     * @param array<int, mixed> $parameters
     */
    private function assertExpectedParametersExist(
        array $submittedNames,
        array $parameters
    ): void {
        $expectedParameters = $this->expectedParameters(
            $parameters
        );

        $missingParameters = array_diff_key(
            $expectedParameters,
            $submittedNames
        );

        if ($missingParameters === []) {
            return;
        }

        throw ValidationException::withMessages([
            'results_data' => [
                sprintf(
                    'Result values are missing for: %s.',
                    implode(
                        ', ',
                        array_values($missingParameters)
                    )
                ),
            ],
        ]);
    }

    /**
     * Extract parameter names from the test snapshot.
     *
     * @param array<int, mixed> $parameters
     * @return array<string, string>
     */
    private function expectedParameters(
        array $parameters
    ): array {
        $names = [];

        foreach ($parameters as $parameter) {
            $name = is_array($parameter)
                ? (
                    $parameter['name']
                    ?? $parameter['parameter']
                    ?? null
                )
                : $parameter;

            $displayName = trim(
                (string) $name
            );

            if ($displayName === '') {
                continue;
            }

            $normalizedName = Str::lower(
                $displayName
            );

            $names[$normalizedName] = $displayName;
        }

        return $names;
    }
}

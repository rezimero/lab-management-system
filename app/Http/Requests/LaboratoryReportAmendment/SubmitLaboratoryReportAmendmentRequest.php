<?php

namespace App\Http\Requests\LaboratoryReportAmendment;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubmitLaboratoryReportAmendmentRequest extends FormRequest
{
    /**
     * Determine whether the user may request amendments.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::REQUEST_LABORATORY_REPORT_AMENDMENTS
            );
    }

    /**
     * Normalize amendment text and result rows.
     */
    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->has('reason')) {
            $payload['reason'] = trim(
                (string) $this->input('reason')
            );
        }

        if ($this->has('patient')) {
            $patient = $this->input('patient');

            $payload['patient'] = is_array($patient)
                ? $this->normalizeArray($patient)
                : $patient;
        }

        if ($this->has('order')) {
            $order = $this->input('order');

            if (is_array($order)) {
                $order = $this->normalizeArray($order);

                if (array_key_exists('priority', $order)) {
                    $order['priority'] = strtolower(
                        trim((string) $order['priority'])
                    );
                }
            }

            $payload['order'] = $order;
        }

        if ($this->has('results')) {
            $results = $this->input('results');

            if (is_array($results)) {
                $payload['results'] = array_map(
                    function (mixed $correction): mixed {
                        if (! is_array($correction)) {
                            return $correction;
                        }

                        $correction = $this->normalizeArray(
                            $correction
                        );

                        if (
                            isset($correction['results_data'])
                            && is_array(
                                $correction['results_data']
                            )
                        ) {
                            $correction['results_data'] = (
                                array_map(
                                    fn (mixed $row): mixed => (
                                        is_array($row)
                                            ? $this->normalizeArray(
                                                $row
                                            )
                                            : $row
                                    ),
                                    $correction['results_data']
                                )
                            );
                        }

                        return $correction;
                    },
                    $results
                );
            }
        }

        $this->merge($payload);
    }

    /**
     * Validate the strict correction allowlist.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:5000',
                'regex:/[\\pL\\pN]{3,}/u',
            ],

            'patient' => [
                'sometimes',
                'array:medical_record_number,first_name,last_name,date_of_birth,gender,email,phone,address',
            ],
            'patient.medical_record_number' => [
                'sometimes',
                'string',
                'min:1',
                'max:255',
            ],
            'patient.first_name' => [
                'sometimes',
                'string',
                'min:1',
                'max:255',
            ],
            'patient.last_name' => [
                'sometimes',
                'string',
                'min:1',
                'max:255',
            ],
            'patient.date_of_birth' => [
                'sometimes',
                'nullable',
                'date_format:Y-m-d',
            ],
            'patient.gender' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
            'patient.email' => [
                'sometimes',
                'nullable',
                'email:rfc',
                'max:255',
            ],
            'patient.phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'patient.address' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],

            'order' => [
                'sometimes',
                'array:priority,referring_clinician,clinical_notes,sample_collected_at',
            ],
            'order.priority' => [
                'sometimes',
                'string',
                Rule::in([
                    'routine',
                    'urgent',
                    'stat',
                ]),
            ],
            'order.referring_clinician' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'order.clinical_notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],
            'order.sample_collected_at' => [
                'sometimes',
                'nullable',
                'date',
            ],

            'results' => [
                'sometimes',
                'array',
                'max:100',
            ],
            'results.*' => [
                'array:result_uuid,results_data,notes',
            ],
            'results.*.result_uuid' => [
                'required',
                'uuid',
                'distinct',
            ],
            'results.*.results_data' => [
                'sometimes',
                'array',
                'max:100',
            ],
            'results.*.results_data.*' => [
                'array:name,value,unit,reference_range,flag,notes',
            ],
            'results.*.results_data.*.name' => [
                'required',
                'string',
                'max:255',
            ],
            'results.*.results_data.*.value' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'results.*.results_data.*.unit' => [
                'nullable',
                'string',
                'max:100',
            ],
            'results.*.results_data.*.reference_range' => [
                'nullable',
                'string',
                'max:255',
            ],
            'results.*.results_data.*.flag' => [
                'nullable',
                Rule::in([
                    'normal',
                    'low',
                    'high',
                    'critical',
                    'abnormal',
                ]),
            ],
            'results.*.results_data.*.notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'results.*.notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:10000',
            ],
        ];
    }

    /**
     * Reject unknown top-level keys and empty correction objects.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                $unknown = array_diff(
                    array_keys($this->all()),
                    [
                        'reason',
                        'patient',
                        'order',
                        'results',
                    ]
                );

                foreach ($unknown as $key) {
                    $validator->errors()->add(
                        (string) $key,
                        'This amendment field is not supported.'
                    );
                }

                foreach (
                    (array) $this->input('results', []) as $index => $correction
                ) {
                    if (! is_array($correction)) {
                        continue;
                    }

                    if (
                        ! array_key_exists(
                            'results_data',
                            $correction
                        )
                        && ! array_key_exists(
                            'notes',
                            $correction
                        )
                    ) {
                        $validator->errors()->add(
                            'results.'.$index,
                            'A result correction must change results_data or notes.'
                        );
                    }
                }
            }
        );
    }

    /**
     * Trim string values while preserving null and JSON types.
     *
     * @return array<string, mixed>
     */
    private function normalizeArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        foreach ($value as $key => $item) {
            if (is_string($item)) {
                $normalized = trim($item);
                $value[$key] = $normalized === ''
                    ? null
                    : $normalized;
            }
        }

        return $value;
    }
}

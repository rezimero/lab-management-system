<?php

namespace App\Http\Requests\Analyzer;

use App\Models\AnalyzerInstrument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnalyzerMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('analyzer_instrument')
            instanceof AnalyzerInstrument;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => trim(
                (string) $this->header(
                    'X-Rezhel-Idempotency-Key'
                )
            ),
            'schema' => trim(
                (string) $this->input('schema')
            ),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ((array) $this->input('results', []) as $resultIndex => $result) {
                if (! is_array($result)) {
                    continue;
                }

                foreach ((array) ($result['observations'] ?? []) as $observationIndex => $observation) {
                    if (! is_array($observation)) {
                        continue;
                    }

                    $value = $observation['value'] ?? null;

                    if ($value !== null && ! is_scalar($value)) {
                        $validator->errors()->add(
                            "results.{$resultIndex}.observations.{$observationIndex}.value",
                            'Analyzer observation values must be scalar.'
                        );
                    }
                }
            }
        });
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => [
                'required',
                'string',
                'max:191',
            ],
            'schema' => [
                'required',
                'string',
                Rule::in([
                    'rezhel.analyzer-result.v1',
                ]),
            ],
            'message_id' => [
                'nullable',
                'string',
                'max:191',
            ],
            'sent_at' => [
                'nullable',
                'date',
            ],
            'results' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],
            'results.*.result_key' => [
                'required',
                'string',
                'max:191',
                'distinct',
            ],
            'results.*.sample_id' => [
                'required',
                'string',
                'max:191',
            ],
            'results.*.test_code' => [
                'required',
                'string',
                'max:120',
            ],
            'results.*.observations' => [
                'required',
                'array',
                'min:1',
                'max:200',
            ],
            'results.*.observations.*.code' => [
                'required',
                'string',
                'max:120',
            ],
            'results.*.observations.*.name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'results.*.observations.*.value' => [
                'required',
                Rule::notIn(['']),
            ],
            'results.*.observations.*.unit' => [
                'nullable',
                'string',
                'max:120',
            ],
            'results.*.observations.*.reference_range' => [
                'nullable',
                'string',
                'max:255',
            ],
            'results.*.observations.*.flag' => [
                'nullable',
                'string',
                'max:80',
            ],
            'results.*.observations.*.notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}

<?php

namespace App\Jobs\Tenant\Analyzer;

use App\Models\AnalyzerMessage;
use App\Models\Tenant;
use App\Services\Tenant\Analyzer\AnalyzerMessageProcessor;
use App\Support\Tenant\AnalyzerIssueCatalog;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\Multitenancy\Jobs\TenantAware;
use Throwable;

class ProcessAnalyzerMessage implements ShouldBeUnique, ShouldQueue, TenantAware
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 1800;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $messageId
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function uniqueId(): string
    {
        return 'analyzer-message-'
            .$this->tenantId.'-'.$this->messageId;
    }

    public function handle(
        AnalyzerMessageProcessor $processor
    ): void {
        $tenant = Tenant::current();

        if (
            ! $tenant instanceof Tenant
            || (int) $tenant->getKey() !== $this->tenantId
        ) {
            throw new \LogicException(
                'Analyzer processing tenant context is invalid.'
            );
        }

        $processor->process($this->messageId);
    }

    public function failed(Throwable $exception): void
    {
        $message = AnalyzerMessage::query()
            ->find($this->messageId);

        if (! $message instanceof AnalyzerMessage) {
            return;
        }

        $message->forceFill([
            'status' => AnalyzerMessage::STATUS_FAILED,
            'processed_at' => now(),
            'last_error_code' => (
                AnalyzerIssueCatalog::PROCESSING_FAILED
            ),
            'last_error' => (
                'Analyzer processing exhausted its retry budget. Inspect '
                .'application logs using message UUID '.$message->uuid.'.'
            ),
        ])->save();
    }
}

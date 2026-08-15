<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Landlord\TenantContextRunner;
use App\Services\Tenant\Notification\CriticalResultEscalationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class EscalateCriticalResults extends Command
{
    protected $signature = (
        'notifications:escalate-critical '
        .'{--tenant= : Tenant ID or exact domain}'
    );

    protected $description = (
        'Escalate overdue unacknowledged critical-result records.'
    );

    public function handle(
        TenantContextRunner $runner
    ): int {
        $escalated = 0;

        $this->tenantQuery()->orderBy('id')->each(
            function (Tenant $tenant) use (
                $runner,
                &$escalated
            ): void {
                $escalated += $runner->run(
                    $tenant,
                    static fn (): int => app(
                        CriticalResultEscalationService::class
                    )->escalateDue()
                );
            }
        );

        $this->info(
            'Critical-result records escalated: '.$escalated
        );

        return self::SUCCESS;
    }

    /**
     * @return Builder<Tenant>
     */
    private function tenantQuery(): Builder
    {
        $selector = trim((string) $this->option('tenant'));

        return Tenant::query()
            ->where('is_active', true)
            ->when(
                $selector !== '',
                static function (Builder $query) use ($selector): void {
                    $query->where(function (Builder $inner) use (
                        $selector
                    ): void {
                        if (ctype_digit($selector)) {
                            $inner->whereKey((int) $selector)
                                ->orWhere('domain', $selector);

                            return;
                        }

                        $inner->where('domain', $selector);
                    });
                }
            );
    }
}

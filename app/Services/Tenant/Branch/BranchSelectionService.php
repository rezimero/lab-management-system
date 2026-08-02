<?php

namespace App\Services\Tenant\Branch;

use App\Models\Branch;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Validation\ValidationException;

class BranchSelectionService
{
    /**
     * @return array{collection: Branch, processing: Branch}
     */
    public function resolve(
        TenantUser $user,
        ?string $collectionUuid,
        ?string $processingUuid
    ): array {
        $user->loadMissing('branches');

        $primary = $user->branches
            ->first(
                static fn (Branch $branch): bool => (
                    (bool) $branch->pivot->is_primary
                )
            ) ?? $user->branches->first();

        $collection = $collectionUuid === null
            ? $primary
            : Branch::query()
                ->where('uuid', $collectionUuid)
                ->where('is_active', true)
                ->first();

        if ($collection === null) {
            throw ValidationException::withMessages([
                'collection_branch_uuid' => [
                    'Select an active collection branch assigned to your account.',
                ],
            ]);
        }

        $processing = $processingUuid === null
            ? (
                $collection->type === 'laboratory'
                    ? $collection
                    : $collection->parent
            )
            : Branch::query()
                ->where('uuid', $processingUuid)
                ->where('type', 'laboratory')
                ->where('is_active', true)
                ->first();

        if ($processing === null) {
            throw ValidationException::withMessages([
                'processing_branch_uuid' => [
                    'Select an active laboratory branch for specimen processing.',
                ],
            ]);
        }

        if (! $user->isAdmin()) {
            $assignedIds = $user->branches
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            if (! in_array((int) $collection->getKey(), $assignedIds, true)) {
                throw ValidationException::withMessages([
                    'collection_branch_uuid' => [
                        'The selected collection branch is not assigned to your account.',
                    ],
                ]);
            }

            $processingAllowed = in_array(
                (int) $processing->getKey(),
                $assignedIds,
                true
            ) || (int) $collection->parent_branch_id
                === (int) $processing->getKey();

            if (! $processingAllowed) {
                throw ValidationException::withMessages([
                    'processing_branch_uuid' => [
                        'The selected processing branch is outside your assigned branch network.',
                    ],
                ]);
            }
        }

        return [
            'collection' => $collection,
            'processing' => $processing,
        ];
    }
}

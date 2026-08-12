<?php

namespace App\Http\Resources\Portal;

use App\Models\PortalInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortalAccountAdministrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $invitation = $this->whenLoaded(
            'latestInvitation'
        );

        return [
            'uuid' => $this->uuid,
            'email' => $this->email,
            'is_active' => (bool) $this->is_active,
            'activated_at' => $this->activated_at
                ?->toIso8601String(),
            'last_login_at' => $this->last_login_at
                ?->toIso8601String(),
            'created_at' => $this->created_at
                ?->toIso8601String(),
            'updated_at' => $this->updated_at
                ?->toIso8601String(),
            'invitation' => $invitation instanceof PortalInvitation
                ? [
                    'uuid' => $invitation->uuid,
                    'status' => $this->invitationStatus(
                        $invitation
                    ),
                    'expires_at' => $invitation->expires_at
                        ?->toIso8601String(),
                    'used_at' => $invitation->used_at
                        ?->toIso8601String(),
                    'revoked_at' => $invitation->revoked_at
                        ?->toIso8601String(),
                    'created_at' => $invitation->created_at
                        ?->toIso8601String(),
                ]
                : null,
        ];
    }

    private function invitationStatus(
        PortalInvitation $invitation
    ): string {
        if ($invitation->used_at !== null) {
            return 'used';
        }

        if ($invitation->revoked_at !== null) {
            return 'revoked';
        }

        if ($invitation->expires_at?->isPast()) {
            return 'expired';
        }

        return 'pending';
    }
}

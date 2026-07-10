<?php

namespace App\Domain\Audit\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'eventType' => $this->event_type,
            'entityType' => $this->entity_type,
            'entityId' => $this->entity_id,
            'action' => $this->action,
            'actorUserId' => $this->actor_user_id,
            'actorUser' => $this->whenLoaded('actorUser', function () {
                return [
                    'id' => $this->actorUser->id,
                    'username' => $this->actorUser->username,
                    'email' => $this->actorUser->email,
                ];
            }),
            'actorSubsystemId' => $this->actor_subsystem_id,
            'actorSubsystem' => $this->whenLoaded('actorSubsystem', function () {
                return [
                    'id' => $this->actorSubsystem->id,
                    'nameEn' => $this->actorSubsystem->name_en,
                    'nameAr' => $this->actorSubsystem->name_ar,
                ];
            }),
            'oldValue' => $this->old_value,
            'newValue' => $this->new_value,
            'ipAddress' => $this->ip_address,
            'userAgent' => $this->user_agent,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}

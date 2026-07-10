<?php

namespace App\Domain\Subsystems\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubsystemResource extends JsonResource
{
    /**
     * The plain API key to show once upon creation or regeneration.
     */
    protected ?string $plainApiKey = null;

    public function __construct($resource, ?string $plainApiKey = null)
    {
        parent::__construct($resource);
        $this->plainApiKey = $plainApiKey;
    }

    public function toArray(Request $request): array
    {
        $lang = $request->header('Accept-Language', 'ar');
        $localizedName = str_starts_with($lang, 'en') ? $this->name_en : $this->name_ar;

        return [
            'id' => $this->id,
            'name' => $localizedName,
            'nameEn' => $this->name_en,
            'nameAr' => $this->name_ar,
            'descriptionEn' => $this->description_en,
            'descriptionAr' => $this->description_ar,
            'status' => $this->status?->value,
            'webhookUrl' => $this->webhook_url,
            'contactEmail' => $this->contact_email,
            'apiKey' => $this->when($this->plainApiKey !== null, $this->plainApiKey),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}

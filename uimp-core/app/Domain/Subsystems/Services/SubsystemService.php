<?php

namespace App\Domain\Subsystems\Services;

use App\Domain\BaseService;
use App\Domain\Subsystems\Enums\SubsystemStatus;
use App\Domain\Subsystems\Models\Subsystem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubsystemService extends BaseService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Subsystem::query();

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'ilike', "%{$search}%")
                  ->orWhere('name_ar', 'ilike', "%{$search}%")
                  ->orWhere('contact_email', 'ilike', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $size = min((int) ($filters['size'] ?? 20), 100);
        return $query->orderBy('name_en')->paginate($size);
    }

    public function findOrFail(string $id): Subsystem
    {
        return Subsystem::findOrFail($id);
    }

    /**
     * Create a new subsystem registration and return the plain API key.
     *
     * @return array{subsystem: Subsystem, plainApiKey: string}
     */
    public function create(array $data): array
    {
        $plainKey = 'uimp_' . Str::random(40);
        $hashedKey = hash('sha256', $plainKey);

        $webhookSecret = 'whsec_' . Str::random(32);

        $subsystem = Subsystem::create([
            'name_en' => $data['nameEn'],
            'name_ar' => $data['nameAr'],
            'description_en' => $data['descriptionEn'] ?? null,
            'description_ar' => $data['descriptionAr'] ?? null,
            'api_key_hash' => $hashedKey,
            'status' => $data['status'] ?? SubsystemStatus::ACTIVE->value,
            'webhook_url' => $data['webhookUrl'] ?? null,
            'webhook_secret' => $webhookSecret,
            'contact_email' => $data['contactEmail'],
        ]);

        return [
            'subsystem' => $subsystem,
            'plainApiKey' => $plainKey,
        ];
    }

    public function update(Subsystem $subsystem, array $data): Subsystem
    {
        $updateFields = [];

        $fieldMap = [
            'nameEn' => 'name_en',
            'nameAr' => 'name_ar',
            'descriptionEn' => 'description_en',
            'descriptionAr' => 'description_ar',
            'status' => 'status',
            'webhookUrl' => 'webhook_url',
            'contactEmail' => 'contact_email',
        ];

        foreach ($fieldMap as $camel => $snake) {
            if (array_key_exists($camel, $data)) {
                $updateFields[$snake] = $data[$camel];
            }
        }

        $subsystem->update($updateFields);

        return $subsystem->fresh();
    }

    /**
     * Regenerate the subsystem API key and return the new plain key.
     *
     * @return array{subsystem: Subsystem, plainApiKey: string}
     */
    public function regenerateApiKey(Subsystem $subsystem): array
    {
        $plainKey = 'uimp_' . Str::random(40);
        $hashedKey = hash('sha256', $plainKey);

        $subsystem->update([
            'api_key_hash' => $hashedKey,
        ]);

        return [
            'subsystem' => $subsystem,
            'plainApiKey' => $plainKey,
        ];
    }

    public function delete(Subsystem $subsystem): void
    {
        $subsystem->delete();
    }
}

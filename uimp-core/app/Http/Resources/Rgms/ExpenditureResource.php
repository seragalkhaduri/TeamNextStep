<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ExpenditureResource
 *
 * @property-read \App\Models\BudgetExpenditure $resource
 *
 * SDD Reference: RGMS SDD §3.5.4, §4.2.7
 */
final class ExpenditureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'funding_source_id' => $this->funding_source_id,
            'allocation_id' => $this->allocation_id,
            'category' => $this->category,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'expenditure_date' => $this->expenditure_date?->toDateString(),
            'description' => $this->description,
            'is_reversal' => $this->reference_expenditure_id !== null,
            'reference_expenditure_id' => $this->reference_expenditure_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
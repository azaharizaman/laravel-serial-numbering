<?php

namespace AzahariZaman\ControlledNumber\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SerialSequenceResource extends JsonResource
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
            'name' => $this->name,
            'pattern' => $this->pattern,
            'current_number' => $this->current_number,
            'reset_type' => $this->reset_type->value,
            'reset_type_label' => $this->reset_type->label(),
            'reset_interval' => $this->reset_interval,
            'last_reset_at' => $this->last_reset_at?->toIso8601String(),
            'reset_strategy_class' => $this->reset_strategy_class,
            'reset_strategy_config' => $this->reset_strategy_config,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

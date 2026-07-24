<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewCategoryResource extends JsonResource
{
    use ResolvesLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->translated('label', $locale),
            'description' => $this->description ? $this->translated('description', $locale) : null,
            'default_difficulty' => $this->default_difficulty,
            'stack_focus' => $this->stack_focus,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}

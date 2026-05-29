<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSkillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name'         => $this->skill_name,
            'level'        => $this->level,
            'is_certified' => $this->is_certified,
            'score'        => $this->score,
        ];
    }
}

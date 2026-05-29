<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'slug'              => $this->slug,
            'title'             => $this->title,
            'category'          => $this->category,
            'level'             => $this->level,
            'description'       => $this->description,
            'skills_covered'    => $this->skills_covered,
            'project_brief'     => $this->project_brief,
            'evaluation_criteria' => $this->evaluation_criteria,
            'passing_score'     => $this->passing_score,
            'duration_days'     => $this->duration_days,
            'validity_months'   => $this->validity_months,
            'price_credits'     => $this->price_credits,
            'attempts_allowed'  => $this->attempts_allowed,
            'badge_color'       => $this->badge_color,
        ];
    }
}

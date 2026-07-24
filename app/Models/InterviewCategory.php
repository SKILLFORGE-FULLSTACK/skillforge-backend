<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InterviewCategory extends Model implements Translatable
{
    use HasTranslations, HasUuids;

    protected $fillable = [
        'key',
        'label',
        'label_en',
        'description',
        'description_en',
        'default_difficulty',
        'stack_focus',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function translatableFields(): array
    {
        return [
            'label' => 'label_en',
            'description' => 'description_en',
        ];
    }
}

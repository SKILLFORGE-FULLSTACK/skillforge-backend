<?php

namespace App\Contracts;

interface Translatable
{
    /**
     * @return array<string, string> colonne source (fr) => colonne cible (ex: "title" => "title_en")
     */
    public static function translatableFields(): array;
}

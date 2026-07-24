<?php

namespace App\Models\Concerns;

/**
 * À utiliser sur les modèles qui implémentent App\Contracts\Translatable.
 * Centralise la résolution "quelle colonne afficher pour cette locale" afin
 * que les Resources n'aient pas à dupliquer cette logique champ par champ.
 */
trait HasTranslations
{
    public function translated(string $field, ?string $locale): string
    {
        if ($locale === 'en') {
            $targetField = static::translatableFields()[$field] ?? null;

            if ($targetField && filled($this->{$targetField})) {
                return $this->{$targetField};
            }
        }

        return (string) $this->{$field};
    }
}

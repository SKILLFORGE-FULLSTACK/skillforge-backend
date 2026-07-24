<?php

namespace App\Services\Translation;

interface TranslatorService
{
    /**
     * Traduit un texte vers la langue cible. Retourne null si le service est
     * indisponible ou mal configuré — l'appelant doit décider du repli
     * (généralement : laisser le champ traduit vide et réessayer plus tard).
     */
    public function translate(string $text, string $target, string $source = 'fr'): ?string;
}

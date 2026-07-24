<?php

namespace App\Http\Resources\Concerns;

use Illuminate\Http\Request;

/**
 * Lit la locale d'affichage envoyée par le frontend (header X-Locale, en
 * phase avec le sélecteur de langue de l'interface) pour les Resources qui
 * exposent du contenu bilingue (voir App\Models\Concerns\HasTranslations).
 */
trait ResolvesLocale
{
    protected function resolveLocale(Request $request): string
    {
        $locale = $request->header('X-Locale', 'fr');

        return in_array($locale, ['fr', 'en'], true) ? $locale : 'fr';
    }
}

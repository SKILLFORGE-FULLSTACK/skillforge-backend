<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nécessaire pour l'inscription recruteur via OAuth (Google/GitHub) :
        // le nom de l'entreprise n'est pas toujours disponible à la création
        // du compte et doit pouvoir être complété plus tard.
        Schema::table('recruiter_profiles', function (Blueprint $table) {
            $table->string('company_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('recruiter_profiles', function (Blueprint $table) {
            $table->string('company_name')->nullable(false)->change();
        });
    }
};

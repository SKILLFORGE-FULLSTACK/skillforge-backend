<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colonnes "_en" pour le contenu bilingue FR/EN. Les colonnes existantes
     * (title, description, ...) restent la version française — c'est la
     * langue de rédaction actuelle de tout le contenu en base — et ne sont
     * pas modifiées ici. La version anglaise est remplie automatiquement en
     * tâche de fond (TranslateContentJob) à la création/modification d'une
     * ligne, pas par cette migration : le contenu déjà existant reste tel
     * quel tant qu'il n'est pas recréé/modifié.
     */
    public function up(): void
    {
        Schema::table('certifications', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->text('description_en')->nullable()->after('description');
            $table->text('project_brief_en')->nullable()->after('project_brief');
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->text('description_en')->nullable()->after('description');
        });

        Schema::table('interview_questions', function (Blueprint $table) {
            $table->string('title_en', 500)->nullable()->after('title');
            $table->text('description_en')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('certifications', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'description_en', 'project_brief_en']);
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'description_en']);
        });

        Schema::table('interview_questions', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'description_en']);
        });
    }
};

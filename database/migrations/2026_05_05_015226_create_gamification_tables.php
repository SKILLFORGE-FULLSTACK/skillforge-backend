<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── TRANSACTIONS XP ────────────────────────────────────────────
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('amount');              // peut être négatif
            $table->string('reason', 100);               // "interview_completed", "cert_passed"
            $table->string('reference_type', 50)->nullable(); // "interview_session", "user_badge"
            $table->uuid('reference_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // ─── COMPÉTENCES UTILISATEUR ────────────────────────────────────
        Schema::create('user_skills', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('skill_name', 100);           // "React", "PostgreSQL", "Docker"
            $table->enum('level', [
                'beginner',
                'intermediate',
                'advanced',
                'expert'
            ])->default('beginner');
            $table->boolean('is_certified')->default(false);
            $table->decimal('score', 5, 2)->default(0);
            $table->integer('sessions_count')->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['user_id', 'skill_name']);
            $table->index('user_id');
        });

        // ─── ACTIVITÉ JOURNALIÈRE (heatmap) ────────────────────────────
        Schema::create('daily_activities', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->smallInteger('xp_earned')->default(0);
            $table->smallInteger('sessions_count')->default(0);
            $table->smallInteger('time_spent_min')->default(0);

            $table->unique(['user_id', 'date']);
            $table->index('user_id');
        });

        // ─── LEADERBOARD HEBDOMADAIRE ───────────────────────────────────
        Schema::create('leaderboard_weekly', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->integer('xp_earned')->default(0);
            $table->smallInteger('rank')->nullable();
            $table->string('category', 50)->default('all'); // all, frontend, backend...

            $table->unique(['user_id', 'week_start', 'category']);
            $table->index(['week_start', 'category']);
        });

        // ─── SAVED PROFILES (recruteurs) ────────────────────────────────
        Schema::create('recruiter_saved_profiles', function (Blueprint $table) {
            $table->foreignUuid('recruiter_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignUuid('developer_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('saved_at')->useCurrent();

            $table->primary(['recruiter_id', 'developer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruiter_saved_profiles');
        Schema::dropIfExists('leaderboard_weekly');
        Schema::dropIfExists('daily_activities');
        Schema::dropIfExists('user_skills');
        Schema::dropIfExists('xp_transactions');
    }
};

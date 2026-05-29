<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── USERS ─────────────────────────────────────────────────────
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('name');
            $table->string('username', 100)->unique()->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('password')->nullable();         // null si OAuth only

            // OAuth
            $table->string('github_id', 100)->nullable()->unique();
            $table->string('google_id', 100)->nullable()->unique();
            $table->string('github_username', 100)->nullable();
            $table->text('github_token_enc')->nullable();   // token chiffré

            // Role et plan
            $table->enum('role', ['developer', 'recruiter', 'admin'])->default('developer');
            $table->enum('plan', ['free', 'starter', 'pro', 'expert'])->default('free');
            $table->timestamp('plan_expires_at')->nullable();
            $table->string('stripe_customer_id')->nullable();

            // Gamification
            $table->integer('xp_total')->default(0);
            $table->smallInteger('level')->default(1);
            $table->smallInteger('current_streak')->default(0);
            $table->smallInteger('longest_streak')->default(0);
            $table->date('last_active_date')->nullable();

            // Préférences
            $table->string('timezone', 100)->default('UTC');
            $table->string('locale', 10)->default('fr');
            $table->json('preferences')->nullable();        // dark mode, notifs...
            $table->boolean('is_public')->default(true);
            $table->boolean('is_available')->default(false); // visible recruteurs

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── PASSWORD RESET ─────────────────────────────────────────────
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // ─── SESSIONS ───────────────────────────────────────────────────
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // ─── DEVELOPER PROFILES ─────────────────────────────────────────
        Schema::create('developer_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('headline', 300)->nullable();
            $table->text('bio')->nullable();
            $table->smallInteger('years_experience')->nullable();
            $table->enum('current_level', ['junior', 'mid', 'senior', 'lead'])->default('junior');
            $table->enum('target_level', ['junior', 'mid', 'senior', 'lead'])->nullable();

            // Recherche d'emploi
            $table->integer('target_salary_min')->nullable();
            $table->integer('target_salary_max')->nullable();
            $table->enum('preferred_contract', ['full_time', 'part_time', 'freelance', 'any'])->default('any');
            $table->enum('work_mode', ['remote', 'hybrid', 'onsite', 'any'])->default('any');
            $table->string('location_country', 100)->nullable();
            $table->string('location_city', 100)->nullable();

            // Stats GitHub importées
            $table->json('github_stats')->nullable();

            // Scores calculés
            $table->decimal('overall_score', 6, 2)->default(0);
            $table->decimal('interview_score', 5, 2)->default(0);
            $table->decimal('cert_score', 5, 2)->default(0);
            $table->integer('sessions_count')->default(0);
            $table->smallInteger('certifications_count')->default(0);

            // Liens
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('resume_url')->nullable();

            $table->timestamp('updated_at')->nullable();
        });

        // ─── RECRUITER PROFILES ─────────────────────────────────────────
        Schema::create('recruiter_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('company_name');
            $table->enum('company_size', ['1-10', '11-50', '51-200', '201-1000', '1000+'])->nullable();
            $table->string('company_logo_url')->nullable();
            $table->string('company_website')->nullable();
            $table->string('industry', 100)->nullable();
            $table->smallInteger('positions_open')->default(0);
            $table->integer('credits_remaining')->default(0);
            $table->integer('total_contacts')->default(0);

            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruiter_profiles');
        Schema::dropIfExists('developer_profiles');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};

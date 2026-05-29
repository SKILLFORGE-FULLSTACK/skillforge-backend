<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── OFFRES D'EMPLOI ────────────────────────────────────────────
        Schema::create('job_postings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->json('required_certs')->nullable();    // certifications requises
            $table->json('required_skills')->nullable();
            $table->enum('min_level', ['junior', 'mid', 'senior', 'lead'])->nullable();
            $table->enum('contract_type', ['full_time', 'part_time', 'freelance']);
            $table->enum('work_mode', ['remote', 'hybrid', 'onsite']);
            $table->string('location')->nullable();
            $table->integer('salary_min')->nullable();
            $table->integer('salary_max')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['active', 'paused', 'closed'])->default('active');
            $table->integer('views_count')->default(0);
            $table->integer('applications_count')->default(0);
            $table->timestamp('published_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('recruiter_id');
        });

        // ─── CONTACTS RECRUTEURS → DÉVELOPPEURS ─────────────────────────
        Schema::create('recruiter_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('developer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('job_posting_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->enum('status', [
                'sent',
                'seen',
                'replied',
                'declined',
                'hired'
            ])->default('sent');
            $table->text('message')->nullable();
            $table->smallInteger('credits_spent')->default(1);
            $table->timestamps();

            $table->index(['recruiter_id', 'status']);
            $table->index('developer_id');
        });

        // ─── MESSAGERIE INTERNE ─────────────────────────────────────────
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('contact_id')
                ->nullable()
                ->constrained('recruiter_contacts')
                ->nullOnDelete();
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['receiver_id', 'is_read']);
            $table->index('sender_id');
        });

        // ─── TRANSACTIONS DE CRÉDITS ─────────────────────────────────────
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');                   // positif = achat, négatif = dépense
            $table->string('reason', 100);               // "pack_purchase", "contact_sent"
            $table->string('stripe_payment_id')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('recruiter_contacts');
        Schema::dropIfExists('job_postings');
    }
};

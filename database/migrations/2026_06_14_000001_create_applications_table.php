<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            // Bot's application.id — idempotency key. Nullable: manual entries have none.
            $table->unsignedBigInteger('external_id')->nullable()->unique();
            // Branch may be unresolved on intake (admin triages); manual entries set it.
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('vacancy_id')->nullable()->constrained('vacancies')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('vacancy_title')->nullable();   // raw title string from the bot
            $table->string('source', 32)->nullable();      // telegram / email / somon / manual
            $table->text('summary')->nullable();
            $table->json('survey')->nullable();
            $table->string('resume_path')->nullable();
            $table->string('resume_filename')->nullable();
            $table->timestamp('source_created_at')->nullable(); // created_at from the bot
            $table->timestamps();
            $table->softDeletes();
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};

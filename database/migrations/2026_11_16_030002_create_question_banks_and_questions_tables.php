<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['pg', 'pg_kompleks', 'benar_salah', 'uraian']);
            $table->longText('question_text');
            $table->string('media_path')->nullable();
            $table->json('options')->nullable();
            $table->enum('scoring_mode', ['full', 'partial'])->default('full');
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->unsignedInteger('order_no')->default(0);
            $table->timestamps();

            $table->index(['question_bank_id', 'order_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
        Schema::dropIfExists('question_banks');
    }
};

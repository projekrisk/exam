<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->text('revision_note')->nullable()->after('is_ready');
            $table->dateTime('revision_requested_at')->nullable()->after('revision_note');
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropColumn(['revision_note', 'revision_requested_at']);
        });
    }
};

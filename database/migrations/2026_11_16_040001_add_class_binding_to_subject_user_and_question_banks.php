<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_user', function (Blueprint $table) {
            $table->string('kelas', 64)->nullable()->after('user_id')->index();
            $table->unique(['subject_id', 'user_id', 'kelas']);
            $table->dropUnique(['subject_id', 'user_id']);
        });

        Schema::table('question_banks', function (Blueprint $table) {
            $table->json('target_classes')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropColumn('target_classes');
        });

        Schema::table('subject_user', function (Blueprint $table) {
            $table->dropUnique(['subject_id', 'user_id', 'kelas']);
            $table->dropColumn('kelas');
            $table->unique(['subject_id', 'user_id']);
        });
    }
};

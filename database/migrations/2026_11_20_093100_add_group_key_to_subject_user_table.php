<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_user', function (Blueprint $table) {
            $table->unsignedSmallInteger('group_key')->default(1)->after('kelas');
            $table->dropUnique(['subject_id', 'user_id', 'kelas']);
            $table->unique(['subject_id', 'user_id', 'kelas', 'group_key'], 'subject_user_unique_group');
        });
    }

    public function down(): void
    {
        Schema::table('subject_user', function (Blueprint $table) {
            $table->dropUnique('subject_user_unique_group');
            $table->dropColumn('group_key');
            $table->unique(['subject_id', 'user_id', 'kelas']);
        });
    }
};

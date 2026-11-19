<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedTinyInteger('token_refresh_minutes')->nullable()->after('require_token');
            $table->dateTime('token_updated_at')->nullable()->after('token_refresh_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['token_refresh_minutes', 'token_updated_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'seat_number')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedSmallInteger('seat_number')->nullable()->after('ruang');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'seat_number')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('seat_number');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('email');
            $table->index('role');
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->boolean('is_private')->default(false)->after('user_id');
            $table->index('is_private');
        });

        DB::table('users')
            ->where('email', 'admin@example.com')
            ->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropIndex(['is_private']);
            $table->dropColumn('is_private');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};

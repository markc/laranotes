<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body')->nullable();
            $table->boolean('is_private')->default(false);
            $table->timestamps();

            $table->index(['folder_id', 'updated_at']);
            $table->index('is_private');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};

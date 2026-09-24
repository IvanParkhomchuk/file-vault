<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stored_files', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable();
            $table->string('deletion_source')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stored_files', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'deletion_source']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stored_files', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('disk');
            $table->string('path', 1024);
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamp('uploaded_at');
            $table->timestamp('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stored_files');
    }
};

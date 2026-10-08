<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parsed_files', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_name');
            $table->string('format'); // e.g. STL, 3MF
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->string('status')->default('completed'); // pending, processing, completed, failed
            $table->json('metadata')->nullable(); // bounding_box, volume, triangles, etc.
            $table->timestamp('parsed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parsed_files');
    }
};

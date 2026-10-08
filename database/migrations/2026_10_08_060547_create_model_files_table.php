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
        Schema::create('model_files', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('storage_path');
            $table->string('extension');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size');
            $table->string('status')->default('pending');

            $table->unsignedBigInteger('vertex_count')->nullable();
            $table->unsignedBigInteger('triangle_count')->nullable();

            $table->double('volume')->nullable();
            $table->double('surface_area')->nullable();

            $table->double('width')->nullable();
            $table->double('height')->nullable();
            $table->double('depth')->nullable();

            $table->double('center_x')->nullable();
            $table->double('center_y')->nullable();
            $table->double('center_z')->nullable();

            $table->double('min_x')->nullable();
            $table->double('min_y')->nullable();
            $table->double('min_z')->nullable();

            $table->double('max_x')->nullable();
            $table->double('max_y')->nullable();
            $table->double('max_z')->nullable();

            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('parsed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('extension');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_files');
    }
};

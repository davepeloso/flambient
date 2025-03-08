<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flambient_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('total_size')->default(0);
            $table->integer('image_count')->default(0);
            $table->string('status', 50)->default('pending');
            $table->timestamps();
            $table->timestamp('expires_at')->nullable();
        });

        Schema::create('flambient_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('batch_id');
            $table->uuid('user_id')->nullable();
            $table->string('filename');
            $table->bigInteger('file_size');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->timestamps();
            
            $table->foreign('batch_id')
                  ->references('id')
                  ->on('flambient_batches')
                  ->onDelete('cascade');
        });

        Schema::create('flambient_image_metadata', function (Blueprint $table) {
            $table->uuid('image_id')->primary();
            $table->string('flash_status')->nullable();
            $table->string('exposure_mode')->nullable();
            $table->string('white_balance')->nullable();
            $table->integer('iso')->nullable();
            $table->decimal('exposure_time', 10, 6)->nullable();
            $table->decimal('aperture', 5, 2)->nullable();
            $table->string('focal_length')->nullable();
            $table->json('additional_metadata')->nullable();
            $table->string('tag', 50)->nullable();
            $table->boolean('ignore')->default(false);
            $table->timestamps();

            $table->foreign('image_id')
                  ->references('id')
                  ->on('flambient_images')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flambient_image_metadata');
        Schema::dropIfExists('flambient_images');
        Schema::dropIfExists('flambient_batches');
    }
};

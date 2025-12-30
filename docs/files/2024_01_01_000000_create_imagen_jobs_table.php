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
        Schema::create('imagen_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('project_uuid')->unique()->nullable(); // Imagen's UUID
            $table->string('project_name');
            $table->string('input_directory');
            $table->string('output_directory');
            
            // Profile and options
            $table->string('profile_key');
            $table->string('photography_type')->nullable();
            $table->json('edit_options'); // ImagenEditOptions as JSON
            
            // Status tracking
            $table->string('status')->default('pending'); // pending, uploading, processing, exporting, downloading, completed, failed
            $table->unsignedInteger('progress')->default(0); // 0-100
            $table->text('error_message')->nullable();
            
            // File tracking
            $table->unsignedInteger('total_files')->default(0);
            $table->unsignedInteger('uploaded_files')->default(0);
            $table->unsignedInteger('downloaded_files')->default(0);
            $table->json('failed_uploads')->nullable(); // Array of filenames that failed
            $table->json('file_manifest')->nullable(); // All files to process
            
            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('upload_completed_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Metadata
            $table->string('source_type')->default('manual'); // manual, shortcut, flambient, product
            $table->uuid('parent_job_id')->nullable(); // For multi-pass workflows
            $table->json('metadata')->nullable(); // Any additional data
            
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('project_name');
            $table->index('created_at');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imagen_jobs');
    }
};

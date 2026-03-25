<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmissionDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admission_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->onDelete('cascade');
            
            // Document Information
            $table->enum('type', [
                'transfer_certificate',
                'birth_certificate',
                'photo',
                'mark_sheet',
                'character_certificate',
                'migration_certificate',
                'other'
            ]);
            
            $table->string('name');
            $table->string('file_path');
            $table->string('file_type');
            $table->integer('file_size'); // in bytes
            $table->text('description')->nullable();
            
            // Review Information
            $table->boolean('is_approved')->default(false);
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['admission_id', 'type', 'is_approved']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admission_documents');
    }
}

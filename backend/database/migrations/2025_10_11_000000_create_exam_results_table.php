<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExamResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('exam_results')) {
            return;
        }
        
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            
            // Result details
            $table->decimal('obtained_marks', 8, 2)->default(0);
            $table->string('grade', 10)->nullable();
            $table->decimal('grade_point', 4, 2)->nullable();
            $table->text('remarks')->nullable();
            
            // Status
            $table->enum('status', [
                'pending', 'passed', 'failed', 'absent', 'malpractice'
            ])->default('pending');
            
            // Submission details
            $table->foreignId('submitted_by')->nullable()->constrained('staff')->onDelete('set null');
            $table->timestamp('submitted_at')->nullable();
            
            // Review details
            $table->foreignId('reviewed_by')->nullable()->constrained('staff')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_remarks')->nullable();
            
            // Publication details
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('staff')->onDelete('set null');
            $table->text('publish_remarks')->nullable();
            
            // Unpublish details
            $table->timestamp('unpublished_at')->nullable();
            $table->foreignId('unpublished_by')->nullable()->constrained('staff')->onDelete('set null');
            $table->text('unpublish_remarks')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['exam_id', 'student_id']);
            $table->index('status');
            $table->index('is_published');
        });
        
        // Add fulltext index for search
        DB::statement('ALTER TABLE exam_results ADD FULLTEXT fulltext_index (remarks, review_remarks)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('exam_results')) {
            Schema::table('exam_results', function (Blueprint $table) {
                $table->dropForeign(['exam_id']);
                $table->dropForeign(['student_id']);
                $table->dropForeign(['submitted_by']);
                $table->dropForeign(['reviewed_by']);
                $table->dropForeign(['published_by']);
                $table->dropForeign(['unpublished_by']);
            });
            
            Schema::dropIfExists('exam_results');
        }
    }
}

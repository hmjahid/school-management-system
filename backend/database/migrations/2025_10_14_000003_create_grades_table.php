<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGradesTable extends Migration
{
    public function up()
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained();
            $table->foreignId('class_id')->constrained('classes');
            $table->foreignId('subject_id')->constrained();
            $table->foreignId('exam_id')->nullable()->constrained();
            $table->decimal('marks_obtained', 8, 2);
            $table->decimal('total_marks', 8, 2);
            $table->string('grade');
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->unique(['student_id', 'class_id', 'subject_id', 'exam_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('grades');
    }
}

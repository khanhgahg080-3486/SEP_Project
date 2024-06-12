<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatekafaExamResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kafa_exam_results', function (Blueprint $table) {
            $table->bigInteger('id')->autoIncrement();
            $table->bigInteger('kafa_exam_id')->unsigned();
            $table->foreign('kafa_exam_id')->references('id')->on('kafa_exams')->onUpdate('cascade');
            $table->bigInteger('student_id')->unsigned();
            $table->foreign('student_id')->references('id')->on('students')->onUpdate('cascade');
            $table->tinyInteger('attempt');
            $table->char('result', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kafa_exam_results');
    }
}

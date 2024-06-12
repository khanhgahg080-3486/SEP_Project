<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddkafaExamPassToStudentEnrolls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_enrolls', function (Blueprint $table) {
            $table->tinyInteger('kafa_exam_modules')->nullable();
            $table->tinyInteger('kafa_exam_pass')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('student_enrolls', function (Blueprint $table) {
            //
        });
    }
}

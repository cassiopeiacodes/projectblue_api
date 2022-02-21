<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProjectTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_task', function (Blueprint $table) {
            $table->bigIncrements("project_task_id");
            $table->unsignedInteger("project_id");
            $table->foreign("project_id")
                ->references("project_id")
                ->on("project")
                ->onDelete('CASCADE');
            $table->string('task')->default('-');
//            $table->enum("status",["idle","processed","on hold","finish","cancel"])->default('idle');
            $table->boolean("is_active")->default(1);
            $table->dateTimeTz('end_at')->nullable();
            $table->softDeletes();
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
        $table->dropForeign(['pick_detail_id']);
        Schema::dropIfExists('project_task');
    }
}

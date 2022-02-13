<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProjectActivityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_activity', function (Blueprint $table) {
            $table->bigIncrements("project_activity_id");
//            $table->primary("project_activity_id");
//            $table->index("project_id");
            $table->unsignedInteger("project_id");
            $table->foreign("project_id")
                ->references("project_id")
                ->on("project_all")
                ->onDelete('CASCADE');
            $table->string('action')->default('Create Project');
            $table->enum("status",["idle","processed","on hold","finish","cancel"])->default('idle');
            $table->json("evidence")->nullable();
            $table->boolean("active_status")->default(1);
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
        Schema::dropIfExists('project_activity');
    }
}

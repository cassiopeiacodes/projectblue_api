<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LatestTaskView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE OR REPLACE VIEW t_latest_task AS
                        SELECT *
                        FROM (
                            SELECT project_id, task, created_at, end_at, project_task_id, rank() over ( partition by project_id order by created_at desc ) as rk
                            FROM project_task
                            WHERE deleted_at is null
                            AND is_active = 1) AS pt
                        WHERE pt.rk = 1");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW t_latest_task");
    }
}

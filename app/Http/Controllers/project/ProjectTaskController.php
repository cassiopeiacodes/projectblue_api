<?php

namespace App\Http\Controllers\project;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use App\Models\Project\Project;
use App\Models\Project\ProjectTask;


class ProjectTaskController extends Controller
{
    public function __construct()
    {}

    public function index( Request $request )
    {
        // datatables parameter
        $param["draw"]      = $request->input("draw") ?? "0";
        $param["length"]    = $request->input("length") ?? "10";


        $data = ProjectActivity::where("project_task.is_active",1);
        $data->leftJoin("project","project_task.project_id","=","project_all.project_id");
        $data->orderBy("project_activity.project_activity_id");
        $data->select(
            "project_all.project_id",
            "project_activity.project_activity_id",
            "project_activity.action as activity_action",
            "project_activity.status as activity_status",
            "project_all.name as project_name",
            "project_all.desc as project_desc"
        );
        $data->whereNull("project_all.deleted_at");
        $data->whereNull("project_activity.deleted_at");

        $result = $data;
        $result->limit($param["length"]);

        $oRes = [
            "draw"              => $param["draw"],
            "data"              => $result->get(),
            "recordsTotal"      => $data->count(),
            "recordsFiltered"   => $result->get()->count(),
        ];

        return response()->json($oRes);
    }

}

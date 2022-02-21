<?php

namespace App\Http\Controllers\project;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use App\Models\Project\Project;
use App\Models\Project\ProjectTask;

use Exception;


class ProjectController extends Controller
{
    private static $dProgressTask = 'Creating project';

    public function __construct()
    {}

    public function index( Request $request )
    {
        $param["draw"]      = $request->input("draw") ?? "0";
        $param["length"]    = $request->input("length") ?? "10";

        $order              = $request->input("order") ?? [["column" => "1", "dir" => "ASC"]];
        $order              = $order[0];

        $dtStart            = $request->input("pDateStart") ?? date("Y-m-d",strtotime('-30 days'));
        $dtEnd              = $request->input("pDateRange") ?? date('Y-m-d');

        $status             = $request->input("pStatus") ?? "";
        $status             = in_array($status, ['process','on hold','finish','cancel', 'idle']) ? $status : "";

        $offset             = $request->input("start") ?? 0;

        $search["project"]  = $request->input("pProject") ?? "";

        $colSel = $colOrder = [
            "project.project_id",
            "project.project_name",
            "project.project_desc",
            "project.created_at",
            "project.status",
            "project_task.task",
        ];
        unset($colOrder[0]);

        $orderKey    = $order["column"] ?? 1 ;
        $orderBy     = array_key_exists($orderKey, $colOrder) ? $colOrder[$orderKey] : $colOrder[1];
        $orderDir    = in_array( strtolower($order["dir"]), ["asc", "desc"]) ? $order["dir"] : "asc";

        $data = Project::select(
            $colSel
        );
        $data->leftJoin('t_latest_task as project_task', 'project_task.project_id','=','project.project_id');
        $data->where(function($query) use ($dtStart,$dtEnd){
            $query->where('project.created_at', '>=', $dtStart);
            $query->orWhere('project.created_at', '<=', $dtEnd);
        });
        if(!empty($status)) $data->where("status",$status);
        if(!empty($search["project"])) $data->where("project_name","like","%$search[project]%");
        $data->orderBy($orderBy, $orderDir);
        $total = $data->count();

        $data->skip($offset);
        $data->take($param["length"]);
        $result = $data->get();

        $oRes = [
            "draw"              => $param["draw"],
            "data"              => $result,
            "recordsTotal"      => $total,
            "recordsFiltered"   => $total,
        ];

        return response()->json($oRes);
    }

    public function create(Request  $request, $id = 0) {
        DB::beginTransaction();
        try {
            if( $id > 0 ) {
                $check = Project::find($id)->select("status")->first();

                if($check->status != "idle") {
                    throw new Exception("This project cannot be edited because it has already been processed.");
                }
            }

            $this->validate($request, [
                "iProjectName"      => "required",
                "iProjectDesc"      => "required",
            ]);

            $data = [];
            $data['project_name']   = $request->input('iProjectName') ?? '';
            $data['project_desc']   = $request->input('iProjectDesc') ?? '';
            $data['status']         = $request->input('iStatus') ?? 'idle';
            $res = Project::updateOrCreate(["project_id" => $id], $data);

            $res2 = ProjectTask::firstOrCreate(
                [
                    "project_id" => $res->project_id
                ], [
                    "task"          => self::$dProgressTask,
                    "is_active"     => 1,
                    "project_id"    => $res->project_id,
                    "end_at"        => date("Y-m-d")
                ]
            );

            DB::commit();

            if(!$res2) throwException(["Failed to create data", 0]);
            return response()->json([
                "message"   => $id > 0 ? "Data saved" : "Data created",
                "status"    => $res
            ]);
        } catch (\Exception $err) {
            DB::rollBack();
            return response()->json(
                [
                    "message"   => $err->getMessage(),
                    "code"      => $err->getCode(),
                    "status"    => false
                ],
                404
            );
        }
    }

    public function setClosing( Request $request ) {
        DB::beginTransaction();
        try {
            $this->validate($request, [
                "iProject"     => "required",
                "iAction"      => [
                    "required",
                    function( $attribute, $val, $fail ) {
                        if( !in_array($val, ["finish","cancel","pending"])) {
                            $fail("The $attribute value not allowed.");
                        }
                    }
                ],
                "iStatus"   => [
                    "required",
                    function( $attribute, $val, $fail ) {
                        if( in_array($val, ["finish","cancel"])) {
                            $fail("Your project already been canceled / finished.");
                        }
                    }
                ]
            ]);

            $project_id = $request->input("iProject") ?? 0;
            $action     = $request->input("iAction") == "pending" ? "on hold" : $request->input("iAction");


            $res = Project::findOrFail($project_id);
            $res->status = $action;
            $res->save();

            $st = $action == "pending" ? "pending" : $action;
            $res2 = ProjectTask::create([
                    "task"          => "This project has been put to state ${st}ed.",
                    "project_id"    => $res->project_id,
                    "end_at"        => date("Y-m-d")
            ]);

            DB::commit();
            if(!$res2) throwException(["Failed to create data", 0]);
            return response()->json([
                "message"   => "Data has ben $action",
                "status"    => $res
            ]);
        } catch (\Exception $err) {
            DB::rollBack();
            return response()->json(
                [
                    "message"   => $err->getMessage(),
                    "code"      => $err->getCode(),
                    "status"    => false
                ],
                404
            );
        }
    }
    public function delete( Request $request )
    {
        DB::beginTransaction();
        try {
            $this->validate($request, [
                "data" => "required",
            ]);

            $project_id = $request->input("data");

            $obj = Project::findOrFail($project_id);
            $obj->delete();
            DB::commit();
            return response()->json(['message' => 'Data deleted successfully'], 200);
        } catch (\Exception $err) {
            DB::rollBack();
            return response()->json(
                [
                    "message"   => $err->getMessage(),
                    "code"      => $err->getCode(),
                    "status"    => false
                ],
                404
            );
        }
    }

    public function getProgress( Request $request, $id ) {
        $param["draw"]      = $request->input("draw") ?? "0";
        $param["length"]    = $request->input("length") ?? "10";

        $order              = $request->input("order") ?? [["column" => "1", "dir" => "ASC"]];
        $order              = $order[0];

        $offset             = $request->input("start") ?? 0;

        $colSel = $colOrder = [
            "project_task_id",
            'project_id',
            "task",
            "created_at",
            "end_at"
        ];
        unset($colOrder[0], $colOrder[1]);

        $orderKey    = $order["column"] ?? 2 ;
        $orderBy     = array_key_exists($orderKey, $colOrder) ? $colOrder[$orderKey] : $colOrder[2];
        $orderDir    = in_array( strtolower($order["dir"]), ["asc", "desc"]) ? $order["dir"] : "asc";

        $data = ProjectTask::select($colSel);
        $data->where("project_id",$id);
        $data->where("is_active",1);
        $data->where("task","!=" ,self::$dProgressTask);
        $data->orderBy($orderBy, $orderDir);
        $total = $data->count();

        $data = ProjectTask::select($colSel);
        $data->where("project_id",$id);
        $data->where("is_active",1);
        $data->where("task","!=" ,self::$dProgressTask);
        $data->orderBy($orderBy, $orderDir);
        $result = $data->get();

        $oRes = [
            "draw"              => $param["draw"],
            "data"              => $result,
            "recordsTotal"      => $total,
            "recordsFiltered"   => $total,
        ];

        return response()->json($oRes);
    }
    public function getGranttProgress( Request $request, $id ) {
        $data = ProjectTask::select([
            "project_task_id",
            'project_id',
            "task",
            "created_at",
            "end_at",
        ]);
        $data->where("project_id",$id);
        $data->orderBy("created_at", "asc");
        return response()->json($data->get());
    }

    public function setProgress( Request $request, $project ) {
        DB::beginTransaction();
        try {
            $this->validate($request, [
                "iTask" => [
                    "required",
                    function( $attribute, $val, $fail) {
                        if($val === self::$dProgressTask) {
                            $fail("The $attribute value not allowed.");
                        }
                    }
                ],
                "iStatus" => [
                    "required",
                    function( $attribute, $val, $fail) {
                        if( !in_array($val, ["idle","process","on hold"])) {
                            $fail("The $attribute value not allowed.");
                        }
                    }
                ]
            ]);

            $data = [];
            $data['task']       = $request->input('iTask') ?? '~ no action ~';
            $data['is_active']  = 1;
            $data['project_id'] = $project ?? 0;
            $res = ProjectTask::create($data);

            $res2 = Project::findOrFail($project);
            $res2->status = 'process';
            $res2->save();

            DB::commit();
            return response()->json([
                "message"   => "Data created",
                "status"    => $res2
            ]);
        } catch (\Exception $err) {
            return response()->json(
                [
                    "status"    => false,
                    "message" => $err->getMessage(),
                    "code" => $err->getCode()
                ],
                404
            );
            DB::rollBack();
        }
    }

    public function updateProgress( Request $request, $project, $activity = 0 ) {
        DB::beginTransaction();
        try {
            $this->validate($request, [ "iTask" => "required", ]);

            $data = [];
            $data['task']       = $request->input('iTask') ?? '~ no action ~';
            $data['status']     = 'processed';
            $data['project_id'] = $project ?? 0;
            $res = ProjectTask::updateOrCreate(["project_task_id" => $activity], $data);

            DB::commit();

            if(!$res) throwException(["Failed to create data", 0]);
            return response()->json([
                "message"   => $activity > 0 ? "Data saved" : "Data created",
                "status"    => $res
            ]);
        } catch (\Exception $err) {
            return response()->json(
                [
                    "status"    => false,
                    "message" => $err->getMessage(),
                    "code" => $err->getCode()
                ],
                404
            );
            DB::rollBack();
        }
    }

    public function deleteProgress( Request $request )
    {
        try {
            DB::beginTransaction();
            $this->validate($request, [
                "iProject" => "required",
            ]);

            $task_id = $request->input("iProject");

            $temp = ProjectTask::whereIn("project_task_id",$task_id)
                ->where("task","!=",self::$dProgressTask)
                ->get();

            if( count($temp) == 0 ) throw new Exception("No data");

            foreach ($temp as $item) {
                $res  = ProjectTask::destroy($item->project_task_id);
                $res2 = ProjectTask::where("project_id", $item->project_id)
                    ->where("task","!=",self::$dProgressTask)
                    ->count();
                if( $res2 == 0 ) {
                    $res3 = Project::where("project_id", "=",$item->project_id)
                        ->update(["status" => "idle"]);
                    if(!$res3) throw new Exception("Failed to processing request [ code 3 ].", 0);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Data deleted successfully'], 200);
        } catch (\Exception $err) {
            DB::rollBack();
            return response()->json(
                [
                    "message"   => $err->getMessage(),
                    "code"      => $err->getCode(),
                    "status"    => false
                ],
                404
            );
        }
    }

    public function getSummary() {
        $res = [];
        $data = Project::select([
            DB::raw("count( case when status = 'idle' then 1 end ) as tot_idle"),
            DB::raw("count( case when status = 'process' then 1 end ) as tot_process"),
            DB::raw("count( case when status = 'on hold' then 1 end ) as tot_on_hold"),
            DB::raw("count( case when status = 'finish' then 1 end ) as tot_finish"),
            DB::raw("count( case when status = 'cancel' then 1 end ) as tot_cancel"),
            DB::raw("count(1) as tot")
        ]);
        $res["summary"] = $data->first();

        $data->where( DB::raw("date(updated_at)"),">=",date("Y-m-d", strtotime("first day of previous month")));
        $data->orWhere( DB::raw("date(updated_at)"),"<=",date("Y-m-d", strtotime("last day of previous month")));
        $res["monthly"] = $data->first();

        $data = ProjectTask::select(
            "task",
            "project_name",
            "status",
            DB::raw("project_task.updated_at as latest_update")
        );
        $data->leftJoin("project", "project_task.project_id","=","project.project_id");
        $data->orderBy("project_task.updated_at");
        $data->whereNull("project.deleted_at");
        $data->take(5);
        $res["latest"] = $data->get();

        return response()->json($res);
    }
}

<?php

namespace App\Http\Controllers\project;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use App\Models\Project\ProjectAll;
use App\Models\Project\ProjectActivity;


class ActivityController extends Controller
{
    public function __construct() { }

    public function index() {
        $data = ProjectAll::with("activity")->get();
        return response()->json($data);
    }

    public function create( Request $request, $id = "" ) {
        DB::beginTransaction();
        try {
            $this->validate($request, [
                "name"          => "required",
                "desc"          => "required",
            ]);

            $data = $request->all();
            $res = ProjectAll::updateOrCreate(["project_id" => $id],$data);
            $res2 = ProjectActivity::firstOrCreate(
                [
                    "project_id" => $res->project_id
                ], [
                    "action"        => "initial project",
                    "active_status" => 0,
                    "status"        => "idle",
                    "project_id"    => $res->project_id
                ]
            );

            DB::commit();
            return response()->json($res2);
        } catch (\Exception $err) {
            return response()->json(
                [
                    "message"   => $err->getMessage(),
                    "code"      => $err->getCode()
                ],
                404
            );
            DB::rollBack();
        }
    }

    public function update( Request $request, $id ) {
        DB::beginTransaction();
        try {
            $project_id = ProjectAll::findOrFail($id);

            $this->validate($request, [
                "action"        => "required",
                "status"        => "required"
            ]);

            $data = $request->all();

            $res2 = ProjectActivity::where("project_id", $id)->update(["active_status" => 1]);

            $res = ProjectActivity::create([
                "action"        => isset($data['action']) ? $data["action"] : "",
                "active_status" => 0,
                "status"        => isset($data["status"]) ? $data["status"] : "",
                "evidence"      => isset($data["evidence"]) ? $data["evidence"] : "",
                "project_id"    => $id
            ]);
            DB::commit();
            return response()->json($res);
        } catch (\Exception $err) {
            return response()->json(
                $err,
                404
            );
            DB::rollBack();
        }
    }

    public function project( Request $request, $project_activity_id = 0, $project_id) {
        DB::beginTransaction();
        try {
            if($project_activity_id == 0) {
                $res = ProjectAll::findOrFail($project_id);
                $project_id = $res->project_id;
            }

            $this->validate($request, [
                "action"        => "required",
                "status"        => "required"
            ]);

            $res2 = ProjectActivity::where("project_id", $project_id)->update(["active_status" => 1]);

            $data = $request->all();
            $res = ProjectActivity::updateOrCreate([
                "project_id"            => $project_id,
                "project_activity_id"   => $project_activity_id
            ],[
                "action"        => isset($data['action']) ?? $data["action"] : "",
                "active_status" => 0,
                "status"        => isset($data["status"]) ? $data["status"] : "",
                "evidence"      => isset($data["evidence"]) ? $data["evidence"] : "",
                "project_id"    => $project_id
            ]);
            DB::commit();
            return response()->json($res);
        } catch (\Exception $err) {
            return response()->json(
                $err,
                404
            );
            DB::rollBack();
        }
    }

    public function delete($mode="project", $id) {
        DB::beginTransaction();
        try {

            if(!in_array($mode, ["project","activity"]))
                throw new \Exception("Invalid mode", 0);

            $obj = $mode == "project" ? ProjectAll::findOrFail($id) : ProjectActivity::findOrFail($id);
            $obj->delete();
            DB::commit();
            return response()->json(['message' => 'Data deleted successfully'], 200);
        } catch (\Exception $err) {
            return response()->json([
                "message"   => $err->getMessage(),
                "code" => $err->getCode()
            ], 404 );
            DB::rollBack();
        }
    }
}

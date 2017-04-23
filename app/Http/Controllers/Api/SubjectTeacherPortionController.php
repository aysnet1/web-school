<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Carbon\Carbon;

class SubjectTeacherPortionController extends Controller
{
    private $table = 'subject_teacher_portion';

    public function index(Request $request)
    {
        $conds = [];
        if($request->input('st')){
            $conds['subject_teacher_id'] = $request->input('st');
        }
        if($request->input('portion')){
            $conds['portion_id'] = $request->input('portion');
        }

        $res = DB::table($this->table)
        ->join('portions', 'portions.id', 'portion_id')
        ->select($this->table . '.id', 'portions.portion', 'percentage')
        ->where($conds)->get();
        return response()->json($res);
    }

    public function store(Request $request)
    {

        $user = $request->user();
        $subTeaId = $request->input('st');
        $teacher = DB::table('subject_teacher')
            ->where('subject_teacher.id', $subTeaId)->first();

        if($user->isInRole(['admin']) || $teacher->teacher_id === $user->id){
            $instance = DB::table($this->table)
            ->where('subject_teacher_id', $subTeaId)
            ->where('portion_id', $request->input('portion'))
            ->where('percentage', $request->input('percentage'))
            ->first();
            if (isset($instance->id)){
                return $instance;
            }
            $vals = [
                'subject_teacher_id' => $subTeaId,
                'portion_id' => $request->input('portion'),
                'percentage' => $request->input('percentage'),
                'created_at' => new Carbon,
                'updated_at' => new Carbon
            ];
            try {
                $id = DB::table($this->table)->insertGetId($vals);
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json(["status" => "Already Exists"], 500);
            }


            $stp = DB::table($this->table)->where('id', $id)->first();

            return response()->json($stp);
        }
        return response()->json(["status"=>"Unauthorized"], 403);
    }

    public function update(Request $request, $id)
    {
        $subTeaId = $request->input('st');
        $teacher = DB::table($this->table)
            ->join('subject_teacher', 'subject_teacher.id', 'subject_teacher_portion.subject_teacher_id')
            ->join('teachers', 'subject_teacher.teacher_id', 'teachers.id')
            ->select('teachers.*')
            ->where('subject_teacher.id', $subTeaId)->first();
        $user = $request->user();
        if($user->isInRole('admin') || $user->id === $teacher->user_id){
            $vals = [
                'subject_teacher_id' => $subTeaId,
                'portion_id' => $request->input('portion'),
                'percentage' => $request->input('percentage'),
            ];

            DB::table($this->table)->where('id', $id)->update($vals);
            $res = DB::table($this->table)->where('id', $id)->first();

            return response()->json($res);
        }
        return response()->json(["status"=>"Unauthorized"], 403);
    }

    public function delete(Request $request, $id)
    {
        $subTeaId = $request->input('st');
        $teacher = DB::table($this->table)
            ->join('subject_teacher', 'subject_teacher.id', 'subject_teacher_portion.subject_teacher_id')
            ->join('teachers', 'subject_teacher.teacher_id', 'teachers.id')
            ->select('teachers.*')
            ->where('subject_teacher.id', $subTeaId)->first();
        $user = $request->user();
        if($user->isInRole('admin') || $user->id === $teacher->user_id){
            DB::table($this->table)->where('id', $id)->delete();
            return response()->json(["status" => "succeeded"]);
        }
        return response()->json(["status"=>"Unauthorized"], 403);
    }
}

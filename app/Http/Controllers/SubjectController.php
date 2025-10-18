<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Subject as MainModel;
use Illuminate\Support\Facades\DB;
use App\Models\TeacherProfile;
use App\Models\TeacherSubject;
use App\Models\User;
use App\Models\Classroom;

class SubjectController extends Controller
{
    public function index()
    {
    $subjects = MainModel::all();
    
    // ÉP KIỂU SANG SỐ TRƯỚC KHI TÍNH TỔNG:
    // Sử dụng map() để ép kiểu string thành integer cho mỗi môn
    $totalCredits = $subjects->sum(function ($subject) {
        return (int) $subject->credits; // Hoặc (float) nếu tín chỉ là số thập phân
    });
    
    // Gán dữ liệu vào mảng $data
    $data['rows'] = $subjects;
    $data['totalCredits'] = $totalCredits; 

    return view('subjects.index', $data);
}

    

    public function add()
    {
        $data['teachers'] = User::where('role', 'teacher')->get();
        return view('subjects.form', $data);
    }

    public function create(Request $request)
    {
        try {
            $params = $request->all();
            DB::transaction(function () use ($params) {
                $rec = MainModel::create([
                'name' => $params['name'],
                'code' => $params['code'],
                'credits' => $params['credits'],
                'semester'=> $params['semester'],
            ]);
                if(isset($params['teacher_profile_id']))
                    foreach($params['teacher_profile_id'] as $row)
                        TeacherSubject::create(['subject_id' => $rec->id, 'teacher_profile_id' => $row]);
            });
            return redirect()->route('subjects')->withSuccess("Đã thêm");
        } catch (\Exception $e) {
            return redirect()->back()->withError($e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $data['rec'] = MainModel::findOrFail($id);
        $data['teachers'] = User::where('role', 'teacher')->get();
        $data['teacher_subject_list'] = TeacherSubject::where('subject_id', $id)->get();
        return view('subjects.form')->with($data);
    }

    public function update(Request $request, $id)
    {
        try {
            $rec = MainModel::findOrFail($id);
            $params = $request->all();
            DB::transaction(function () use ($params, $rec) {
                $teacher_subject_list = $rec->teacherSubjectList;
                foreach($teacher_subject_list as $row)
                    $row->delete();
                    $rec->update([
                    'name' => $params['name'],
                    'code' => $params['code'],
                    'credits' => $params['credits'],
                    'semester'=> $params['semester'],
                    
                ]);
                if(isset($params['teacher_profile_id']))
                    foreach($params['teacher_profile_id'] as $row)
                        TeacherSubject::create(['subject_id' => $rec->id, 'teacher_profile_id' => $row]);
            });
            return redirect()->route('subjects')->withSuccess("Đã cập nhật");
        } catch (\Exception $e) {
            return redirect()->back()->withError($e->getMessage())->withInput();
        }
    }

    public function delete($id)
    {
        try {
            $rec = MainModel::findOrFail($id);
            $rec->delete();
            return redirect()->back()->withSuccess("Đã xóa");
        } catch (\Exception $e) {
            return redirect()->back()->withError($e->getMessage());
        }
    }

    public function showSubject($id)
    {
        $subject = MainModel::findOrFail($id);
        $teacher_subject_list = TeacherSubject::with('teacherProfile.user')->where('subject_id', $id)->get();
        $classroom_list = Classroom::where('subject_id', $id)->get();
        
        return view('subjects.subject_info', compact('subject', 'teacher_subject_list', 'classroom_list'))  ;
        // return view('subjects.subject_info')->with('subject', $subject)
        //     ->with('teacher_subject_list', $teacher_subject_list)
        //     ->with('classroom_list', $classroom_list);
    }
}

<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponser;
use App\Models\CertificateRequest;
use DataTables;

class CertificateRequestController extends Controller
{
    use ApiResponser;

    public function index()
    {
        return DataTables::of(CertificateRequest::query()->orderBy('created_at', 'desc'))
        ->addColumn('approve', function ($student) {
            if ($student->status_id == 1) {
                $input = '<input data-action="approve" type="checkbox" class="switch" name="switchstatus" >';
            } else {
                $input = '<input data-action="approve" type="checkbox" class="switch" name="switchstatus" checked>';
            }
            return '<label class="switch">' . $input . '<span class="slider round"></span></label>';
        })
        ->addColumn('course', function ($student) {
            return $student->course->name_ar;
        })
        ->addColumn('student', function ($student) {
            return $student->student->first_name . ' ' . $student->student->last_name;
        })
        ->addColumn('created_at', function ($student) {
            return $student->created_at->format('Y-m-d');
        })
        ->editColumn('id', '{{$id}}')
        ->rawColumns(['created_at', 'approve', 'course', 'student'])
        ->make(true);
    }
    public function show($id)
    {
        $certifcates = CertificateRequest::where('student_id', $id)->get();
        return $this->success(['certifcates' => $certifcates]);
    }
    public function approve($id)
    {
        $certifcate = CertificateRequest::find($id);
        if ($certifcate) {
            $certifcate->status_id = ($certifcate->status_id == 1 ? 2 : 1);
            if ($certifcate->status_id == 2) {
                $certifcate->save();
                return $this->success($certifcate, __('main.certifcate_approved'));
            } else {
                $certifcate->save();
                return $this->success($certifcate, __('main.certifcate_pending'));
            }
        }
        return $this->error(__('main.item_not_found'), 404);
    }
}

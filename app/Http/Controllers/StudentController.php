<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $students = Student::all();
        return $students;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'fname' => 'required|string|max:100',
            'lname' => 'required|string|max:100',
            'mname' => 'string|max:100',
            'suffix' => 'string|max:5',
            'bdate' => 'required|date|max:10',
            'bplace' => 'required|string|max:250',
            'gender' => 'required|string|max:100',
            'religion' => 'required|string|max:100',
            'address' => 'required|string|max:100',
            'contact_no' => 'required|string|max:100',
            'email' => 'required|string|max:100',
            'password' => 'required|string|max:100',
            
        ]);

        $students = Student::create($validatedData);
        return response()->json($students, 201); 
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        //
    }
}

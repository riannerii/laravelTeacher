<?php

namespace App\Http\Controllers;

use App\Models\Klase;
use App\Models\Admin;
use App\Models\Grade;
use App\Models\Announcement;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreKlaseRequest;
use App\Http\Requests\UpdateKlaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KlaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $classes = Klase::all();
        return $classes;
    }

    public function login(Request $request){

        $request->validate([
            "email"=>"required|email|exists:admins",
            "password"=>"required"
        ]);
        $admin = Admin::where('email',$request->email)->first();
        if(!$admin|| !Hash::check($request->password,$admin->password)){
            return [
                "message"=>"The provider credentials are incorrect"
            ];
        }
        $token = $admin->createToken($admin->fname);
        // $token = $admin->createToken($admin->fname)->plainTextToken;

        return [
            'admin' => $admin,
            'token' => $token->plainTextToken,
            'id'=> $admin->admin_id
        ];


    }

    public function logout(Request $request){
        $request->user()->tokens()->delete();
        return [
            'message'=>'You are logged out'
        ];
        // return 'logout';
    }

    public function UserDetails($id){
        $user = Admin::where('admin_id',$id)
                        ->first();
        return $user;
    }

    public function store(Request $request){
        $validatedData = $request->validate([
            'admin_id'=> 'required|int|max:4',
            'subject_id'=> 'required|int|max:4',
            'section' => 'required|string|max:255',
            'schedule' => 'required|string|max:255'
        ]);

        $classes = Klase::create($validatedData);
        return response()->json($classes, 201);
    }

    //classes
    public function getClasses($uid) {
        // Fetch classes based on the admin_id
        $classes = DB::table('klases')
            ->leftJoin('sections', 'klases.section_id', '=', 'sections.section_id')
            ->leftJoin('subjects', 'sections.grade_level', '=', 'subjects.grade_level')
            ->where('klases.admin_id', '=', $uid)
            ->select('klases.*', 'subjects.*', 'sections.*')
            ->get();

        return response()->json($classes);
    }

    public function getClassesToday($uid) {
        // Get today's day (e.g., 'Mon', 'Tue', etc.)
        $today = date('D'); // 'D' gives a three-letter abbreviation (e.g., 'Mon', 'Tue', etc.)
    
        $classes = DB::table('klases')
            ->leftJoin('sections', 'klases.section_id', '=', 'sections.section_id')
            ->leftJoin('subjects', 'sections.grade_level', '=', 'subjects.grade_level')
            ->select('klases.*', 'subjects.*', 'sections.*')
            ->where('klases.schedule', 'LIKE', "%$today%") // Filter by today's abbreviated day
            ->where('klases.admin_id', '=', $uid)
            ->orderByRaw("STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(klases.schedule, ' ', -1), '-', 1), '%l:%i%p')")
            ->get();
    
        return $classes;
    }
    
    public function getClassInfo($cid){
        $klase = DB::table('rosters')
        ->join('students', 'rosters.LRN', '=', 'students.LRN')
        ->leftJoin('parent_guardians', 'rosters.LRN', '=', 'parent_guardians.LRN')
        ->leftJoin('klases', 'rosters.class_id', '=', 'klases.class_id')
        ->leftJoin('sections', 'klases.section_id', '=', 'sections.section_id')
        ->leftJoin('subjects', 'sections.grade_level', '=', 'subjects.grade_level')
        // ->leftJoin('grades', 'rosters.LRN', '=', 'grades.LRN')
        ->where('rosters.class_id', '=', $cid)
        ->select(
            'students.fname as student_fname', 
            'students.lname as student_lname',  
            'sections.grade_level as student_gradelvl',
            'students.contact_no as student_contact_no',
            'parent_guardians.fname as guardian_fname', 
            'parent_guardians.lname as guardian_lname', 
            'students.gender',
            'students.*',
            'sections.*', 
            'klases.*', 
            'subjects.*',
            'parent_guardians.*'
            // 'grades.*'
        )
        ->orderByRaw("CASE WHEN students.gender = 'Male' THEN 0 ELSE 1 END") // Male first, then female
        ->orderBy('students.lname')  // Optional: order by last name for further sorting
        ->get();
        
        return response()->json($klase);
            
    }

    public function getClassGrades($cid){
        $klase = DB::table('rosters')
        ->join('students', 'rosters.LRN', '=', 'students.LRN')
        ->leftJoin('grades', 'rosters.LRN', '=', 'grades.LRN')
        ->where('rosters.class_id', '=', $cid)
        ->select(
            'students.LRN',
            'students.fname as student_fname', 
            'students.lname as student_lname',  
            'students.contact_no as student_contact_no', 
            DB::raw("
                MAX(CASE WHEN grades.term = 'First Quarter' THEN grades.grade ELSE NULL END) as grade_Q1,
                MAX(CASE WHEN grades.term = 'First Quarter' THEN grades.permission ELSE NULL END) AS permission_Q1,
                MAX(CASE WHEN grades.term = 'Second Quarter' THEN grades.grade ELSE NULL END) as grade_Q2,
                MAX(CASE WHEN grades.term = 'Second Quarter' THEN grades.permission ELSE NULL END) AS permission_Q2,
                MAX(CASE WHEN grades.term = 'Third Quarter' THEN grades.grade ELSE NULL END) as grade_Q3,
                MAX(CASE WHEN grades.term = 'Third Quarter' THEN grades.permission ELSE NULL END) AS permission_Q3,
                MAX(CASE WHEN grades.term = 'Fourth Quarter' THEN grades.grade ELSE NULL END) as grade_Q4,
                MAX(CASE WHEN grades.term = 'Fourth Quarter' THEN grades.permission ELSE NULL END) AS permission_Q4,
                MAX(CASE WHEN grades.term = 'Midterm' THEN grades.grade ELSE NULL END) as grade_midterm,
                MAX(CASE WHEN grades.term = 'Midterm' THEN grades.permission ELSE NULL END) AS permission_midterm,
                MAX(CASE WHEN grades.term = 'Final' THEN grades.grade ELSE NULL END) as grade_final,
                MAX(CASE WHEN grades.term = 'Final' THEN grades.permission ELSE NULL END) AS permission_final
            ")
        )
        ->groupBy('students.LRN', 'students.fname', 'students.lname', 'students.contact_no')
        ->orderByRaw("CASE WHEN students.gender = 'Male' THEN 0 ELSE 1 END") // Male first, then female
        ->orderBy('students.lname')  // Optional: order by last name for further sorting
        ->get();

        return response()->json($klase);
            
    }

    public function updateClassGrades(Request $request, $cid){
        
        $validatedData = $request->validate([
            'grades' => 'required|array',
            'grades.*.LRN' => 'required|string',
            'grades.*.term' => 'required|string|in:First Quarter,Second Quarter,Third Quarter,Fourth Quarter,Midterm,Final',
            'grades.*.grade' => 'required|numeric',
        ]);

        // Initialize an array to collect results
        $results = [];

        // Iterate through each grade entry
        foreach ($validatedData['grades'] as $gradeData) {
            // Use updateOrInsert to either update an existing record or insert a new one
            $result = DB::table('grades')
                ->updateOrInsert(
                [
                    'LRN' => $gradeData['LRN'],
                    'term' => $gradeData['term'],
                    'class_id' => $cid // Use $cid as the class_id
                ],
                [
                    'grade' => $gradeData['grade'],
                    // 'updated_at' => now(), // Optionally set the updated_at timestamp
                ]
            );

            // Collect the result
            $results[] = [
                'LRN' => $gradeData['LRN'],
                'term' => $gradeData['term'],
                'grade' => $gradeData['grade'],
                'operation' => $result ? 'updated' : 'inserted', // Indicate if it was updated or inserted
            ];
        }

        // Return a JSON response with the operation results
        return response()->json([
            'message' => 'Grades updated successfully',
            'results' => $results,
        ]);

    }

    public function getClassAttendance($cid){
        // Build the attendance query to get unique dates for the specified class
        $attendanceDates = DB::table('attendances')
            ->where('class_id', $cid)
            ->distinct()
            ->pluck('date');

        // Build the attendance query
        $attendance = DB::table('rosters')
            ->join('students', 'rosters.LRN', '=', 'students.LRN')
            ->leftJoin('attendances', function ($join) use ($cid) {
                $join->on('rosters.LRN', '=', 'attendances.LRN')
                    ->where('attendances.class_id', '=', $cid);
            })
            ->where('rosters.class_id', '=', $cid)
            ->select(
                'students.LRN',
                'students.fname as student_fname',
                'students.lname as student_lname',
                'attendances.date',
                'attendances.status'
            )
            ->get();

        // Transforming the data into the desired format
        $students = $attendance->groupBy('LRN')->map(function ($items) use ($attendanceDates) {
            $firstItem = $items->first();
            $fullName = "{$firstItem->student_lname}, {$firstItem->student_fname}";
            $attendance = [];

            // Loop through unique attendance dates
            foreach ($attendanceDates as $date) {
                // Find the attendance status for the current date
                $item = $items->firstWhere('date', $date);
                $dayName = date('l', strtotime($date)); // Get the day name

                $attendance[] = [
                    'date' => $date, // Actual date
                    'status' => $item->status ?? 'Absent', // Default to 'Absent' if no status is found
                    'day_name' => $dayName, // Day name (e.g., Monday)
                    // 'day_number' => $dayNumbers[$dayName] ?? null // Day number (e.g., 1 for Monday)
                ];
            }

            return [
                'id' => $firstItem->LRN,
                'name' => $fullName,
                'attendance' => $attendance
            ];
        })->values(); // Reset the keys for the final array

        return response()->json(['students' => $students]);
    }

    public function updateClassAttendance(Request $request, $cid){
        // Validate the incoming request data
        $validatedData = $request->validate([
            'attendance' => 'required|array',
            'attendance.*.LRN' => 'required|string',
            'attendance.*.date' => 'required|date', // Ensure it's a valid date
            'attendance.*.status' => 'required|string|in:present,late,absent', // Valid status
        ]);

        // Initialize an array to collect results
        $results = [];

        // Iterate through each attendance entry
        foreach ($validatedData['attendance'] as $attendanceData) {
            // Use updateOrInsert to either update an existing record or insert a new one
            $result = DB::table('attendances')
                ->updateOrInsert(
                    [
                        'LRN' => $attendanceData['LRN'],
                        'date' => $attendanceData['date'],
                        'class_id' => $cid // Use $cid as the class_id
                    ],
                    [
                        'status' => $attendanceData['status'],
                        // Optionally update the timestamp
                        // 'updated_at' => now(),
                    ]
                );

            // Collect the result (inserted or updated)
            $results[] = [
                'LRN' => $attendanceData['LRN'],
                'date' => $attendanceData['date'],
                'status' => $attendanceData['status'],
                'operation' => $result ? 'updated' : 'inserted', // Indicate if it was updated or inserted
            ];
        }

        // Return a JSON response with the operation results
        return response()->json([
            'message' => 'Attendance updated successfully',
            'results' => $results,
        ]);
    }

    //account
    public function updatePass(Request $request){
        // Validate incoming request
        $request->validate([
            'admin_id' => 'required|integer|exists:admins,admin_id',
            'oldPassword' => 'nullable|string', // Make oldPassword optional
            'newPassword' => 'nullable|string|min:8|confirmed', // Allow newPassword to be optional
            'fname' => 'required|string|max:255',
            'mname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:admins,email,' . $request->admin_id . ',admin_id', // Check uniqueness for email
            'address' => 'required|string|max:255',
        ]);

        // Retrieve user
        $user = Admin::find($request->admin_id);

        // If old password is provided, check it
        if ($request->oldPassword && !Hash::check($request->oldPassword, $user->password)) {
            return response()->json(['message' => 'Wrong password'], 401);
        }

        // Update user details
        if ($request->newPassword) {
            $user->password = Hash::make($request->newPassword); // Update password if provided
        }
        
        $user->fname = $request->fname;
        $user->mname = $request->mname;
        $user->lname = $request->lname;
        $user->email = $request->email;
        $user->address = $request->address;

        $user->save(); // Save all changes

        return response()->json(['message' => 'User details updated successfully']);
    }

    public function uploadImage(Request $request){
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'admin_id' => 'required|exists:admins,admin_id'
        ]);

        try {
            $admin = Admin::findOrFail($request->input('admin_id'));
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('assets/adminPic');

            // Ensure the directory exists
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Delete the old image if exists
            if ($admin->admin_pic && file_exists($path = $destinationPath . '/' . $admin->admin_pic)) {
                unlink($path);
            }

            // Move the new image and update the admin profile
            $image->move($destinationPath, $imageName);
            $admin->update(['admin_pic' => $imageName]);

            return response()->json([
                'message' => 'Image uploaded successfully.',
                'image_url' => url('assets/adminPic/' . $imageName)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Image upload failed.'], 500);
        }
    }

    //announcements

    public function getClassAnnouncements($cid)
    {
        $announcements = DB::table('announcements')
        ->where('announcements.class_id', '=', $cid)
        ->select('announcements.*')
        ->get();

        return response()->json($announcements);
    }

}

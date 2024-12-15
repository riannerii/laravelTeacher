<?php

namespace App\Http\Controllers;

use App\Models\Klase;
use App\Models\Admin;
use App\Models\Grade;
use App\Models\Announcement;
use App\Models\Message;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreKlaseRequest;
use App\Http\Requests\UpdateKlaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class KlaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $classes = Klase::all();
        return $classes;
    }

    public function login(Request $request){

        $request->validate([
            "email"=>"required|email|exists:admins",
            "password"=>"required"
        ]);
        $admin = Admin::where('email',$request->email)
            ->where('role', '=', 'Teacher')
            ->first();
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

    //home
    public function getClassesToday($uid) {
        // Get today's day (e.g., 'Mon', 'Tue', etc.)
        $today = date('D'); // 'D' gives a three-letter abbreviation (e.g., 'Mon', 'Tue', etc.)
    
        $classes = DB::table('classes')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.section_id')
            ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->select('classes.*', 'subjects.*', 'sections.*')
            ->where('classes.schedule', 'LIKE', "%$today%") // Filter by today's abbreviated day
            ->where('classes.admin_id', '=', $uid)
            ->orderByRaw("STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(classes.schedule, ' ', -1), '-', 1), '%l:%i%p')")
            ->get();
    
            foreach ($classes as $class) {
                // Adjust the path to match your directory structure
                $class->image = url('assets/subPic/' . $class->image); // Ensure this points to the correct location
                \Log::info('Image URL: ' . $class->image); // Log for debugging
            }

        return $classes;
    }

    public function getInquiries(Request $request){
        $uid = $request->input('uid');

        $data = DB::table('messages')
            ->leftJoin('students', function ($join) {
                $join->on('messages.message_sender', '=', 'students.LRN');
            })
            ->leftJoin('enrollments', function ($join) {
                $join->on('students.LRN', '=', 'enrollments.LRN');
            })
            ->leftJoin('parent_guardians', function ($join) {
                $join->on('messages.message_sender', '=', 'parent_guardians.guardian_id');
            })     
            ->leftJoin('admins', 'messages.message_reciever', '=', 'admins.admin_id')
            ->whereNotIn('messages.message_sender', function ($query) {
                $query->select('admin_id')->from('admins');
            })
            ->where('messages.message_reciever', '=', $uid)
            // ->join('admins as sender_admin', 'messages.message_sender', '=', 'sender_admin.admin_id')
            // ->join('students as reciever', 'messages.message_reciever', '=', 'reciever.LRN')
            ->select('messages.*', 
                    DB::raw('CASE 
                    WHEN messages.message_sender IN (SELECT LRN FROM students) THEN 
                    CONCAT(students.fname, " ", 
                        CASE 
                            WHEN students.mname IS NOT NULL THEN CONCAT(LEFT(students.mname, 1), ". ") 
                            ELSE "" 
                        END, 
                    students.lname)
                    WHEN messages.message_sender IN (SELECT guardian_id FROM parent_guardians) THEN 
                        CONCAT(parent_guardians.fname, " ", 
                            CASE 
                                WHEN parent_guardians.mname IS NOT NULL THEN CONCAT(LEFT(parent_guardians.mname, 1), ". ") 
                                ELSE "" 
                            END, 
                        parent_guardians.lname)
                    END as sender_name'),
                    DB::raw('CONCAT(admins.fname, " ",COALESCE(LEFT(admins.mname, 1),""), ". ", admins.lname)as admin_name'),
                    DB::raw('CASE 
                    WHEN messages.message_sender IN (SELECT LRN FROM students) THEN 
                        CASE 
                            WHEN enrollments.strand IS NULL THEN enrollments.grade_level 
                            ELSE CONCAT(enrollments.grade_level, " ", enrollments.strand) 
                        END
                    ELSE NULL
                    END as label')
                    )
            ->havingRaw('sender_name IS NOT NULL')
            ->orderBy('messages.created_at', 'desc')
            ->get();
    
        return $data;
    }

    //classes
    
    public function getClasses($uid) {
        // Fetch classes based on the admin_id
        $classes = DB::table('classes')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.section_id')
            ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id') 
            ->where('classes.admin_id', '=', $uid)
            ->select('classes.*', 'sections.*', 'subjects.*', 'subjects.image')
            ->get();
    
        if ($classes->isEmpty()) {
            return response()->json(['message' => 'No classes found for this admin ID'], 404);
        }
    
        foreach ($classes as $class) {
            // Adjust the path to match your directory structure
            $class->image = url('assets/subPic/' . $class->image); // Ensure this points to the correct location
            \Log::info('Image URL: ' . $class->image); // Log for debugging
        }
    
        return response()->json($classes);
    }

    public function getClassInfo($cid) {
        $klase = DB::table('rosters')
            ->join('students', 'rosters.LRN', '=', 'students.LRN')
            ->leftJoin('parent_guardians', 'rosters.LRN', '=', 'parent_guardians.LRN')
            ->leftJoin('classes', 'rosters.class_id', '=', 'classes.class_id')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.section_id')
            ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->leftJoin('enrollments', 'rosters.LRN', '=', 'enrollments.LRN') // Joining enrollments table
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
                'classes.*',
                'subjects.*',
                'parent_guardians.*',
                'enrollments.guardian_name',
                'enrollments.guardian_no' 
            )
            ->orderByRaw("CASE WHEN students.gender = 'Male' THEN 0 ELSE 1 END") // Male first, then female
            ->orderBy('students.lname')  // Optional: order by last name for further sorting
            ->get();
            
        return response()->json($klase);
    }

    public function getClassGrades($cid){
        $klase = DB::table('rosters')
            ->join('students', 'rosters.LRN', '=', 'students.LRN')
            ->leftJoin('grades', function ($join) use ($cid) {
                $join->on('rosters.LRN', '=', 'grades.LRN')
                    ->where('grades.class_id', '=', $cid);
            })
            ->where('rosters.class_id', '=', $cid) // Filter students by class_id
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
            ->orderBy('students.lname') // Optional: order by last name for further sorting
            ->get();

        return response()->json($klase);
    }

    public function updateClassGrades(Request $request, $cid) {
        // Initialize an array to collect results
        $results = [];
    
        // Iterate through each grade entry
        foreach ($request->grades as $gradeData) {
            // Determine if the grade already exists (i.e., it's an update)
            $existingGrade = DB::table('grades')
                ->where('LRN', $gradeData['LRN'])
                ->where('term', $gradeData['term'])
                ->where('class_id', $cid)
                ->first();
    
            // If it doesn't exist, we treat it as an insert
            if (!$existingGrade) {
                // Validate for insert: grade must be between 60 and 100
                $validatedData = $request->validate([
                    'grades.*.LRN' => 'required|string',
                    'grades.*.term' => 'required|string|in:First Quarter,Second Quarter,Third Quarter,Fourth Quarter,Midterm,Final',
                    'grades.*.grade' => 'required|numeric|between:60,99', // Apply grade validation for insert
                    'grades.*.permission' => 'nullable|string|in:none,pending',
                ]);
            } else {
                $validatedData = $request->validate([
                    'grades.*.LRN' => 'required|string',
                    'grades.*.term' => 'required|string|in:First Quarter,Second Quarter,Third Quarter,Fourth Quarter,Midterm,Final',
                    'grades.*.grade' => 'required|numeric|between:60,99',
                    'grades.*.permission' => 'nullable|string|in:none,pending',
                ]);
            }
    
            // Default permission is 'none' if not provided
            $permission = $gradeData['permission'] ?? (strlen((string) $gradeData['grade']) === 2 ? 'none' : 'pending');
    
            // Use updateOrInsert to either update an existing record or insert a new one
            $result = DB::table('grades')
                ->updateOrInsert(
                    [
                        'LRN' => $gradeData['LRN'],
                        'term' => $gradeData['term'],
                        'class_id' => $cid // Use $cid as the class_id
                    ],
                    [
                        'grade' => $gradeData['grade'], // Save the new grade
                        'permission' => $permission, // Set permission based on request or default logic
                        'updated_at' => now(), // Optionally set the updated_at timestamp
                    ]
                );
    
            // Collect the result
            $results[] = [
                'LRN' => $gradeData['LRN'],
                'term' => $gradeData['term'],
                'grade' => $gradeData['grade'], // Include the saved grade
                'permission' => $permission, // Include the updated permission
                'operation' => $result ? 'updated' : 'inserted', // Indicate if it was updated or inserted
            ];
        }
    
        // Return a JSON response with the operation results
        return response()->json([
            'message' => 'Grades and permissions updated successfully',
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
            ->orderByRaw("CASE WHEN students.gender = 'Male' THEN 0 ELSE 1 END") // Male first, then female
            ->orderBy('students.lname')  // Optional: order by last name for further sorting
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
    public function getClassAnnouncements($cid){
        $announcements = DB::table('announcements')
        ->where('announcements.class_id', '=', $cid)
        ->select('announcements.*')
        ->get();

        return response()->json($announcements);
    }

    public function postAnnouncements(Request $request){
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'announcement' => 'required|string|max:5000',
            'admin_id' => 'required|exists:admins,admin_id',
            'class_id' => 'required|exists:classes,class_id',
        ]);

        $announcement = Announcement::create($validatedData);
        return response()->json($announcement, 201);
    }

    public function destroyAnnouncements($ancmnt_id){
        try {
            $announcement = Announcement::find($ancmnt_id);

            if ($announcement) {
                $announcement->delete();
                return response()->json(['message' => 'Deleted successfully!'], 200);
            }

            return response()->json(['message' => 'Announcement not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting announcement: ' . $e->getMessage()], 500);
        }
    }

    public function getStudentParents() {
        // Fetch students
        $students = DB::table('students')
        ->select('students.LRN', DB::raw("
        CONCAT(
            students.fname, 
            ' ', 
            CASE 
                WHEN students.mname IS NOT NULL AND students.mname != '' THEN CONCAT(LEFT(students.mname, 1), '. ')
                ELSE ''
            END,
            students.lname
            ) as account_name
            "))
            ->get()
            ->map(function ($student) {
                return [
                    'account_id' => $student->LRN,
                    'account_name' => $student->account_name,
                    'type' => 'student',
                ];
            });
    
        // Fetch distinct parents by email while retaining the original selection
        $parents = DB::table('parent_guardians')
            ->select('parent_guardians.guardian_id', DB::raw('CONCAT(parent_guardians.fname, " ", LEFT(COALESCE(parent_guardians.mname, ""), 1), ". ", parent_guardians.lname) as account_name'))
            ->whereIn('guardian_id', function($query) {
                $query->select(DB::raw('MIN(guardian_id)')) // Get the first guardian_id for each email
                      ->from('parent_guardians')
                      ->groupBy('email'); // Group by email to ensure distinct entries
            })
            ->get()
            ->map(function ($parent) {
                return [
                    'account_id' => $parent->guardian_id,
                    'account_name' => $parent->account_name,
                    'type' => 'parent',
                ];
            });
    
        // Combine both collections into one
        $accounts = $students->merge($parents);
    
        return response()->json($accounts);
    }

    public function getMessages(Request $request) {
        $uid = $request->input('uid');
    
        // Main query to get messages for the entire conversation
        $msg = DB::table('messages')
            ->leftJoin('students', function ($join) {
                $join->on('messages.message_sender', '=', 'students.LRN');
            })
            ->leftJoin('admins', function ($join) {
                $join->on('messages.message_sender', '=', 'admins.admin_id');
            })
            ->leftJoin('parent_guardians', function ($join) {
                $join->on('messages.message_sender', '=', 'parent_guardians.guardian_id');
            })
            ->leftJoin('students as receiver_students', function ($join) {
                $join->on('messages.message_reciever', '=', 'receiver_students.LRN');
            })
            ->leftJoin('admins as receiver_admins', function ($join) {
                $join->on('messages.message_reciever', '=', 'receiver_admins.admin_id');
            })
            ->leftJoin('parent_guardians as receiver_guardians', function ($join) {
                $join->on('messages.message_reciever', '=', 'receiver_guardians.guardian_id');
            })
            ->where(function($query) use ($uid) {
                $query->where('messages.message_sender', '=', $uid) // Messages sent by the user
                      ->orWhere('messages.message_reciever', '=', $uid); // Messages received by the user
            })
            ->select('messages.*', 
                DB::raw('CASE 
                        WHEN messages.message_sender IN (SELECT LRN FROM students) THEN 
                            CONCAT(students.fname, 
                                IFNULL(CONCAT(" ", LEFT(students.mname, 1), "."), ""), 
                                " ", 
                                students.lname)
                        WHEN messages.message_sender IN (SELECT admin_id FROM admins) THEN 
                            CONCAT(receiver_students.fname, 
                                IFNULL(CONCAT(" ", LEFT(receiver_students.mname, 1), "."), ""), 
                                " ", 
                                receiver_students.lname)
                        WHEN messages.message_sender IN (SELECT guardian_id FROM parent_guardians) THEN 
                            CONCAT(parent_guardians.fname, 
                                IFNULL(CONCAT(" ", LEFT(parent_guardians.mname, 1), "."), ""), 
                                " ", 
                                parent_guardians.lname)
                    END as sender_name'),
                    DB::raw('IF(messages.read_at IS NULL, 0, 1) as is_read')
                    )
            ->havingRaw('sender_name IS NOT NULL')
            ->orderBy('messages.created_at', 'desc')
            ->get();
        return $msg;
    }

    public function markAsRead(Request $request) {
        $sid = $request->input('sid'); // The ID of the user whose messages are being marked as read

        // Update the read_at timestamp for all messages involving the user
        $read = DB::table('messages')
            ->where(function($query) use ($sid) {
                $query->where('messages.message_sender', '=', $sid) // Messages sent by the user
                      ->orWhere('messages.message_reciever', '=', $sid); // Messages received by the user
            })
            ->update(['read_at' => now()]); // Set the read_at timestamp to the current time
    
        return response()->json(['success' => true, 'updated_count' => $read]);
    }

    public function getUnreadCount(Request $request)
    {
        $uid = $request->input('uid'); // Get the user ID from the request

        // Count unread messages for the user
        $unreadCount = DB::table('messages')
            ->where('message_reciever', $uid)
            ->where('read_at', null)
            ->count();

        // return response()->json(['unread_count' => $unreadCount]);
        return $unreadCount;
    }

    public function getConvo(Request $request, $sid) {
        // Initialize the response variable
        $user = null;
    
        // Check if the $sid corresponds to a student
        $student = DB::table('students')
            ->where('students.LRN', $sid)
            ->select('students.LRN', DB::raw("
            CONCAT(
                students.fname, 
                ' ', 
                CASE 
                    WHEN students.mname IS NOT NULL AND students.mname != '' THEN CONCAT(LEFT(students.mname, 1), '. ')
                    ELSE ''
                END,
                students.lname
                ) as account_name
                "))
            ->first(); // Use first() to get a single record
    
        if ($student) {
            // If a student is found, format the response
            $user = [
                'account_id' => $student->LRN,
                'account_name' => $student->account_name,
                'type' => 'student',
            ];
        } else {
            // If no student found, check for a parent
            $parent = DB::table('parent_guardians')
                ->where('parent_guardians.guardian_id', $sid)
                ->select('parent_guardians.guardian_id', DB::raw('CONCAT(parent_guardians.fname, " ",  LEFT(COALESCE(parent_guardians.mname, ""), 1), ". ", parent_guardians.lname) as account_name'))
                ->first(); // Use first() to get a single record
    
            if ($parent) {
                // If a parent is found, format the response
                $user = [
                    'account_id' => $parent->guardian_id,
                    'account_name' => $parent->account_name,
                    'type' => 'parent',
                ];
            }
        }
    
        // Initialize the conversation variable
        $convo = [];
    
        // If user is found, fetch the conversation
        if ($user) {
            $uid = $request->input('uid');
    
            $convo = DB::table('messages')
            ->leftJoin('students', 'messages.message_sender', '=', 'students.LRN')
            ->leftJoin('admins', 'messages.message_sender', '=', 'admins.admin_id')
            ->leftJoin('parent_guardians', 'messages.message_sender', '=', 'parent_guardians.guardian_id')
            ->where(function ($query) use ($uid) {
                $query->where('messages.message_sender', $uid)
                    ->orWhere('messages.message_reciever', $uid);
            })
            ->where(function ($query) use ($sid) {
                $query->where('messages.message_sender', $sid)
                    ->orWhere('messages.message_reciever', $sid);
            })
            ->selectRaw("
            messages.*,
            CASE 
                WHEN messages.message_sender = ? THEN 'me' 
                ELSE NULL 
            END as me,
            CASE 
                WHEN messages.message_sender IN (SELECT LRN FROM students) THEN 
                    CONCAT(
                        students.fname, ' ', 
                        CASE 
                            WHEN students.mname IS NOT NULL AND students.mname != '' THEN CONCAT(LEFT(students.mname, 1), '. ')
                            ELSE ''
                        END,
                        students.lname
                    )
                WHEN messages.message_sender IN (SELECT guardian_id FROM parent_guardians) THEN 
                    CONCAT(
                        parent_guardians.fname, ' ', 
                        CASE 
                            WHEN parent_guardians.mname IS NOT NULL AND parent_guardians.mname != '' THEN CONCAT(LEFT(parent_guardians.mname, 1), '. ')
                            ELSE ''
                        END,
                        parent_guardians.lname
                    )
                ELSE NULL 
            END as sender_name
        ", [$uid])
        ->get();

        }
    
        // Return the user information and conversation or a not found message
        return response()->json([
            'user' => $user ?: ['message' => 'User  not found'],
            'conversation' => $convo,
        ]);
    }

    public function sendMessage(Request $request){
        $validator = Validator::make($request->all(), [
            'message_sender' => 'required',
            'message_reciever' => 'required',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $message = Message::create([
            'message_sender' => $request->input('message_sender'), // Ensure the key matches your database column
            'message_reciever' => $request->input('message_reciever'), // Ensure the key matches your database column
            'message' => $request->input('message'), // Ensure the key matches your database column
            'message_date' => now(),
        ]);

        return response()->json($message, 201);
    }

    public function getrecepeints(Request $request){
        $students = DB::table('students')
        ->select(DB::raw('LRN AS receiver_id, CONCAT(fname, " ", lname) AS receiver_name'));
        $guardians = DB::table('parent_guardians')
            ->select(DB::raw('guardian_id AS receiver_id, CONCAT(fname, " ", lname) AS receiver_name'));
        $admins = DB::table('admins')
            ->select(DB::raw('admin_id AS receiver_id, CONCAT(fname, " ", lname) AS receiver_name'));
        $recipients = $students->unionAll($guardians)->unionAll($admins)->get();
        return response()->json($recipients);
    }

    public function composenewmessage(Request $request){
        // Validate the incoming request data
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'message_date' => 'required|date',
            'message_sender' => [
                'required',
                function ($attribute, $value, $fail) {
                    $existsInStudents = DB::table('students')->where('LRN', $value)->exists();
                    $existsInGuardians = DB::table('parent_guardians')->where('guardian_id', $value)->exists();
                    $existsInAdmins = DB::table('admins')->where('admin_id', $value)->exists();
    
                    if (!$existsInStudents && !$existsInGuardians && !$existsInAdmins) {
                        $fail("The selected $attribute is invalid.");
                    }
                },
            ],
            'message_reciever' => [
                'required',
                function ($attribute, $value, $fail) {
                    $existsInStudents = DB::table('students')->where('LRN', $value)->exists();
                    $existsInGuardians = DB::table('parent_guardians')->where('guardian_id', $value)->exists();
                    $existsInAdmins = DB::table('admins')->where('admin_id', $value)->exists();
    
                    if (!$existsInStudents && !$existsInGuardians && !$existsInAdmins) {
                        $fail("The selected $attribute is invalid.");
                    }
                },
            ],
        ]);
    
        try {
            // Create a new message
            $message = new Message();
            $message->message_sender = $validated['message_sender'];
            $message->message_reciever = $validated['message_reciever'];
            $message->message = $validated['message'];
            $message->message_date = $validated['message_date'];
            $message->save();
    
            // Log a success message
            Log::info('Message successfully composed', [
                'message_id' => $message->message_id,
                'sender' => $validated['message_sender'],
                'receiver' => $validated['message_reciever'],
                'message_content' => $validated['message'],
                'message_date' => $validated['message_date'],
            ]);
    
            // Return the updated list of messages
            return $this->getMessages($request);  // Call getMessages method to return updated conversation
        } catch (\Exception $e) {
            // Log any error that occurs
            Log::error('Error sending message: ' . $e->getMessage());
    
            // Return an error response
            return response()->json(['error' => 'Failed to send message'], 500);
        }
    }

    public function updateGradePermission(Request $request){
        $student = Student::where('LRN', $request->LRN)->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Update the permission based on the term
        $permissionField = 'permission_' . str_replace(' ', '_', strtolower($request->term));
        $student->$permissionField = $request->permission;
        $student->save();

        return response()->json(['message' => 'Permission updated successfully']);
    }



}

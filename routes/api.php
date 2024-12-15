<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\KlaseController;
use App\Http\Controllers\StudentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('admins', AdminController::class);
Route::apiResource('announcements', AnnouncementController::class);
Route::apiResource('subjects', SubjectController::class);
Route::apiResource('students', StudentController::class);


Route::post('/login', [KlaseController::class, 'login']);

Route::get('/getInquiries', [KlaseController::class, 'getInquiries']);


Route::get('/getclasses/{uid}', [KlaseController::class, 'getClasses']);
Route::get('/getClassesToday/{uid}', [KlaseController::class, 'getClassesToday']);
Route::get('/getClassInfo/{cid}', [KlaseController::class, 'getClassInfo']);

Route::get('/getClassGrades/{cid}', [KlaseController::class, 'getClassGrades']);
Route::post('/updateClassGrades/{cid}', [KlaseController::class, 'updateClassGrades']);

Route::get('/getClassAttendance/{cid}', [KlaseController::class, 'getClassAttendance']);
Route::post('/updateClassAttendance/{cid}', [KlaseController::class, 'updateClassAttendance']);

Route::put('/update-password', [KlaseController::class, 'updatePass']);
    Route::post('/upload-image', [KlaseController::class, 'uploadImage']);
    Route::get('assets/adminPic/{filename}', function ($filename) {
        $path = public_path('assets/adminPic/' . $filename);
        
        if (file_exists($path)) {
            return response()->file($path);
        }
    
        abort(404);
    });

Route::get('/getClassAnnouncements/{cid}', [KlaseController::class, 'getClassAnnouncements']);
Route::post('/postAnnouncements',[KlaseController::class,'postAnnouncements']);
Route::delete('destroyannouncements/{ancmnt_id}', [KlaseController::class, 'destroyAnnouncements']);

// Message 
Route::get('/getStudentParents', [KlaseController::class, 'getStudentParents']);
Route::get('/getMessages', [KlaseController::class, 'getMessages']);
Route::get('/getConvo/{sid}', [KlaseController::class, 'getConvo']);
Route::post('/sendMessage', [KlaseController::class, 'sendMessage']);
Route::get('/getrecepeints', [KlaseController::class, 'getrecepeints']);
Route::post('/composemessage', [KlaseController::class, 'composenewmessage']);

Route::post('/markAsRead', [KlaseController::class, 'markAsRead']);
Route::get('/getUnreadCount', [KlaseController::class, 'getUnreadCount']);

Route::post('/update-grade-permission', [KlaseController::class, 'updateGradePermission']);






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
// Route::apiResource('klases', KlaseController::class);

Route::post('/login', [KlaseController::class, 'login']);

// Route::get('/getclasses', [KlaseController::class, 'getclasses']);
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



    // Announcement
// Route::get('announcements',[KlaseController::class,'getAnnouncements']);
// Route::get('/announcements/{announcement}', [KlaseController::class, 'showtoupdate']);
// Route::post('/postAnnouncements',[KlaseController::class,'postAnnouncements']);
// Route::put('/updateAnnouncements/{announcement}', [KlaseController::class, 'updateAnnouncements']);
// Route::delete('destroyannouncements/{announcement_id}', [KlaseController::class, 'destroyAnnouncements']);
 


// Route::post('/submit-grades/{cid}', [KlaseController::class, 'updateClassGrades']);




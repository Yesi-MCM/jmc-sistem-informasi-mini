<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransportAllowanceController;
use App\Http\Controllers\LogController;

// Public Auth Endpoints
Route::prefix('auth')->group(function () {
    Route::get('captcha', [AuthController::class, 'getCaptcha']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
});

// Protected Endpoints
Route::middleware('jwt.auth')->group(function () {
    
    // Auth profile
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });

    // Regions autocomplete
    Route::get('regions/districts', [RegionController::class, 'searchDistricts']);

    // Employee Management (RBAC Protected)
    Route::prefix('employees')->group(function () {
        Route::get('form-meta', [EmployeeController::class, 'getFormMeta']);
        Route::get('', [EmployeeController::class, 'index'])->middleware('jwt.auth:pegawai,read');
        Route::post('', [EmployeeController::class, 'store'])->middleware('jwt.auth:pegawai,create');
        Route::post('bulk-status', [EmployeeController::class, 'changeStatusBulk'])->middleware('jwt.auth:pegawai,update');
        Route::post('bulk-delete', [EmployeeController::class, 'deleteBulk'])->middleware('jwt.auth:pegawai,delete');
        Route::get('export/excel', [EmployeeController::class, 'exportExcel'])->middleware('jwt.auth:pegawai,read');
        Route::get('export/pdf', [EmployeeController::class, 'exportPdf'])->middleware('jwt.auth:pegawai,read');
        Route::get('dashboard/stats', [EmployeeController::class, 'getDashboardStats'])->middleware('jwt.auth:dashboard,read');
        Route::get('{id}', [EmployeeController::class, 'show'])->middleware('jwt.auth:pegawai,read');
        Route::post('{id}', [EmployeeController::class, 'update'])->middleware('jwt.auth:pegawai,update');
        Route::delete('{id}', [EmployeeController::class, 'destroy'])->middleware('jwt.auth:pegawai,delete');
        Route::get('{id}/export/pdf', [EmployeeController::class, 'exportDetailPdf'])->middleware('jwt.auth:pegawai,read');
    });

    // Attendance Management (RBAC Protected)
    Route::prefix('attendances')->group(function () {
        Route::get('', [AttendanceController::class, 'index'])->middleware('jwt.auth:presensi,read');
        Route::get('template', [AttendanceController::class, 'downloadTemplate'])->middleware('jwt.auth:presensi,read');
        Route::post('import', [AttendanceController::class, 'import'])->middleware('jwt.auth:presensi,create');
        Route::get('import/{id}/status', [AttendanceController::class, 'getImportStatus'])->middleware('jwt.auth:presensi,read');
        Route::get('employee/{employeeId}', [AttendanceController::class, 'show'])->middleware('jwt.auth:presensi,read');
    });

    // User Management (RBAC Protected)
    Route::prefix('users')->group(function () {
        Route::get('', [UserController::class, 'index'])->middleware('jwt.auth:user,read');
        Route::post('', [UserController::class, 'store'])->middleware('jwt.auth:user,create');
        Route::get('roles', [UserController::class, 'getRoles']);
        Route::get('roles/{id}', [UserController::class, 'getRoleDetails'])->middleware('jwt.auth:role,read');
        Route::get('search-employees', [UserController::class, 'searchUnregisteredEmployees']);
        Route::get('{id}', [UserController::class, 'show'])->middleware('jwt.auth:user,read');
        Route::put('{id}', [UserController::class, 'update'])->middleware('jwt.auth:user,update');
        Route::delete('{id}', [UserController::class, 'destroy'])->middleware('jwt.auth:user,delete');
    });

    // Transport Allowance Settings (RBAC Protected)
    Route::prefix('allowance-settings')->group(function () {
        Route::get('', [TransportAllowanceController::class, 'getSetting']);
        Route::post('', [TransportAllowanceController::class, 'updateSetting'])->middleware('jwt.auth:setting_tunjangan,create');
    });

    // Transport Allowance Periods & Calculations (RBAC Protected)
    Route::prefix('allowances')->group(function () {
        Route::get('periods', [TransportAllowanceController::class, 'getPeriods'])->middleware('jwt.auth:tunjangan_transport,read');
        Route::get('periods/{id}', [TransportAllowanceController::class, 'getPeriodDetails'])->middleware('jwt.auth:tunjangan_transport,read');
        Route::post('calculate', [TransportAllowanceController::class, 'calculate'])->middleware('jwt.auth:tunjangan_transport,create');
    });

    // System Audit Logs (RBAC Protected)
    Route::get('logs', [LogController::class, 'index'])->middleware('jwt.auth:log,read');
});

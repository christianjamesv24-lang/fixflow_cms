<?php

use App\Http\Controllers\AdminContentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminPeopleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TechnicianController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::get('/services', [HomeController::class, 'services']);
Route::get('/services/{slug}', [HomeController::class, 'serviceShow']);
Route::get('/about', [HomeController::class, 'about']);
Route::get('/contact', [HomeController::class, 'contact']);
Route::post('/contact', [HomeController::class, 'contactStore']);
Route::get('/p/{slug}', [HomeController::class, 'page']);

Route::get('/login', [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/logout', [AuthController::class, 'logout']);

Route::middleware('role:admin,technician')->group(function () {
    Route::get('/chat', [ChatController::class, 'index']);
    Route::get('/chat/messages', [ChatController::class, 'messages']);
    Route::post('/chat/messages', [ChatController::class, 'send']);
    Route::post('/chat/mark-read', [ChatController::class, 'markRead']);
});

Route::middleware('role:admin,technician,customer')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/password', [ProfileController::class, 'password']);
});

Route::middleware('role:customer')->group(function () {
    Route::get('/customer', [CustomerController::class, 'dashboard']);
    Route::get('/customer/requests', [CustomerController::class, 'index']);
    Route::get('/customer/requests/new', [CustomerController::class, 'create']);
    Route::post('/customer/requests', [CustomerController::class, 'store']);
    Route::get('/customer/requests/{id}', [CustomerController::class, 'show'])->whereNumber('id');
    Route::post('/customer/requests/{id}/cancel', [CustomerController::class, 'cancel'])->whereNumber('id');
    Route::post('/customer/requests/{id}/feedback', [CustomerController::class, 'feedback'])->whereNumber('id');
});

Route::middleware('role:technician')->group(function () {
    Route::get('/technician', [TechnicianController::class, 'dashboard']);
    Route::get('/technician/jobs', [TechnicianController::class, 'jobs']);
    Route::get('/technician/jobs/{id}', [TechnicianController::class, 'show'])->whereNumber('id');
    Route::post('/technician/jobs/{id}/accept', [TechnicianController::class, 'accept'])->whereNumber('id');
    Route::post('/technician/jobs/{id}/reject', [TechnicianController::class, 'reject'])->whereNumber('id');
    Route::post('/technician/jobs/{id}/start', [TechnicianController::class, 'start'])->whereNumber('id');
    Route::post('/technician/jobs/{id}/complete', [TechnicianController::class, 'complete'])->whereNumber('id');
    Route::get('/technician/schedule', [TechnicianController::class, 'schedule']);
});

Route::middleware('role:admin')->group(function () {
    Route::get('/admin', [AdminController::class, 'dashboard']);
    Route::get('/admin/requests', [AdminController::class, 'requests']);
    Route::get('/admin/requests/{id}', [AdminController::class, 'requestShow'])->whereNumber('id');
    Route::post('/admin/requests/{id}/assign', [AdminController::class, 'assign'])->whereNumber('id');
    Route::post('/admin/requests/{id}/status', [AdminController::class, 'updateStatus'])->whereNumber('id');
    Route::post('/admin/requests/{id}/schedule', [AdminController::class, 'schedule'])->whereNumber('id');
    Route::post('/admin/requests/{id}/cost', [AdminController::class, 'updateCost'])->whereNumber('id');
    Route::get('/admin/appointments', [AdminController::class, 'appointments']);
    Route::post('/admin/appointments/{id}/status', [AdminController::class, 'appointmentStatus'])->whereNumber('id');
    Route::get('/admin/feedback', [AdminController::class, 'feedback']);
    Route::get('/admin/messages', [AdminController::class, 'messages']);
    Route::post('/admin/messages/{id}/status', [AdminController::class, 'messageStatus'])->whereNumber('id');
    Route::get('/admin/reports', [AdminController::class, 'reports']);
    Route::get('/admin/reports/export', [AdminController::class, 'export']);
    Route::get('/admin/activity', [AdminController::class, 'activity']);
    Route::get('/admin/settings', [AdminController::class, 'settings']);
    Route::post('/admin/settings', [AdminController::class, 'saveSettings']);

    Route::get('/admin/technicians', [AdminPeopleController::class, 'technicians']);
    Route::get('/admin/technicians/create', [AdminPeopleController::class, 'technicianCreate']);
    Route::post('/admin/technicians', [AdminPeopleController::class, 'technicianStore']);
    Route::get('/admin/technicians/{id}/edit', [AdminPeopleController::class, 'technicianEdit'])->whereNumber('id');
    Route::post('/admin/technicians/{id}', [AdminPeopleController::class, 'technicianUpdate'])->whereNumber('id');
    Route::post('/admin/technicians/{id}/delete', [AdminPeopleController::class, 'technicianDelete'])->whereNumber('id');
    Route::get('/admin/customers', [AdminPeopleController::class, 'customers']);
    Route::get('/admin/customers/{id}', [AdminPeopleController::class, 'customerShow'])->whereNumber('id');

    Route::get('/admin/services', [AdminContentController::class, 'services']);
    Route::get('/admin/services/create', [AdminContentController::class, 'serviceCreate']);
    Route::post('/admin/services', [AdminContentController::class, 'serviceStore']);
    Route::get('/admin/services/{id}/edit', [AdminContentController::class, 'serviceEdit'])->whereNumber('id');
    Route::post('/admin/services/{id}', [AdminContentController::class, 'serviceUpdate'])->whereNumber('id');
    Route::post('/admin/services/{id}/delete', [AdminContentController::class, 'serviceDelete'])->whereNumber('id');
    Route::get('/admin/pages', [AdminContentController::class, 'pages']);
    Route::get('/admin/pages/create', [AdminContentController::class, 'pageCreate']);
    Route::post('/admin/pages', [AdminContentController::class, 'pageStore']);
    Route::get('/admin/pages/{id}/edit', [AdminContentController::class, 'pageEdit'])->whereNumber('id');
    Route::post('/admin/pages/{id}', [AdminContentController::class, 'pageUpdate'])->whereNumber('id');
    Route::post('/admin/pages/{id}/delete', [AdminContentController::class, 'pageDelete'])->whereNumber('id');
});

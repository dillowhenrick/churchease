<?php

use App\Enums\User\UserRolesEnum;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Super Admin Dashboard
    Route::middleware('role:'.UserRolesEnum::SuperAdmin->value)->group(function () {
        Route::get('admin/dashboard', function () {
            return Inertia::render('core/administrator/super-admin/dashboard');
        })->name('admin.dashboard');
    });

    // Church Admin Dashboard
    Route::middleware('role:'.UserRolesEnum::ChurchAdmin->value)->group(function () {
        Route::get('admin/church/dashboard', function () {
            return Inertia::render('core/administrator/church-admin/dashboard');
        })->name('admin.church.dashboard');
    });
});

require __DIR__.'/settings.php';

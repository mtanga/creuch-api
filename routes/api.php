<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\PayController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('check', [UserController::class, 'check']);
Route::post('checkmatricule', [UserController::class, 'checkmatricule']);
Route::post('generate_matricule', [UserController::class, 'generate_matricule']);
Route::post('checkcode', [UserController::class, 'checkcode']);

Route::post('contact', [UserController::class, 'contact']);
Route::post('groupuser', [UserController::class, 'groupuser']);
Route::post('categegoriesuser', [UserController::class, 'categegoriesuser']);

Route::post('check_code_or_matricule', [UserController::class, 'check_code_or_matricule']);


Route::post('pointretraits', [UserController::class, 'pointretraits']);
Route::post('checkCard', [PayController::class, 'checkCard']);
Route::post('makePayement', [PayController::class, 'makePayement']);

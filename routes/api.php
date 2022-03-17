<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('register', 'UserController@register');
Route::post('login', 'UserController@login');

Route::group(['middleware' => ['jwt.verify']], function() {
Route::post('topup', 'TransactionController@topup');
Route::post('withdraw', 'TransactionController@withdraw');
Route::post('transfer', 'TransactionController@transfer');
Route::get('mutasi', 'TransactionController@mutasi');

Route::post('reset_password', 'UserController@reset_password');
});

<?php

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

Route::get('/', function () {
    return ['last_api_version' => env('API_VERSION')];
});

Route::group(['prefix' => 'v1'], function () {
    Route::group(['prefix' => 'dict'], function () {
        Route::get('/programs', 'DictController@getDictPrograms');
    });

    Route::group(['prefix' => 'policies'], function () {
        Route::post('/', 'ApiController@postPolicyCreate');
        Route::post('/calculate', 'ApiController@postPolicyCalculate');
        Route::group(['prefix' => '/{contractID}'],function () {
            Route::get('/', 'ApiController@getPolicy');// вернуть не полис а контракт!
            Route::get('/status', 'ApiController@getPolicyStatus'); //! Проверка договора на предмет оплаты
            Route::get('/payLink', 'ApiController@getPolicyPayLink'); //! Получение ссылки на поплату
            Route::post('/accept', 'ApiController@postPolicyAccept'); //!!!!!!
            Route::get('/print', 'ApiController@getPolicyPdf'); //! Получение печатной формы
            Route::post('/send', 'ApiController@postPolicySend');
        });
    });

    // Resources paths for admin panel
    Route::group(['prefix' => 'program'], function () {
        Route::get('/', 'ProgramController@index');
        Route::post('/', 'ProgramController@store');
        Route::put('/{id}', 'ProgramController@update');
        Route::delete('/{id}', 'ProgramController@destroy');
    });
    Route::group(['prefix' => 'object'], function () {
        Route::get('/', 'ObjectController@index');
        Route::post('/', 'ObjectController@store');
        Route::put('/{id}', 'ObjectController@update');
        Route::delete('/{id}', 'ObjectController@destroy');
    });
    Route::group(['prefix' => 'owner'], function () {
        Route::get('/', 'OwnerController@index');
        Route::post('/', 'OwnerController@store');
        Route::put('/{id}', 'OwnerController@update');
        Route::delete('/{id}', 'OwnerController@destroy');
    });
    Route::group(['prefix' => 'company'], function () {
        Route::get('/', 'CompanyController@index');
        Route::post('/', 'CompanyController@store');
        Route::put('/{id}', 'CompanyController@update');
        Route::delete('/{id}', 'CompanyController@destroy');
    });
    Route::group(['prefix' => 'contract'], function () {
        Route::get('/', 'ContractController@index');
        Route::post('/', 'ContractController@store');
        Route::put('/{id}', 'ContractController@update');
        Route::delete('/{id}', 'ContractController@destroy');
    });
    Route::group(['prefix' => 'payment'], function () {
        Route::get('/', 'PaymentController@index');
        Route::post('/', 'PaymentController@store');
        Route::put('/{id}', 'PaymentController@update');
        Route::delete('/{id}', 'PaymentController@destroy');
    });
});

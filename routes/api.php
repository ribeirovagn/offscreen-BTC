<?php

use Illuminate\Http\Request;

Route::group(['prefix' => 'v1'], function() {
    Route::post('operation', 'OperationController@index');
    Route::get('notify/{txid}', 'TransactionController@notify');

//    if (env('APP_ENV') === 'review') {
        Route::post('send', 'TransactionController@send');
        Route::post('keys', 'TransactionController@getKeys');
        Route::post('newaddress', 'AddressController@create');
        Route::get('balance/{address}', 'BalanceController@show');
        Route::post('increment', 'BalanceController@increment');
        Route::post('decrement', 'BalanceController@decrement');
//    }
});
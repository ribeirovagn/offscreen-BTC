<?php

use Illuminate\Http\Request;

Route::group(['prefix' => 'v1'], function() {
    Route::post('operation', 'OperationController@index');
    Route::get('notify/{txid}', 'TransactionController@notify');
    
    if (env('APP_ENV') === 'local') {
        Route::post('send', 'TransactionController@send');
    }
});


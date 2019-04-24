<?php

use Illuminate\Http\Request;

Route::group(['prefix' => 'v1'], function() {
    Route::get('notify/{txid}', 'TransactionController@notify');

    Route::post('operation', 'OperationController@index')->middleware('multiwallet');
//    if (env('APP_ENV') === 'review') {
    Route::post('psbt', 'TransactionController@sendPsbt')->middleware('multiwallet');
    Route::post('send', 'TransactionController@send')->middleware('multiwallet');
    Route::post('confirmation/{txid}', 'TransactionController@confirmation');
    Route::post('listlockunspent', 'TransactionController@listlockunspent')->middleware('multiwallet');
    Route::post('keys', 'TransactionController@getKeys')->middleware('multiwallet');
    Route::post('newaddress', 'AddressController@create')->middleware('multiwallet');
    Route::get('balance', 'BalanceController@getBalance')->middleware('multiwallet');
    Route::get('balance/{address}', 'BalanceController@show')->middleware('multiwallet');
    Route::post('increment', 'BalanceController@increment')->middleware('multiwallet');
    Route::post('decrement', 'BalanceController@decrement')->middleware('multiwallet');
    Route::post('check', 'BalanceController@checkMultiWallet')->middleware('multiwallet');
    Route::post('credentialsbytx/{txid}', 'TransactionController@getCredentialsByTx');
//    }
});

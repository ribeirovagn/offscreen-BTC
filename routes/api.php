<?php

use Illuminate\Http\Request;

Route::group(['prefix' => 'v1'], function() {
    Route::post('operation', 'OperationController@index');
});

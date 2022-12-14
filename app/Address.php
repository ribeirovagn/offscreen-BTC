<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'wallet',
        'balance',
        'application_data_id'
    ];
    
    
    protected $hidden = [
        'created_at',
        'updated_at',
        'id',
    ];


}

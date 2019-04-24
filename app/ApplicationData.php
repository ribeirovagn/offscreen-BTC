<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApplicationData extends Model {

    protected $fillable = [
        'name',
        'ip',
        'wallet_name',
        'coinbase',
        'authenticity_endpoint',
        'notify_endpoint'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

}

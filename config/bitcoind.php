<?php

return [

    'default' => [
        'scheme' => env('BITCOIND_SCHEME', 'http'),
        'host' => env('BITCOIND_HOST', 'localhost'),
        'port' => env('BITCOIND_PORT', 8332),
        'user' => env('BITCOIND_USER', 'vagner'),
        'password' => env('BITCOIND_PASSWORD', 'YourSuperGreatPasswordNumber_DO_NOT_USE_THIS_OR_YOU_WILL_GET_ROBBED_385593'),
        'ca' => null
    ]
];

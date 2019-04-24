<?php

namespace App\Http\Middleware;

use Closure;
use App\ApplicationData;

class MultiWallet {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $ApplicationData = ApplicationData::where('ip', $request->ip())->firstOrFail();
//        $GLOBALS['app_response'] = $ApplicationData;
        $GLOBALS['app_response'] = [
            'id' => $ApplicationData->id,
            'name' => $ApplicationData->name,
            'ip' => $ApplicationData->ip,
            'wallet_name' => $ApplicationData->wallet_name,
            'coinbase' => $ApplicationData->coinbase,
            'authenticity_endpoint' => $ApplicationData->authenticity_endpoint,
            'notify_endpoint' => $ApplicationData->notify_endpoint
        ];

        return $next($request);
    }

}

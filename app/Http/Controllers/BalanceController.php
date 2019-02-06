<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BalanceController extends Controller
{

    /**
     *
     * Recupera o balance do core
     * @return type
     * @throws Exception
     */
    public static function getBalance(){
        try {

            $bitcoind = bitcoind()->getBalance();
            $address = json_decode($bitcoind->getBody());

            return $address->result;

        } catch (\Exception $ex) {

            throw new \Exception($ex->getMessage());
        }
    }

}

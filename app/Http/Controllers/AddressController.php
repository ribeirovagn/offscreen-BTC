<?php

namespace App\Http\Controllers;

use App\Address;
use Illuminate\Http\Request;

class AddressController extends Controller {

    /**
     * Create new address.
     *
     * @return \Illuminate\Http\Response
     */
    public static function index() {
        try {
            
            $bitcoind = bitcoind()->getNewAddress();
            $address = json_decode($bitcoind->getBody());
            
            return $address->result;
            
        } catch (\Exception $ex) {
            
            throw new Exception($ex->getMessage());
        }
    }
    

    /**
     * Display the specified resource.
     *
     * @param  \App\Address  $address
     * @return \Illuminate\Http\Response
     */
    public static function show($address) {
        try {
            
            $bitcoind = bitcoind()->getAddressInfo($address);
            $address = json_decode($bitcoind->getBody());
            return $address->result;
            
        } catch (\Exception $ex) {
            
            throw new Exception($ex->getMessage());
        }
    }

}

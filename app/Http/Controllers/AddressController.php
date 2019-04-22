<?php

namespace App\Http\Controllers;

use App\Address;
use Illuminate\Http\Request;
use App\Balance;

class AddressController extends Controller {

    /**
     * Create new address.
     *
     * @return \Illuminate\Http\Response
     */
    public static function create() {
        try {

            $bitcoind = bitcoind()->getNewAddress();
            $address = json_decode($bitcoind->getBody());
            $operationController = new OperationController();

            Address::create([
                'wallet' => $address->result,
                'balance' => $operationController->_encryptResponse('0.00000000')
            ]);

            return $address->result;
        } catch (\Exception $ex) {

            throw new \Exception($ex->getMessage());
        }
    }

    /**
     * 
     * @param type $data
     * @return type
     */
    public static function createBatch($address, $amount) {

        $operationController = new OperationController();
        return Address::create([
                    'wallet' => $address,
                    'balance' => $operationController->_encryptResponse($amount)
        ]);
    }

    /**
     * Display info.
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

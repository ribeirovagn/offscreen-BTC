<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Address;

class BalanceController extends Controller
{

    /**
     *
     * Recupera o balance do core
     * @return mixed
     * @throws \Exception
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

    /**
     *
     * Recupera o balance de um usuário
     * @param mixed $address
     * @return mixed
     * @throws \Exception
     */
    public static function show($address){
        $operationController = new OperationController();
        $balance = Address::where('wallet', $address)->first();
        
        if(is_null($balance)){
            throw new \Exception('Remetente não encontrado');
        }
        
        $balance->balance = (float) $operationController->_decryptRequest($balance->balance);
        return $balance;
    }
    
    /**
     * 
     * @param mixed $address
     * @param mixed $amount
     * @return boolean
     * @throws \Exception
     */
    public static function check($address, $amount){
        $balance = self::show($address);
        if(sprintf("%.8f", $balance->balance) === sprintf("%.8f", $amount)){
            return true;
        }
        
        throw new \Exception("155");
    }
    
    
    public function increment(Request $request){
        return self::_increment($request->address, $request->amount);
    }
    
    public function decrement(Request $request){
        return self::_decrement($request->address, $request->amount);
    }


    /**
     *
     * @param mixed $address
     * @param mixed $amount
     * @throws \Exception
     */
    public static function _increment($address, $amount){
        $operationController = new OperationController();
        
        $balance = self::show($address);
        $total = ($balance->balance + $amount);
        $balance->balance = $operationController->_encryptResponse($total);
        
        $balance->update();
        
    }

    /**
     *
     * @param mixed $address
     * @param mixed $amount
     * @throws \Exception
     */
    public static function _decrement($address, $amount){
        $operationController = new OperationController();
        
        $balance = self::show($address);
        $total = ($balance->balance - $amount);
        $balance->balance = $operationController->_encryptResponse($total);
        
        $balance->update();
                
    }
    

}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Address;

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
            throw new Exception($ex->getMessage());
        }        
    }
    
    /**
     * 
     * Recupera o balance de um usuário 
     * @param type $address
     * @return type
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
     * @param type $address
     * @param type $amount
     * @return boolean
     * @throws \Exception
     */
    public static function check($address, $amount){
        $balance = self::show($address);
        if($balance->balance === $amount){
            return true;
        }
        
        throw new \Exception("Saldos diferentes!!!");
    }
    
    
    public function increment(Request $request){
        return self::_increment($request->address, $request->amount);
    }
    
    public function decrement(Request $request){
        return self::_decrement($request->address, $request->amount);
    }
    
    
    /**
     * 
     * @param type $address
     * @param type $amount
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
     * @param type $address
     * @param type $amount
     */
    public static function _decrement($address, $amount){
        $operationController = new OperationController();
        
        $balance = self::show($address);
        $total = ($balance->balance - $amount);
        $balance->balance = $operationController->_encryptResponse($total);
        
        $balance->update();
                
    }
    

}
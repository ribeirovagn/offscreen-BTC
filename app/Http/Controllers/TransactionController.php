<?php

namespace App\Http\Controllers;

use App\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //
    }

    /**
     *
     *
     * @return \Illuminate\Http\Response
     */
    public static function create($data) {
        try {

            $amount = 0;
            $total = $data['amount'] + $data['fee'];

            $output = bitcoind()->listunspent();
            $result = $output->get();

            $translist = [];

            foreach ($result as $key => $saida) {
                $translist[] = [
                    'txid' => $saida['txid'],
                    'vout' => $saida['vout'],
                    'scriptPubKey' => $saida['scriptPubKey'],
                    'redeemScript' => env('REDEEMSCRIPT'),
                    'amount' => $saida['amount']
                ];

                $addr[$key] = $saida['address'];
                $amount += $saida['amount'];
                if ($amount >= $total) {
                    break;
                }
            }

            $rest = sprintf('%.8f', $amount) - sprintf('%.8f', $total);

            $where[$data['toAddress']] = sprintf('%.8f', $data['amount']);
            $where['37rVUh5sz6FMaPKqYfw3o5id6yXrEJJejA'] = sprintf('%.8f', $rest);

            $hex = bitcoind()->createrawtransaction($translist, $where);

            $signed = $this->signrawtransaction($hex->get(), $translist, $data['scriptPubKey']);
            $signed = $this->signrawtransaction($signed['hex'], $translist, $data['scriptPubKey2']);
            
            $return_tx = bitcoind()->sendrawtransaction($signed['hex']);
            return $return_tx->get();
            
        } catch (\Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }
    
    
    
    private function checkAuthenticity($transaction){
        
    }

    /**
     * 
     * Assina as transações
     * 
     * @param type $hex
     * @param type $unspend
     * @param type $privKey
     * @return type
     */
    private function signrawtransaction($hex, $unspend, $privKey) {
        $sign = bitcoind()->signrawtransaction($signed['hex'], $translist, [$data['scriptPubKey']]);
        return $sign->get();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show($txid) {
        //
    }

}

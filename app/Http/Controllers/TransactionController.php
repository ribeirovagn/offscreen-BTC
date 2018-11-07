<?php

namespace App\Http\Controllers;

use App\Transaction;
use Illuminate\Http\Request;
use App\Enum\OperationTypeEnum;

class TransactionController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //
    }

    public function send(Request $request) {
        return self::create($request->all());
    }

    /**
     *
     *
     * @return \Illuminate\Http\Response
     */
    public static function create($data) {
        try {
            $authenticate = self::_checkAuthenticity($data);

            $amount = 0;
            $total = ($data['amount'] + $data['fee']);

            $output = bitcoind()->listunspent();
            $result = $output->get();

            $translist = [];

            foreach ($result as $key => $saida) {
                $translist[] = [
                    'txid' => $saida['txid'],
                    'vout' => $saida['vout'],
                    'scriptPubKey' => $saida['scriptPubKey'],
                    'redeemScript' => '5221033094d0c601b5b30aced38a05eb13794953f11b716f9cbceb9e1e1f09adfbcb5521026ad7017fc261b4f0f3bc3961cab5c2dd6e7af0893826646084eeefc5e05637302103aa95d256540a02b7f3d9790b456f61dff2d507413cad91385917417c1086e44253ae',
                    'amount' => $saida['amount']
                ];

                $addr[$key] = $saida['address'];
                $amount += $saida['amount'];
                if ($amount >= $total) {
                    break;
                }
            }
            
            $rawchangeaddress = (bitcoind()->getrawchangeaddress("p2sh-segwit"))->get();
            
            $rest = sprintf('%.8f', $amount) - sprintf('%.8f', $total);

            $where[$data['toAddress']] = sprintf('%.8f', $data['amount']);
            $where[$rawchangeaddress] = sprintf('%.8f', $rest);

            $hex = bitcoind()->createrawtransaction($translist, $where);
            
            $signed = self::signrawtransaction($hex->get(), $translist, 'KzQRkgswuhoofED1wLu4N5bmyudMBQGc1J57EfzQaxZSmXUUckM7');
            $signed = self::signrawtransaction($signed['hex'], $translist, 'KyHJu81GcGp8Lm6dxpZnXzQRtBEpAcht88UeXWzQL1XnWjq2WpmT');

            $sender =  bitcoind()->sendrawtransaction($signed['hex']);
            return $sender->get();
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    /**
     * 
     * @param type $transaction
     * @return type
     * @throws \Exception
     */
    private static function _checkAuthenticity($transaction) {
        try {

            $response = GuzzleController::postOffscreen(OperationTypeEnum::CHECK_AUTHENTICITY, $transaction);
            if (!$response) {
                throw new \Exception("[ECI]");
            }
            return self::_getKeys();
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    /**
     * 
     * @return type
     * @throws \Exception
     */
    private static function _getKeys() {
        $response = GuzzleController::postSign();
        if (!$response) {
            throw new \Exception("[KSI]");
        }
        return $response;
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
    private static function signrawtransaction($hex, $unspend, $privKey) {
        return (bitcoind()->signrawtransactionwithkey($hex, [$privKey], $unspend))->get();
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

    /**
     * 
     * @return type
     * @throws \Exception
     */
    public function keys() {
        $response = GuzzleController::postSign();
        if (!$response) {
            throw new \Exception("[KSI]");
        }
        return $response;
    }

    /**
     * 
     * @param type $txid
     * @return type
     */
    public function notify($txid) {
        $data = $this->_gettransaction($txid);
        $response = GuzzleController::postOffscreen(OperationTypeEnum::NOTIFY_WALLET, $data);
        return $response;
    }
    
    /**
     * 
     * @param type $txid
     * @return type
     */
    public function confirmation($txid){
        return $this->_gettransaction($txid);
    }

    /**
     * 
     * @param type $txid
     * @return type
     */
    private function _gettransaction($txid) {
        $gettransaction = bitcoind()->gettransaction($txid);
        $transactionData = $gettransaction->get();
        $data = [
            'amount' => abs($transactionData['amount']),
            'fee' => isset($transactionData['fee']) ? $transactionData['fee'] : 0,
            'confirmations' => $transactionData['confirmations'],
            'txid' => $transactionData['txid'],
            'toAddress' => $transactionData['details'][0]['address']
        ];
        return $data;
    }
    
    public static function estimateFee($conf_target){
        $gettransaction = bitcoind()->estimatesmartfee($conf_target);
        $result = $gettransaction->get();
        return (string)$result['feerate'];
    }

}

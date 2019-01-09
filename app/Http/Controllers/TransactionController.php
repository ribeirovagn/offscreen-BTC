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
                    'redeemScript' => $authenticate['redeemScript'],
                    'amount' => $saida['amount']
                ];

                $amount += $saida['amount'];
                if ($amount >= $total) {
                    break;
                }
            }

            $rawchangeaddress = (bitcoind()->getrawchangeaddress())->get();
            $rest = sprintf('%.8f', $amount) - sprintf('%.8f', $total);
            $where[$data['toAddress']] = sprintf('%.8f', $data['amount']);
            $where[$rawchangeaddress] = sprintf('%.8f', $rest);
            $hex = bitcoind()->createrawtransaction($translist, $where);
            bitcoind()->walletpassphrase($authenticate['key'], 10);
            $signed = (bitcoind()->signrawtransactionwithwallet($hex->get(), $translist))->get();
            $sender =  bitcoind()->sendrawtransaction($signed['hex']);
            bitcoind()->walletlock();
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
            'amount' => abs($transactionData['details'][0]['amount']),
            'fee' => isset($transactionData['fee']) AND $transactionData['fee'] > 0 ? $transactionData['fee'] : 0,
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

    public static function received(){
        $gettransactions = bitcoind()->listreceivedbyaddress();
        $result = $gettransactions->get();
        return $result;
    }
}

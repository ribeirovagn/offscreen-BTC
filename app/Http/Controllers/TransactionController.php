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

    public function sendPsbt(Request $request) {
        return self::createPsbt($request->all());
    }

    /**
     *
     *
     * @return \Illuminate\Http\Response
     */
    public static function create($data) {
        try {
            BalanceController::check($data['fromAddress'], $data['balance']);

            $authenticate = self::_checkAuthenticity($data);

            $amount = 0;
            $total = ($data['amount'] + $data['fee']);
            $output = bitcoind()->listunspent(1, 99, [env('HOTWALLET')]);
            $result = $output->get();

            $translist = [];

            foreach ($result as $key => $saida) {
                if ($saida['spendable']) {
                    $translist[] = [
                        'txid' => $saida['txid'],
                        'vout' => $saida['vout'],
                        'scriptPubKey' => $saida['scriptPubKey'],
                        'redeemScript' => $saida['redeemScript'],
                        'amount' => (string) $saida['amount']
                    ];

                    $amount += $saida['amount'];
                    if ($amount >= $total) {
                        break;
                    }
                }
            }

            $rest = sprintf('%.8f', $amount) - sprintf('%.8f', $total);
            $where[$data['toAddress']] = sprintf('%.8f', $data['amount']);
            $where[env('HOTWALLET')] = (string) $rest;

            $hex = (bitcoind()->createrawtransaction($translist, $where))->get();
            $sign = (bitcoind()->signrawtransactionwithkey($hex, [$authenticate['key'], $data['scriptPubKey']], $translist))->get();
            $testmempoolaccept = (bitcoind()->testmempoolaccept([$sign['hex']]))->get();

            if ($testmempoolaccept[0]['allowed']) {
                $sender = bitcoind()->sendrawtransaction($sign['hex']);
                return $sender->get();
            }

            throw new \Exception($testmempoolaccept[0]['reject-reason'], 422);
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    public static function createPsbt($data) {
        try {

//            BalanceController::check($data['send'], $data['balance']);

            $authenticate = self::_checkAuthenticity($data);

            $amount = 0;
            $total = ($data['amount'] + $data['fee']);
            $output = bitcoind()->wallet('psbt1')->listunspent(1, 99, [env('HOTWALLET')]);
            $result = $output->get();

            $translist = [];

            foreach ($result as $key => $saida) {
                if (!$saida['spendable']) {
                    $translist[] = [
                        'txid' => $saida['txid'],
                        'vout' => $saida['vout'],
                        'scriptPubKey' => $saida['scriptPubKey'],
                        'redeemScript' => $saida['redeemScript'],
                        'amount' => (string) $saida['amount']
                    ];

                    $amount += $saida['amount'];
                    if ($amount >= $total) {
                        break;
                    }
                }
            }
//            return $translist;

            $rest = sprintf('%.8f', $amount) - sprintf('%.8f', $total);
            $where[$data['toAddress']] = sprintf('%.8f', $data['amount']);
            $where[env('HOTWALLET')] = (string) $rest;

            $hex = (bitcoind()->wallet('psbt1')->createrawtransaction($translist, $where))->get();
            $hex = (bitcoind()->wallet('psbt1')->converttopsbt($hex))->get();
            
            bitcoind()->wallet("psbt2")->walletpassphrase("zaq12wsx", 1);
            $psbt1 = (bitcoind()->wallet("psbt2")->walletprocesspsbt($hex))->get();
            bitcoind()->wallet("psbt3")->walletpassphrase("zaq12wsx", 1);
            $psbt2 = (bitcoind()->wallet("psbt3")->walletprocesspsbt($psbt1['psbt']))->get();
            
            $final = (bitcoind()->wallet("psbt1")->finalizepsbt($psbt2['psbt']))->get();
            
            $testmempoolaccept = (bitcoind()->testmempoolaccept([$final['hex']]))->get();

            if ($testmempoolaccept[0]['allowed']) {
                $sender = bitcoind()->sendrawtransaction($final['hex']);
                return $sender->get();
            }

            throw new \Exception($testmempoolaccept[0]['reject-reason'], 422);            
        
        } catch (\Exception $exc) {
            throw new \Exception($exc->getMessage());
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
//                throw new \Exception("[ECI]", 422);
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

    public function getKeys() {
        return self::_getKeys();
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
        return (bitcoind()->signrawtransactionwithkey($hex, $privKey, $unspend))->get();
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
    public function confirmation($txid) {
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
            'fee' => isset($transactionData['fee']) ? abs($transactionData['fee']) : 0,
            'confirmations' => $transactionData['confirmations'],
            'txid' => $transactionData['txid'],
            'toAddress' => $transactionData['details'][0]['address']
        ];
        return $data;
    }

    public static function estimateFee($conf_target) {
        $gettransaction = bitcoind()->estimatesmartfee($conf_target);
        $result = $gettransaction->get();
        return (string) $result['feerate'];
    }

    public static function received() {
        $gettransactions = bitcoind()->listreceivedbyaddress();
        $result = $gettransactions->get();
        return $result;
    }

}

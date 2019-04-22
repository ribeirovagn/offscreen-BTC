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
//            BalanceController::check($data['fromAddress'], $data['balance']);

            $authenticate = self::_checkAuthenticity($data);

            $amount = 0;
            $total = ($data['amount'] + $data['fee']);
            $result = (bitcoind()->wallet(env('WALLET_MAIN'))->listunspent())->get();

            $translist = [];

            foreach ($result as $key => $saida) {
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


            $rawchangeaddress = (bitcoind()->wallet(env('WALLET_MAIN'))->getrawchangeaddress())->get();

            $rest = sprintf('%.8f', $amount) - sprintf('%.8f', $total);
//            $where[$data['toAddress']] = sprintf('%.8f', $data['amount']);
            $where[env('HOTWALLET')] = (string) $total;

            $hex = (bitcoind()->wallet(env('WALLET_MAIN'))->createrawtransaction($translist, $where, 1))->get();
            
            $fundrawtransaction = (bitcoind()->wallet(env('WALLET_MAIN'))->fundrawtransaction($hex, [
                        'changeAddress' => env('HOTWALLET'),
                        'includeWatching' => true
                    ]))->get();
            
            $sign = (bitcoind()->wallet(env('WALLET_MAIN'))->signrawtransactionwithkey($fundrawtransaction['hex'], [$data['scriptPubKey']], []))->get();

            $decode = (bitcoind()->wallet(env('WALLET_MAIN'))->decoderawtransaction($sign['hex']))->get();

            $multisigTrans[] = [
                'txid' => $decode['txid'],
                'vout' => 0,
                'scriptPubKey' => $decode['vout'][0]['scriptPubKey']['hex'],
                'redeemScript' => $authenticate['redeemScript'],
//                'amount' => (string) $decode['vout'][1]['value']
            ];

//            return $multisigTrans;
            $options[$data['toAddress']] = $data['amount'];
            $hex = (bitcoind()->wallet(env('WALLET_MAIN'))->createrawtransaction($multisigTrans, $options))->get();


            $decode = (bitcoind()->wallet(env('WALLET_MAIN'))->decoderawtransaction($hex))->get();

//            return $decode;

            $sign = (bitcoind()->wallet(env('WALLET_MAIN'))->signrawtransactionwithkey($fundrawtransaction['hex'], [$authenticate['key'], $data['scriptPubKey']], []))->get();


            return $sign;

            $testmempoolaccept = (bitcoind()->wallet(env('WALLET_MAIN'))->testmempoolaccept([$sign['hex']]))->get();

            return $testmempoolaccept;

            if ($testmempoolaccept[0]['allowed']) {
                $sender = bitcoind()->wallet(env('WALLET_MAIN'))->sendrawtransaction($sign['hex']);
                return $sender->get();
            }

            throw new \Exception($testmempoolaccept[0]['reject-reason'], 422);
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    public function listlockunspent() {
        $output = (bitcoind()->wallet('testing')->listunspent())->get();
        $output = (bitcoind()->wallet('psbt1')->lockunspent(false))->get();
        return;
        $translist = [];
        foreach ($output as $key => $saida) {
            if ($saida['spendable']) {
                $translist[] = [
                    'txid' => $saida['txid'],
                    'vout' => $saida['vout']
                ];
            }
        }
        return $translist;
    }

    public static function createPsbt($data) {
        try {

            BalanceController::check($data['fromAddress'], $data['balance']);

            $authenticate = self::_checkAuthenticity($data);

            bitcoind()->wallet(env('WALLET_SECONDARY'))->walletpassphrase($authenticate['key'], 2);
            bitcoind()->wallet(env('WALLET_SIGN'))->walletpassphrase($data['scriptPubKey'], 2);

            $amount = 0;
            $total = ($data['amount'] + $data['fee']);

            $where[$data['toAddress']] = sprintf('%.8f', $data['amount']);

            $hex = (bitcoind()->wallet(env('WALLET_MAIN'))->walletcreatefundedpsbt([], $where, 101, ['includeWatching' => true], true))->get();
            $decode = (bitcoind()->wallet(env('WALLET_MAIN'))->decodepsbt($hex['psbt']))->get();

            $psbt1 = (bitcoind()->wallet(env('WALLET_SECONDARY'))->walletprocesspsbt($hex['psbt']))->get();
            $psbt2 = (bitcoind()->wallet(env('WALLET_SIGN'))->walletprocesspsbt($psbt1['psbt']))->get();
            $final = (bitcoind()->wallet(env('WALLET_MAIN'))->finalizepsbt($psbt2['psbt']))->get();

            $decode = (bitcoind()->wallet(env('WALLET_MAIN'))->decoderawtransaction($final['hex']))->get();

            $testmempoolaccept = (bitcoind()->wallet(env('WALLET_MAIN'))->testmempoolaccept([$final['hex']]))->get();

            if ($testmempoolaccept[0]['allowed']) {
                $sender = bitcoind()->wallet(env('WALLET_MAIN'))->sendrawtransaction($final['hex']);
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
        return (bitcoind()->wallet(env('WALLET_MAIN'))->signrawtransactionwithkey($hex, $privKey, $unspend))->get();
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
        $gettransaction = bitcoind()->wallet(env('WALLET_MAIN'))->gettransaction($txid);
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
        $gettransaction = bitcoind()->wallet(env('WALLET_MAIN'))->estimatesmartfee($conf_target);
        $result = $gettransaction->get();
        return (string) $result['feerate'];
    }

    public static function received() {
        $gettransactions = bitcoind()->wallet(env('WALLET_MAIN'))->listreceivedbyaddress();
        $result = $gettransactions->get();
        return $result;
    }

}

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

            $output = bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->listunspent();
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

            $rawchangeaddress = (bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->getrawchangeaddress())->get();
            $rest = sprintf('%.8f', $amount) - sprintf('%.8f', $total);
            $where[$data['toAddress']] = sprintf('%.8f', $data['amount']);
            $where[$rawchangeaddress] = sprintf('%.8f', $rest);
            $hex = bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->createrawtransaction($translist, $where);
            bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->walletpassphrase($authenticate['key'], 2);
            $signed = (bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->signrawtransactionwithwallet($hex->get(), $translist))->get();
            bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->walletlock();

            $testmempoolaccept = (bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->testmempoolaccept([$signed['hex']]))->get();

            if ($testmempoolaccept[0]['allowed']) {
                $sender = bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->sendrawtransaction($signed['hex']);
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

            $hex = (bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->walletcreatefundedpsbt([], $where, 101, ['includeWatching' => true], true))->get();
            $decode = (bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->decodepsbt($hex['psbt']))->get();

            $psbt1 = (bitcoind()->wallet(env('WALLET_SECONDARY'))->walletprocesspsbt($hex['psbt']))->get();
            $psbt2 = (bitcoind()->wallet(env('WALLET_SIGN'))->walletprocesspsbt($psbt1['psbt']))->get();
            $final = (bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->finalizepsbt($psbt2['psbt']))->get();

            $decode = (bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->decoderawtransaction($final['hex']))->get();

            $testmempoolaccept = (bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->testmempoolaccept([$final['hex']]))->get();

            if ($testmempoolaccept[0]['allowed']) {
                $sender = bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->sendrawtransaction($final['hex']);
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
        return (bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->signrawtransactionwithkey($hex, $privKey, $unspend))->get();
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
        if ($data) {
            $response = GuzzleController::postOffscreen(OperationTypeEnum::NOTIFY_WALLET, $data);
            return $response;
        }
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
        if ($this->getCredentialsByTx($txid)) {
            $gettransaction = bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->gettransaction($txid);
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
        return false;
    }

    public static function estimateFee($conf_target) {
        $gettransaction = bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->estimatesmartfee($conf_target);
        $result = $gettransaction->get();
        return (string) $result['feerate'];
    }

    public static function received() {
        $gettransactions = bitcoind()->wallet($GLOBALS['app_response']['wallet_name'])->listreceivedbyaddress();
        $result = $gettransactions->get();
        return $result;
    }

    public function getCredentialsByTx($txid) {
        $applicationData = \App\ApplicationData::all();

        foreach ($applicationData as $ApplicationData) {
            try {
                $gettransaction = bitcoind()->wallet($ApplicationData->wallet_name)->gettransaction($txid);
                $GLOBALS['app_response'] = [
                    'id' => $ApplicationData->id,
                    'name' => $ApplicationData->name,
                    'ip' => $ApplicationData->ip,
                    'wallet_name' => $ApplicationData->wallet_name,
                    'coinbase' => $ApplicationData->coinbase,
                    'authenticity_endpoint' => $ApplicationData->authenticity_endpoint,
                    'notify_endpoint' => $ApplicationData->notify_endpoint
                ];

                return true;
            } catch (\Exception $ex) {
                
            }
        }
        return false;
    }

}

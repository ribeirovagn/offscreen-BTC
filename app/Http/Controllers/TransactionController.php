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
                    'redeemScript' => '5221033094d0c601b5b30aced38a05eb13794953f11b716f9cbceb9e1e1f09adfbcb5521026ad7017fc261b4f0f3bc3961cab5c2dd6e7af0893826646084eeefc5e05637302103aa95d256540a02b7f3d9790b456f61dff2d507413cad91385917417c1086e44253ae',
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

            $sign = bitcoind()->signrawtransaction($hex->get(), $translist, [$data['scriptPubKey']]);
            $signed = $sign->get();


            if (isset($data['scriptPubKey2'])) {
                $sign = bitcoind()->signrawtransaction($signed['hex'], $translist, [$data['scriptPubKey']]);
                $signed = $sign->get();
            } else {
                $sign = bitcoind()->signrawtransaction($signed['hex'], $translist, ['KyHJu81GcGp8Lm6dxpZnXzQRtBEpAcht88UeXWzQL1XnWjq2WpmT']);
                $signed = $sign->get();
            }

            $return_tx = bitcoind()->sendrawtransaction($signed['hex']);
            $tx = $return_tx->get();

            return $tx;
        } catch (\Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function edit(Transaction $transaction) {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $transaction) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transaction $transaction) {
        //
    }

}

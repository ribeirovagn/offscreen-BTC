<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class GuzzleController extends Controller {

    /**
     * 
     * @param type $type
     * @param type $data
     * @return type
     * @throws \Exception
     */
    public static function postOffscreen($type, $data = "") {
        $operationController = new OperationController();

        try {
            $result = (new Client())->post(config('services.offscreen.endpoint'), [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    encrypt([
                        'data' => $data,
                        'type' => $type
                    ])
                ]
            ]);
            $response = $result->getBody()->getContents();
            return $operationController->_decryptRequest($response);
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    
    /**
     * Recupera as assinaturas
     * @return type
     * @throws \Exception
     */
    public static function postSign() {
        $operationController = new OperationController();

        try {
            $result = (new Client())->post(config('services.sign.endpoint'), [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    encrypt([
                        'data' => "transactionssign",
                        'type' => "transactionssign",
                        'coin' => env("COIN")
                    ])
                ]
            ]);
            $response = $result->getBody()->getContents();
            return $operationController->_decryptRequest($response);
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

}

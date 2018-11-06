<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enum\OperationTypeEnum;

class OperationController extends Controller {

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {

        try {

            $input = $request->all();
            $input = $this->_decryptRequest($input[0]);

            switch ($input['type']) {
                case OperationTypeEnum::CREATE_ADDRESS:
                    $result = AddressController::index();
                    break;

                case OperationTypeEnum::FIRST_SIGN_TRANSACTION:

                    $result = TransactionController::create($input['data']);
                    break;

                case OperationTypeEnum::GET_BALANCE:
                    $result = BalanceController::getBalance();
                    break;

                case OperationTypeEnum::SECOND_SIGN_TRANSACTION:
                    $result = TransactionController::create($input['data']);
                    break;

                case OperationTypeEnum::CONFIRMATION:
                    $result = TransactionController::confirmation($input['data']['txid']);
                    break;

                default:
                    throw new \Exception('EDI');
            }

            return $this->_encryptResponse($result);
        } catch (\Exception $ex) {
            return $this->_encryptResponse(['error' => $ex->getMessage()]);
        }
    }

    /**
     * 
     * @param Request $request
     * @return type
     */
    public function _decryptRequest($request) {
        return decrypt($request);
    }

    /**
     * 
     * @param Request $request
     * @return type
     */
    public function _encryptResponse($response) {
        return encrypt($response);
    }

}

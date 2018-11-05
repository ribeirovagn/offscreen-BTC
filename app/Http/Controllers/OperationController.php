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

            $input = $this->_decryptRequest($request);

            switch ($input['type']) {
                case OperationTypeEnum::CREATE_ADDRESS:
                    $result = AddressController::index();
                    break;

                case OperationTypeEnum::FIRST_SIGN_TRANSACTION:
                    $input['data']['scriptPubKey2'] = env('KEY');
                    $result = TransactionController::create($input['data']);
                    break;

                case OperationTypeEnum::GET_BALANCE:
                    $result = BalanceController::getBalance();
                    break;

                case OperationTypeEnum::SECOND_SIGN_TRANSACTION:
                    $result = TransactionController::create($input['data']);
                    break;

                default:
                    return response(['error' => 'Invalid data...'], 422);
            }

            return $this->_encryptResponse($result);
        } catch (\Exception $ex) {
            return response(['error' => $ex->getMessage()], 422);
        }
    }

    
    /**
     * 
     * @param Request $request
     * @return type
     */
    private function _decryptRequest(Request $request) {
        $input = $request->all();
        return decrypt($input[0]);
    }

    
    /**
     * 
     * @param Request $request
     * @return type
     */
    private function _encryptResponse($response) {
        return encrypt($response);
    }

}

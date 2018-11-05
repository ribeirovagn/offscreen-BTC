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
            
            $input = decrypt($input[0]);

            switch ($input['type']) {
                case OperationTypeEnum::CREATE_ADDRESS:
                    $result = AddressController::index();
                    break;

                case OperationTypeEnum::FIRST_SIGN_TRANSACTION:
                    $result = TransactionController::create($input['data']);
                    break;

                case OperationTypeEnum::GET_BALANCE:
                    $result = AddressController::getBalance();

                    break;

                case OperationTypeEnum::SECOND_SIGN_TRANSACTION:

                    break;

                default:
                    return response(['error' => 'Invalid data...'], 422);
            }

            return encrypt($result);
            
        } catch (\Exception $ex) {
            return response(['error' => $ex->getMessage()], 422);
        }
    }

}

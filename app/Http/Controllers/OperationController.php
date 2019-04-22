<?php

namespace App\Http\Controllers;

use App\Enum\OperationTypeEnum;
use Illuminate\Http\Request;

class OperationController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        try {

            $input = $request->all();
            $input = $this->_decryptRequest($input[0]);

            switch ($input['type']) {
                case OperationTypeEnum::CREATE_ADDRESS:
                    $result = AddressController::create();
                    break;
                case OperationTypeEnum::IMPORT_ADDRESS:
                    $result = AddressController::createBatch($input['data']['address'], $input['data']['amount']);
                    break;

                case OperationTypeEnum::FIRST_SIGN_TRANSACTION:
                    $result = TransactionController::createPsbt($input['data']);
                    break;

                case OperationTypeEnum::GET_BALANCE:
                    $result = BalanceController::getBalance();
                    break;

                case OperationTypeEnum::SECOND_SIGN_TRANSACTION:
                    $result = TransactionController::createPsbt($input['data']);
                    break;

                case OperationTypeEnum::CONFIRMATION:
                    $TransactionController = new TransactionController();
                    $result = $TransactionController->confirmation($input['data']['txid']);
                    break;

                case OperationTypeEnum::ESTIMATE_SMART_FEE:
                    $result = TransactionController::estimateFee($input['data']);
                    break;

                case OperationTypeEnum::RECEIVED_TRANSACTIONS:
                    $result = TransactionController::received();
                    break;

                case OperationTypeEnum::NOTIFY_WALLET:
                    $TransactionController = new TransactionController();
                    $result = $TransactionController->notify($input['data']['txid']);
                    break;

                case OperationTypeEnum::DECREMENT_BALANCE:
                    $TransactionController = new TransactionController();
                    $result = BalanceController::_decrement($input['data']['address'], $input['data']['amount']);
                    break;

                case OperationTypeEnum::INCREMENT_BALANCE:
                    $TransactionController = new TransactionController();
                    $result = BalanceController::_increment($input['data']['address'], $input['data']['amount']);
                    break;

                default:
                    throw new \Exception('Operação desconhecida!');
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
    public function _decryptRequest($request)
    {
        return decrypt($request);
    }

    /**
     *
     * @param Request $request
     * @return type
     */
    public function _encryptResponse($response)
    {
        return encrypt($response);
    }

}

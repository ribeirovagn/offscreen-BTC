<?php


namespace App\Enum;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OperationTypeEnum
 *
 * @author vagner
 */
abstract class OperationTypeEnum {
    const CREATE_ADDRESS = 'CREATE ADDRESS';
    const FIRST_SIGN_TRANSACTION = 'FIRST SIGN TRANSACTION';
    const SECOND_SIGN_TRANSACTION = 'SECOND SIGN TRANSACTION';
    const GET_BALANCE = 'GET BALANCE';
    const ESTIMATE_SMART_FEE = 'ESTIMATE SMART FEE';
    const CHECK_AUTHENTICITY = 'CHECK AUTHENTICITY';
    const NOTIFY_WALLET = 'NOTIFY WALLET';
    const CONFIRMATION = 'CONFIRMATION';
    const RECEIVED_TRANSACTIONS = 'RECEIVED TRANSACTIONS';
    const INCREMENT_BALANCE = 'INCREMENT BALANCE';
    const DECREMENT_BALANCE = 'DECREMENT BALANCE';
}

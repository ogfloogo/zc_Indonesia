<?php

namespace app\pay\controller;

use app\api\controller\Controller;
use app\pay\model\Coverpay as ModelPaymentCoverpay;
use think\Hook;
use fast\Random;
use think\Log;

/**
 * Rpay
 */
class Coverpay extends Controller
{

    /**
     * 代收回调
     */
    public function paynotify()
    {
        $data = $_POST;
        Log::mylog('支付回调_data', $data, 'coverpayhd');
        (new ModelPaymentCoverpay())->paynotify($data);
        exit('OK');
    }

    /**
     * 代付回调
     */
    public function paydainotify()
    {
        $data = $_POST;
        Log::mylog('提现回调_data', $data, 'coverpaydfhd');
        (new ModelPaymentCoverpay())->paydainotify($data);
        exit('OK');
    }

    /**
     * 代收回调
     */
    // public function paynotifytest()
    // {
    //     $data = $_POST;
    //     Log::mylog('支付回调_data', $data, 'rpayhd');
    //     (new Modelrpay())->paynotifytest($data);
    //     exit('success');
    // }
}

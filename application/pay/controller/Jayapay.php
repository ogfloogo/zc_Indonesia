<?php

namespace app\pay\controller;

use app\api\controller\Controller;
use app\pay\model\Jayapay as ModelPaymentJayapay;
use think\Hook;
use fast\Random;
use think\Log;

/**
 * Rpay
 */
class Jayapay extends Controller
{

    /**
     * 代收回调
     */
    public function paynotify()
    {
        $data = file_get_contents("php://input");
        Log::mylog('支付回调_data', $data, 'jayapayhd');
        (new ModelPaymentJayapay())->paynotify(json_decode($data,true));
        exit('success');
    }

    /**
     * 代付回调
     */
    public function paydainotify()
    {
        $data = file_get_contents("php://input");
        Log::mylog('提现回调_data', $data, 'jayapaydfhd');
        (new ModelPaymentJayapay())->paydainotify(json_decode($data,true));
        exit('success');
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

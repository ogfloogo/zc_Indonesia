<?php

namespace app\pay\controller;

use app\api\controller\Controller;
use app\pay\model\Bspay as ModelPaymentBspay;
use think\Hook;
use fast\Random;
use think\Log;

/**
 * Rpay
 */
class Bspay extends Controller
{

    /**
     * 代收回调
     */
    public function paynotify()
    {
        $data = file_get_contents("php://input");
        Log::mylog('支付回调_data', $data, 'bspayhd');
        (new ModelPaymentBspay())->paynotify(json_decode($data,true));
        exit('OK');
    }

    /**
     * 代付回调
     */
    public function paydainotify()
    {
        $data = file_get_contents("php://input");
        Log::mylog('提现回调_data', $data, 'bspaydfhd');
        (new ModelPaymentBspay())->paydainotify(json_decode($data,true));
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

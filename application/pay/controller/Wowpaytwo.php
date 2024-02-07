<?php

namespace app\pay\controller;

use app\api\controller\Controller;
use app\pay\model\Wowpaytwo as ModelPaymentWowpaytwo;
use think\Hook;
use fast\Random;
use think\Log;

/**
 * Rpay
 */
class Wowpaytwo extends Controller
{

    /**
     * 代收回调
     */
    public function paynotify()
    {
        $data = file_get_contents("php://input");
        Log::mylog('支付回调_data', $data, 'wowpaytwohd');
        $header = $this->request->header();
        $header = json_decode($header,true);
        $sign = $header['x-sign'];
        (new ModelPaymentWowpaytwo())->paynotify(json_decode($data,true),$sign);
        echo '{"success": true}';exit;
    }

    /**
     * 代付回调
     */
    public function paydainotify()
    {
        $data = file_get_contents("php://input");
        Log::mylog('提现回调_data', $data, 'wowpaytwopaydfhd');
        (new ModelPaymentWowpaytwo())->paydainotify(json_decode($data,true));
        echo '{"success": true}';exit;
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

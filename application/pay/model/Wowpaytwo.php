<?php

namespace app\pay\model;

use function EasyWeChat\Kernel\Support\get_client_ip;

use app\api\model\Report;
use app\api\model\Usercash;
use app\api\model\Userrecharge;
use app\api\model\Usertotal;
use think\Cache;
use think\Model;
use think\Db;
use think\Log;
use think\Exception;


class Wowpaytwo extends Model
{
    //代付提单url(提现)
    public $dai_url = 'https://lPDLbr.wowpayidr.com/rest/cash-out/disbursement';
    //代收提交url(充值)
    public $pay_url = 'https://lPDLbr.wowpayidr.com/rest/cash-in/payment-checkout';
    //代付回调(提现)
    public $notify_dai = 'https://api.rothpro.id/pay/Wowpaytwo/paydainotify';
    //代收回调(充值)
    public $notify_pay = 'https://api.rothpro.id/pay/Wowpaytwo/paynotify';
    //代收秘钥
    public $key = "C3GASXHHTIDZXSWTK2TVCTIPSLF7ZR6A";
    //代付秘钥
    public function pay($order_id, $price, $userinfo, $channel_info)
    {
        if($channel_info['busi_code']){
            $supportMethods = $channel_info['busi_code'];
        }else{
            $supportMethods = [];
        }
        $param = [
            'referenceId' => $order_id,
            'amount' => $price,
            'notifyUrl' => $this->notify_pay,
            'supportMethods' => [$supportMethods]
        ];
        Log::mylog("提交参数", $param, "wowpaytwo");
        $header[] = "X-SECRET: {$this->key}";
        $header[] = "X-SN: {$channel_info['merchantid']}";
        $header[] = "Content-Type: application/json";
        $return_json = $this->http_Post($this->pay_url, $header,json_encode($param,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        Log::mylog("返回参数", $return_json, "wowpaytwo");
        $return_array = json_decode($return_json, true);
        if ($return_array['code'] == 'SUCCESS') {
            $return_array = [
                'code' => 1,
                'payurl' => !empty(($return_array['data']['url'])) ? ($return_array['data']['url']) : '',
                'type' => 1,
            ];
        } else {
            $return_array = [
                'code' => 0,
                'msg' => $return_array['message'],
            ];
        }
        return $return_array;
    }

    /**
     * 代收回调
     */
    public function paynotify($params,$params2,$sign)
    {
        if ($params['orders'][0]['status'] == 'SUCCEED') {
            Log::mylog('验签', $sign, 'wowpaytwohd');
            $check = base64_encode(hash_hmac('sha256', $params2, $this->key ,true));
            if ($sign != $check) {
                Log::mylog('验签失败', $params, 'wowpaytwohd');
                return false;
            }
            $order_id = $params['referenceId']; //商户订单号
            $order_num = $params['orders'][0]['msn']; //平台订单号
            $amount = $params['orders'][0]['receivedAmount']; //支付金额
            (new Paycommon())->paynotify($order_id, $order_num, $amount, 'wowpaytwohd');
        } else {
            //更新订单信息
            $upd = [
                'status' => 2,
                'order_id' => $params['referenceId'],
                'updatetime' => time(),
            ];
            (new Userrecharge())->where('order_id', $params['referenceId'])->where('status', 0)->update($upd);
            Log::mylog('支付回调失败！', $params, 'wowpaytwohd');
        }
    }

    /**
     *提现 
     */
    public function withdraw($data, $channel)
    {
        $bankname = '';
        if($data['bankname'] == 'Bank BRI'){
            $bankname = '002';
        }

        if($data['bankname'] == 'Bank Mandiri'){
            $bankname = '008';
        }

        if($data['bankname'] == 'Bank BNI'){
            $bankname = '009';
        }

        if($data['bankname'] == 'Bank Danamon'){
            $bankname = '011';
        }

        if($data['bankname'] == 'Bank Permata'){
            $bankname = '013';
        }

        if($data['bankname'] == 'Bank BCA'){
            $bankname = '014';
        }

        if($data['bankname'] == 'BII Maybank'){
            $bankname = '016';
        }

        if($data['bankname'] == 'Bank Panin'){
            $bankname = '019';
        }

        if($data['bankname'] == 'CIMB Niaga'){
            $bankname = '022';
        }

        if($data['bankname'] == 'Bank UOB INDONESIA'){
            $bankname = '023';
        }
        if($data['bankname'] == 'Bank OCBC NISP'){
            $bankname = '028';
        }
        if($data['bankname'] == 'CITIBANK'){
            $bankname = '031';
        }
        if($data['bankname'] == 'Bank ARTHA GRAHA'){
            $bankname = '037';
        }
        if($data['bankname'] == 'Bank TOKYO MITSUBISHI UFJ'){
            $bankname = '042';
        }
        if($data['bankname'] == 'Bank DBS'){
            $bankname = '046';
        }
            if($data['bankname'] == 'Standard Chartered'){
            $bankname = '050';
        }
        if($data['bankname'] == 'Bank CAPITAL'){
            $bankname = '054';
        }
        if($data['bankname'] == 'ANZ Indonesia'){
            $bankname = '061';
        }
        if($data['bankname'] == 'Bank OF CHINA'){
            $bankname = '069';
        }
        if($data['bankname'] == 'Bank HSBC'){
            $bankname = '041';
        }
        if($data['bankname'] == 'Bank MAYAPADA'){
            $bankname = '097';
        }
        if($data['bankname'] == 'Bank Jawa Barat'){
            $bankname = '110';
        }
        if($data['bankname'] == 'Bank JATENG'){
            $bankname = '113';
        }
        if($data['bankname'] == 'Bank Jatim'){
            $bankname = '114';
        }
        if($data['bankname'] == 'Bank Aceh Syariah'){
            $bankname = '116';
        }

        if($data['bankname'] == 'OVO'){
            $bankname = 'OVO';
        }
        if($data['bankname'] == 'Dana'){
            $bankname = 'DANA';
        }

        if($data['bankname'] == 'ShopeePay'){
            $bankname = 'SHOPEEPAY';
        }
        if(empty($bankname)){
            return ['code '=>'fail','message'=>'不支持的银行'];
        }
        $param = array(
            'referenceId' => $data['order_id'],
            'bankCode' => $bankname,
            'customerName' => $data['username'], //收款姓名
            'cardNo' => $data['bankcard'], //收款账号
            'amount' => $data['trueprice'],
            'notifyUrl' => $this->notify_dai,
        );
        $header[] = "X-SECRET: {$this->key}";
        $header[] = "X-SN: {$channel['merchantid']}";
        $header[] = "Content-Type: application/json";
        Log::mylog('提现提交参数', $param, 'wowpaytwodf');
        $return_json = $this->http_Post($this->dai_url, $header,json_encode($param,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        Log::mylog($return_json, 'wowpaytwodf', 'wowpaytwodf');
        return $return_json;
    }

    /**
     * 提现回调
     */
    public function paydainotify($params,$params2,$sign)
    {
        $check = base64_encode(hash_hmac('sha256', $params2, $this->key ,true));
        if ($sign != $check) {
            Log::mylog('验签失败', $params, 'wowpaytwodfhd');
            return false;
        }
        $usercash = new Usercash();
        if ($params['status'] != 'SUCCEED') {
            try {
                $r = $usercash->where('order_id', $params['referenceId'])->find()->toArray();
                if ($r['status'] == 5) {
                    return false;
                }
                $upd = [
                    'status'  => 4, //新增状态 '代付失败'
                    'updatetime'  => time(),
                ];
                $res = $usercash->where('id', $r['id'])->update($upd);
                if (!$res) {
                    return false;
                }
                Log::mylog('代付失败,订单号:' . $params['referenceId'], 'wowpaytwodfhd');
            } catch (Exception $e) {
                Log::mylog('代付失败,订单号:' . $params['referenceId'], $e, 'wowpaytwodfhd');
            }
        } else {
            try {
                $r = $usercash->where('order_id', $params['referenceId'])->find()->toArray();
                $upd = [
                    'order_no'  => $params['id'],
                    'updatetime'  => time(),
                    'status' => 3, //新增状态 '代付成功'
                    'paytime' => time(),
                ];
                $res = $usercash->where('status', 'lt', 3)->where('id', $r['id'])->update($upd);
                if (!$res) {
                    return false;
                }
                //统计当日提现金额
                $report = new Report();
                $report->where('date', date("Y-m-d", time()))->setInc('cash', $r['price']);
                //用户提现金额
                (new Usertotal())->where('user_id', $r['user_id'])->setInc('total_withdrawals', $r['price']);
                (new Paycommon())->withdrawa($r['user_id'],$r['id']);
                Log::mylog('提现成功', $params, 'wowpaytwodfhd');
            } catch (Exception $e) {
                Log::mylog('代付失败,订单号:' . $params['referenceId'], $e, 'wowpaytwodfhd');
            }
        }
    }

    function sendSign($params, $appsecret)
    {
        ksort($params);
        $signStr = '';
        foreach ($params as $key => $val) {
            if ($val != null||is_bool($val)) {
                if(is_bool($val)){
                    if($val){
                        $val = 'true';
                    }else{
                        $val = 'false';
                    }
                }
                $signStr .= $key . '=' . $val . '&';
            }
        }
        $signStr .= 'key=' . $appsecret;
        Log::mylog('拼接字符串', $signStr, 'Gtrpayhd');
        // echo $signStr;
        return strtolower(md5($signStr));
    }

    function httpPost($url, $data)
    {

        $postData = http_build_query($data); //重要！！！
        $ch = curl_init();
        // 设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        $header = array();
        $header[] = 'User-Agent: ozilla/5.0 (X11; Linux i686) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.186 Safari/535.1';
        $header[] = 'Accept-Charset: UTF-8,utf-8;q=0.7,*;q=0.3';
        $header[] = 'Content-Type:application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);    // 对证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);    // 从证书中检查SSL加密算法是否存在
        //curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    // 使用自动跳转
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);       // 自动设置Referer
        curl_setopt($ch, CURLOPT_POST, 1);      // 发送一个 常规的Post请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);    // Post提交的数据包
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);      // 设置超时限制防止死循环
        curl_setopt($ch, CURLOPT_HEADER, 0);        // 显示返回的Header区域内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    //获取的信息以文件流的形式返回

        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "Errno" . curl_error($ch);   // 捕抓异常
        }
        curl_close($ch);    // 关闭CURL
        return $output;
    }

    function http_post($sUrl, $aHeader, $aData){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $sUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $aData); // Post提交的数据包
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($ch, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

        //curl_setopt($ch, CURLOPT_HEADER, 1); //取得返回头信息
     
        $sResult = curl_exec($ch);
        if($sError=curl_error($ch)){
            die($sError);
        }
        curl_close($ch);
        return $sResult;
    }

    public function curl($postdata)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->pay_url); //支付请求地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json; charset=utf-8',
            )
        );
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function curls($postdata)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://payment.weglobalpayment.com/pay/transfer"); //支付请求地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * 代收回调
     */
    public function paynotifytest($params)
    {
        if ($params['tradeResult'] == 1) {
            //$sign = $params['sign'];
            // unset($params['sign']);
            // unset($params['signType']);
            // $check = $this->generateSign($params, $this->key);
            // if ($sign != $check) {
            //     Log::mylog('验签失败', $params, 'ppayhd');
            //     return false;
            // }
            $order_id = $params['merchantOrderId']; //商户订单号
            $order_num = $params['orderId']; //平台订单号
            $amount = $params['amount']; //支付金额
            (new Paycommon())->paynotify($order_id, $order_num, $amount, 'ppayhd');
        } else {
            //更新订单信息
            $upd = [
                'status' => 2,
                'order_id' => $params['mchOrderNo'],
                'updatetime' => time(),
            ];
            (new Userrecharge())->where('order_id', $params['mchOrderNo'])->where('status', 0)->update($upd);
            Log::mylog('支付回调失败！', $params, 'ppayhd');
        }
    }
}

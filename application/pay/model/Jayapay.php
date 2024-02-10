<?php

namespace app\pay\model;

use function EasyWeChat\Kernel\Support\get_client_ip;

use app\api\model\Report;
use app\api\model\Usercash;
use app\api\model\Userrecharge;
use app\api\model\Usertotal;
use fast\Http;
use think\Cache;
use think\Model;
use think\Db;
use think\Log;
use think\Exception;


class Jayapay extends Model
{
    //代付提单url(提现)
    public $dai_url = 'https://openapi.jayapayment.com/gateway/cash';
    //代收提交url(充值)
    public $pay_url = 'https://openapi.jayapayment.com/gateway/prepaidOrder';
    //代付回调(提现)
    public $notify_dai = 'https://api.rothpro.id/pay/jayapay/paydainotify';
    //代收回调(充值)
    public $notify_pay = 'https://api.rothpro.id/pay/jayapay/paynotify';
    //私钥
    public $privateKey = "MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAKqdZdnPfY4WE+LjLXa8l7ZLuD3hEl4Ddx45z63Zd4flHZ2A/u2+6/xPTbuXe4uKMHz/1Xw5In1MN4xt1XzLXCU6LA1SkjfAruIUMRqCq0uotGCbGoObKmBXj9g6rHXfby88AIUYKixvB69j+LX2Gtljy73NmxBMqGqLuuoU60P7AgMBAAECgYEApnO85Ez8D10Wtaoxtr+zu5QQAb7Nro7u7bw1E0UwypIK3Jj3HXfAv32XNsdJLw/40lq+Kt2K+ncbUpPtTj0QfWFWXrbGuEFD0K441qOygRXRU38ZeHttvMCoJ1L4KmRYD6emD83DZuyuNTqZn/uWIjUjOACdANM/rB+Ag8RwGeECQQDXMuReea6NjdO1UxC/zsvCcPPNvgkJHyb9qELdDDx0xZw8vFGdoW0/yhf0lN6K4/BsoG/uIJ6uwW7T6nDlAOC5AkEAyvaIgWcrASkSdl89Zqvjrs7Zz2IiShPGjOcD1hSit3R5iqiYtqK0iKy5O41oTF4+tR2O7bxwIFi+dkSvVPGoUwJAcqrahwDdou3EtmTJkvr1Femr76BoHiUsWJMU9cY0XKCDbNWHw6+lkAz2kMvWfTmR/b/LhJ57D3FgxaixeUTreQJAYpWXPou3JswlbJvcPje10x4kTAhVsXejlKNNiLnjQ4ru0nwMGYI0gMKtApEwHRAWE3E1EKLcL8oLUXURWQg8gwJAUFdLSmgtBNPcdCy+rWOT+DkVRCbVzOXSWocm9bMAEJx6LlodyuKe/mjMV13TVkfgPPoxVRsHGOdAH2u55BNlpQ==";
    public $pt = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCO1N4DR/0NaKcqpFK9fRwt3nZh+CE1o9hwOtha5lz9BZxh+LzamKapbKFSM5c3Pe1hgMl4gsLgcvrPVtzzoIWqL4odY1xqAE/bj0ByG7h9wJGObvc1QCmnocn8Jn02JhzS8Q0vE0CW4ilxERg2U8L8n3MBcTZqdb/kUrF7eQx1ZwIDAQAB";
    public function pay($order_id, $price, $userinfo, $channel_info)
    {
        $param = [
            'merchantCode' => $channel_info['merchantid'],
            'orderType' => 0,
            'orderNum' => $order_id, //交易请求流水号
            'payMoney' => intval($price),
            'productDetail' => "1.0",
            'notifyUrl' => $this->notify_pay,
            'dateTime' => date("YmdHis", time()),
            'expiryPeriod' => 60*60,
            'name' => 'jack',
            'email' => 'alao@gmail.com',
            'phone' => "9639639639",
        ];
        $sign = $this->encrypt($param);
        $param['sign'] = $sign;
        Log::mylog("提交参数", $param, "jayapay");
        $header[] = "Content-Type: application/json;charset=utf-8";
        $return_json = $this->http_Post($this->pay_url, $header,json_encode($param,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        Log::mylog("返回参数", $return_json, "jayapay");
        $return_array = json_decode($return_json, true);
        if ($return_array['platRespCode'] == "SUCCESS") {
            $return_array = [
                'code' => 1,
                'payurl' => !empty(($return_array['url'])) ? ($return_array['url']) : '',
                'type' => 1,
            ];
        } else {
            $return_array = [
                'code' => 0,
                'msg' => $return_array['platRespMessage'],
            ];
        }
        return $return_array;
    }

    /**
     * 代收回调
     */
    public function paynotify($params)
    {
        if ($params['code'] == "00"&&$params['status'] == 'SUCCESS') {
            $platSign = $params['platSign'];
            unset($params['platSign']);
            $check = $this->decrypt($platSign);
            ksort($params);
            $params_str = '';
            foreach ($params as $key => $val) {
                $params_str = $params_str . $val;
            }
            if ($check!=$params_str) {
                Log::mylog('验签失败', $params, 'jayapayhd');
                return false;
            }
            $order_id = $params['orderNum']; //商户订单号
            $order_num = $params['platOrderNum']; //平台订单号
            $amount = $params['payMoney']; //支付金额
            (new Paycommon())->paynotify($order_id, $order_num, $amount, 'jayapayhd');
        } else {
            //更新订单信息
            $upd = [
                'status' => 2,
                'order_id' => $params['orderNum'],
                'updatetime' => time(),
            ];
            (new Userrecharge())->where('order_id', $params['orderNum'])->where('status', 0)->update($upd);
            Log::mylog('支付回调失败！', $params, 'jayapayhd');
        }
    }

    /**
     *提现 
     */
    public function withdraw($data, $channel)
    {
        $bankname = '';
        $type = 'bankcard';
        if($data['bankname'] == 'Bank Permata'){
            $bankname = '013';
        }

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
            $bankname = '10001';
        }
        if($data['bankname'] == 'Dana'){
            $bankname = '10002';
        }

        if($data['bankname'] == 'ShopeePay'){
            $bankname = '10008';
        }
        if(empty($bankname)){
            return ['platRespCode'=>'fail','msg'=>'不支持的银行'];
        }
        $param = array(
            'merchantCode' => $channel['merchantid'],
            'orderType' => 0,
            'method' => 'Transfer',
            'orderNum' => $data['order_id'], //交易请求流水号
            'money' => intval($data['trueprice']),
            'feeType' => 1,
            'bankCode' => $bankname,
            'number' => $data['bankcard'], //收款账号
            'name' => $data['username'],
            'mobile' => ($data['phone']),
            'email' => 'Test@gmail.com',
            'notifyUrl' => $this->notify_dai,
            'dateTime' => date("YmdHis", time()),
            'description' => 'description'
        );
        $sign = $this->encrypt($param);
        $param['sign'] = $sign;
        Log::mylog("提交参数", $param, "jayapaydf");
        $header[] = "Content-Type: application/json;charset=utf-8";
        $return_json = $this->http_Post($this->dai_url, $header,json_encode($param,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        Log::mylog($return_json, 'jayapaydf', 'jayapaydf');
        return $return_json;
    }

    /**
     * 提现回调
     */
    public function paydainotify($params)
    {
        $platSign = $params['platSign'];
        unset($params['platSign']);
        $check = $this->decrypt($platSign);
        ksort($params);
        $params_str = '';
        foreach ($params as $key => $val) {
            $params_str = $params_str . $val;
        }
        if ($params_str!=$check) {
            Log::mylog('验签失败', $params, 'jayapaydfhd');
            return false;
        }
        $usercash = new Usercash();
        if ($params['status'] != "2") {
            try {
                $r = $usercash->where('order_id', $params['orderNum'])->find()->toArray();
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
                Log::mylog('代付失败,订单号:' . $params['orderNum'], 'jayapaydfhd');
            } catch (Exception $e) {
                Log::mylog('代付失败,订单号:' . $params['orderNum'], $e, 'jayapaydfhd');
            }
        } else {
            try {
                $r = $usercash->where('order_id', $params['orderNum'])->find()->toArray();
                $upd = [
                    'order_no'  => $params['platOrderNum'],
                    'updatetime'  => time(),
                    'status' => 3, //新增状态 '代付成功'
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
                Log::mylog('提现成功', $params, 'jayapaydfhd');
            } catch (Exception $e) {
                Log::mylog('代付失败,订单号:' . $params['orderNum'], $e, 'jayapaydfhd');
            }
        }
    }

    function sendSign($params, $appsecret)
    {
        ksort($params);
        $signStr = '';
        foreach ($params as $key => $val) {
            if ($val != null) {
                $signStr .= $key . '=' . $val . '&';
            }
        }
        $signStr .= 'key=' . $appsecret;
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

    //参数1：访问的URL，参数2：post数据
    public static function curl_request($url, $post = "")
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        return $data;
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
            //     Log::mylog('验签失败', $params, 'Cloudsafepayhd');
            //     return false;
            // }
            $order_id = $params['merchantOrderId']; //商户订单号
            $order_num = $params['orderId']; //平台订单号
            $amount = $params['amount']; //支付金额
            (new Paycommon())->paynotify($order_id, $order_num, $amount, 'Cloudsafepayhd');
        } else {
            //更新订单信息
            $upd = [
                'status' => 2,
                'order_id' => $params['mchOrderNo'],
                'updatetime' => time(),
            ];
            (new Userrecharge())->where('order_id', $params['mchOrderNo'])->where('status', 0)->update($upd);
            Log::mylog('支付回调失败！', $params, 'Cloudsafepayhd');
        }
    }

    # 加签
    function encrypt($data){
        $mch_private_key = $this->privateKey;
        ksort($data);
        $str = '';
        foreach ($data as $k => $v){
            if(!empty($v)||$v == 0){
                $str .= $v;
            }
        }
        Log::mylog('字符串', $str, 'javapayhd');
        $encrypted = '';
        //替换成自己的私钥
        $pem = chunk_split($mch_private_key, 64, "\n");
        $pem = "-----BEGIN PRIVATE KEY-----\n" . $pem . "-----END PRIVATE KEY-----\n";
        $private_key = openssl_pkey_get_private($pem);
        $crypto = '';
        foreach (str_split($str, 117) as $chunk) {
            openssl_private_encrypt($chunk, $encryptData, $private_key);
            $crypto .= $encryptData;
        }
        $encrypted = base64_encode($crypto);
        $encrypted = str_replace(array('+','/','='),array('-','_',''),$encrypted);

        return $encrypted;
    }

    //解密
    function decrypt($data){
        $public_key = $this->pt;
        $public_key = '-----BEGIN PUBLIC KEY-----'."\n".$public_key."\n".'-----END PUBLIC KEY-----';
        $data = base64_decode($data);
        $pu_key =  openssl_pkey_get_public($public_key);
        $crypto = '';
        foreach (str_split($data, 128) as $chunk) {
            openssl_public_decrypt($chunk, $decryptData, $pu_key);
            $crypto .= $decryptData;
        }
        Log::mylog('支付回调失败！', $crypto, 'jayapayhd');
        return $crypto;
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

    function getUrlStr($data)
    {
        ksort($data);
        $urlStr = [];
        foreach ($data as $k => $v) {
            if (!empty($v) && $k != 'sign') {
                $urlStr[] = $k . '=' . rawurlencode($v);
            }
            if ($k == 'paymentType') {
                $urlStr[] = $k . '=' . rawurlencode($v);
            }
        }
        return join('&', $urlStr);
    }
}

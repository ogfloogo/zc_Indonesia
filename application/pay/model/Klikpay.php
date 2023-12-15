<?php

namespace app\pay\model;

use fast\Http;
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


class Klikpay extends Model
{
    //代付提单url(提现)
    public $dai_url = 'https://openapi.klikpay.link/gateway/cash';
    //代收提交url(充值)
    public $pay_url = 'https://openapi.klikpay.link/gateway/prepaidOrder';
    //代付回调(提现)
    public $notify_dai = 'https://api.rothai.id/pay/klikpay/paydainotify';
    //代收回调(充值)
    public $notify_pay = 'https://api.rothai.id/pay/klikpay/paynotify';
    //支付成功跳转地址    
    public $callback_url = 'https://www.rothai.id/topupsuccess.html';

//    public $key = 'DA09816BF55F8F93B9E41E95C605DA61';
    private $privateKey = "MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBAL2eayQ00B+tkAYKa8y1/Ahl3U/U5pCa4CW+cq+ZZOC4TQOkRds/ih7kt8MtjS2zn3h3I64ptovAuxHLgoO1ZKeMVQFii6K6MH56eIY1500VSHcdNlYAj1qf5Sq4O+KN38EV+XZpzVL9l18z4wB0Eao44qtAslyOQtEeOzH+aE79AgMBAAECgYB3MJwyXwwvzV6JnYVTYVUyRi2N38qqPOAGeacHYAl8A/1M+7Z5cYA1mRk1k215u73S8Di6Yjf/sr8kGWNJvPyuC6WftSjf/aSx4rPLKUCj5bYpo1RbXLtaS8sitDFG6Gh5YyWTNOJ5xAPS7cYIatrJi6w4eiLLAxaSIs2OmDzzdQJBAOTdBCUTSeyjeXWsEWpghvbCdFYcipiabr1ctjDqXFYQHfqEqEVW0q1Hd3WbwM1HG9p0M7Kr5EvVkgLjFUBykXcCQQDUGimuJR+YvkidrgaCPFZob8FOeV+ErMwsE78l/uXQtNxcxO/iK5O307lsjb41F5+8MFXFeWWflq6wdZc1nSArAkA14/U95bTajAPluYbATcp6AH/0ZvQbywGYhWRBUG49tb3ZZ7NX4IHrW5C9mNdlfwEIB6TExcUUNk4Tb/VpH5ExAkAaT8yP3+cVibV0ahxAl7+YYRS36XKiANWrOh/iVMapp2YW1A92HTXL/iXog4TvDSkxrUVWpAKpU4YEOfIyInXVAkAbP6AjYKteFHeUFeZDhXsKPJTQ1zh+vI7WNdAyRnnpCghgZVIe5RmxWLLKe9DZSBiHx8lyhVs18xSMIuYJoxXw";
    private $pt = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCAB46RzcFtBWq3tOT0MyKz8E/zutnZfg/S97NMXblqJ7Rj1mqc0Ugf2+Epo5qB5zzb8TQelDyNi/GxPLuMGHMSnuVxAR0DYOLp6Fyl++zHHveyvn69Pn0NjnX9gOZ4+YlKC1nPJdbyCXnt91GQgGTvhwLui/Q8Zn2qDCL2II5jnQIDAQAB";

    public function pay($order_id, $price, $userinfo, $channel_info)
    {
        $param = [
            'merchantCode' => $channel_info['merchantid'],
            'orderNum' => $order_id,
            'payMoney' => number_format($price,2,'.',''),
            'productDetail' => 'product',
            'name' => 'zhangsan',
            'email' => 'zhangsan@gmail.com',
            'phone' => '13122336688',
            'notifyUrl' => $this->notify_pay,
            'redirectUrl' => $this->callback_url,
            'expiryPeriod' => '1440',
            'dateTime' => date('Ymdhis'),
//            'payType' => $channel_info['busi_code'],
        ];
        $sign = $this->encrypt($param);
        $param['sign'] = $sign;
        Log::mylog("提交参数", $param, "klilpay");
        $header[] = "Content-Type: application/json;charset=utf-8";
        $return_json = $this->http_Post($this->pay_url, $header,json_encode($param,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        Log::mylog("返回参数", $return_json, "klilpay");
        $return_array = json_decode($return_json, true);
        if ($return_array['platRespCode'] == 'SUCCESS') {
            $return_array = [
                'code' => 1,
                'payurl' => !empty(($return_array['url'])) ? urlencode($return_array['url']) : '',
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
        if ($params['code'] == '00') {
            $sign = $params['platSign'];
//            unset($params['platSign']);
            $check = $this->decrypt($params);
            if ($check) {
                $order_id = $params['orderNum']; //商户订单号
                $order_num = $params['platOrderNum']; //平台订单号
                $amount = $params['payMoney']; //支付金额
                (new Paycommon())->paynotify($order_id, $order_num, $amount, 'klikpayhd');
                echo 'SUCCESS';exit;
            }else{
                Log::mylog('验签失败', $sign.'---'.$check, 'klikpayhd');
                return false;
            }
        } else {
            //更新订单信息
            $upd = [
                'status' => 2,
                'order_id' => $params['orderNum'],
                'updatetime' => time(),
            ];
            (new Userrecharge())->where('order_id', $params['orderNum'])->where('status', 0)->update($upd);
            Log::mylog('支付回调失败！', $params, 'klikpayhd');
        }
    }

    /**
     *提现 
     */
    public function withdraw($data, $channel)
    {
        $bankname = '';
        if($data['bankname'] == 'Bank BRI'){
            $bankname = 'BRI';
        }

        if($data['bankname'] == 'Bank Mandiri'){
            $bankname = 'MANDIRI';
        }

        if($data['bankname'] == 'Bank BNI'){
            $bankname = 'BNI';
        }

        if($data['bankname'] == 'Bank Danamon'){
            $bankname = 'DANAMON';
        }

        if($data['bankname'] == 'Bank Permata'){
            $bankname = 'PERMATA';
        }

        if($data['bankname'] == 'Bank BCA'){
            $bankname = 'BCA';
        }

        if($data['bankname'] == 'BII Maybank'){
            $bankname = 'BANK_MAYBANK';
        }

        if($data['bankname'] == 'Bank Panin'){
            $bankname = 'PANIN';
        }

        if($data['bankname'] == 'CIMB Niaga'){
            $bankname = 'CIMB';
        }

        if($data['bankname'] == 'Bank UOB INDONESIA'){
            $bankname = 'BANK_BUANA';
        }
        if($data['bankname'] == 'Bank OCBC NISP'){
            $bankname = 'NISP';
        }
        if($data['bankname'] == 'CITIBANK'){
            $bankname = 'CITIBANK';
        }
        if($data['bankname'] == 'Bank ARTHA GRAHA'){
            $bankname = 'ARTHA';
        }
        if($data['bankname'] == 'Bank TOKYO MITSUBISHI UFJ'){
            $bankname = 'BANK_TOKYO';
        }
        if($data['bankname'] == 'Bank DBS'){
            $bankname = 'DBS';
        }
        if($data['bankname'] == 'Standard Chartered'){
            $bankname = 'STANDARD_CHARTERED';
        }
        if($data['bankname'] == 'Bank CAPITAL'){
            $bankname = 'CAPITAL';
        }
        if($data['bankname'] == 'ANZ Indonesia'){
            $bankname = 'BANK_ANZ';
        }
        if($data['bankname'] == 'Bank OF CHINA'){
            $bankname = 'BOC';
        }
        if($data['bankname'] == 'Bank HSBC'){
            $bankname = 'HSBC';
        }
        if($data['bankname'] == 'Bank MAYAPADA'){
            $bankname = 'MAYAPADA';
        }
        if($data['bankname'] == 'Bank JATENG'){
            $bankname = 'BANK_JATENG';
        }
        if($data['bankname'] == 'Bank Jatim'){
            $bankname = 'BANK_JATIM';
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
            return ['platRespCode'=>'fail','platRespMessage'=>'不支持的银行'];
        }
        $params = array(
            'merchantCode' => $channel['merchantid'],
            'orderNum' => $data['order_id'],
            'money' => number_format($data['trueprice'],2,'.',''),
            'description' => 'description',
            'bankAccount' => $data['bankcard'], //收款账号
            'name' => $data['username'], //收款姓名
            'bankCode' => $bankname,
            'notifyUrl' => $this->notify_dai,
            'feeType' => 1
        );
        $sign = $this->encrypt($params);
        $params['sign'] = $sign;
        Log::mylog('提现提交参数', $params, 'klikdf');
        $header[] = "Content-Type: application/json;charset=utf-8";
        $return_json = $this->http_Post($this->dai_url, $header,json_encode($params,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        Log::mylog($return_json, 'klikdf', 'klikdf');
        return $return_json;
    }

    /**
     * 提现回调
     */
    public function paydainotify($params)
    {
        $check = $this->decrypt($params);
        if ($check) {
            Log::mylog('验签失败', $params, 'klikpaydfhd');
            return false;
        }
        $usercash = new Usercash();
        if ($params['status'] != '2') {
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
                Log::mylog('代付失败,订单号:' . $params, 'klikpaydfhd');
            } catch (Exception $e) {
                Log::mylog('代付失败,订单号:' . $params['orderNum'], $e, 'klikpaydfhd');
            }
        } else {
            try {
                $r = $usercash->where('order_id', $params['orderNum'])->find()->toArray();
                $upd = [
                    'order_no'  => $params['platOrderNum'],
                    'updatetime'  => time(),
                    'status' => 3, //新增状态 '代付成功'
                    'paytime' => time(),
                ];
                $res = $usercash->where('status','lt',3)->where('id', $r['id'])->update($upd);
                if (!$res) {
                    return false;
                }
                //统计当日提现金额
                $report = new Report();
                $report->where('date', date("Y-m-d", time()))->setInc('cash', $r['price']);
                //用户提现金额
                (new Usertotal())->where('user_id', $r['user_id'])->setInc('total_withdrawals', $r['price']);
                Log::mylog('提现成功', $params, 'klikpaydfhd');
            } catch (Exception $e) {
                Log::mylog('代付失败,订单号:' . $params['orderNum'], $e, 'klikpaydfhd');
            }
        }
    }

    /**
     * 生成签名   sign = Md5(key1=vaIue1&key2=vaIue2…商户密钥);
     *  @$params 请求参数
     *  @$secretkey   密钥
     */
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

    function encrypt($data){
        $mch_private_key = $this->privateKey;
        ksort($data);
        $str = '';
        foreach ($data as $k => $v){
            if(!empty($v)){
                $str .= $v;
            }
        }
        Log::mylog('字符串', $str, 'klikpayhd');
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
        $mch_public_key = $this->pt;
        ksort($data);
        $toSign ='';
        foreach($data as $key=>$value){
            if(strcmp($key, 'platSign')!= 0  && $value!=''){
                $toSign .= $value;
            }
        }

        $str = rtrim($toSign,'&');

        $encrypted = '';
        //替换自己的公钥
        $pem = chunk_split( $mch_public_key,64, "\n");
        $pem = "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----\n";
        $publickey = openssl_pkey_get_public($pem);

        $base64=str_replace(array('-', '_'), array('+', '/'), $data['platSign']);

        $crypto = '';
        foreach(str_split(base64_decode($base64), 128) as $chunk) {
            openssl_public_decrypt($chunk,$decrypted,$publickey);
            $crypto .= $decrypted;
        }
        Log::mylog('decrypt', $crypto.'---'.$str, 'klikpayhd');
        if($str != $crypto){
            return false;
        }else{
            return true;
        }
    }
}

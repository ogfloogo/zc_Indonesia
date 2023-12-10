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


class Nicepay extends Model
{
    //代付提单url(提现)
    public $dai_url = 'http://merchant.nicepay.pro/api/withdraw';
    //代收提交url(充值)
    public $pay_url = 'http://merchant.nicepay.pro/api/recharge';
    //代付回调(提现)
    public $notify_dai = 'https://api.rothai.id/pay/nicepay/paydainotify';
    //代收回调(充值)
    public $notify_pay = 'https://api.rothai.id/pay/nicepay/paynotify';
    //支付成功跳转地址    
    public $callback_url = 'https://www.rothai.id/topupsuccess.html';
    //代收秘钥
    public $key = "82b037ada98d4379e511adbca5352a88";
    //代付秘钥
    public function pay($order_id, $price, $userinfo, $channel_info)
    {
        $param = [
            'app_key' => $channel_info['merchantid'],
            'balance' => $price,
            'ord_id' => $order_id,
            'notify_url' => $this->notify_pay,
            "p_method" => "",
        ];
        $sign = $this->sendSign($param, $this->key);
        $param['sign'] = $sign;
        Log::mylog("提交参数", $param, "Nicepay");
        $return_json = $this->fetch_page_json($this->pay_url, $param);
        Log::mylog("返回参数", $return_json, "Nicepay");
        $return_array = json_decode($return_json, true);
        if ($return_array['err'] == 0) {
            $return_array = [
                'code' => 1,
                'payurl' => !empty(urldecode($return_array['url'])) ? urldecode($return_array['url']) : '',
            ];
        } else {
            $return_array = [
                'code' => 0,
                'msg' => $return_array['err_msg'],
            ];
        }
        return $return_array;
    }

    /**
     * 代收回调
     */
    public function paynotify($params)
    {
        if ($params['status'] == 1) {
            $sign = $params['sign'];
            unset($params['sign']);
            $check = $this->sendSign($params, $this->key);
            if ($sign != $check) {
                Log::mylog('验签失败', $params, 'Nicepayhd');
                return false;
            }
            $order_id = $params['order']; //商户订单号
            $order_num = ""; //平台订单号
            $amount = $params['amount']; //支付金额
            (new Paycommon())->paynotify($order_id, $order_num, $amount, 'Nicepayhd');
        } else {
            //更新订单信息
            $upd = [
                'status' => 2,
                'order_id' => $params['order'],
                'updatetime' => time(),
            ];
            (new Userrecharge())->where('order_id', $params['order'])->where('status', 0)->update($upd);
            Log::mylog('支付回调失败！', $params, 'Nicepayhd');
        }
    }

    /**
     *提现 
     */
    public function withdraw($data, $channel)
    {
        $bankname = '';
        if($data['bankname'] == 'Bank BRI'){
            $bankname = '2';
        }

        if($data['bankname'] == 'Bank Mandiri'){
            $bankname = '8';
        }

        if($data['bankname'] == 'Bank BNI'){
            $bankname = '9';
        }

        if($data['bankname'] == 'Bank Danamon'){
            $bankname = '11';
        }

        if($data['bankname'] == 'Bank Permata'){
            $bankname = '13';
        }

        if($data['bankname'] == 'Bank BCA'){
            $bankname = '14';
        }

        if($data['bankname'] == 'BII Maybank'){
            $bankname = '16';
        }

        if($data['bankname'] == 'Bank Panin'){
            $bankname = '19';
        }

        if($data['bankname'] == 'CIMB Niaga'){
            $bankname = '22';
        }

        if($data['bankname'] == 'Bank UOB INDONESIA'){
            $bankname = '23';
        }
        if($data['bankname'] == 'Bank OCBC NISP'){
            $bankname = '28';
        }
        if($data['bankname'] == 'CITIBANK'){
            $bankname = '31';
        }
        if($data['bankname'] == 'Bank ARTHA GRAHA'){
            $bankname = '37';
        }
        if($data['bankname'] == 'Bank TOKYO MITSUBISHI UFJ'){
            $bankname = '42';
        }
        if($data['bankname'] == 'Bank DBS'){
            $bankname = '46';
        }
        if($data['bankname'] == 'Standard Chartered'){
            $bankname = '50';
        }
        if($data['bankname'] == 'Bank CAPITAL'){
            $bankname = '54';
        }
        if($data['bankname'] == 'ANZ Indonesia'){
            $bankname = '61';
        }
        if($data['bankname'] == 'Bank OF CHINA'){
            $bankname = '69';
        }
        if($data['bankname'] == 'Bank Bumi Arta'){
            $bankname = '76';
        }
        if($data['bankname'] == 'Bank HSBC'){
            $bankname = '41';
        }
        if($data['bankname'] == 'Bank Rabobank'){
            $bankname = '89';
        }
        if($data['bankname'] == 'Bank JTRUST INDONESIA'){
            $bankname = '95';
        }
        if($data['bankname'] == 'Bank MAYAPADA'){
            $bankname = '97';
        }
        if($data['bankname'] == 'Bank Jawa Barat'){
            $bankname = '110';
        }
        if($data['bankname'] == 'Bank DKI'){
            $bankname = '111';
        }
        if($data['bankname'] == 'Bank BPD DIY'){
            $bankname = '112';
        }
        if($data['bankname'] == 'Bank JATENG'){
            $bankname = '113';
        }
        if($data['bankname'] == 'Bank Jatim'){
            $bankname = '114';
        }
        if($data['bankname'] == 'Bank Jambi'){
            $bankname = '115';
        }
        if($data['bankname'] == 'Bank Aceh Syariah'){
            $bankname = '116';
        }
        if($data['bankname'] == 'Bank SUMUT'){
            $bankname = '117';
        }
        if($data['bankname'] == 'Bank NAGARI'){
            $bankname = '1181';
        }

        if($data['bankname'] == 'Bank BTN'){
            $bankname = '200';
        }
        if($data['bankname'] == 'OVO'){
            $bankname = '10001';
        }
        if($data['bankname'] == 'Dana'){
            $bankname = '10002';
        }
        if(empty($bankname)){
            return ['err'=>'1','platRespMessage'=>'不支持的银行'];
        }
        $param = array(
            'app_key' => $channel['merchantid'],
            'ord_id' => $data['order_id'],
            'balance' => $data['trueprice'],
//            'bankCode' => 'GCASH',
            'name' => $data['username'], //收款姓名
            'card' => $data['bankcard'], //收款账号
            'p_bank_code' => $bankname,
            'notify_url' => $this->notify_dai,
        );
        $sign = $this->sendSign($param, $this->key);
        $param['sign'] = $sign;
        Log::mylog('提现提交参数', $param, 'Nicepaydf');
        $return_json = $this->fetch_page_json($this->dai_url, $param);
        Log::mylog($return_json, 'Nicepaydf', 'Nicepaydf');
        return $return_json;
    }

    /**
     * 提现回调
     */
    public function paydainotify($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $check = $this->sendSign($params, $this->key);
        if ($sign != $check) {
            Log::mylog('验签失败', $params, 'Nicepaydfhd');
            return false;
        }
        $usercash = new Usercash();
        if ($params['status'] != 1) {
            try {
                $r = $usercash->where('order_id', $params['order'])->find()->toArray();
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
                Log::mylog('代付失败,订单号:' . $params['order'], 'Nicepaydfhd');
            } catch (Exception $e) {
                Log::mylog('代付失败,订单号:' . $params['order'], $e, 'Nicepaydfhd');
            }
        } else {
            try {
                $r = $usercash->where('order_id', $params['order'])->find()->toArray();
                $upd = [
                    // 'order_no'  => $params['ptOrderNo'],
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
                Log::mylog('提现成功', $params, 'Nicepaydfhd');
            } catch (Exception $e) {
                Log::mylog('代付失败,订单号:' . $params['order'], $e, 'Nicepaydfhd');
            }
        }
    }

    function sendSign($param, $salt)
    {
        $data = $param;
        ksort($data);

        $str = "";
        foreach ($data as $key => $value) {
            $str = $str . $value;
        }
        $str = $str . $salt;
        return md5($str);
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

    function fetch_page_json($url, $params = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:application/json;charset=UTF-8"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_URL, $url);

        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        $errmsg = curl_error($ch);
        if ($errno != 0) {
            throw new Exception($errmsg, $errno);
        }
        curl_close($ch);
        return $result;
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

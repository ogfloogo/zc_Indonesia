<?php

namespace app\pay\model;

use function EasyWeChat\Kernel\Support\get_client_ip;

use app\api\model\Report;
use app\api\model\Usercash;
use app\api\model\Userrecharge;
use app\api\model\Usertotal;
use think\Model;
use think\Db;
use think\Log;
use think\Exception;


class Bspay extends Model
{
    //代付提单url(提现)
    public $dai_url = 'https://pay.baishipay.com/api/payout/order/create';
    //代收提交url(充值)
    public $pay_url = 'https://pay.baishipay.com/api/payment/order/create';
    //代付回调(提现)
    public $notify_dai = 'https://api.rothpro.id/pay/bspay/paydainotify';
    //代收回调(充值)
    public $notify_pay = 'https://api.rothpro.id/pay/bspay/paynotify';
    //代收秘钥
    public $key = "SN00G2X3JNVJ9KATHPN4PBKDVJDV";
    //代付秘钥
    public $key2 = "SN0YXGYQFZPFCHMBM6WS5WZRVQ1S";
    public function pay($order_id, $price, $userinfo, $channel_info)
    {

        $param = [
            'appid' => $channel_info['merchantid'],
            'amount' => $price,
            'order_no' => $order_id,
            'timestamp' => time(),
            'version' => '2.0',
        ];
        $sign = $this->sign($param, $this->key);
        $param['notify_url'] = $this->notify_pay;
        $param['sign'] = $sign;
        Log::mylog("提交参数", $param, "bspay");
        $return_json = $this->httpPost($this->pay_url, $param);
        Log::mylog("返回参数", $return_json, "bspay");
        $return_array = json_decode($return_json, true);
        if ($return_array['code'] == "0") {
            $return_array = [
                'code' => 1,
                'payurl' => !empty(($return_array['pay_url'])) ? ($return_array['pay_url']) : '',
            ];
        } else {
            $return_array = [
                'code' => 0,
                'msg' => $return_array['msg'],
            ];
        }
        return $return_array;
    }

    /**
     * 代收回调
     */
    public function paynotify($params)
    {
        if ($params['order_status'] == 'SUCCESS') {
            $sign = $params['sign'];
            unset($params['sign']);
            $check = $this->ascii_params($params);
            $checksign = md5($check.'&key='.$this->key);
            if ($sign != $checksign) {
                Log::mylog('验签失败', $params, 'bspayhd');
                return false;
            }
            $order_id = $params['shanghu_order_no']; //商户订单号
            $order_num = $params['plantform_order_no']; //平台订单号
            $amount = $params['real_amount']; //支付金额
            (new Paycommon())->paynotify($order_id, $order_num, $amount, 'bspayhd');
        } else {
            //更新订单信息
            $upd = [
                'status' => 2,
                'order_id' => $params['shanghu_order_no'],
                'updatetime' => time(),
            ];
            (new Userrecharge())->where('order_id', $params['shanghu_order_no'])->where('status', 0)->update($upd);
            Log::mylog('支付回调失败！', $params, 'bspayhd');
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
            $bankname = 'B0048';
        }

        if($data['bankname'] == 'Bank BRI'){
            $bankname = 'B0044';
        }

        if($data['bankname'] == 'Bank Mandiri'){
            $bankname = 'B0045';
        }

        if($data['bankname'] == 'Bank BNI'){
            $bankname = 'B0046';
        }

            if($data['bankname'] == 'Bank Danamon'){
            $bankname = 'B0047';
        }

        if($data['bankname'] == 'Bank BCA'){
            $bankname = 'B0049';
        }

        if($data['bankname'] == 'BII Maybank'){
            $bankname = 'B0050';
        }

        if($data['bankname'] == 'Bank Panin'){
            $bankname = 'B0051';
        }

        if($data['bankname'] == 'CIMB Niaga'){
            $bankname = 'B0052';
        }

        if($data['bankname'] == 'Bank UOB INDONESIA'){
            $bankname = 'B0053';
        }
        if($data['bankname'] == 'Bank OCBC NISP'){
            $bankname = 'B0054';
        }
        if($data['bankname'] == 'CITIBANK'){
            $bankname = 'B0055';
        }
        if($data['bankname'] == 'Bank ARTHA GRAHA'){
            $bankname = 'B0057';
        }
        if($data['bankname'] == 'Bank DBS'){
            $bankname = 'B0059';
        }
        if($data['bankname'] == 'Standard Chartered'){
            $bankname = 'B0060';
        }
        if($data['bankname'] == 'Bank CAPITAL'){
            $bankname = 'B0061';
        }
        if($data['bankname'] == 'ANZ Indonesia'){
            $bankname = 'B0062';
        }
        if($data['bankname'] == 'Bank HSBC'){
            $bankname = 'B0065';
        }
            if($data['bankname'] == 'Bank MAYAPADA'){
            $bankname = 'B0069';
        }
        if($data['bankname'] == 'Bank Jawa Barat'){
            $bankname = 'B0070';
        }
        if($data['bankname'] == 'Bank JATENG'){
            $bankname = 'B0073';
        }
        if($data['bankname'] == 'Bank Jatim'){
            $bankname = 'B0074';
        }
        if($data['bankname'] == 'Bank Aceh Syariah'){
            $bankname = 'B0076';
        }

        if($data['bankname'] == 'OVO'){
            $bankname = 'B0147';
        }
        if($data['bankname'] == 'Dana'){
            $bankname = 'B0144';
        }

        if($data['bankname'] == 'ShopeePay'){
            $bankname = 'B0148';
        }
        if(empty($bankname)){
            return ['code'=>'-1','msg'=>'不支持的银行'];
        }
        $param = array(
            'appid' => $channel['merchantid'],
            'amount' => $data['trueprice'],
            'order_no' => $data['order_id'],
            'timestamp' => time(),
            'version' => '2.0',
        );
        $sign = $this->ascii_params($param);
        $param['bank_code'] = $bankname;
        $param['account_name'] = $data['username'];
        $param['account_no'] = $data['bankcard'];
        $param['notify_url'] = $this->notify_dai;

        $param['sign'] = md5($sign.'&key='.$this->key2);
        Log::mylog('提现提交参数', $param, 'bspaydf');

        $header[] = "Content-Type: application/json;charset=utf-8";
        $return_json = $this->httpPost($this->dai_url, $header,json_encode($param,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        
        Log::mylog($return_json, 'bspaydf', 'bspaydf');
        return $return_json;
    }

    /**
     * 提现回调
     */
    public function paydainotify($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $check = $this->ascii_params($params);
        $checksign = md5($check.'&key='.$this->key2);
        if ($sign != $checksign) {
            Log::mylog('验签失败', $params, 'bspaydfhd');
            return false;
        }
        $usercash = new Usercash();
        if ($params['order_status'] == 'SUCCESS') {
            try {
                $r = $usercash->where('order_id', $params['shanghu_order_no'])->find()->toArray();
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
                Log::mylog('代付失败,订单号:' . $params['shanghu_order_no'], 'bspaydfhd');
            } catch (Exception $e) {
                Log::mylog('代付失败,订单号:' . $params['shanghu_order_no'], $e, 'bspaydfhd');
            }
        } else {
            try {
                $r = $usercash->where('order_id', $params['shanghu_order_no'])->find()->toArray();
                $upd = [
                    'order_no'  => $params['plantform_order_no'],
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
                Log::mylog('提现成功', $params, 'bspaydfhd');
            } catch (Exception $e) {
                Log::mylog('代付失败,订单号:' . $params['shanghu_order_no'], $e, 'bspaydfhd');
            }
        }
    }

    function sign($data, $key)
    {
        $str = $this->ascii_params($data);
        $signature = "";
        if (function_exists('hash_hmac'))
        {
            $signature = base64_encode(hash_hmac("sha1", $str, $key, true));
        }
        else
        {
            $blocksize = 64;
            $hashfunc = 'sha1';
            if (strlen($key) > $blocksize)
            {
                $key = pack('H*', $hashfunc($key));
            }
            $key = str_pad($key, $blocksize, chr(0x00));
            $ipad = str_repeat(chr(0x36), $blocksize);
            $opad = str_repeat(chr(0x5c), $blocksize);
            $hmac = pack('H*', $hashfunc(($key ^ $opad) . pack('H*', $hashfunc(($key ^ $ipad) . $str))));
            $signature = base64_encode($hmac);
        }
        return $signature;
    }

    function ascii_params($params = array())
    {
        if (!empty($params))
        {
            $p = ksort($params);
            if ($p)
            {
                $str = '';
                foreach ($params as $k => $val)
                {
                    $str .= $k . '=' . $val . '&';
                }
                $strs = rtrim($str, '&');
                return $strs;
            }
        }
        return '';
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

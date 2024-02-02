<?php

namespace app\api\model;

use app\admin\model\activity\Fission;
use app\admin\model\activity\Turntabletimes;
use think\Model;
use think\Log;

/**
 * 活动
 */
class Turntable extends Model
{
    protected $name = 'turntable';

    public function addtimes($user_id, $money = 0, $type)
    {
//        if($user_id != 29598){
//            return true;
//        }
        $turntable = self::where(['starttime' => ['<=', time()], 'endtime' => ['>=', time()]])->find();
        if (!$turntable) {
            return true;
        }
        $user = (new User())->where(['id'=>$user_id])->find();
        if($user['sid'] == 0){
            return true;
        }
        $sid = $user['sid'];
        //用户注册时间不在活动时间之内   不符合条件
        if($user['createtime'] < $turntable['starttime'] || $user['createtime'] > $turntable['endtime']){
            return true;
        }
        if ($money >= $turntable['recharge']) {
            $times = 1;
        } else {
            $times = 0;
        }
        if ($times) {
            $exist = (new Turntabletimes())->where(['a_id' => $turntable['id'], 'user_id' => $sid])->find();
            if (!$exist) {
                $create = [
                    'a_id' => $turntable['id'],
                    'user_id' => $sid,
                    'times' => 1,
                    'oid' => $user_id
                ];
                (new Turntabletimes())->create($create);
            } else {
                $exist_user = (new Turntabletimes())->where(['a_id' => $turntable['id'], 'user_id' => $sid])->where('find_in_set(:oid,oid)',['oid'=>$user_id])->find();
                if(!$exist_user){
                    $exist->times = $exist->times + 1;
                    $exist->oid = $exist->oid.','.$user_id;
                    $exist->save();
                }
            }
        }


//        if ($type == 1) {
//            //邀请
//            $times = 0;
//            $where['sid'] = $user_id;
//            $where['createtime'] = ['between', [$turntable['starttime'],$turntable['endtime']]];
//            $invite_number = db('user')->where($where)->count();
//            if($invite_number > 0 && $turntable['invite'] > 0){
//                $mod = $invite_number % $turntable['invite'];
//                if ($mod == 0) {
//                    $times = 1;
//                }
//            }
//        } else { //2充值
//            if ($money >= $turntable['recharge']) {
//                $times = 1;
//            } else {
//                $times = 0;
//            }
//        }
//        if ($times) {
//            $exist = (new Turntabletimes())->where(['a_id' => $turntable['id'], 'user_id' => $user_id])->find();
//            if (!$exist) {
//                $create = [
//                    'a_id' => $turntable['id'],
//                    'user_id' => $user_id,
//                    'times' => 1
//                ];
//                (new Turntabletimes())->create($create);
//            } else {
//                if($turntable['type'] == 2){
//                    (new Turntabletimes())->where(['a_id' => $turntable['id'], 'user_id' => $user_id])->setInc('times', 1);
//                }
//            }
//        }
    }

    public function addtimes2($userid,$pid,$price){
        //判断时间
        $starttime = strtotime(config('site.starttime'));
        $endtime = strtotime(config('site.endtime'));
        if(time()>= $starttime&&time()<=$endtime){
            //判断是否符合助力条件
            //1、金额
            if($price >= config('site.fission_money')){
                //成为有效用户(活动时间内投资2w以上)
                $fission_user =   (new Fission())->where(['pid'=>$userid])->find();
                if(!$fission_user){
                    $create = [
                        'pid' => $userid,
                        'createtime' => time()
                    ];
                    (new Fission())->create($create);
                }
                //2、判断注册时间是否在活动时间内
                $user = (new User())->where(['id'=>$userid])->find();
                if($user['createtime']>= $starttime&&$user['createtime']<=$endtime){
                    //3、判断上级是否是有效用户
                    if($pid == 0){
                        return true;
                    }
                    $pidinfo = (new Fission())->where(['pid'=>$pid])->find();
                    if($pidinfo){
                        //4、判断是否助力过
                        $exist_user = (new Fission())->where(['pid' => $pid])->where('find_in_set(:oid,oid)',['oid'=>$userid])->find();
                        if(!$exist_user){
                            //记录助力用户
                            $pidinfo->oid = !$pidinfo->oid ? $userid : $pidinfo->oid.','.$userid;
                            $teamstring = $pidinfo->oid;
                            $pidinfo->save();
                            (new Usermoneylog())->moneyrecords($pid, config('site.fission_reward'), 'inc', 3, "裂变用户{$userid}");
                            $teamnum = count(explode(',',$teamstring));
                            if($teamnum == 10){
                                (new Usermoneylog())->moneyrecords($pid, config('site.fission10'), 'inc', 3, "团队有效人数达到10");
                            }
                            if($teamnum == 20){
                                (new Usermoneylog())->moneyrecords($pid, config('site.fission20'), 'inc', 3, "团队有效人数达到20");
                            }
                            if($teamnum == 50){
                                (new Usermoneylog())->moneyrecords($pid, config('site.fission50'), 'inc', 3, "团队有效人数达到50");
                            }
                            if($teamnum == 100){
                                (new Usermoneylog())->moneyrecords($pid, config('site.fission100'), 'inc', 3, "团队有效人数达到100");
                            }
                        }
                    }
                }
            }
        }
    }
}

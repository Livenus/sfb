<?php 
namespace app\common\validate;

use think\Validate;
class MemberMonylog extends Validate{
    protected $rule = [
        'money'  =>  'require|number|check_money',
        'verify_code'  =>  'require|number|check_code',

    ];
    protected $message = [
        'check_code.require'  => '提现金额必填',
        'verify_code.require'  => '验证码必填',
        'verify_code.check_code'  => '验证码不正确',
        'money.require'  => '提现金额必填',
        'money.number'  => '提现金额只能为数字',
        'money.check_money'  => '提现金额不能大于余额，不能少于最小提现金额',
];
    protected $scene = [
    ];
    protected function check_code($code,$rule,$data){
    $sms=model("SmsLog");
    $res=$sms->get_by_phone($data["phone"],7);
     if($res["captcha"]==$data["verify_code"]){
         $sms->editById(array("used"=>1),$res["id"]);
         return true;
     }else{
         return false;
     }
    }

    protected function check_money($code,$rule,$data){
        $set=model("setting");
        $log=model("MemberMonylog");
        $set_data=$set->select_all();
        $lose=$log->sum_money_litttle($data["member_id"]);
        $data["has_money"]=$data["has_money"]-$lose;
       if($data["has_money"]>=0&&$data["has_money"]>=$data["money"]&&$data["money"]>=$set_data["rate_min"]){
           return true;
           
       }else{
           return false;
       }
    }
 
}
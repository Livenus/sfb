<?php 
namespace app\api\validate;
use think\Validate;
class Sms extends Validate{
    protected $rule = [
        'phone'  =>  'require|max:11|check_mobile:zl'
    ];
    
    protected $message = [
        'phone.require'  =>  '手机号不能为空',
        'phone.check_mobile' =>  '手机格式不正确',
    ];
    
    protected function check_mobile($value){
        $flag = preg_match('/^1(3\d{1}|47|5[^4]|78|8[^1])\d{8}$/',$value);
        if($flag == 1){
            return true;
        }
        return false;
    }
}
?>
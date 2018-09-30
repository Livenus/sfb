<?php 
namespace app\admin\validate;

use think\Validate;
use think\Response;
class MemberGroup extends Validate{
    protected $rule =   [
        'name'  => 'require|min:3|max:20',
        'rate_1'  => 'require|is_min',
        'rate_2'  => 'require|is_min2'
    ];
    
    protected $message  =   [
        'name.require' => '名称必须填写',
        'name.max'     => '名称最多不能超过20个字符',
        'name.min'     => '名称最少不能低于5个字符',
        'name.unique'   => '名称已存在',
        'name.between' => '名称必须在5~20个字符之间',
        'rate_1.is_min' => '无积分费率不能小于最低费率',
        'rate_2.is_min2' => '有积分费率不能小于最低费率'
    ];
    
    protected $scene = [
        'gedit'  =>  ['name','rate_1','rate_2'],
    ];
    protected function is_min($value,$rule,$data){
        $rate_key= model("channel")->get_pay_way_key("rate_1");
        if($data["rate_1"]>=$rate_key['low_fee']){
            return true;
        }
        return false;
        
        
    }
    protected function is_min2($value,$rule,$data){
        $rate_key= model("channel")->get_pay_way_key("rate_2");
        if($data["rate_2"]>=$rate_key['low_fee']){
            return true;
        }
        return false;
        
        
    }
}
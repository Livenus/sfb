<?php 
namespace app\admin\validate;

use think\Validate;

class Channel extends Validate{
    protected $rule =   [
        'name'  => 'require|min:3|max:75|unique:member_group',
          'type'  => 'require',      
          'account_time'  => 'require',  
          'rate_money'  => 'require|between:0.01,100000000|is_min', 
          'max_money'  => 'require|between:1,100000000',    
          'min_money'  => 'require|between:1,100000000|check_min',   
          'start_time'  => 'require|date',    
          'end_time'  => 'require|date|check_end_time'
    ];
    
    protected $message  =   [
        'name.require' => '名称必须填写',
        'name.max'     => '名称最多不能超过24个字符',
        'name.min'     => '名称最少不能低于3个字符',
        'name.unique'   => '名称已存在',
        'rate_money.require' => '代扣手续费必填',
        'rate_money.between' => '代扣手续费必须大于0',
        'rate_money.is_min' => '代扣手续费不能小于最低手续费',
        'type.require' => '渠道必填',
         'account_time.require' => '到账时间',
         'max_money.require' => '最高金额必填',
         'max_money.between' => '最高金额1-100000000',
         'min_money.require' => '最低金额必填',
         'min_money.between' => '最高金额1-100000000',
         'min_money.check_min' => '最小金额不能大于最大金额',
         'start_time.require' => '开始日期必填',
         'start_time.date' => '时间格式不对',
         'end_time.require' => '截止日期必填',
          'end_time.date' => '结束时间格式不对',
          'end_time.check_end_time' => '截止日期需要大于开放日期',
    ];
        protected function check_min($value,$rule,$data)
    {
             if($data['max_money']>$data["min_money"]){
                 return true;
             }else{
                 return false;
             }
    }
        protected function check_end_time($value,$rule,$data)
    {
            $start= strtotime($data["start_time"]);
            $end= strtotime($data["end_time"]);
             if( $end> $start){
                 return true;
             }else{
                 return false;
             }
    }
    protected function is_min($value,$rule,$data){
        if($data["rate_money"]>=$data["rate_money_min"]){
            return true;
        }
        return false;
        
    }
}
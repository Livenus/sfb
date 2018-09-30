<?php 
namespace app\common\validate;

use think\Validate;
class CreditPlan extends Validate{
    protected $rule = [
        'credit_id'  =>  'require|number',
        'credit_bank'  =>  'require',
        'credit_num'  =>  'require',
        'back_date'  =>  'require|/^\[.*?\]$/',
        'amount'  =>  'require|number|/^d+$/',
        'per'  =>  'require|between:1,100',
        'id'  =>  'require|/^\d+$/',
        'province'  =>  'require',
        'province_name'  =>  'require',
        'city'  =>  'require',
        'city_name'  =>  'require'
    ];
    protected $message = [
        'credit_id'  =>  '信用卡必填',
        'credit_num.unique'  =>  '卡号已存在',   
        'credit_num'  =>  '卡号必填',
        'credit_bank'  =>  '银行必填',
        'back_date'  =>  '还款日期必填',
        'amount'  =>  '总额必填',
        'per'  =>  '方式必填',
        'province'  =>  '省份必填',
        'city'  =>  '城市必填',
    ];
    
    protected $scene = [
        'add'   =>  ['credit_id','credit_bank','credit_num','back_date','per','province','province_name','city','city_name'],
        'edit'   =>  ['bill_date','due_date','id'],
    ];
    
 
}
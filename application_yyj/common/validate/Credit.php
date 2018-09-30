<?php 
namespace app\common\validate;

use think\Validate;
class Credit extends Validate{
    protected $rule = [
        'member_id'  =>  'require|number',
        'bank_name'  =>  'require',
        'bank_card'  =>  'require|is_in',
        'validity'  =>  'require|/^\d{4}.*?\d$/',
        'safe_num'  =>  'require|/\d{3}/',
        'bill_date'  =>  'require|between:0,33',
        'due_date'  =>  'require|between:0,33',
        'bank_mobile'  =>  'require|/^1\d{10}$/',
        'id'  =>  'require|/^\d+$/',
    ];
    protected $message = [
        'bank_name'  =>  '银行名称必填',
        'bank_card'  =>  '卡号必填',
        'bank_card.is_in'  =>  '卡号已存在',
        'validity'  =>  '有效期必填',
        'safe_num'  =>  '安全码必填必须为3位数字',
        'bill_date'  =>  '账单日必填',
        'due_date'  =>  '还款日必填',
        'bank_mobile'  =>  '手机号必填',
    ];
    
    protected $scene = [
        'add'   =>  ['member_id','bank_name','bank_card','validity','safe_num','bill_date','due_date','bank_mobile'],
        'edit'   =>  ['bill_date','id'],
    ];
    public function is_in($value,$rule,$data){
        $count=model("Credit")->where(["del"=>0,"bank_card"=>$value,"subMerchantNo"=>["neq",0],"authent_no"=>["neq",0]])->count();
        if($count){
            return false;
        }
        return true;
    }
 
}
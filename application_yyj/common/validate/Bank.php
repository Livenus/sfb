<?php 
namespace app\common\validate;

use think\Validate;
class Bank extends Validate{
    protected $rule = [
        'member_id'  =>  'require|number',
        'bank_name'  =>  'require|check_bank',
        'bank_num'  =>  'require|^([\d]{4})([\d]{4})([\d]{4})([\d]{0,})?|unique:bank|is_bank',
        'id'=>''
    ];
    protected $message = [
        'member_id.require'  => '用户ID必填',
        'bank_name.require'  => '银行卡名称必填',
        'bank_num.require'  => '银行卡号必填',
        'bank_num'=>'银行卡号不正确',
        'bank_name.check_bank'  => '不支持该银行卡',
        'bank_num.unique'  => '该卡号已存在',
        'bank_num.is_bank'  => '不支持该银行',
        'id.require'  => 'ID必填',
        'id.check_count'  => 'ID不存在',
    ];
    
    protected $scene = [
        'del'   =>  ['id'=>'require|check_count'],
    ];
    protected function  check_bank($value){
        return true;
       $value=trim($value);
       $value= mb_substr($value, 0,1);
       $map["BANK_NAME"]=["like","%{$value}%"];
       $bank_count=model('bank_type')->where($map)->count();
        if($bank_count){
            return true;
        }else{
            return false;
        }
    }
    protected function  is_bank($value){
        return true;
       $value=trim($value);
       $value= substr($value, 0,1);
       $map["CARD_BIN"]=["like","%{$value}%"];
       $bank_count=model('bank_type')->where($map)->count();
        if($bank_count){
            return true;
        }else{
            return false;
        }
    }
    protected function  check_count($value){
        $map["id"]=$value;
       $bank_count=model('bank')->where($map)->count();
        if($bank_count){
            return true;
        }else{
            return false;
        }
    }

 
}
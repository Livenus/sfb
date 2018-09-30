<?php 
namespace app\common\validate;

use think\Validate;
class Shop extends Validate{
    protected $rule = [
        'member_id'  =>  'require|number',
        'name'  =>  'require|length:2,68',
        'short_name'  =>  'require|length:2,68',
        'address'  =>  'require|length:2,68',
        'company'  =>  'require|length:2,68',
        'cat'  =>  'require|length:2,68',
        'bus'  =>  'require|length:2,68|is_img',
        'banner'  =>  'require|length:2,68|is_img',
        'house'  =>  'require|length:2,68|is_img',
        'money'  =>  'require|length:2,68|is_img',
        'idcard'  =>  'require|length:2,68|is_img',
    ];
    protected $message = [
        'member_id.require'  => '用户ID必填',
        'name.require'  => '名称必填',
        'short_name.require'  => '简称必填',
        'address.require'  => '地址必填',
        'company.require'  => '注册名称必填',
        'cat.require'  => '类目必填',
        'bus.require'  => '营业执照必填',
         'house.require'  => '店内照片必填',
         'banner.require'  => '店铺头门必填',
         'money.require'  => '收银台照片必填',
        'bus.is_img'  => '营业执照照片地址不合法',
         'house.is_img'  => '店内照片照片地址不合法',
         'banner.is_img'  => '店铺头门照片地址不合法',
         'money.is_img'  => '收银台照片照片地址不合法',
         'idcard.is_img'  => '收银台照片照片地址不合法',
];
    protected $scene = [
    ];
    protected  function is_img($value,$rule,$data){
        if(preg_match("/(jpg|png|jpeg){1}/", $value)){
            return true;
        }else{
            return false;
        }
        
    }

 
}
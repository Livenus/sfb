<?php 
namespace app\common\validate;

use think\Validate;
class MemberArea extends Validate{
    protected $rule = [
        'member_id'  =>  'require|number|between:1,100000000',
        'province'  =>  'require|number|between:1,100000000',
        'city'  =>  'require|number|between:1,100000000',
        'county'  =>  'require|number|between:1,100000000',
        'detail'  =>  'require|length:1,100000000',
    ];
    protected $message = [
        'member_id.require'  => '用户ID必填',
        'province.require'  => '省id必填',
        'city.require'  => '市id必填',
        'province.between'  => '省id不合法',
        'city.between'  => '市id不合法',
        'county.between'  => '区、县必填',
        'detail.require'  => '开户行必填',
        'detail.length'  => '详细地址必填',
    ];
    
    protected $scene = [
    ];

 
}
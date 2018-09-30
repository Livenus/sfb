<?php 
namespace app\admin\validate;

use think\Validate;

class Sms extends Validate{
    protected $rule =   [
        'name'  => 'require|min:2|max:10|unique:sms_tpl',
        'code'  =>  'require|unique:sms_tpl',
    ];
    
    protected $message  =   [
        'name.require' => '模板名称必须填写',
        'name.max'     => '模板名称最多不能超过10个字符',
        'name.min'     => '模板名称最少不能低于5个字符',
        'name.unique'   => '该模板名称已经存在，不要重复添加',
        'code.require'  =>  '模板调用代码不能为空',
        'code.unique'   =>  '模板调用代码是唯一的，不能重复'
    ];
    
    protected $scene = [
        'add'  =>  ['name','code'],
        'edit' =>  [
            'name' => 'require|min:5|max:10',
            'code' => 'require'
        ]
    ];
}
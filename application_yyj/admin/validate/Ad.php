<?php 
namespace app\admin\validate;

use think\Validate;

class Ad extends Validate{
    protected $rule =   [
        'name'  => 'require|min:5|max:20|unique:adv',
    ];
    
    protected $message  =   [
        'name.require' => '广告名称必须填写',
        'name.max'     => '广告名称最多不能超过20个字符',
        'name.min'     => '广告名称最少不能低于5个字符',
        'name.unique'   => '该广告名称已经存在，不要重复添加'
    ];
    
    protected $scene = [
        'add'  =>  ['name'],
        'edit' =>  [
            'name' => 'require|min:5|max:20'
        ]
    ];
}
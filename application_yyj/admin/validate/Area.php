<?php 
namespace app\admin\validate;

use think\Validate;

class Area extends Validate{
    protected $rule =   [
        'a_name'  => 'require|min:5|max:10|unique:area'
    ];
    
    protected $message  =   [
        'a_name.require' => '名称必须填写',
        'a_name.max'     => '名称最多不能超过10个字符',
        'a_name.min'     => '名称最少不能低于5个字符',
        'a_name.unique'   => '名称已存在',
        'a_name.between' => '名称必须在5~10个字符之间'
    ];
    
    protected $scene = [
        'add'  =>  ['a_name'],
    ];
}
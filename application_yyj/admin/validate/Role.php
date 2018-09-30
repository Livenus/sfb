<?php 
namespace app\admin\validate;

use think\Validate;

class Role extends Validate{
    protected $rule =   [
        'title'  => 'require|min:5|max:10|unique:auth_group'
    ];
    
    protected $message  =   [
        'title.require' => '名称必须填写',
        'title.max'     => '名称最多不能超过10个字符',
        'title.min'     => '名称最少不能低于5个字符',
        'title.unique'   => '该权限组已经存在，不要重复添加',
        'title.between' => '名称必须在5~10个字符之间'
    ];
    
    protected $scene = [
        'add'  =>  ['title'],
        'edit' =>  ['title']
    ];
}
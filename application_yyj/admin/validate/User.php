<?php 
namespace app\admin\validate;

use think\Validate;

class User extends Validate{
    protected $rule = [
        'username' => 'require|min:5|max:10|unique:admin',
        'password'  => 'require|min:6|max:32',
        'realname' => 'min:2|max:5'
    ];
    protected $message  = [
        'username.require'  =>  '用户名必填',
        'username.unique' => '该用户名已经存在，不要重复添加',
        'password.require' =>  '密码必填',
        'username.min' => '名称最少不能低于5个字符',
        'username.max' => '名称最多不能超过10个字符',
        'password.min'=> '密码最少不能低于6位',
        'password.max'=> '密码最多不能超过32位',
        'realname.min' => '名称最少不能低于2个字符',
        'realname.max' => '真名最多不能超过5个字符',
    ];
    
    protected $scene = [
        'add' => ['username','password','realname'],
        'edit'  =>  [
            'password' => 'min:6|max:32',
            'realname'
        ],
    ];
    
    
}
?>
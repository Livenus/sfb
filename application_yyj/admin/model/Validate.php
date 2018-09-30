<?php
namespace app\admin\validate;
class Validate extends \think\Validate{
	protected $rule = [
        'name'  =>  'require|max:25',
        'email' =>  'email',
    ];
    
    protected $message = [
        'name.require'  =>  '用户名必须',
        'email' =>  '邮箱格式错误',
	];
	
}
?>

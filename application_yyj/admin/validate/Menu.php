<?php 
namespace app\admin\validate;

use think\Validate;

class Menu extends Validate{
    protected $rule =   [
        'title'  => 'require|min:1|unique:menu',
        //'pid'   => 'checkPid:zl',
    ];
    
    protected $message  =   [
        'title.require' => '名称必须填写',
        'title.max'     => '名称最多不能超过201个字符',
        'title.min'     => '名称最少不能低于1个字符',
        'title.unique'   => '该菜单名称已经存在，不要重复添加',
        'pid.checkPid'  => '该菜单下有子菜单，不能更改为二级菜单'
    ];
    
    protected $scene = [
        'add'  =>  ['title'],
        'edit' =>  [
            'title' =>'require|min:1|max:200',
            //'id'   => 'checkPid:zl',
        ]
    ];
    // 自定义验证规则
    protected function checkPid($value,$rule,$data){
        if($value < 0) return false;
        $ret = model('menu')->where('pid',$value)->count();
        if($ret > 0){
            return false;
        }
        return true;
    }
}
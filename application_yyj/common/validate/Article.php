<?php 
namespace app\common\validate;

use think\Validate;

class Article extends Validate{
    protected $rule = [
        'title'  =>  'require|max:200',
        'code'   =>  'checkCode',
        'type'   =>  'require|checkType'
    ];
    protected $message = [
        'title.require'  => '标题必填',
        'title.max'      => '名称最多不能超过200个字符',
        'code'           => '类型为‘协议’时，code必填1',
        'type'           => '类型不正确'
    ];
    
    protected $scene = [
        'edit'  =>  ['title','code','type'],
        'add'   =>  ['title','code', 'type']
    ];
    
    protected  function checkType($value, $rule, $data){
        return ($value == '1' && empty($data['code'])) ? '类型为‘协议’时，code必填' : true ; 
    }
    protected  function checkCode($value, $rule, $data){
        return (empty($value) && $data['code'] == '1') ? '类型为‘协议’时，code必填' : true ; 
    }
    
}
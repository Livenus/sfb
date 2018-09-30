<?php 
namespace app\admin\validate;

use think\Validate;

class MsgAdmin extends Validate{
    protected $rule =   [
        'title'  => 'require|min:5|max:20',
        'content'  => 'require',
         'start'  => 'require',
         'end'  => 'require|is_right',
    ];
    
    protected $message  =   [
        'title.require' => '标题必须填',
        'title.min' => '标题长度大于5小于20',
        'content.require' => '内容必须填',
        'start.require' => '开始时间必须填',
        'end.require' => '结束必须填',
        'end.is_right' => '结束时间必须大于开始时间',
    ];
    protected function is_right($value,$rule,$data){
        if($data['end']>$data['start']){
            return true;
        }else{
             return false;
        }
    }
}
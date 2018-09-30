<?php 
namespace app\admin\model;

class SmsLog extends \app\admin\model\Index{
    public function getAddTimeAttr($value){
        return date("Y-m-d H:i:s",$value);
    }
    
    public function getIpAttr($value){
        return long2ip($value) ;
    }
    public function getTypeAttr($value){
        $status = [1=>'注册',2=>'登录',3=>'找回密码',4=>'绑定手机'];
        return $status[$value];
    }
    public function getStateTextAttr($value,$data){
        $state = ['1'=>'发送成功','0'=>'发送失败'];
        return $state[$data['state']];
    }
    /*protected function scopePhone($query,$phone){
        $query->where('phone',$phone);
    }
    
    protected function scopeAddTime($query,$start,$end){
        $query->whereBetween('add_time',[$start.','.$end]);
    } */  
}
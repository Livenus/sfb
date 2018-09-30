<?php 
namespace app\admin\model;

class Log extends \app\admin\model\Index{
    public function getIpAttr($value){
		return long2ip($value);
	}
	
	public function getLogTimeAttr($value){
		return date("Y-m-d H:i:s",$value);
	}
}
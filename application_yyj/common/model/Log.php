<?php
namespace app\common\model;
use think\Config;
class Log extends \app\common\model\Home{
  
    public  function get_by_id($id){
        
        $data=$this->where(array("id"=>$id))->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
}
?>
<?php 
namespace app\api\model;
class Adv extends \app\api\model\Home{
    public  function get_by_id($id){
        $map["id"]=$id;
        $map["stat"]=1;
        $data=$this->where($map)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
}
?>
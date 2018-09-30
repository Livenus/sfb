<?php 
namespace app\admin\model;
class Sumsite extends \app\admin\model\Index{
        

    public function update_num($key,$value){
        $res=$this->where(array("id"=>1))->setInc($key,$value);
        return $res;
    }
    public function update_less($key,$value){
        $res=$this->where(array("id"=>1))->setDec($key,$value);
        return $res;
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    public function get_by_id($map,$order="id asc",$limit=""){
   
        $data=$this->where(array("id"=>1))->find();
        if($data){
            return $data->toArray();
        }else{
            return false;
        }
    }
}
?>
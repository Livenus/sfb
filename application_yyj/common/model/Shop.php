<?php
namespace app\common\model;
use think\Config;
class Shop extends \app\common\model\Home{
    protected $auto = [];
    protected $insert = ['add_time'];  
    protected $update = [];  
        
    protected function setAddtimeAttr()
    {
        return time();
    }  
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    public  function update_by_member_id($data,$id){
        
        $status=$this->isUpdate()->save($data,array("member_id"=>$id));
        return $status;
    }
    public  function get_by_id($map){
        $data=$this->where($map)->field(['id'])->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
}
?>
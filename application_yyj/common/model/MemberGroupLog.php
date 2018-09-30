<?php
namespace app\common\model;
use think\Config;
class MemberGroupLog extends \app\common\model\Home{
    protected $auto = [];
    protected $insert = ['add_time'];  
    protected $update = [];  
        
    protected function setAddtimeAttr()
    {
        return time();
    }  
     protected function getAddtimeAttr($value)
    {
        return date("Y-m-d H:i:s",$value);
    }  
     protected function getFinishtimeAttr($value)
    {
        return date("Y-m-d H:i:s",$value);
    }  
    public  function get_by_orderid($id){
        $map['order_id']=$id;
        $data=$this->where($map)->field(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(true)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    public function editbyorder_id($data,$id){
        $map['order_id']=$id;
        
        $status=$this->where($map)->update($data);
        return $status;
    }
}
?>
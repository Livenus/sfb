<?php
namespace app\admin\model;
use think\Config;
class MemberGroupLog extends \app\admin\model\Index{
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
    //返回ID
    public function addItem_id($data){
      
        try{
            $this ->data($data)->save();
        }catch( \Exception $e){
            return $this->err($e->getMessage());
        }

       return $this->suc($this->id);  
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
    public  function get_by_id($id){
        $map["id"]=$id;
        $data=$this->where($map)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
}
?>
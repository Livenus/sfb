<?php
namespace app\common\model;
use think\Config;
class Product extends \app\common\model\Home{
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->order($order)->limit($limit)->select();
        if($data){
            $data=collection($data)->toArray();
            return $data;
        }else{
            return false;
        }
        
    }
        public function get_count($map){
        
        return $this->where($map)->count();
    }
    public  function get_by_id($id){
        $map['id']=$id;
        $data=$this->where($map)->field(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public  function del_by_id($id){
        $map['id']=$id;
        return $this->where($map)->delete();
    }
    public  function del_all($ids){
        
        return $this->where("id","in",$ids)->delete();
    }
}
?>
<?php
namespace app\common\model;
use think\Config;
class CreditPlanItem extends \app\common\model\Home{
    protected $auto = [];
    protected $insert = ['add_time'];  
    protected $update = [];  
        
    protected function setAddtimeAttr()
    {
        return time();
    }  
    public function select_all($map=[],$order="id asc",$limit=""){
        $data=$this->where($map)->field(true)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    public function addItem_id($data) {
        $data['add_time']=time();
        db("CreditPlanItem")->insert($data);
        return db("CreditPlanItem")->getLastInsID();
    }
    public function edit($data,$id) {
        if(is_numeric($id)){
          return db("CreditPlanItem")->where(["id"=>$id])->update($data);  
        }else{
            return db("CreditPlanItem")->where($id)->update($data);
        }
        
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
    public  function get_by_map($map){
        $data=$this->where($map)->field(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public function get_first($map){
        $data=$this->where($map)->field(true)->order("num asc")->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
        
    }
    public function get_end($map){
        $data=$this->where($map)->field(true)->order("num desc")->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
        
    }
    public  function del_by_id($member_id,$id){
        $map['member_id']=$member_id;
        $map['id']=$id;
        return $this->where($map)->delete();
    }
    public  function del_all($ids){
        
        return $this->where("id","in",$ids)->delete();
    }
}
?>
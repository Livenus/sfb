<?php
namespace app\common\model;
use think\Config;
class CreditPlan extends \app\common\model\Home{
    protected $auto = [];
    protected $insert = ['add_time'];  
    protected $update = [];  
        
    protected function setAddtimeAttr()
    {
        return time();
    }  
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(true)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
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
    public  function get_by_map($map){
        $data=$this->where($map)->field(true)->find();
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
    public  function del_empty($member_id,$credit_id){
        $map['member_id']=$member_id;
        $map['status']=0;
         $map['credit_id']=$credit_id;
         $ids=$this->where(["credit_id"=>$credit_id])->column("id");
        $map1["credit_plan_id"]=["in",$ids];
         $map1['status']=0;
        $status=model("CreditPlanItem")->where($map1)->delete();
        return $this->where($map)->delete();
    }
    public  function del_all($ids){
        
        return $this->where("id","in",$ids)->delete();
    }

}
?>
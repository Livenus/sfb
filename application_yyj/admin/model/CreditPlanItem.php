<?php
namespace app\admin\model;
use think\Config;
class CreditPlanItem extends \app\common\model\Home{
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
    public function addItem_id($data) {
        $data['add_time']=time();
        db("CreditPlanItem")->insert($data);
        return db("CreditPlanItem")->getLastInsID();
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
    public function get_user($id){
         $data=$this->join("yys_credit_plan","yys_credit_plan_item.credit_plan_id=yys_credit_plan.id")
                 ->join("yys_credit","yys_credit_plan.credit_id=yys_credit.id")
                 ->join("yys_member","yys_credit.member_id=yys_member.id")
                 ->where("yys_credit_plan_item.id={$id}")
                 ->field("yys_member.uname")
                 ->find();
        return $data;
    }
    public  function del_by_id($id){
        $map['id']=$id;
        return $this->where($map)->delete();
    }
    public  function del_all($ids){
        
        return $this->where("id","in",$ids)->delete();
    }
    public function edit($data,$id) {
        if(is_numeric($id)){
          return db("CreditPlanItem")->where(["id"=>$id])->update($data);  
        }else{
            return db("CreditPlanItem")->where($id)->update($data);
        }
        
    }
}
?>
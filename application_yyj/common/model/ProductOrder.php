<?php
namespace app\common\model;
use think\Config;
class ProductOrder extends \app\common\model\Home{
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
    public function editById($data, $id,$order=[]) {
        $this->set_pid_red($data, $id,$order);
        return parent::editById($data, $id);
    }
    public function set_pid_red($data,$id){
        $user_model=model("Member");
        $set_model=model("ProductSet");
        $red_model=model("Red");
        $order=$this->get_by_id($id);
        $user=$user_model->get_by_id($order["member_id"]);
        $parent=$user_model->get_by_id($user["p_id"]);
        $parent_parent=$user_model->get_by_id($parent["p_id"]);
        //上一级
        if($parent&&$parent["membergroup_id"]&&$user["membergroup_id"]>=14){
            $set=$set_model->get_by_map($parent["membergroup_id"],$user["membergroup_id"]);

            if($set["level1"]){
                 $old=$red_model->get_by_uid($parent["id"]);
                 $more=$order["amount"]*$set["level1"]/100;
                $update["red"]=$old["red"]+$more;
                $status1=$red_model->editById($update,$old["id"],$more,$old["member_id"],$id);
                mlog($status1);
            }
        }
        //上二级
        if($parent_parent&&$parent_parent["membergroup_id"]&&$user["membergroup_id"]>=14){
            $set=$set_model->get_by_map($parent_parent["membergroup_id"],$user["membergroup_id"]);
            if($set["level2"]){
                $old=$red_model->get_by_uid($parent_parent["id"]);
                 $more=$order["amount"]*$set["level2"]/100;
                $update["red"]=$old["red"]+$more;
                $status2=$red_model->editById($update,$old["id"],$more,$old["member_id"],$id);
                mlog($status2);
            }
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
    public  function get_by_sn($sn){
        
        $map["sn"]=$sn;
        $data=$this->where($map)->find();
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
    public function sum($map){
        
        $sum=$this->where($map)->sum("num");
        return $sum+0;
    }
}
?>
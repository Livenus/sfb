<?php
namespace app\common\model;
use think\Config;
class Red extends \app\common\model\Home{
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->order($order)->limit($limit)->select();
        if($data){
            $data=collection($data)->toArray();
            return $data;
        }else{
            return false;
        }
        
    }
    public function editById($data, $id,$more=0,$member_id=0,$order_id=0,$num=0,$amount=0,$bonus=0) {
        if($more){
                $log["red"]=$more;  
                $log["member_id"]=$member_id;  
                $log["reason"]=$order_id;  
                $log["add_time"]=time();  
                $log["num"]=$num;  
                $log["amount"]=$amount;  
                $log["bonus"]=$bonus;  
                $stat=model("RedLog")->addItem($log);
                mlog($stat);
        }

        return parent::editById($data, $id);
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
        public  function get_by_uid($id){
        $map['member_id']=$id;
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
}
?>
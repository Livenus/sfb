<?php
namespace app\common\model;
class Order extends \app\common\model\Home{
    protected $auto = [];
    protected $insert = ['add_time','sn'];  
    protected $update = [];  
    protected function setAddtimeAttr()
    {
        return time();
    }
    protected function setSnAttr()
    {
        return "sfb".date("Ymdhis"). mt_rand(10000, 99999);
    }
    public function select_all($map,$order="id desc",$limit=""){
        $map["outer_sn"]=['EXP', '!=""'];
        $data=$this->where($map)->order($order)->field(["id","member_id","sn","outer_sn","pay_way","amount","type","channel_id","stat","add_time","finish_time"])->limit($limit)->select();
        if(is_array($data)){
            return collection($data)->toArray();
        }else{
            return false;
        }
        
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    public function edit_by_sn($data,$sn){
        return $this->where(array("sn"=>$sn))->update($data);
    }
    public  function get_by_id($member_id,$id){
        $map["id"]=$id;
        $map["member_id"]=$member_id;
        $data=$this->where($map)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public  function get_by_id_o($id){
        $map["id"]=$id;
        $data=$this->where($map)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public  function get_by_outer_sn($outer_sn,$status=0){
        $map["outer_sn"]=$outer_sn;
        $map["stat"]=$status;
        $data=$this->where($map)->find();
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
    public  function get_by_sn_lock($sn){
        
        $map["sn"]=$sn;
        $data=$this->where($map)->lock(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
    }
    public  function del_by_id($id){
        
        return $this->where(array("id"=>$id))->delete();
    }
    public  function del_all($ids){
        
        return $this->where("id","in",$ids)->delete();
    }
}
?>
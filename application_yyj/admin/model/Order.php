<?php
namespace app\admin\model;
class Order extends \app\admin\model\Index{
    
    
    
    
    
    
    
    
    protected function getAddtimeAttr($value)
    {
        return date("Y-m-d H:i:s",$value);
    }
    protected function getFinishtimeAttr($value)
    {    if($value){
        return date("Y-m-d H:i:s",$value);
        }
        return "--";
    }
    public function select_all($map,$order="id desc",$limit=""){
        //$map["outer_sn"]=['EXP', '!=""'];
        $data=$this->where($map)->order($order)->field(["id","member_id","sn","outer_sn","amount","type","bank_num","pay_way","rate","low_rate","rate_money","low_rate_money","channel_id","stat","reason","add_time","finish_time"])->limit($limit)->select();
        if(is_array($data)){
            return collection($data)->toArray();
        }else{
            return false;
        }
        
    }
    public function select_all_id($map){
        $data=$this->where($map)->column("sn");
        if(is_array($data)){
            return $data;
        }else{
            return false;
        }
        
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    //总流水、
    public  function get_sum_money($map=[]){
        $map["stat"]=1;
        $money=$this->where($map)->sum("amount");
        return $money+0;
    }
    public  function get_sum_money_group($map=[],$order="",$limit=""){
        $map["stat"]=1;
        $data=$this->where($map)->field(['sum(amount) sum'])->order($order)->limit($limit)->find();
        $sum=$data->toArray();
        return $sum["sum"]+0;
    }
    public function select_all_day_count($map=[]){
        $map["stat"]=1;
        $count=$this->where($map)->whereTime('add_time', 'today')->sum("amount");
        return $count+0;
    }
    public function sum_month(){
        $map["stat"]=1;
        $count=$this->where($map)->whereTime('add_time', 'm')->sum("amount");
        return $count+0;
    }
    //总流水、
    public  function get_sum_all_get($type,$map=array()){
        $map["stat"]=1;
        $map["type"]=$type;
        if($type==1){
             $money=$this->where($map)->sum("amount*rate/1000-amount*low_rate/1000+rate_money-low_rate_money");
        }else{
             $money=$this->where($map)->sum("amount");
        }
       
        return $money+0;
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
    public  function del_by_id($id){
        
        return $this->where(array("id"=>$id))->delete();
    }
    public  function del_all($ids){
        
        return $this->where("id","in",$ids)->delete();
    }
}
?>
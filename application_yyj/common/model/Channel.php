<?php
namespace app\common\model;
use think\Config;
class Channel extends \app\common\model\Home{
  
        
    public function table_info(){
        $sql="SELECT a.COLUMN_NAME as name, a.COLUMN_COMMENT as comment
FROM information_schema.COLUMNS a 
WHERE a.TABLE_NAME = 'yys_channel'";
        $data=$this->query($sql);
        $result=array();
        foreach($data as $v){
            $result[$v['name']]=$v["comment"];
        }
        return $result;
    }       
    public function select_all($a_name="",$order="id desc",$limit=""){
        $map=[];
        if($a_name){
                  $map['name']  = ["like","%{$a_name}%"];  
        }

        
        $data=$this->where($map)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    public  function get_by_id($id){
        
        $data=$this->where(array("id"=>$id))->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    //$map["pay_type"]=[">",0];$map["stat"]=1;$t=date("H:i:s");$map["min_money"]=["elt",$money];$map["max_money"]=["egt",$money];$map["start_time"]=["elt",$t];$map["end_time"]=["egt",$t];
    public  function get_by_type($type,$money){
        
        $map["type"]=$type;
        $map["stat"]=1;
        
        
        
        
        
        $data=$this->where($map)->field(["id","name","start_time","end_time","addtime","pay_type","rate","rate_money","account_time","max_money","min_money",'desc','sot'])->order('sot ASC')->select();
        return $data;
    }
    //支付方式获取ids
    public  function get_by_type_paytype($type,$paytype){
        $map["type"]=$type;
        $map["pay_type"]=$paytype;
        $data=$this->where($map)->column("id");
        return $data;
    }
    public  function get_by_rate($rate){
        $map["rate"]=$rate;
        $map["stat"]=1;
        $data=$this->where($map)->field(["id","name","start_time","end_time","addtime","pay_type","rate","account_time","max_money","min_money"])->find();
        return $data;
    }
    public  function del_by_id($id){
        
        return $this->where(array("id"=>$id))->delete();
    }
    public  function del_all($ids){
        
        return $this->where("id","in",$ids)->delete();
    }
    //获取支付接口
     public function get_pay_way($money=0,$pay_type_id=""){
        if($pay_type_id&& is_numeric($pay_type_id)){
            $data=$this->get_by_id($pay_type_id);
             Config::load(APP_PATH.'/pay_type.php');
            $unionpay= get_self();
        $unionpays=[];
        foreach($unionpay as $v){
            if($v["id"]==$data["type"]){
                foreach($v["pay_way"] as $vv){
                     if($vv["type"]==$data["pay_type"]){
                         $unionpays=$vv;
                     }
                }
            }
          }
          if($unionpays){
             return $unionpays;
          }
        }
         return false;
    }
    //通过键获取支付接口
     public function get_pay_way_key($rate_key=""){
        Config::load(APP_PATH.'/pay_type.php');
        $unionpay= get_self();
        $unionpays=[];
        foreach($unionpay as $v){
                foreach($v["pay_way"] as $vv){
                     if($vv["rate_type"]==$rate_key){
                         $unionpays=$vv;
                         return $unionpays;
                         break;
                     }
                }
            
          }
          if($unionpays){
             return $unionpays;
          }
         return false;
    }
    //获取支付渠道
     public function get_pay_way_parent($money=0,$pay_type_id=""){
        if($pay_type_id&& is_numeric($pay_type_id)){
            $data=$this->get_by_id($pay_type_id);
             Config::load(APP_PATH.'/pay_type.php');
            $unionpay= get_self();
        $unionpays=[];
        foreach($unionpay as $v){
            if($v["id"]==$data["type"]){
                    $unionpays=$v;
            }
          }
          if($unionpays){
             return $unionpays;
          }
        }
         return false;
    }
}
?>
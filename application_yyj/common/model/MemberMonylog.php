<?php
namespace app\common\model;
use think\Config;
use think\Db;
class MemberMonylog extends \app\common\model\Home{
    protected $auto = [];
    protected $insert = ['add_time'];  
    protected $update = ['update_time'];  
        
    protected function setAddtimeAttr()
    {
        return time();
    }  
    protected function getAddtimeAttr($values)
    {
        return date("Y-m-d H:i:s",$values);
    }  
    protected function setUpdatetimeAttr()
    {
        return time();
    }  
    protected function getUpdatetimeAttr($values)
    {  if($values){
        return date("Y-m-d H:i:s",$values);
    }else{
        return "";
    }
        
    }  
    public function table_info(){
        $sql="SELECT a.COLUMN_NAME as name, a.COLUMN_COMMENT as comment
FROM information_schema.COLUMNS a 
WHERE a.TABLE_NAME = 'yys_member_monylog'";
        $data=$this->query($sql);
        $result=array();
        foreach($data as $v){
            $result[$v['name']]=$v["comment"];
        }
        return $result;
    }  
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(true)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
    public function select_all_day($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(['sum(val) sum','FROM_UNIXTIME(add_time) days','add_time'])->order($order)->limit($limit)->group("day(days)")->select();
        return collection($data)->toArray();
    }
    public function select_all_day_count($map=[],$order="id desc",$limit=""){
        $count=$this->where($map)->field(['sum(val) sum','FROM_UNIXTIME(add_time) days','add_time'])->order($order)->limit($limit)->group("day(days)")->count();
        return $count;
    }
    public  function get_by_id($id){
        
        $data=$this->where(array("id"=>$id))->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    //今日流水
    public  function sum_money($member_id){
        $map["member_id"]=$member_id;
        $map["type"]=["in","1,2,6,7,8,9"];
        //$map["add_time"]=["between",[$day_int,$night_int]];
        $sum=$this->where($map)->whereTime('add_time', 'd')->sum("val");
        $sum=$sum+0;
        return $sum;
    }
    //等待审金额
    public  function sum_money_litttle($member_id){
        $map["member_id"]=$member_id;
        $map["type"]=3;
        $map["status"]=0;
        $sum=$this->where($map)->sum("val");
        $sum=$sum+0;
        return $sum;
    }
    //提现
       public  function sum_money_cash($member_id){
        $map["member_id"]=$member_id;
        $map["type"]=3;
        $map["status"]=["in","0,1"];
        $sum=$this->where($map)->sum("val");
        $sum=$sum+0;
        return $sum;
    }
    //总返佣
       public  function sum_money_back($sn){
        $map["type_ordersn"]=$sn;
        $map["type"]=["in","1,2,6,7,8,9"];
        $sum=$this->where($map)->sum("val");
        $sum=$sum+0;
        return $sum;
    }
       public  function sum_money_back_all($map=array()){
        $map["type"]=["in","1,2,6,7,8,9"];
        $sum=$this->where($map)->sum("val");
        $sum=$sum+0;
        return $sum;
    }
    //返利总计
    public  function sum_money_back_all_group($map=[],$order="",$limit=""){
        $map["type"]=["in","1,2,6,7,8,9"];
        $data=$this->where($map)->field(['sum(val) sum'])->order($order)->limit($limit)->find();
        $sum=$data->toArray();
        return $sum["sum"]+0;
    }
      public  function sum_money_back_all_member($member_id){
        $map["member_id"]=$member_id;
        $map["type"]=["in","1,2,6,7,8,9"];
        $sum=$this->where($map)->sum("val");
        $sum=$sum+0;
        return $sum;
    }
    public  function count_id($member_id){
        $map["member_id"]=$member_id;
        $map["type"]=["in","1,2,6,7,8,9"];
        //$map["add_time"]=["between",[$day_int,$night_int]];
        $sum=$this->where($map)->whereTime('add_time', 'd')->count();
        return $sum;
    }
    public  function select_member_level($member_id,$level){
        $day=date("Y-m-d 00:00:02");
        $night=date("Y-m-d 23:59:59");
        $day_int= strtotime($day);
        $night_int= strtotime($night);
        $map["member_id"]=$member_id;
        $map["add_time"]=["between",[$day_int,$night_int]];
        $sql="select member.id as id,member.phone as phone,member.add_time as add_time ,member.is_rz_1,member.is_rz_2,member.p_id,gg.name
from yys_member_monylog as money
join yys_member as member 
on money.op_id=member.id 
join yys_member_group as gg 
on member.membergroup_id=gg.id  
where money.member_id=$member_id and member.membergroup_id=$level 
group by member.id";
        $member_data=Db::query($sql);
        return $member_data;
    }
    public  function count_id_level($member_id,$level){
        $map["member_id"]=$member_id;
        //$map["add_time"]=["between",[$day_int,$night_int]];
        $sum=$this->where($map)->whereTime('add_time', 'd')->count();
        return $sum;
    }
    //市级代理返佣
    public function set_city($member,$order,$rate_key,$mem_data_group){
        $set=model("Setting")->select_all();
        if(empty($set["city_back_cash"])||!is_numeric($set["city_back_cash"])){
            return false;
        }else{
            $back=$set["city_back_cash"];
        }
        $area=model("MemberArea");
        $areaname=model("Area");
        $member_model=model("Member");
        $user=$area->get_by_member_id($member["id"]);
        $user_pid=$area->get_city_user_key($user["city"],$rate_key);
        if(empty($user_pid)){
            return false;
        }

        $areaname_aname=$areaname->get_by_name($user["city"]);
        $money=$order["amount"];
        $money1=$money*$back/100;
        if($money1<=0){
            return false;
        }
        if($money1){
            $data["member_id"]=$user_pid["id"];
            $data["type"]=8;
            $data["op_id"]=$member["id"];
            $data["type_ordersn"]=$order["sn"];
            $data["val"]= $money1;
            $msg="您的{$areaname_aname['a_name']}会员". substr($member["phone"], 0,6)."*****,刷卡成功，";
            return $member_model->update_money($user_pid["id"],$money1,0,$data,$msg);
        }
        return false;
    }
    //省级代理返佣
    public function set_pro($member,$order,$rate_key,$mem_data_group){
        $set=model("Setting")->select_all();
        if(empty($set["pro_back_cash"])||!is_numeric($set["pro_back_cash"])){
            return false;
        }else{
            $back=$set["pro_back_cash"];
        }
        $area=model("MemberArea");
        $areaname=model("Area");
        $member_model=model("Member");
        $user=$area->get_by_member_id($member["id"]);

        $user_pid=$area->get_pro_user_key($user["province"],$rate_key);
        if(empty($user_pid)){
            return false;
        }
        $areaname_aname=$areaname->get_by_name($user["province"]);
        $money=$order["amount"];
        $money1=$money*$back/100;
        if($money1<=0){
            return false;
        }

        if($money1){
            $data["member_id"]=$user_pid["id"];
            $data["type"]=6;
            $data["op_id"]=$member["id"];
            $data["type_ordersn"]=$order["sn"];
            $data["val"]= $money1;
            $msg="您的{$areaname_aname['a_name']}会员". substr($member["phone"], 0,6)."*****,刷卡成功，";
            return $member_model->update_money($user_pid["id"],$money1,0,$data,$msg);
        }
        return false;
        
    }
    //市级代理买会员返佣
    public function set_city_mem($member,$order){
        $set=model("Setting")->select_all();
        if(empty($set["city_back_up"])||!is_numeric($set["city_back_up"])){
            return false;
        }else{
            $back=$set["city_back_up"];
        }
        $area=model("MemberArea");
        $member_model=model("Member");
        $user=$area->get_by_member_id($member["id"]);
        $user_pid=$area->get_city_user($user["city"]);
        if(empty($user_pid)){
            return false;
        }
        $money=$order["amount"];
        $money1=$money*$back/100;
        if($money1){
            $data["member_id"]=$user_pid["id"];
            $data["type"]=9;
            $data["op_id"]=$member["id"];
            $data["type_ordersn"]=$order["sn"];
            $data["val"]= $money1;
            return $member_model->update_money($user_pid["id"],$money1,1,$data);
        }
        return false;
    }
    //省级代理买会员返佣
    public function set_pro_mem($member,$order){
        $set=model("Setting")->select_all();
        if(empty($set["pro_back_up"])||!is_numeric($set["pro_back_up"])){
            return false;
        }else{
            $back=$set["pro_back_up"];
        }
        $area=model("MemberArea");
        $member_model=model("Member");
        $user=$area->get_by_member_id($member["id"]);
        $user_pid=$area->get_pro_user($user["province"]);
        if(empty($user_pid)){
            return false;
        }
        $money=$order["amount"];
        $money1=$money*$back/100;
        if($money1){
            $data["member_id"]=$user_pid["id"];
            $data["type"]=7;
            $data["op_id"]=$member["id"];
            $data["type_ordersn"]=$order["sn"];
            $data["val"]= $money1;
            return $member_model->update_money($user_pid["id"],$money1,1,$data);
        }
        return false;
        
    }
    //刷卡上三级返利
    public  function set_pid_money($sn){
        //读取费率字段
        $Channel=model("Channel");
        $order=model("Order");
        $Member=model("Member");
        $MemberGroup=model("MemberGroup");
        $order_data=$order->get_by_sn($sn);
        

            $pay_class = $Channel->get_pay_way($order_data["amount"], $order_data["channel_id"]);
            $rate_key=$pay_class["rate_type"];
        $mem_data=$Member->get_by_id($order_data["member_id"]);

         $mem_data_group=$MemberGroup->get_by_id($mem_data["membergroup_id"]);

        $mem_data_pid=$Member->get_by_id($mem_data["p_id"]);

         $mem_data_group_pid=$MemberGroup->get_by_id($mem_data_pid["membergroup_id"]);
         //统计数据
        $Sumsite=model("Sumsite");
        $Sumsite->update_num("order_num",1);
        $Sumsite->update_num("order_money",$order_data["amount"]);
        $Sumsite->update_num("cash_money",$order_data["amount"]);
         //市级
         $city=$this->set_city($mem_data,$order_data,$rate_key,$mem_data_group);
         //省级
         $pro=$this->set_pro($mem_data,$order_data,$rate_key,$mem_data_group);
         $a=$mem_data_group[$rate_key];
         $b=$mem_data_group_pid[$rate_key];
         $rate111=$a-$b;
        //一级省市代理不参加上下级返佣
        if($mem_data_pid&&$mem_data_group_pid[$rate_key]&&$mem_data_group[$rate_key]&&$mem_data_group_pid[$rate_key]<$mem_data_group[$rate_key]){
            $data["member_id"]=$mem_data_pid["id"];
            $data["type"]=1;
            $data["op_id"]=$mem_data["id"];
            $data["rate_from"]=$mem_data_group[$rate_key];
            $data["rate_to"]=$mem_data_group_pid[$rate_key];
             $data["val"]=$mem_data_group[$rate_key]-$mem_data_group_pid[$rate_key];
             if($data["val"]>0){
                  $data["type_ordersn"]=$order_data["sn"];
                  $data["val"]= $order_data["amount"]*$data["val"]/1000+$mem_data_group[$rate_key."_money"]-$mem_data_group_pid[$rate_key."_money"];
                  mlog($data["val"]);
                 $msg="您的一级会员手机号". substr($mem_data["phone"], 0,6)."*****,刷卡成功，";
                 $Member->update_money($data["member_id"],$data["val"],0,$data,$msg);
 

                 //统计
             }
            
            
            
        }
        
        $mem_data_pid1=$Member->get_by_id($mem_data_pid["p_id"]);
         $mem_data_group_pid1=$MemberGroup->get_by_id($mem_data_pid1["membergroup_id"]);
//二级
         if($rate111>=0){
             $rate112=$b-$mem_data_group_pid1[$rate_key];
             $a=$b;
             $b=$mem_data_group_pid1[$rate_key];
         }else{
             $rate112=$a-$mem_data_group_pid1[$rate_key];
             $a=$a;
             $b=$mem_data_group_pid1[$rate_key];
         }
        if($mem_data_pid1&&$mem_data_group_pid1[$rate_key]&&$mem_data_group_pid[$rate_key]&&$rate112>0){
            $data=[];
            $data["member_id"]=$mem_data_pid1["id"];
            $data["type"]=1;
            $data["op_id"]=$mem_data["id"];
            $data["rate_from"]=$mem_data_group_pid[$rate_key];
            $data["rate_to"]=$mem_data_group_pid1[$rate_key];
             $data["val"]=$rate112;
             if($data["val"]>0){
                  $data["type_ordersn"]=$order_data["sn"];
                  $data["val"]= $order_data["amount"]*$data["val"]/1000+$mem_data_group_pid[$rate_key."_money"]-$mem_data_group_pid1[$rate_key."_money"];
                  $msg="您的二级会员手机号". substr($mem_data["phone"], 0,6)."*****,刷卡成功，";
                  $Member->update_money($data["member_id"],$data["val"],0,$data,$msg);
                     

             }
            
            
            
        }
        $mem_data_pid2=$Member->get_by_id($mem_data_pid1["p_id"]);
         $mem_data_group_pid2=$MemberGroup->get_by_id($mem_data_pid2["membergroup_id"]);
//三级
          if($rate112>=0){
              $rate113=$b-$mem_data_group_pid2[$rate_key];
          }else{
              $rate113=$a-$mem_data_group_pid2[$rate_key];
          }
        if($mem_data_pid2&&$mem_data_group_pid2[$rate_key]&&$mem_data_group_pid1[$rate_key]&&$rate113>0){
            $data=[];
            $data["member_id"]=$mem_data_pid2["id"];
            $data["type"]=1;
            $data["op_id"]=$mem_data["id"];
            $data["rate_from"]=$mem_data_group_pid1[$rate_key];
            $data["rate_to"]=$mem_data_group_pid2[$rate_key];
             $data["val"]=$rate113;
             if($data["val"]>0){
                  $data["type_ordersn"]=$order_data["sn"];
                  $data["val"]= $order_data["amount"]*$data["val"]/1000+$mem_data_group_pid1[$rate_key."_money"]-$mem_data_group_pid2[$rate_key."_money"];;
                  $msg="您的三级会员手机号". substr($mem_data["phone"], 0,6)."*****,刷卡成功，";
                  $Member->update_money($data["member_id"],$data["val"],0,$data,$msg);
                    
             }
            
            
            
        }
        return true;

    }

    //购买会员上三级返利
    public  function set_pid_money_up($sn){
        $order=model("Order");
        $Member=model("Member");
        $MemberGroup=model("MemberGroup");
        $MemberGroupLog=model("MemberGroupLog");

        $order_data=$order->get_by_sn($sn);
        $mem_data=$Member->get_by_id($order_data["member_id"]);

         $mem_data_group=$MemberGroup->get_by_id($mem_data["membergroup_id"]);

        $mem_data_pid=$Member->get_by_id($mem_data["p_id"]);

         $mem_data_group_pid=$MemberGroup->get_by_id($mem_data_pid["membergroup_id"]);
         //购买的服务
         $level=$MemberGroupLog->get_by_orderid($order_data["id"]);
         $level_group=$MemberGroup->get_by_id($level["heigh_group"]);
         //统计数据
        $Sumsite=model("Sumsite");
        $Sumsite->update_num("order_num",1);
        $Sumsite->update_num("order_money",$order_data["amount"]);
        $Sumsite->update_num("up_money",$order_data["amount"]);
         //市级
         $city=$this->set_city_mem($mem_data,$order_data);
         //省级
         $pro=$this->set_pro_mem($mem_data,$order_data);
         //设置会员组
         if($level_group){
             $Member->editById(array("membergroup_id"=>$level["heigh_group"]),$mem_data["id"]);
         }
         $mem_data_group=$MemberGroup->get_by_id($level["heigh_group"]);
        //一级
        if($mem_data_pid&&$mem_data_group_pid['id']>=$mem_data_group['id']){
            $data["member_id"]=$mem_data_pid["id"];
            $data["type"]=2;
            $data["op_id"]=$mem_data["id"];
             //$data["val"]=$mem_data_group["money"]-$mem_data_group_pid["money"];
             if($order_data["amount"]){
                  $data["type_ordersn"]=$order_data["sn"];
                  $data["val"]= $order_data["amount"]*$mem_data_group_pid["level_1_rate"]/100;
                 $msg="您的一级会员手机号". substr($mem_data["phone"], 0,6)."*****,升级成功，";
                  $Member->update_money($data["member_id"],$data["val"],1,$data,$msg);
             }
            
            
            
        }
        $mem_data_pid1=$Member->get_by_id($mem_data_pid["p_id"]);
         $mem_data_group_pid1=$MemberGroup->get_by_id($mem_data_pid1["membergroup_id"]);
//二级
        if($mem_data_pid1&&$mem_data_group_pid1['id']&&$mem_data_group_pid1['id']>=$mem_data_group['id']&&$mem_data_group_pid1['id']>=$mem_data_pid['id']){
            $data=[];
            $data["member_id"]=$mem_data_pid1["id"];
            $data["type"]=2;
            $data["op_id"]=$mem_data["id"];
             //$data["val"]=$mem_data_group_pid["money"]-$mem_data_group_pid1["money"];
             if($order_data["amount"]){
                  $data["type_ordersn"]=$order_data["sn"];
                  $data["val"]= $order_data["amount"]*$mem_data_group_pid1["level_2_rate"]/100;
                  $msg="您的二级会员手机号". substr($mem_data["phone"], 0,6)."*****,升级成功，";
                  $Member->update_money($data["member_id"],$data["val"],1,$data,$msg);

             }
            
            
            
        }
        $mem_data_pid2=$Member->get_by_id($mem_data_pid1["p_id"]);
         $mem_data_group_pid2=$MemberGroup->get_by_id($mem_data_pid2["membergroup_id"]);
//三级
        if($mem_data_pid2&&$mem_data_group_pid2['id']&&$mem_data_group_pid1['id']&&$mem_data_group_pid2['id']>=$mem_data_group['id']&&$mem_data_group_pid2['id']>=$mem_data_group_pid['id']&&$mem_data_group_pid2['id']>=$mem_data_group_pid1['id']){
            $data=[];
            $data["member_id"]=$mem_data_pid2["id"];
            $data["type"]=2;
            $data["op_id"]=$mem_data["id"];
             //$data["val"]=$mem_data_group_pid1["money"]-$mem_data_group_pid2["money"];
             if($order_data["amount"]){
                  $data["type_ordersn"]=$order_data["sn"];
                  $data["val"]= $order_data["amount"]*$mem_data_group_pid2["level_3_rate"]/100;
                  $msg="您的三级会员手机号". substr($mem_data["phone"], 0,6)."*****,升级成功，";
                  $Member->update_money($data["member_id"],$data["val"],1,$data,$msg);

             }
            
            
            
        }
        return true;

    }
        //可提现金额
    public function get_last_money($mem){

        $lose=$this->sum_money_litttle($mem["id"]);
        
        return $mem["money"]-$lose;
    }

}
?>
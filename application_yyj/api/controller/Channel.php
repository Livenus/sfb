<?php
namespace app\api\controller;
use think\Config;
use think\Response;
class Channel  extends \app\api\controller\Home {
        public function _initialize(){
        $this->Channel = model("Channel");
        $this->MemberGroup = model("MemberGroup");
        $this->Member = model("Member");
        $this->get_user_id();
        }
    //获取所有渠道
    public  function get_all_channnelAc(){
        
         Config::load(APP_PATH.'/pay_type.php');
        $unionpay= get_self();
        $unionpays=[];
        foreach($unionpay as $v){
            $data=["name"=>$v["name"],"id"=>$v["id"],"status"=>$v["status"],"icon"=>$v["icon"],"icon_no"=>$v["icon_no"]];
            $unionpays[]=$data;
        }
        if(count($unionpays)>0){
             return $this->suc($unionpays);
        }else{
              return $this->err('9099','没有配置支付渠道');
        }
        
    }
    //获取渠道信息
    public  function get_pay_typeAc($type="",$money=""){
        $rule=[
            "type"=>"require|number",
            "money"=>"require|number"
        ];
        $check=$this->validate($_POST, $rule);
        if($check!==true){
            return $this->err(9000, $check);
            
        }
      if($type){
           $mem=$this->Member->get_by_id($this->member_id);
            $mem_group=$this->MemberGroup->get_by_id($mem["membergroup_id"]);
            $data=$this->Channel->get_by_type($type,$money);
            foreach($data as $k=>$v){
                $key=$v["rate"].'_money';
                $data[$k]["rate"]=$mem_group[$v["rate"]];
                 $data[$k]["rate_money"]=$mem_group[$key];
                
            }
            if($data){
                return $this->suc($data); 
            }
            
        }
 return $this->err('900','没有数据');
        
        
    }
    //费率
    public  function get_group_channelAc(){
        $mem=$this->Member->get_by_id($this->member_id);
        $mem_group=$this->MemberGroup->get_by_id($mem["membergroup_id"]);
        $map["id"]=["<",14];
        $map["stat"]=1;
        $group=$this->MemberGroup->select_all($map);
        foreach ($group as $k=>$v){
            $rate=array();
            $channel1=$this->Channel->get_by_rate("rate_1");
            if($channel1){
                $channel1["rate"]=$mem_group[$channel1['rate']];
                $rate[]=$channel1;
            }
            $channel2=$this->Channel->get_by_rate("rate_2");
            if($channel2){
                $channel2["rate"]=$mem_group[$channel2['rate']];
                $rate[]=$channel2;
            }
            $channel3=$this->Channel->get_by_rate("rate_3");
            if($channel3){
                $channel3["rate"]=$mem_group[$channel3['rate']];
                $rate[]=$channel3;
            }
            $group[$k]["rate_list"]=$rate;
        }
        $table_info=$this->MemberGroup->table_info();
        $table_info2=$this->Channel->table_info();
        $reponse["channel_table_info"]=$table_info2;
        $reponse["group_table_info"]=$table_info;
        $reponse["list"]=$group;
        return $this->suc($reponse);
    }
}

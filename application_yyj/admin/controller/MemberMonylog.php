<?php 
namespace app\admin\controller;
use think\Config;
class MemberMonylog extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->MemberMonyLog = model('MemberMonylog');
        $this->Member=model("Member");
        $this->Setting=model("Setting");
        $this->Order=model("Order");
        $this->Channel=model("Channel");
        $this->MemberGroup=model("MemberGroup");
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
        Config::load(APP_PATH.'/pay_type.php');
    }
    public function indexAc(){
        $id = $this->MemberMonyLog->check('channel_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $input=input("get.");
        $this->assign("input",$input);
        $sum=$this->MemberMonyLog->sum_money_back_all();
        $this->assign("sum",$sum);
        return $this->fetch();
    }
    public function cashAc(){
        $id = $this->MemberMonyLog->check('channel_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $input=input("get.");
        $this->assign("input",$input);
        return $this->fetch();
    }
    public function allAc(){
        $id = $this->MemberMonyLog->check('channel_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $input=input("get.");
        $unionpay= get_self();
        if($this->request->isAjax()){
            $validate = $this->validate($data,'MemberMonyLog');
            if($validate !== true){
                return message(0, $validate);
            }
            $status=$this->MemberMonyLog->addItem_id($data);
            
            $pay=$this->MemberMonyLog->get_pay_way(100,$status["data"]);
            if($pay){
                $this->MemberMonyLog->editById(["rate"=>$pay["rate_type"]],$status["data"]);
            }
            return $status;
        }
        $this->assign("input",$input);
        $this->assign("unionpay",$unionpay);
        return $this->fetch();
    }
    public function addAc(){
        $id = $this->MemberMonyLog->check('channel_add');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
         $data=$this->request->param();

        $unionpay= get_self();
        if($this->request->isAjax()){
            $validate = $this->validate($data,'MemberMonyLog');
            if($validate !== true){
                return message(0, $validate);
            }
            $status=$this->MemberMonyLog->addItem_id($data);
            
            $pay=$this->MemberMonyLog->get_pay_way(100,$status["data"]);
            if($pay){
                $this->MemberMonyLog->editById(["rate"=>$pay["rate_type"]],$status["data"]);
            }
            return $status;
        }
        $this->assign("unionpay",$unionpay);
        return $this->fetch();
    }
    public  function listajaxAc(){
        $setting=model("Setting");
        $set=$setting->select_all();
        $map=[];
        $a_name=input("get.a_name");
        $res=input("get.");
        if($res["order"]){
            $order="id ".$res["order"];
        }
        if($res["limit"]){
           $limit=$res["offset"].",".$res["limit"];
        }
        if($res["a_name"]){
            $map["type_ordersn"]=["like","%{$res['a_name']}%"];
        }
        if($res["type"]==1||$res["type"]==2||$res["type"]==3){
            $map["type"]=$res["type"];
        }
        if($res["type"]==13){
            $map["type"]=["in","1,2,6,7,8,9"];
        }
        if($res["type"]==15){
            $map["type"]=["in","1,2,6,7,8,9"];
        }
        if($res["start"]&&$res["end"]){
            $start= strtotime($res["start"]);
            $end= strtotime($res["end"]);
            $map["add_time"]=["between","$start,$end"];
        }
        if($res["val"]){
             $map["val"]=$res["val"];
        }
        if($res["from_user"]){
            $user=$this->Member->get_by_name($res["from_user"]);
            if($user){
              $map["op_id"]=$user["id"];  
            }else{
                 $map["op_id"]=0;
             }
            
        }
        if($res["get_user"]){
            $user=$this->Member->get_by_name($res["get_user"]);
            if($user){
              $map["member_id"]=$user["id"];  
            }else{
                $map["member_id"]=0;
            }
            
        }
        //支付渠道
        if($res["pay_type"]){
            $channels=$this->Channel->get_by_type_paytype($res["pay_type"],$res["pay_type_way"]);
            if($channels){
                $mapo["channel_id"]=["in",$channels];
                $orders=$this->Order->select_all_id($mapo);
                if($orders){
                    $map["type_ordersn"]=["in",$orders];
                }
            }
            if($map["type_ordersn"]){
                
            }else{
                $map["type_ordersn"]=0;
            }
        }
        if($res["uid"]&& is_numeric($res["uid"])){
            $map["member_id"]=$res["uid"];
            
        }
        $order="id desc";
        //查询返佣
        if($res["self_type"]==13&&$res["type"]==0){
            $map["type"]=["in","1,2,6,7,8,9"];
            
        }
        $sum=$this->MemberMonyLog->sum_money_back_all($map);
        $this->assign("sum",$sum);
       $lists = $this->MemberMonyLog->select_all($map,$order,$limit);
      $rate=$this->Setting->get_by_key("rate");
       foreach ($lists  as $k=>$v){
              $mem=$this->Member->get_by_id($v["member_id"]);
              $mem_group=$this->MemberGroup->get_by_id($mem["membergroup_id"]);
              $lists[$k]["member"]=$mem;
              $lists[$k]["mem_group"]=$mem_group;
              $lists[$k]["rate"]=$rate["value"];
              $lose_moeny=$v["val"]*$rate["value"]/100;
              $lose_moeny=$lose_moeny<=$set["rate_min_lose"]?$set["rate_min_lose"]:$lose_moeny;
              $lose_moeny=$lose_moeny>=$set["rate_max_lose"]?$set["rate_max_lose"]:$lose_moeny;
              $lists[$k]["lose_moeny"]=$lose_moeny;
              $lists[$k]["real_moeny"]=$v["val"]-$lose_moeny;
               if($res["self_type"]==13){
                   $memfrom=$this->Member->get_by_id($v["op_id"]);
                   $lists[$k]["memfrom"]=$memfrom;
            
                   $order=$this->Order->get_by_sn($v["type_ordersn"]);
                   $lists[$k]["outer_sn"]=$order["outer_sn"];
                   if($v["type"]==1){
                       $lists[$k]["msg"]="会员{$memfrom['uname']}刷卡{$order['amount']}元，返佣{$v['val']}"; 
                       
                   }elseif($v["type"]==6){
                       $lists[$k]["msg"]="会员{$memfrom['uname']}刷卡{$order['amount']}元，返佣{$v['val']}";  
                       
                   }elseif($v["type"]==7){
                       $lists[$k]["msg"]="会员{$memfrom['uname']}刷卡{$order['amount']}元，返佣{$v['val']}";  
                       
                   }elseif($v["type"]==8){
                       $lists[$k]["msg"]="会员{$memfrom['uname']}刷卡{$order['amount']}元，返佣{$v['val']}";  
                       
                   }elseif($v["type"]==9){
                       $lists[$k]["msg"]="会员{$memfrom['uname']}刷卡{$order['amount']}元，返佣{$v['val']}";  
                       
                   }else{
                     $lists[$k]["msg"]="会员{$memfrom['uname']}升级消费{$order['amount']}元，返佣{$v['val']}";    
                   }
               }elseif($res["self_type"]==15){
                   //流水
                   $memfrom=$this->Member->get_by_id($v["op_id"]);
                   $lists[$k]["memfrom"]=$memfrom;
            
                   $order=$this->Order->get_by_sn($v["type_ordersn"]);
                                $channel=$this->Channel->get_pay_way_parent(100,$order["channel_id"]);
                                $lists[$k]["channel"]=$channel;
                                $lists[$k]["outer_sn"]=$order["outer_sn"];
                                $channeld=$this->Channel->get_by_id($order["channel_id"]);
                                $lists[$k]["channeld"]=$channeld;
                   }
       }
        echo json_encode(array("sum"=>$sum,'rows'=>$lists,"total"=>$this->MemberMonyLog->get_count($map)));
        
    }
    public function editAc($id=0){
        $check = $this->MemberMonyLog->check('channel_edit');
        if($check){
            $this->admin_priv($check);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $data=$this->MemberMonyLog->get_by_id($id);
        $this->assign("info",$data);
          return $this->fetch("add");
    }
    public  function update_statusAc($id="",$status){
        $input=input("post.");
        $rule=[
            "id"=>"require|number"
        ];
        $check=$this->validate($input, $validate);
        if($check!==true){
              return message(0,$check);
        }
       $money=$this->MemberMonyLog->get_by_id($id);
       if($money["status"]!=0){
           return message(0,"状态不可更改，请勿重复操作");
       }
       //批量更新
        if(is_array($input["id"])){

            $this->MemberMonyLog->editByids(array("status"=>$status),$input["id"]);
                        return message(1,"更新成功");
        }
        $res=$this->MemberMonyLog->editById(array("status"=>$status),$id);
        if($res["stat"]==1){
            $money=$this->MemberMonyLog->get_by_id($id);
            $mem=$this->Member->get_by_id($money["member_id"]);
            $moneys=$mem["money"]-$money["val"];
            if($status==1){
            $this->Member->editById(array("money"=>$moneys),$money["member_id"]);    
            //统计数据
            $Sumsite=model("Sumsite");
            $Sumsite->update_less("total_money",$money["val"]);
            }
            $msg["type"]=10;
            $msg["to"]=$money["member_id"];
            $msg["content"]=$status;
            model("Msg")->addItem($msg);
            return message(1,"更新成功");
        }
        return message(0,"更新失败");
        
        
    }
    public function delAc(){
        $id = $this->MemberMonyLog->check('channel_del');
        if($id){
            $ret = $this->admin_priv($id,$this->request->isAjax());
            if(!$ret) return message(0,'你没有操作权限');
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $ids = input('post.')['id'];
       if(is_numeric($ids)){
           $status=$this->MemberMonyLog->del_by_id($ids);
       }else{
            $status=$this->MemberMonyLog->del_all($ids);
       }
       
       
       if($status){
           return message(1, "删除成功");
       }else{
           return message(0, "删除失败");       
       }
    }
}
?>
<?php 
namespace app\admin\controller;
use think\Config;
class Order extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->Order = \think\Loader::model('Order');
        $this->Member=model("Member");
        $this->Channel=model("Channel");
        $this->Bank=model("Bank");
        $this->MemberMonylog=model("member_monylog");
        $this->MemberGroup=model("MemberGroup");
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
        Config::load(APP_PATH.'/pay_type.php');
    }
    public function indexAc(){
        $id = $this->Order->check('channel_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $input=input("get.");
        $this->assign("input",$input);

        return $this->fetch();
    }
    public function fitAc(){
         $cache=model("Cache");
        $id = $this->Order->check('channel_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }

        $all_money_make=$this->Order->get_sum_all_get(1);
        $all_money_make_2=$this->Order->get_sum_all_get(2);
        $all_money=$this->Order->get_sum_money();
        $back=$this->MemberMonylog->sum_money_back_all();

        $all_money_make=$all_money_make+$all_money_make_2;
        $last=$all_money_make-$back;
        $all_money_make= round($all_money_make,2);
        $all_money= round($all_money,2);
        $last= round($last,2);
        $this->assign("all_money_make",$all_money_make);
        $this->assign("all_money",$all_money);
        $this->assign("last",$last);
        return $this->fetch();
    }
    public function addAc(){
        $id = $this->Order->check('channel_add');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
         $data=$this->request->param();

        $unionpay= get_self();
        if($this->request->isAjax()){
            $validate = $this->validate($data,'Order');
            if($validate !== true){
                return message(0, $validate);
            }
            $status=$this->Order->addItem_id($data);
            
            $pay=$this->Order->get_pay_way(100,$status["data"]);
            if($pay){
                $this->Order->editById(["rate"=>$pay["rate_type"]],$status["data"]);
            }
            return $status;
        }
        $this->assign("unionpay",$unionpay);
        return $this->fetch();
    }
    public  function listajaxAc(){
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
            $map["outer_sn"]=["like","%{$res['a_name']}%"];
        }
        if($res["type"]==1){
            $map["type"]=["in","1,6,8"];
        }
        if($res["type"]==2){
            $map["type"]=["in","2,7,9"];
        }
        if($res["start"]&&$res["end"]){
            $start= strtotime($res["start"]);
            $end= strtotime($res["end"]);
            $map["add_time"]=["between","$start,$end"];
        }
        if($res["member_name"]){
             $mapm["uname"]=$res["member_name"];
             $mems=$this->Member->select_all_id($mapm);
             if($mems){
                 $map["member_id"]=["in",$mems];
             }
        }
        if($res["phone"]){
             $mapm["phone"]=$res["phone"];
             $mems=$this->Member->select_all_id($mapm);
             if($mems){
                 $map["member_id"]=["in",$mems];
             }
        }
        if($res["uid"]&& is_numeric($res["uid"])){
            $map["member_id"]=$res["uid"];
            
        }
        //盈利
        if($res["self_type"]==13){
            $map["stat"]=1;
        }
        $all_money_make=$this->Order->get_sum_all_get(1,$map);
        $all_money_make_2=$this->Order->get_sum_all_get(2,$map);
        $all_money=$this->Order->get_sum_money($map);
        $order_ids=$this->Order->select_all_id($map);
        $map_order["type_ordersn"]=["in",$order_ids];
        $back=$this->MemberMonylog->sum_money_back_all($map_order);

        $all_money_make=$all_money_make+$all_money_make_2;
        $last=$all_money_make-$back;
        $all_money_make= round($all_money_make,2);
        $all_money= round($all_money,2);
        $last= round($last,2);
        $this->assign("all_money_make",$all_money_make);
        $this->assign("all_money",$all_money);
        $this->assign("last",$last);
       $lists = $this->Order->select_all($map,"id desc",$limit);
       foreach ($lists  as $k=>$v){
           if($v["type"]==1){
               $lists[$k]["type_name"]="刷卡";
           }else{
                $lists[$k]["type_name"]="升级消费";
           }
           $mem=$this->Member->get_by_id($v["member_id"]);
           $bank=$this->Bank->get_by_num($v["bank_num"]);
           $lists[$k]["bank_name"]=$bank["bank_name"];
           $lists[$k]["uname"]=$mem["uname"];
           $lists[$k]["phone"]=$mem["phone"];
           $lists[$k]["last_money"]=$v["amount"]-$v["amount"]*$v["rate"]/1000-$v["rate_money"];
           $lists[$k]["truename"]=$mem["truename"];
           $lists[$k]["truename"]=$mem["truename"];
           $group=$this->MemberGroup->get_by_id($mem["membergroup_id"]);
            $lists[$k]["member_group"]=$group["name"];
            $channel=$this->Channel->get_by_id($v["channel_id"]);
            $lists[$k]["channel_id"]=$channel["name"];
            //收益
            if($res["self_type"]==13&&$v["type"]==1){
                $channel=$this->Channel->get_by_id($v["channel_id"]);
                //实际支付方式
                $pay_way=$this->Channel->get_pay_way(100,$v["channel_id"]);
                //实际支付费率
                $rate=$group[$pay_way["rate_type"]];
                $lists[$k]["rated"]=$v["amount"]*$v["rate"]/1000;
                 $lists[$k]["rated_low"]=$v["amount"]*$v["low_rate"]/1000;
                 $lists[$k]["rate_real"]=$v["amount"]-$lists[$k]["last_money"]-$lists[$k]["rated_low"]-$lists[$k]["low_rate_money"];
                 $lists[$k]["rate_real"]=round($lists[$k]["rate_real"],3);
                 //订单总返佣
                 $back=$this->MemberMonylog->sum_money_back($v["sn"]);
                 $lists[$k]["back"]=$back;
                  $lists[$k]["last"]=$lists[$k]["rate_real"]-$back;
                  $lists[$k]["last"]=round($lists[$k]["last"],3);
            }elseif($res["self_type"]==13&&$v["type"]==2){
                //升级缴费
                $back=$this->MemberMonylog->sum_money_back($v["sn"]);
                $lists[$k]["rate_real"]=$v["amount"];
                  $lists[$k]["last"]=$lists[$k]["rate_real"]-$back;
            }
       }
               
        echo json_encode(array("all_money"=>$all_money,"all_money_make"=>$all_money_make,"last"=>$last,'rows'=>$lists,"total"=>$this->Order->get_count($map)));
        
    }
    public function editAc($id=0){
        $check = $this->Order->check('channel_edit');
        if($check){
            $this->admin_priv($check);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $data=$this->Order->get_by_id($id);
            $channel=$this->Channel->get_by_id($data["channel_id"]);
            $data["channel_id"]=$channel["name"];
        $data["member"]=$this->Member->get_by_id($data["member_id"]);
        $this->assign("info",$data);
          return $this->fetch("add");
    }
    public function delAc(){
        $id = $this->Order->check('channel_del');
        if($id){
            $ret = $this->admin_priv($id,$this->request->isAjax());
            if(!$ret) return message(0,'你没有操作权限');
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $ids = input('post.')['id'];
       if(is_numeric($ids)){
           $status=$this->Order->del_by_id($ids);
       }else{
            $status=$this->Order->del_all($ids);
       }
       
       
       if($status){
           return message(1, "删除成功");
       }else{
           return message(0, "删除失败");       
       }
    }
}
?>
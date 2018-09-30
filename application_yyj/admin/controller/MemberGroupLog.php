<?php 
namespace app\admin\controller;
use think\Config;
class MemberGroupLog extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->MemberGroupLog = model('MemberGroupLog');
        $this->Member = model('Member');
        $this->Admin = model('Admin');
        $this->MemberGroup = model('MemberGroup');
        $this->Order = model('Order');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();

    }
    public function indexAc(){
        $id = $this->MemberGroupLog->check('channel_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $data=$this->MemberGroupLog->select_all();
        $this->assign("data",$data);
        return $this->fetch();
    }
    public function editAc($id=0){
        $check = $this->MemberGroupLog->check('channel_edit');
        if($check){
            $this->admin_priv($check);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $data=$this->MemberGroupLog->get_by_id($id);
                  $member=$this->Member->get_by_id($data["member_id"]);
                  $data["member"]=$member;
                  $data["money"]=$data["heigh_money"]-$data["low_money"];
                  $low_group=$this->MemberGroup->get_by_id($data["low_group"]);
                   $heigh_group=$this->MemberGroup->get_by_id($data["heigh_group"]);
                   $data["low_group"]=$low_group["name"];
                    $data["heigh_group"]=$heigh_group["name"];
                   $data["order"]=$this->Order->get_by_id($data["order_id"]);
                   $data["stat"]=$data["order"]["stat"];
        $this->assign("info",$data);
          return $this->fetch();
    }
    public  function listajaxAc(){
        $map=[];
        $a_name=input("get.a_name");
        $res=input("get.");
        $order="id desc";
        if(0){
            $order="id ".$res["order"];
        }
        if($res["limit"]){
           $limit=$res["offset"].",".$res["limit"];
        }
        if($res["member"]){
            $mem=$this->Member->get_by_name($res["member"]);
            if($mem){
                $map["member_id"]=$mem["id"];
            }else{
               $map["member_id"]=0;  
            }
        }
        if($res["start"]&&$res["end"]){
            $start= strtotime($res["start"]);
            $end= strtotime($res["end"]);
            $map["add_time"]=["between","$start,$end"];
        }
        $map["status"]=1;
       $lists = $this->MemberGroupLog->select_all($map,$order,$limit);
      
       foreach ($lists  as $k=>$v){
                  $member=$this->Member->get_by_id($v["member_id"]);
                  $lists[$k]["member"]=$member;
                  $lists[$k]["money"]=$v["heigh_money"]-$v["low_money"];
                  $low_group=$this->MemberGroup->get_by_id($v["low_group"]);
                   $heigh_group=$this->MemberGroup->get_by_id($v["heigh_group"]);
                   $lists[$k]["low_group"]=$low_group["name"];
                    $lists[$k]["heigh_group"]=$heigh_group["name"];
                    if(is_numeric($v["is_admin"])&&$v["is_admin"]==1){
                       $lists[$k]["admin"]=$this->Admin->get_by_id($v["order_id"]); 
                    }
       }
        echo json_encode(array('rows'=>$lists,"total"=>$this->MemberGroupLog->get_count($map)));
        
    }
    
}
?>
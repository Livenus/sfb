<?php 
namespace app\admin\controller;
use think\Config;
class CreditPlan extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->CreditPlanItem=model("CreditPlanItem");
         $this->CreditPlan=model("CreditPlan");
         $this->Credit=model("Credit");
        $this->Member=model("Member");
    }
    public function indexAc(){
        $input=input("get.");
        $this->assign("input",$input);
        return $this->fetch();
    }
    public  function listajaxAc(){
        $map=[];
        $res=input("get.");
        if($res["order"]){
            $order="id ".$res["order"];
        }
        if($res["limit"]){
           $limit=$res["offset"].",".$res["limit"];
        }
        if($res["start"]&&$res["end"]){
            $start= strtotime($res["start"]);
            $end= strtotime($res["end"]);
            $map["add_time"]=["between","$start,$end"];
        }
        if($res["bank_card"]){
            $map["bank_card"]=["like","%{$res['bank_card']}%"];
        }
        if($res["member_name"]){
             $mapm["uname"]=$res["member_name"];
             $mems=$this->Member->select_all_id($mapm);
             if($mems){
                 $map["member_id"]=["in",$mems];
             }
        }
       $lists = $this->CreditPlan->select_all($map,"id desc",$limit);    
       foreach($lists as $k=>$v){
           $member=$this->Member->get_by_id($v['member_id']);
           $lists[$k]['uname']=$member['uname'];
           $lists[$k]['add_time']=date("Y-m-d",$v["add_time"]);
       }
        echo json_encode(array('rows'=>$lists,"total"=>$this->CreditPlan->get_count($map)));
        
    }
    public function editAc($id=0){
        $data=$this->CreditPlan->get_by_id($id);
        $data["member"]=$this->Member->get_by_id($data["member_id"]);
        $data["credit"]=$this->Credit->get_by_id($data["credit_id"]);
        $this->assign("info",$data);
          return $this->fetch("add");
    }
    public function delAc(){
        $ids = input('post.')['id'];
       if(is_numeric($ids)){
           $status=$this->CreditPlan->del_by_id($ids);
       }else{
            $status=$this->CreditPlan->del_all($ids);
       }
       
       
       if($status){
           return message(1, "删除成功");
       }else{
           return message(0, "删除失败");       
       }
    }
}
?>
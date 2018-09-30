<?php 
namespace app\admin\controller;
use think\Config;
class Task  extends \think\Controller{
    
    
    public function _initialize(){
        parent::_initialize();
        $this->task=model("task");
        $this->Member = model('Member');
        $this->Admin = model('Admin');
        $this->Order = model('Order');
        $this->MemberMonylog = model('MemberMonylog');
        $this->Sum = model('Sum');
    }
    public function indexAc(){
       Config::load(APP_PATH.'/task.php');
       $task= config("task");
      foreach($task as $k=>$v){
          $this->$v["name"]($v["frequent"]);
          
      }
        
    }
    public function autoPay($fre){
        $data["name"]=__FUNCTION__;
        $data["add_time"]=time();
        $has=$this->task->get_by_name($data["name"]);
        $isout=$has["finishtime"]+$fre<time();
        if($has&&$isout){
            
        }else if(empty($has)){
            
        }else{
            echo "nottime";
            return false;
        }
        $id= $this->task->addItem_id($data);
        //统计数据
         $now=time();
         $yer=$now-24*3600;
         $action=action("api/CreditPlan/autoPay");
         echo "finish";
         $data1["finishtime"]=time();
         $s=$this->task->editById($data1,$id);
    }
    public function autoinsteadPay($fre){
        $data["name"]=__FUNCTION__;
        $data["add_time"]=time();
        $has=$this->task->get_by_name($data["name"]);

        $isout=$has["finishtime"]+$fre<time();
        if($has&&$isout){
            
        }else if(empty($has)){
            
        }else{
            echo "nottime";
            return false;
        }
        $id= $this->task->addItem_id($data);
        //统计数据
         $now=time();
         $yer=$now-24*3600;

         action("api/CreditPlan/autoinsteadPay");
         echo "finish";
         $data1["finishtime"]=time();
         $s=$this->task->editById($data1,$id);
         exit();
    }
    //业绩分红
    public function autoBonus($fre){
        $data["name"]=__FUNCTION__;
        $data["add_time"]=time();
        $has=$this->task->get_by_name($data["name"]);
        $isout=$has["finishtime"]+$fre<time();
        if($has&&$isout){
            
        }else if(empty($has)){
            
        }else{
            echo "nottime";
            return false;
        }
        $id= $this->task->addItem_id($data);
        //统计数据
         $now=time();
         $yer=$now-24*3600;
         $action=action("api/Bonus/autoBonus");
         echo "finish";
         $data1["finishtime"]=time();
         $s=$this->task->editById($data1,$id);
    }
    public function order($fre){
        $data["name"]=__FUNCTION__;
        $data["add_time"]=time();
        $has=$this->task->get_by_name($data["name"]);
        $isout=$has["finishtime"]+$fre<time();
        if($has&&$isout){
            
        }else if(empty($has)){
            
        }else{
            echo "nottime";
            return false;
        }
        $id= $this->task->addItem_id($data);
        //统计数据
         $now=time();
         $yer=$now-24*3600;
         $this->set_sum(date("Y-m-d",$yer));
         echo "finish";
         $data1["finishtime"]=time();
         $s=$this->task->editById($data1,$id);
    }
    //一天的统计数据
    public function set_sum($day=""){
        $data=[];
        if($day){
            
        }else{
            $day=date("Y-m-d");
        }
        $data["date_add"]=$day;
        $data["add_time"]=strtotime($day);
        $count=$this->Sum->get_count($data);
        if($count>0){
            
             return;
             
        }
        $data["user_money"]=$this->Member->sum_money();
        $night=strtotime($day);
        $day=strtotime($day)+3600*24;
        $map["add_time"]=["between","$night,$day"];
        $map["type"]=1;
        $data["user_cash"]=$this->Order->get_sum_money_group($map);
        $map["type"]=2;
        $data["user_cash2"]=$this->Order->get_sum_money_group($map);
        unset($map["type"]);
        $data["user_back"]=$this->MemberMonylog->sum_money_back_all_group($map);
        $data["user_more"]=$this->Member->get_count_day_group($map);
        unset($map["add_time"]);
        $map["membergroup_id"]=10;
        $data["user_more_g10"]=$this->Member->get_count_day_ggroup($map);
        $map["membergroup_id"]=11;
        $data["user_more_g11"]=$this->Member->get_count_day_ggroup($map);
        $map["membergroup_id"]=12;
        $data["user_more_g12"]=$this->Member->get_count_day_ggroup($map);
        $map["membergroup_id"]=13;
        $data["user_more_g13"]=$this->Member->get_count_day_ggroup($map);
        $map["membergroup_id"]=14;
        $data["user_more_g14"]=$this->Member->get_count_day_ggroup($map);
        $map["membergroup_id"]=15;
        $data["user_more_g15"]=$this->Member->get_count_day_ggroup($map);
        $map["membergroup_id"]=16;
        $data["user_more_g16"]=$this->Member->get_count_day_ggroup($map);
        $map["membergroup_id"]=17;
        $data["user_more_g17"]=$this->Member->get_count_day_ggroup($map);
        $map["membergroup_id"]=18;
        $data["user_more_g18"]=$this->Member->get_count_day_ggroup($map);
        if($data){
            $this->Sum->addItem_id($data);
            
        }
        
    }
    public  function testAc(){
        $now=time();
        $yer=$now-24*3600;
        for($i=0;$i<29;$i++){
            $t=$now-(30-$i)*24*3600;
            $day=date("Y-m-d",$t);
            $this->set_sum($day);
        }
        
        
        
    }
}
?>
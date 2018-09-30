<?php
namespace app\api\controller;
use think\Config;
use think\Response;
use think\Validate;
class Area  extends \app\api\controller\Home {
        public function _initialize(){
        $this->Area = model("Area");
        $this->Member_login_token = model("Member_login_token");
        $this->get_user_id();
        }
    //由上级ID获取地区列表
    public  function getlistbypidAc($pid=""){
        $rule=[
            "pid"=>"require|number"
            
        ];
        $data['pid']=$pid;
    $map["a_pid"]=$pid;
$validate = new Validate($rule);
$result   = $validate->check($data);
if(!$result){
    $errmsg=$validate->getError();
    return $this->err("9909", $errmsg);
}
$reponse_data= $this->Area->select_all($map);
if($reponse_data){
  return $this->suc($reponse_data);    
}else{
     return $this->err("900", "没有数据");
}
    }
   //下级获取地区
   public  function getbysonidAc($sonid=""){
        $rule=[
            "sonid"=>"require|number"
            
        ];
        $data['sonid']=$sonid;
    $map["a_id"]=$sonid;
$validate = new Validate($rule);
$result   = $validate->check($data);
if(!$result){
    $errmsg=$validate->getError();
    return $this->err("9909", $errmsg);
}
//上一级
$pid=$this->Area->get_pid($sonid);
if($pid1!==false){
    $reponse_data["level3_id"]=$pid;
    $reponse_data["level3_list"]=$this->Area->select_all(array("a_pid"=>$pid));
}else{
        $reponse_data["level3_id"]="";
    $reponse_data["level3_list"]=[];
}
$pid1=$this->Area->get_pid($pid);

if($pid1!==false){
    $reponse_data["level2_id"]=$pid1;
    $reponse_data["level2_list"]=$this->Area->select_all(array("a_pid"=>$pid1));
}else{
        $reponse_data["level2_id"]="";
        $reponse_data["level2_list"]=[];
}
$pid2=$this->Area->get_pid($pid1);

if($pid2!==false){
    $reponse_data["level1_id"]=$pid2;
    $reponse_data["level1_list"]=$this->Area->select_all(array("a_pid"=>$pid2));
}else{
        $reponse_data["level1_id"]="";
        $reponse_data["level1_list"]=[];
}
if($reponse_data){
  return $this->suc($reponse_data);    
}else{
     return $this->err("900", "没有数据");
}
   
       
       
   }
}

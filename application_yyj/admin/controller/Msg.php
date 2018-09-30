<?php 
namespace app\admin\controller;
class Msg extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->Msg =model('Msg');
        $this->Member =model('Member');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }
    public function indexAc(){
        return $this->fetch();
    }
    public  function listajaxAc(){
        $map=[];
        $res=input("get.");
        if($res["limit"]){
           $limit=$res["offset"].",".$res["limit"];
        }
        if($res["start"]&&$res["end"]){
            $start= strtotime($res["start"]);
            $end= strtotime($res["end"]);
            $map["add_time"]=["between","$start,$end"];
        }
        if($res["from_user"]){
            $name=$this->Member->get_by_name($res["from_user"]);
            if($name){
                $map["to"]=$name["id"];
            }else{
                $map["to"]=0;
            }
            }
        $lists=$this->Msg->select_all($map,"id desc",$limit);
        foreach($lists as $k=>$v){
            $lists[$k]["member"]=$this->Member->get_by_id($v["to"]);
        }
        echo json_encode(array('rows'=>$lists,"total"=>$this->Msg->get_count($map)));
        
    }

}
?>
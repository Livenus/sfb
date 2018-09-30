<?php 
namespace app\admin\controller;
class BackCheckLog extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->Msg =model('Msg');
        $this->BackCheckLog =model('BackCheckLog');
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
            $map["response_time"]=["between","$start,$end"];
        }
        $lists=$this->BackCheckLog->select_all($map,"id desc",$limit);
        echo json_encode(array('rows'=>$lists,"total"=>$this->BackCheckLog->get_count($map)));
        
    }

}
?>
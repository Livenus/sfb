<?php
/**
 * 广告接口
 */

namespace app\api\controller;

class BackCheckLog extends \app\api\controller\Home {
    public function _initialize(){
        parent::_initialize();
        $this->Msg =model('Msg');
        $this->BackCheckLog =model('BackCheckLog');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();

    }
    
    /**
     * 根据广告位ID获取广告列表
     * @return unknown
     */
    public function listAc(){
        $map=[];
        $res=input("post.");
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
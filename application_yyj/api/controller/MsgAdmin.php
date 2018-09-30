<?php
/**
 * 广告接口
 */

namespace app\api\controller;

class MsgAdmin extends \app\api\controller\Home {
    public function _initialize(){
        $this->MsgAdmin = model('MsgAdmin');

    }
    public  function get_hashAc(){
        $this->get_user_id();
        $data=$this->MsgAdmin->get_by_hash();
        if($data){
          return $this->suc($data);   
        }
       
         return $this->err(9000,"没有消息");
    }
    public  function detailAc(){
        $this->get_user_id();
        $data=$this->MsgAdmin->get_by_map();

        if($data){
        $data["table_info"]=$this->MsgAdmin->table_info();
          return $this->suc($data);   
        }
       
         return $this->err(9000,"没有消息");
        
    }
}
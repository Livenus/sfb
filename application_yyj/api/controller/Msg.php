<?php
namespace app\api\controller;
use think\Config;
use think\Response;
class Msg  extends \app\api\controller\Home {
        public function _initialize(){
        $this->Msg = model("Msg");
        $this->get_user_id();
        }
        public function indexAc($is_read=0){
            $map["to"]=$this->member_id;
            $map["is_read"]=$is_read;
            $data["data"]=$this->Msg->select_by_type($map);
            $data["table_info"]=$this->Msg->table_info();
            return $this->suc($data);
        }
        //列表
        public function get_listAc($status="",$type="",$order="",$limit=""){
            $map["to"]=$this->member_id;
            if($type){
            $map["type"]=$type;   
            }
            if(is_numeric($status)){
            $map["is_read"]=$status;   
            }
            $data["list"]=$this->Msg->select_all($map,$order,$limit);
            $data["count"]=$this->Msg->get_count($map);
            $data["table_info"]=$this->Msg->table_info();
            return $this->suc($data);
        }
        //详情
        public function get_by_idAc($id=""){
            $rule=[
                "id"=>"require|number"
            ];
            $check=$this->validate($_POST, $rule);
            if($check!==true){
                return $this->err(9000, $check);
            }
            $data=$this->Msg->get_by_id($this->member_id,$id);
            if($data){
                            return $this->suc($data);
            }
                return $this->err(900, "没有数据");
        }
        //标记已读
        public function is_readAc($id=""){
            $rule=[
                "id"=>"require"
            ];
            $check=$this->validate($_POST, $rule);
            if($check!==true){
                return $this->err(9000, $check);
            }
           $ids= json_decode($id,true);
           $map["id"]=["in",$ids];
           $map["to"]=$this->member_id;
            $result=$this->Msg->editByIds($map);
             if($result){
                 return $this->suc("标记成功");
             }
            return $this->err(9000, "没有更改任何数据");
        }
        public function get_not_readAc($status=0){
            $map["is_read"]=$status;
            $map["to"]=$this->member_id;
            $count=$this->Msg->get_count($map);
            return $this->suc($count);
        }
}

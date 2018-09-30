<?php
namespace app\common\model;
class MemberLoginToken extends \app\common\model\Home{
    protected $auto = [];
    protected $insert = ['member_name'];  
    protected $update = [];  
   //token获取用户 
 public  function get_by_token($token){
        $map["token"]=$token;
        $map["exprired_time"]=[">", time()];
        $data=$this->where($map)->field(["id","member_id","exprired_time"])->find();
        if($data){
            $ids=$data->toArray();
             return $ids["member_id"];
        }else{
            return false;
        }
    }
   public  function get_by_token_info($token){
        $map["token"]=$token;
        $map["exprired_time"]=[">", time()];
        $data=$this->where($map)->field(["id","member_id","exprired_time"])->find();
        if($data){
            $ids=$data->toArray();
             return $ids;
        }else{
            return false;
        }
    }
   public  function get_by_token_info_t($token){
        $map["token"]=$token;
        $data=$this->where($map)->field(["id","member_id","exprired_time"])->find();
        if($data){
            $ids=$data->toArray();
             return $ids;
        }else{
            return false;
        }
    }
 public  function get_by_old_token($token){
        $map["old_token"]=$token;
        $data=$this->where($map)->field(true)->find();
        if($data){
            $ids=$data->toArray();
             return $ids;
        }else{
            return false;
        }
    }
    
}
?>
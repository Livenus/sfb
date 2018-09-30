<?php
namespace app\common\model;
use think\Config;
class Bank extends \app\common\model\Home{
    protected $auto = [];
    protected $insert = ['add_time'];  
    protected $update = [];  
        
    protected function setAddtimeAttr()
    {
        return time();
    }  
    public function select_all($map=[],$order="id desc",$limit=""){
        $map['is_del']=0;
        $data=$this->where($map)->field(true)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
    public function get_count(){
        
        return $this->count();
    }
    public  function get_by_id($member_id,$id){
        $map['is_del']=0;
        $map['member_id']=$member_id;
        $map['id']=$id;
        $data=$this->where($map)->field(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public  function get_by_num($member_id,$bank_num){
        $map['is_del']=0;
        $map['member_id']=$member_id;
        $map['bank_num']=$bank_num;
        $data=$this->where($map)->field(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public  function del_by_id($member_id,$id){
        $map['member_id']=$member_id;
        $map['id']=$id;
        return $this->where($map)->delete();
    }
    public  function del_all($ids){
        
        return $this->where("id","in",$ids)->delete();
    }

}
?>
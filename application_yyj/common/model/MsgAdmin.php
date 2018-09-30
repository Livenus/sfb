<?php
namespace app\common\model;
use think\Config;
class MsgAdmin extends \app\common\model\Home{
    protected $auto = ["add_time","update_time"];
    protected $insert = ['add_time','hash'];  
    protected $update = ["update_time"];  
        
    protected function setAddtimeAttr()
    {
        return time();
    } 
    protected function getStartAttr($value)
    {
        return date("Y-m-d H:i:s",$value);
    }
    protected function getEndAttr($value)
    {
        return date("Y-m-d H:i:s",$value);
    } 
    public function select_all($map=[],$order="id desc",$limit=""){
        $map['is_del']=0;
        $data=$this->where($map)->field(true)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
    public function get_count(){
        
        return $this->count();
    }
    public  function get_by_map($map=array()){
        $data=$this->where($map)->field(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public function table_info(){
        $sql="SELECT a.COLUMN_NAME as name, a.COLUMN_COMMENT as comment
FROM information_schema.COLUMNS a 
WHERE a.TABLE_NAME = 'yys_msg_admin'";
        $data=$this->query($sql);
        $result=array();
        foreach($data as $v){
            $result[$v['name']]=$v["comment"];
        }
        return $result;
    } 
    public  function get_by_hash($map=array()){
        $now=time();
        $map['stat']=1;
        $map['start']=['elt',$now];
        $map['end']=['egt',$now];
        $data=$this->where($map)->value("hash");
        if($data){
             return $data;
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
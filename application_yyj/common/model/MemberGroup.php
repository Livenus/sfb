<?php
namespace app\common\model;
class MemberGroup extends \app\common\model\Home{

    public function table_info(){
        $sql="SELECT a.COLUMN_NAME as name, a.COLUMN_COMMENT as comment
FROM information_schema.COLUMNS a 
WHERE a.TABLE_NAME = 'yys_member_group'";
        $data=$this->query($sql);
        $result=array();
        foreach($data as $v){
            $result[$v['name']]=$v["comment"];
        }
        return $result;
    }    
    public  function get_by_id($id){
        
        $data=$this->where(array("id"=>$id))->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    //获取上级
    public  function get_by_id_up($id){
        $map["id"]=[">",$id];
        $data=$this->where($map)->order("id asc")->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public function select_all($map=[],$order="id asc",$limit=""){
        $data=$this->where($map)->field(true)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
}
?>
<?php
namespace app\common\model;
class Imgfile extends \app\common\model\Home{
    protected $auto = [];
    protected $insert = ['add_time'];  
    protected $update = [];  
        
    protected function setAddTimeAttr()
    {
        return time();
    }
    public function table_info(){
        $sql="SELECT a.COLUMN_NAME as name, a.COLUMN_COMMENT as comment
FROM information_schema.COLUMNS a 
WHERE a.TABLE_NAME = 'yys_imgfile'";
        $data=$this->query($sql);
        $result=array();
        foreach($data as $v){
            $result[$v['name']]=$v["comment"];
        }
        return $result;
    } 
    public  function get_by_id($id,$type){
        $map['id']=["in",$id];
         $map['type']=$type;
        $data=$this->where($map)->field(['path'])->find();
        if($data){
            $row=$data->toArray();
             return $row["path"];
        }else{
            return false;
        }
       
    }
    public  function del_by_type($member_id,$ids){
        $map["member_id"]=$member_id;
        $map["type"]=["in","1,2,3,4"];
        $map['id']=["not in",$ids];
        return $this->where($map)->delete();
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(true)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
}
?>
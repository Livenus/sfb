<?php 
namespace app\admin\model;
class Task extends \app\admin\model\Index{
    protected $auto = [];
    protected $insert = ['add_time'];  
    protected $update = [];  
        
    protected function setAddtimeAttr()
    {
        return time();
    } 
    protected function setFinishtimeAttr()
    {
        return time();
    } 
    public function addItem_id($data){
        db("task")->insert($data);
        return db("task")->getLastInsID();
    }
    public function editById($data,$id){
        
        return db("task")->where(["id"=>$id])->update($data);
    }
    public function get_by_name($name){
        $map["name"]=$name;
        $last=$this->where($map)->order("id desc")->find();
        if($last){
            return $last;
        }else{
            return false;
        }
    }
}
?>
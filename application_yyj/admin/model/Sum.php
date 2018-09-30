<?php 
namespace app\admin\model;
class Sum extends \app\admin\model\Index{
    protected $pk = 'id';
    protected $auto = [];
        

    public function addItem_id($data){
        $this->isUpdate(false)->data($data)->save();
        return $this->id;
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    public function select_all($map,$order="id asc",$limit=""){
        $data=$this->where($map)->order($order)->field(true)->limit($limit)->select();
        if(is_array($data)){
            return collection($data)->toArray();
        }else{
            return false;
        }
        
    }
}
?>
<?php 
namespace app\common\model;
class Sumsite extends \app\common\model\Home{
        

    public function update_num($key,$value){
        $res=$this->where(array("id"=>1))->setInc($key,$value);
        return $res;
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
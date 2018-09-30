<?php
namespace app\common\model;
use think\Config;
class Debug extends \app\common\model\Home{
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(true)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
    
    protected function getRequestAttr($value)
    {
        return date("Y-m-d H:i:s",$value);
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    public function get_last($map){
        
        return $this->where($map)->order("id desc")->find();
    }
     public function del($map){
        
        return $this->where($map)->delete();
    }
}
?>
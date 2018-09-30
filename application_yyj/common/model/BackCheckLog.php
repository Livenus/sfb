<?php
namespace app\common\model;
use think\Config;
class BackCheckLog extends \app\common\model\Home{
        
    protected function getResponseTimeAttr($value)
    {
        return date("Y-m-d H:i:s",$value);
    }  
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(true)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    public function select_by_type($map){
        $data=$this->where($map)->field(['type','count(id) as num'])->group("type")->select();
        return collection($data)->toArray();
    }
   

}
?>
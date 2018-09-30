<?php
namespace app\common\model;
use think\Config;
class Area extends \app\common\model\Home{

    public function select_all($map=[],$order="a_sort desc",$limit=""){
        $data=$this->where($map)->field(['a_id','a_name'])->order($order)->limit($limit)->select();
        if($data){
            return collection($data)->toArray();
        }else{
            return false;
        }
        
    }
    public  function get_pid($a_id){
        $data=$this->where(array("a_id"=>$a_id))->field(['a_pid'])->find();
        if($data){
            $data_row=$data->toArray();
            return $data_row['a_pid'];
        }else{
            return false;
        }
    }
    public  function get_by_name($id){
        
        $data=$this->where(array("a_id"=>$id))->find();
        if($data){
            $row=$data->toArray();
             return $row;
        }else{
            return false;
        }
       
    }
        public  function get_city_codes($id){
        
        $data=$this->where(array("areacode"=>$id))->find();
        if($data){
            $row=$data->toArray();
             return $row;
        }else{
            return false;
        }
       
    }
        public  function get_city_code($id){
        
        $data=$this->where(array("a_id"=>$id))->find();
        if($data){
            $row=$data->toArray();
             return $row["areacode"];
        }else{
            return false;
        }
       
    }
    public  function format_city($pro,$city,$county){

        
    }
    
}
?>
<?php
namespace app\common\model;
use think\Db;
class Cache extends \app\common\model\Home{
    protected $auto = [];
    protected $insert = ['add_time'];  
    protected $update = [];  
    protected function setAddtimeAttr()
    {
        return time();
    } 
    public function editBykey($data, $key,$value){
        try{
           
            Db::table('yys_cache')->where($key,$value)->update($data);
        }catch( \Exception $e){
            return $this->err($e->getMessage());
        }
        
        return $this->suc('修改成功');
        
    } 
    public function addItem($key,$value,$exptime) {
        $data["key"]=$key;
        $data["value"]=$value;
        $data["exptime"]=$exptime+time();
        if($this->get_by_key($data["key"])){
            return $this->editBykey($data,"key",$data["key"]);
        }
        return parent::addItem($data);
    }

    public function select_all(){
        
        $data=$this->select();
        $data_key=[];
        foreach($data as $k=>$v){
            $data_key[$v['key']]=$v["value"];
            
        }
        return $data_key;
    }
    public function get_by_key($key){
        
        $data=$this->where(array("key"=>$key))->find();
        if($data){
           return $data->toArray(); 
        } else {
            return false;
      }
        
    }
    //获取
    public function get_by_cache($key){
        $map["key"]=$key;
        $map["exptime"]=[">",time()];
        $data=$this->where($map)->find();
        if($data){
           return $data->toArray(); 
        } else {
            return false;
      }
        
    }
    
}
?>
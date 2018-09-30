<?php
namespace app\common\model;
class Setting extends \app\common\model\Home{
      
    public function editBykey($data, $key,$value){
        try{
            db("setting")->where(["key"=>$key])->update(["value"=>$value]);
        }catch( \Exception $e){
            return $this->err($e->getMessage());
        }
        
        return $this->suc('修改成功');
        
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
    
}
?>
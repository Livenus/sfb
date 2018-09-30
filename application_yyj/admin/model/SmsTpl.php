<?php
namespace app\admin\model;
class SmsTpl extends \app\admin\model\Index{
    
    
    
    
    //
    public  function get_by_code($code){
        
        $data=$this->where(array("code"=>$code))->field(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
}

?>

<?php
namespace app\api\model;
class SmsTpl extends \app\api\model\Home{
    
    
    
    
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

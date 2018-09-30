<?php
namespace app\common\model;
class CreditSet extends \app\common\model\Home{
      
    public  function get_by_id(){
        $data=$this->find();
        if($data){
            return $data->toArray();
        }
        return null;
    }
    public function get_count(){
        return $this->count();
    }
}
?>
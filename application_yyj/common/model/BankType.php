<?php
namespace app\common\model;
class BankType extends \app\common\model\Home{
  
      
     
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(['bank_name'])->group("bank_name")->select();
        return collection($data)->toArray();
    }
     
    public function get_bankno($bank_name){
        $data=$this->where(array("bank_name"=>$bank_name))->value("bank_no");
        return $data;
    }
}
?>
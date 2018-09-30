<?php
namespace app\api\controller;
use think\Config;
use think\Response;
use think\Validate;
class Product  extends \app\api\controller\Home {
        public function _initialize(){
        $this->Product = model("Product");
        $this->get_user_id();
        }
    //
    public  function indexAc(){
        $data=$this->Product->select_all(["stat"=>1]);
        foreach($data as $k=>$v){
            $data[$k]["content"]=html_entity_decode($data[$k]["content"]);
        }
        return $this->suc($data);

    }
  
   
       
       
   
}

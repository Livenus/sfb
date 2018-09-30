<?php
namespace app\api\controller;
use think\Config;
use think\Response;
class Shop  extends \app\api\controller\Home {
        public function _initialize(){
        $this->Shop = model("Shop");
        $this->Member = model("Member");
        $this->Member_login_token = model("Member_login_token");
        $this->get_user_id();
        }
        public function add_shopAc($name="",$short_name="",$address="",$company="",$cat="",$bus="",$banner="",$house="",$money="",$idcard=""){
            $input=input("post.");
            $input["member_id"]=$this->member_id;
            $check=$this->validate($input, "Shop");
            if($check!==true){
                return $this->err(9000, $check);
            }
            unset($input["__token_"]);
            $map["member_id"]=$this->member_id;
            $shop=$this->Shop->get_by_id($map);
            if($shop){
                
                            $status=$this->Shop->update_by_member_id($input,$this->member_id);
            }else{
                          $res=$this->Shop->addItem_id($input);  
            }

            if($res["stat"]==1||$status!==false){
                $this->Member->editByid(array("is_rz_3"=>2),$this->member_id);
                if($shop){
                    $res["data"]=$shop["id"];
                }
                return $this->suc($res["data"]);
            }
            return $this->err(9001, "添加失败");
        }
}

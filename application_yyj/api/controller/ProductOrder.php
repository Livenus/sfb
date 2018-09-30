<?php
namespace app\api\controller;
use think\Config;
use think\Response;
use think\Validate;
use think\Db;
class ProductOrder  extends \app\api\controller\Home {
        public function _initialize(){
        $this->Product = model("Product");
        $this->Red = model("Red");
        $this->ProductOrder = model("ProductOrder");
        $this->get_user_id();
        }
        public function indexAc(){
            $map["member_id"]=$this->member_id;
            $data=$this->ProductOrder->select_all($map);
            return $this->suc($data);
            
        }
        public function sumAc(){
            $map["member_id"]=$this->member_id;
            $map["stat"]=1;
            $data["num"]=$this->ProductOrder->sum($map);
            $red=$this->Red->get_by_uid($this->member_id);
            $data["red"]=$red["red"]+0;
            return $this->suc($data);
            
        }
    //
    public  function addAc(){
        $product_id=input("post.id");
        $num=input("post.num");
        if(empty($product_id)||!is_numeric($product_id)){
            return $this->err(9000,"id不能为空");
        }
        if(empty($num)||!is_numeric($num)&&!is_int($num)&&$num>0){
            return $this->err(9000,"数量不能为空,必须为整数");
        }
        $product=$this->Product->get_by_id($product_id);
        if(empty($product)||$product["stat"]!=1){
            return $this->err(9000,"产品不存在");
        }
        $sum=$this->ProductOrder->sum(["member_id"=>$this->member_id,"stat"=>1]);
        if($sum+$num>$product["per_num"]){
            return $this->err(9000,"超出购买份数");
        }
        $data["product_id"]=$product_id;
        $data["num"]=$num;
        $data["member_id"]=$this->member_id;
        $data["amount"]=$num*$product["price"];
        $data["red"]=$num*$product["red"];
        $data["sn"]="gq".date("YmdHis"). mt_rand(1000, 9999);
        $data["stat"]=0;
        $data["add_time"]=time();
        $info = $this->Red->where('member_id',$data['member_id'])->find();
        $info["num"]=$num;
        $info["amount"]=$data["amount"];
        $info["red"]=$data["red"];
        Db::startTrans();
        try{
            $status=$this->ProductOrder->addItem_id($data);

            if($status["stat"]==1){
                $reponse["url"]= url("order/alipay",array("sn"=>$data["sn"]));
            }
            Db::commit();
        }catch(\Exception $e){
            Db::rollback();
            return $this->err(9000,$status["errmsg"]);
        }
      
        return $this->suc($reponse);

    }
  
   
       
       
   
}

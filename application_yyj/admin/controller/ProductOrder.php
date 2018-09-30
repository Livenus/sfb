<?php
namespace app\admin\controller;
class ProductOrder extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
       $this->Product = model("Product");
       $this->ProductOrder = model("ProductOrder");
       $this->Member = model("Member");
       $this->request=request();
    }
    public function indexAc(){
        
      return $this->fetch();
    }
    /**
     * 删除文章
     */
    public function delAc(){
        $ids = input('post.')['id'];
       if(count($ids)==1){
           $status=$this->ProductOrder->del_by_id($ids[0]);
       }else{
            $status=$this->ProductOrder->del_all($ids);
       }
       
       
       if($status){
           return message(1, "删除成功");
       }else{
           return message(0, "删除失败");       
       }
    }
    public function editAc($id){

         $data=$this->ProductOrder->get_by_id($id);
           $user=$this->Member->get_by_id($data["member_id"]);
           $data["truename"]=$user["truename"];
           $data["phone"]=$user["phone"];
         $this->assign("info",$data);
         return $this->fetch();
    }
    public  function listajaxAc(){
        $req=input("get.");
        if($res["order"]){
            $order="id ".$res["order"];
        }
        if($res["limit"]){
           $limit=$res["offset"].",".$res["limit"];
        }
        $map=[];
       $lists = $this->ProductOrder->select_all($map,"id desc",$limit);
       foreach($lists as $k=>$v){
           $user=$this->Member->get_by_id($v["member_id"]);
           $lists[$k]["truename"]=$user["truename"];
           $lists[$k]["phone"]=$user["phone"];
           $lists[$k]["add_time"]=date("Y-m-d H:i:s",$v["add_time"]);
       }
        echo json_encode(array('rows'=>$lists,"total"=>$this->ProductOrder->get_count($map)));
        
    }
}
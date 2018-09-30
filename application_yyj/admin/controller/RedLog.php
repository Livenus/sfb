<?php
namespace app\admin\controller;
/**
 * 文章
 * @author jhy
 *
 */

//use \app\common\validate\Article;
class RedLog extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
       $this->RedLog = model("RedLog");
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
           $status=$this->Product->del_by_id($ids[0]);
       }else{
            $status=$this->Product->del_all($ids);
       }
       
       
       if($status){
           return message(1, "删除成功");
       }else{
           return message(0, "删除失败");       
       }
    }
    
    
 
    public  function listajaxAc(){
        $req=input("get.");
         $order="id desc";
        if($res["limit"]){
           $limit=$res["offset"].",".$res["limit"];
        }
        $map=[];
       $lists = $this->RedLog->select_all($map,$order,$limit);
       foreach($lists as $k=>$v){
           $user=$this->Member->get_by_id($v["member_id"]);
           $lists[$k]["username"]=$user["uname"];
           $lists[$k]["phone"]=$user["phone"];
           $lists[$k]["add_time"]=date("Y-m-d H:i:s",$v["add_time"]);
       }
        echo json_encode(array('rows'=>$lists,"total"=>$this->RedLog->get_count($map)));
        
    }
   
}
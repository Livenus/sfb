<?php
namespace app\admin\controller;
/**
 * 文章
 * @author jhy
 *
 */

//use \app\common\validate\Article;
class Red extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
       $this->Red = model("Red");
       $this->Member = model("Member");
       $this->request=request();
    }
    public function indexAc(){
        
      return $this->fetch();
    }
    /**
     * 修改文章
     */
    public function editAc($id){
         if($this->request->isAjax()&&$id){
             $input=input("post.");
             unset($input["id"]);
                     $data["update_time"]=time();
                     $data["red"]=$input["red"];
                     $old=$this->Red->get_by_id($id);
                     $more=$data["red"]-$old["red"];
             $result=$this->Red->editById($data,$id,$more,$old["member_id"],1);
             if($result["stat"]==1){
                  admin_log($data["red"],"更新业绩","");
                 return message(1,"编辑成功");
             }else{
                 return message(0,"编辑失败");   
             }
             
         }

         $data=$this->Red->get_by_id($id);
           $user=$this->Member->get_by_id($data["member_id"]);
           $data["truename"]=$user["truename"];
           $data["phone"]=$user["phone"];
         $this->assign("info",$data);
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
        if($res["order"]){
            $order="id ".$res["order"];
        }
        if($res["limit"]){
           $limit=$res["offset"].",".$res["limit"];
        }
        $map=[];
       $lists = $this->Red->select_all($map,$order,$limit);
       foreach($lists as $k=>$v){
           $user=$this->Member->get_by_id($v["member_id"]);
           $lists[$k]["username"]=$user["uname"];
           $lists[$k]["phone"]=$user["phone"];
       }
        echo json_encode(array('rows'=>$lists,"total"=>$this->Red->get_count($map)));
        
    }
    public function update_statusAc($id="",$status){
        $input=input("post.");
        $rule=[
            "id"=>"require|number"
        ];
        $check=$this->validate($input, $validate);
        if($check!==true){
              return message(0,$check);
        }
       //批量更新
        if(is_array($input["id"])){

            $this->Product->editByids(array("stat"=>$status),$input["id"]);
                        return message(1,"更新成功");
        }
        $res=$this->Product->editById(array("stat"=>$status),$id);
        if($res["stat"]==1){
             admin_log($status,"更改文章状态",$this->lang['channel']);
            return message(1,"更新成功");
        }
        return message(0,"更新失败");
        
    } 
}
<?php
namespace app\admin\controller;
/**
 * 文章
 * @author jhy
 *
 */

//use \app\common\validate\Article;
class Product extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
       $this->Product = model("Product");
       $this->request=request();
    }
    public function indexAc(){
        
      return $this->fetch();
    }
    /**
     * 添加一文章
     */
    public function addAc(){
        $params=input("post.");
        if(!$this->request->isAjax()){
          return view('edit');  
        }
        $rule=[
            "price"=>"number",
            "title"=>"require|length:1,650",
         "description"=>"require|length:1,650",
         "content"=>"require|length:1,1650",
         "stat"=>"require|number",
       ];
        $validate = $this->validate($params, $rule);
        if($validate !== true){
             return message(0,$validate);
        }
        $params["add_time"]=time();
        $r = $this->Product ->addItem($params);
        if($r['stat'] == '1'){
             return message(1,"添加成功");
        }else{
            return message(0,"添加失败");   
        }
    }
    
    /**
     * 修改文章
     */
    public function editAc($id){
         if($this->request->isAjax()&&$id){
             $input=input("post.");
             unset($input["id"]);
                     $input["update_time"]=time();
                     $input["content"]=(string)htmlentities(input('post.content'));
             $result=$this->Product->editById($input,$id);
             
             if($result["stat"]==1){
                 return message(1,"编辑成功");
             }else{
                 return message(0,"编辑失败".$result["msg"]);   
             }
             
         }
         $data=$this->Product->get_by_id($id);
         $data["content"]= html_entity_decode($data["content"]);
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
    
    
    /**
     * 获取参数
     * @param $type 类型，add添加，edit为修改
     */
    private function _get_params($type = 'add'){
        $params = array();
        $params['title'] = (string)input('post.title');
        $params['code'] = (string)input('post.code');
        $params['content']   =(string)htmlentities(input('post.content'));
        $params['des']   =(string)input('post.des');
        $params['stat']   =(int)input('post.stat');
        $params['addtime']   = (int)input('post.addtime');
        $params['type']   =(int)input('post.type');


        return $params;
    }
    public  function listajaxAc(){
        $req=input("get.");
        if($res["order"]){
            $order="id ".$res["order"];
        }
        if($res["limit"]){
           $limit=$res["offset"].",".$res["limit"];
        }
        if($req["title"]!=-1){
            
            $map["title"]=["like","%{$req["title"]}%"];
        }
       $lists = $this->Product->select_all($map,$order,$limit);
        echo json_encode(array('rows'=>$lists,"total"=>$this->Product->get_count($map)));
        
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
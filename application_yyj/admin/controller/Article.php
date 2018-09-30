<?php
namespace app\admin\controller;
/**
 * 文章
 * @author jhy
 *
 */

//use \app\common\validate\Article;
class Article extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
       $this->Article = model("Article");
       $this->request=request();
    }
    public function indexAc(){
        
      return $this->fetch();
    }
    /**
     * 添加一文章
     */
    public function addAc(){
        $article_mod = new \app\common\model\Article();
        $id = $article_mod->check('article_add');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $params = $this->_get_params('add');
        if(!$this->request->ispost()){
          return view('edit');  
        }
        $validate = $this->validate($params, 'Article.add');
        if($validate !== true){
            return $validate;
        }
        
        $r = $article_mod ->addItem($params);
        if($r['stat'] == '1'){
            $this->redirect("index");
        }else{
            $this->redirect("index");
        }
    }
    
    /**
     * 修改文章
     */
    public function editAc($id){
         if($this->request->ispost()&&$id){
             $input=input("post.");
                     $params = $this->_get_params('add');
             $result=$this->Article->editById($params,$id);
             if($result["stat"]==1){
                 //return message(1,"编辑成功");
             }else{
                 //return message(0,"编辑失败");   
             }
             
         }
         $data=$this->Article->get_by_id($id);
$data["content"]=html_entity_decode($data["content"]);
         $this->assign("info",$data);
         return $this->fetch();
    }
    /**
     * 删除文章
     */
    public function delAc(){
        $ids = input('post.')['id'];
       if(count($ids)==1){
           $status=$this->Article->del_by_id($ids[0]);
       }else{
            $status=$this->Article->del_all($ids);
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
        $params['mfile']   =$this->upload();

        return $params;
    }
public function upload(){
    // 获取表单上传文件 例如上传了001.jpg
    $file = request()->file('mfile');
    
    // 移动到框架应用根目录/public/uploads/ 目录下
    if($file){
        $path=ROOT_PATH . 'public' . DS . 'uploads';
        $info = $file->move($path);
        if($info){
            return "/uploads/".$info->getSaveName();
        }else{
            // 上传失败获取错误信息
            return $file->getError();
        }
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
        if($req["type"]!=-1){
            $map["type"]=$req["type"];
        }
        if($req["title"]!=-1){
            
            $map["title"]=["like","%{$req["title"]}%"];
        }
       $lists = $this->Article->select_all($map,$order,$limit);
        echo json_encode(array('rows'=>$lists,"total"=>$this->Article->get_count($map)));
        
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

            $this->Article->editByids(array("stat"=>$status),$input["id"]);
                        return message(1,"更新成功");
        }
        $res=$this->Article->editById(array("stat"=>$status),$id);
        if($res["stat"]==1){
             admin_log($status,"更改文章状态",$this->lang['channel']);
            return message(1,"更新成功");
        }
        return message(0,"更新失败");
        
    } 
}
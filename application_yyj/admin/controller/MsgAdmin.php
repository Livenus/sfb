<?php
namespace app\admin\controller;
/**
 * 文章
 * @author jhy
 *
 */

//use \app\common\validate\Article;
class MsgAdmin extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
       $this->MsgAdmin = model("MsgAdmin");
       $this->request=request();
    }
    public function indexAc(){
        
      return $this->fetch();
    }
    /**
     * 添加一文章
     */
    public function addAc(){
        $params = input("post.");
        $params["start"]= strtotime($params["start"]);
        $params["end"]= strtotime($params["end"]);
        if(!$this->request->isAjax()){
            $info=$this->MsgAdmin->get_by_map();
            $this->assign("info",$info);
          return view('edit');  
        }
        $validate = $this->validate($params, 'MsgAdmin');
        if($validate !== true){
            return message(0, $validate);;
        }
        $params["start"]= strtotime($params["start"]);
        $params["end"]= strtotime($params["end"]);
        $params["update_time"]=time();
        $params["hash"]=md5($params["title"].$params["content"].$params["start"].$params["end"]);
        $r = $this->MsgAdmin ->addItem($params);
        if($r['stat'] == '1'){
            return json($r);
        }else{
            return json($r);
        }
    }
        public function editAc(){
        $params = input("post.");
        $params["start"]= strtotime($params["start"]);
        $params["end"]= strtotime($params["end"]);
        $validate = $this->validate($params, 'MsgAdmin');
        if($validate !== true){
            return message(0, $validate);
        }
                $params["update_time"]=time();

        $params["hash"]=md5($params["title"].$params["content"].$params["start"].$params["end"]);
        $r = $this->MsgAdmin ->editById($params,$params["id"]);
        if($r['stat'] == '1'){
            return json($r);
        }else{
            return json($r);
        }
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
<?php 
namespace app\admin\controller;
class Setting extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->Setting =model('Setting');
        $this->MemberGroup =model("MemberGroup");

        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }
    public function indexAc(){
        $id = $this->Setting->check('setting_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $map["stat"]=1;
         $group=$this->MemberGroup->select_all($map);
         $set=$this->Setting->get_by_key("member_group");
         $rate=$this->Setting->select_all();
         $this->assign("set",$set);
         $this->assign("rate",$rate);
         $this->assign("group",$group);
        return $this->fetch();
    }
    public function postionlistAc(){
        $this->assign('action',$this->request->action());
        return $this->fetch();
    }
    public function editAc(){
        $id = $this->Setting->check('setting_edit');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $data=$this->request->param();
        $req=input("post.");
        if($this->request->isAjax()){
            $add["key"]="member_group";
            $add["value"]=$data["member_group"];

            if($req){
            foreach ($req as $k=>$v){
                $d["key"]=$k;
                $d["value"]=$v;
                if($this->Setting->get_by_key($k)){
                  $status=$this->Setting->editBykey($d,$k,$v);
                }else{
               $this->Setting->addItem($d); 
                }


            }
              admin_log(json_encode($req),"修改表","设置项");
                return message(1, "修改成功");
            }else{
            return message(1, "修改成功");
            }

        }
    }
    public  function set_codeAc(){
        $imgpos=$this->Setting->get_by_key("imgpos");
        $imgpos_d= explode(",", $imgpos["value"]);
        $this->assign("imgpos_d",$imgpos_d[2]);
        $this->assign("phone",1);
        
        return view("./static/webpage/html/code001.html");
        
    }
    public  function saveAc(){ 
        $str=input("post.save");
        $res=file_put_contents("./static/webpage/html/code001.html", $str);
        return message(1,"保存成功");
        
    }
    public function save_img_posAc($x="",$y="",$imgurl=""){
        if($x&&$y&&$imgurl){
        }else{
            echo "坐标，图片地址必填";
        }
        $data["value"]="$x,$y,$imgurl";
        $data["key"]="imgpos";
        if($this->Setting->get_by_key("imgpos")){
           $status=$this->Setting->editBykey($data, "imgpos",$data["value"]);
        }else{
            $status=$this->Setting->addItem($data); 
        }
        if($status['stat']==1){
            
            return message(1, "修改成功");
        }
        return message(0, "修改失败");
    }

}
?>
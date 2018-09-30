<?php 
namespace app\admin\controller;
class Debug extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->Debug =model('Debug');
    }
    public function indexAc(){
        
        $count=$this->Debug->get_count($map);
        if($count>100){
            $last=$this->Debug->get_last();
            $last_id=$last["id"];
            $map["id"]=["<",$last_id-100];
            $this->Debug->del($map);
        }
        return $this->fetch();
    }
    public  function listajaxAc(){
        $lists=$this->Debug->select_all($map,"id desc",$limit);
        echo json_encode(array('rows'=>$lists,"total"=>$this->Debug->get_count($map)));
        
    }

}
?>
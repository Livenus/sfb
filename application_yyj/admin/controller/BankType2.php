<?php
namespace app\admin\controller;

class BankType2 extends \app\admin\controller\Home{
    public $area;
    private $lang;
    public function _initialize(){
        parent::_initialize();
        $this->BankType2 = \think\Loader::model('BankType2');
    }
    public function indexAc(){
        return $this->fetch();
    }
    public function listajaxAc(){
         $input=input('get.');
         $map=array();
        if($input['bank_name']){
            $map["bank_name"]=["like","%{$input['bank_name']}%"];
        }
        $data = $this->BankType2->select_all_item($map,"sorts desc","{$input['offset']},{$input['limit']}");
        exit(json_encode(array('rows'=>$data,'total'=>$this->BankType2->get_count($map))));
    }
    public function updateAc(){
        
        $input=input('post.');
        foreach($input as $k=>$v){
            if($k&&$v){
                $map['bank_name']=$k;
                $data['sorts']=$v;
                $stat=$this->BankType2->edit($data,$map);
            }
        }
        if($stat){
             return message(1, "修改成功");
        }
         return message(1, "修改失败");
    }
    public function addAc(){
         $input=input('post.');
         if(request()->isPost()){
             $stat=$this->BankType2->add($input);
             return message(1, "添加成功");
         }
          return $this->fetch();
    }
    public function editAc($bank_no){
         $input=input('post.');
         if(request()->isPost()){
             $stat=$this->BankType2->edit($input,["bank_no"=>$bank_no]);
             return message(1, "编辑成功");
         }
         $info=$this->BankType2->getBymap(["bank_no"=>$bank_no]);
         $this->assign("info",$info);
          return $this->fetch("add");
    }
}
?>
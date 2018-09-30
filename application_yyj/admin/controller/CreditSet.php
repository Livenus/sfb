<?php 
namespace app\admin\controller;
class CreditSet extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->CreditSet =model('CreditSet');
        $this->request = \think\Request::instance();
    }
    public function indexAc(){
         $info=$this->CreditSet->get_by_id();
         $info["pay_way"]= json_decode($info["pay_way"],TRUE);
         $this->assign("info",$info);
        return $this->fetch();
    }
    public function postionlistAc(){
        $this->assign('action',$this->request->action());
        return $this->fetch();
    }
    public function editAc(){
        $data=$this->request->param();
        $rule=[
            "min"=>"number|between:0.1,100000000",
            "max"=>"number|between:0.1,100000000",
            "num"=>"number|between:1,100",
            "num"=>"number|between:1,100",
            "per_max"=>"number|between:1,100",
            "day_max"=>"number|between:1,100",
            "more_day_max"=>"number|between:1,100",
            "per_max"=>"number|between:1,100", 
            "many_pay"=>"number|between:1,100", 
            "many_buy"=>"number|between:1,100", 
            "rate"=>"number|between:0.01,100", 
            "single_payment"=>"number|between:0.01,10000", 
        ];
        $check=$this->validate($data, $rule);
        if($check!==true){
            return message(0,$check);
        }
        $req=input("post.");
        if($this->request->isAjax()){
            $req["pay_way"]= json_encode($req["pay_way"]);
                   if($this->CreditSet->get_count()){
                       $datap=$req;
                       unset($datap['id']);
                       $stat=$this->CreditSet->editById($datap,$req["id"]);
                   }else{
                       $this->CreditSet->addItem($req);
                   }
              admin_log(json_encode($req),"修改表","设置项");
                return message(1, "修改成功");
            }else{
            return message(1, "修改成功");
            }

        }
    

  
}
?>
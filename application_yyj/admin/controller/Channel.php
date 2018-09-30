<?php 
namespace app\admin\controller;
use think\Config;
class Channel extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->Channel = \think\Loader::model('Channel');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
        Config::load(APP_PATH.'/pay_type.php');
    }
    public function indexAc(){
        $id = $this->Channel->check('channel_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $order="id desc";
        $data=$this->Channel->select_all($order);
        $this->assign("data",$data);
        return $this->fetch();
    }
    public function addAc(){
        $id = $this->Channel->check('channel_add');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
         $data=$this->request->param();
         
        $unionpay= get_self();
        if($this->request->isAjax()){
            $validate = $this->validate($data,'Channel');
            if($validate !== true){
                return message(0, $validate);
            }
            admin_log($data,$this->lang['add'],$this->lang['channel']);
            unset($data['rate_money_min']);
            $status=$this->Channel->addItem_id($data);
            
            $pay=$this->Channel->get_pay_way(100,$status["data"]);
            if($pay){
                $this->Channel->editById(["rate"=>$pay["rate_type"]],$status["data"]);
            }
            return $status;
        }
        $this->assign("unionpay",$unionpay);
        return $this->fetch();
    }
    public  function listajaxAc(){
        $a_name=input("get.a_name");
        $res=input("get.");
        if($res["order"]){
            $order="sot asc,id ".$res["order"];
        }else{
             $order='sot asc';
        }

        if($res["limit"]){
           $limit=$res["offset"].",".$res["limit"];
        }
       $lists = $this->Channel->select_all($map,$order,$limit);
       foreach($lists as $k=>$v){
             $pay_way=$this->Channel->get_pay_way_parent(100,$v["id"]);
             $lists[$k]["pay_way"]=$pay_way;
             $pay_way_type=$this->Channel->get_pay_way(100,$v["id"]);
             $lists[$k]["pay_way_type"]=$pay_way_type;
       }
        echo json_encode(array('rows'=>$lists,"total"=>$this->Channel->get_count($map)));
        
    }
    public function editAc($id=0){
        $check = $this->Channel->check('channel_edit');
        if($check){
            $this->admin_priv($check);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $data=$this->request->param();
         $unionpay= get_self();
        if($this->request->isAjax()){
            $validate = $this->validate($data,'Channel');
            if($validate !== true){
                return message(0, $validate);
            }
            admin_log($data["id"],$this->lang['edit'],$this->lang['channel']);
            unset($data['rate_money_min']);
            $result=$this->Channel->editById($data,$data["id"]);
            //会员组费率
            $pay=$this->Channel->get_pay_way(100,$data["id"]);
            if($pay){
                 
                $this->Channel->editById(["rate"=>$pay["rate_type"]],$data["id"]);
            }
         return $result;

        }
        $data=$this->Channel->get_by_id($id);
        $this->assign("info",$data);
         $this->assign("unionpay",$unionpay);
          return $this->fetch("add");
    }
    public function delAc(){
        $id = $this->Channel->check('channel_del');
        if($id){
            $ret = $this->admin_priv($id,$this->request->isAjax());
            if(!$ret) return message(0,'你没有操作权限');
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $ids = input('post.')['id'];
       if(count($ids)==1){
           $status=$this->Channel->del_by_id($ids[0]);
       }else{
            $status=$this->Channel->del_all($ids);
       }
       
       
       if($status){
           return message(1, "删除成功");
       }else{
           return message(0, "删除失败");       
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

            $this->Channel->editByids(array("stat"=>$status),$input["id"]);
                        return message(1,"更新成功");
        }
        $res=$this->Channel->editById(array("stat"=>$status),$id);
        if($res["stat"]==1){
             admin_log($status,$this->lang['edit'],$this->lang['channel']);
            return message(1,"更新成功");
        }
        return message(0,"更新失败");
        
    } 
}
?>
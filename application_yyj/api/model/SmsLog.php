<?php 
namespace app\api\model;

class SmsLog extends \app\api\model\Home{
    protected $insert=['ip'];
    public function setIpAttr(){
        return ip2long(real_ip());
    }
    public function addItem($data){
        return parent::addItem($data);
    }
    public  function get_by_phone($phone,$type=1){
        $map["phone"]=$phone;
        $map["type"]=$type;
        $map["state"]=1;
        $exp_time= time()-1800;
        $map["add_time"]=["gt",$exp_time];
        
        $data=$this->where($map)->order("id desc")->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
    }
}
?>
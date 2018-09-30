<?php
namespace app\api\controller;
use think\Config;
use think\Response;
class Bank  extends \app\api\controller\Home {
        public function _initialize(){
        $this->member = new \app\common\model\Member;
        $this->Bank = model("Bank");
        $this->Member_login_token = model("Member_login_token");
        $this->get_user_id();
        }
    //添加银行卡
    public  function add_bank_cardAc($bank_name="",$bank_num="",$bank_phone=""){
            $data["member_id"]=$this->member_id;
            $mem=$this->member->get_by_id($this->member_id);
            if($mem["is_rz_1"]==1&&$mem["is_rz_2"]==1){
                
            }else{
              return $this->err('9008','请先实名认证');  
            }
            $data["bank_name"]=$bank_name;
            $data["bank_num"]=$bank_num;
            $data["bank_phone"]=$bank_phone;
            $error=$this->validate($data, "Bank");
            if($error!==true){
                
                 return $this->err('9000',$error);
            }
        $check["payeeAcc"]=$data["bank_num"];
        $check["payerIdNum"]=$mem["idnum"];
        $check["payerName"]=$mem["truename"];
        $check["merOrderId"]="yz".date("YmdHis");
        $phone=$bank_phone;
        if(empty($phone)){
           $phone=$mem["bank_phone"]; 
        }
        $check["payerPhoneNo"]=$phone;
        $check["tran_time"]=date("Y-m-d H:i:s");
        //银行卡三要素

        $Kuaiyunzhong= new \ext\Kuaiyunzhong();
        $check_bank=$Kuaiyunzhong->check_sign3($check);
        if($check_bank!==true){
            return $this->err('9009','发生错误，提交失败,请核对银行卡号，开户身份证，开户姓名是否是您本人'. '(四要素验证错误' .$check_bank["message"].')');
        }

        if($bank_name&&$bank_num){
            
             $input = input("post.");
            $data['validity'] = strtotime($input['validity']);
            if(strlen($input['safe_num'])!=3){
                  return $this->err('9009','安全码必须为三位');  
            }
            $data['safe_num'] = $input['safe_num'];
            $resid=$this->Bank->addItem_id($data);
        }
        if($resid["stat"]==1){
 
           return $this->suc($resid["data"]);
        }else{
            
             return $this->err('9099','添加失败'.$resid['msg']);
        }
    }
    public function check_bankAc($bank_num,$idnum,$truename,$phone){
        $check["payeeAcc"]=$bank_num;
        $check["payerIdNum"]=$idnum;
        $check["payerName"]=$truename;
        $check["merOrderId"]="yz".date("YmdHis");
        $check["payerPhoneNo"]=$phone;
        $check["tran_time"]=date("Y-m-d H:i:s");
        $Kuaiyunzhong= new \ext\Kuaiyunzhong();
        $check_bank=$Kuaiyunzhong->check_sign3($check);
        if($check_bank!==true){
            return $this->err('9009','发生错误，提交失败,请核对银行卡号，开户身份证，开户姓名是否是您本人'. '(四要素验证错误' .$check_bank["message"].')');
        }
    }
    //添加银行卡
    public  function edit_bank_cardAc($validity="",$safe_num="",$bank_num="",$bank_phone=""){
            $data["member_id"]=$this->member_id;
            $mem=$this->member->get_by_id($this->member_id);
            if($mem["is_rz_1"]==1&&$mem["is_rz_2"]==1){
                
            }else{
              return $this->err('9008','请先实名认证');  
            }
         $bank=$this->Bank->get_by_num($this->member_id,$bank_num);
         if(empty($bank)){
              return $this->err('9008','银行为空');  
         }
          $input = input("post.");
         if(empty($input['bank_phone'])){
              return $this->err('9009','预留手机号必填');  
         }
        if($validity&&$safe_num){
            if(strlen($input['safe_num'])!=3){
                  return $this->err('9009','安全码必须为三位');  
            }

            $data['validity'] = strtotime($input['validity']);
            $data['safe_num'] = $input['safe_num'];
            $data['bank_phone'] = $input['bank_phone'];
            $resid=$this->Bank->editByid($data,$bank["id"]);
        }
        if($resid["stat"]==1){
 
           return $this->suc($resid["data"]);
        }else{
            
             return $this->err('9099','添加失败'.$resid['msg']);
        }
    }
    //获取银行卡列表
    public  function get_bank_listAc($order="id desc",$offset=0,$length=5){
        $map["member_id"]=$this->member_id;
        $data=$this->Bank->select_all($map);
        if($data){
              return $this->suc($data);
        }else{
              return $this->err('900','没有数据');
        }
    }
    //获取银行卡信息
    public  function get_bank_by_idAc($id=""){
        $data["id"]=$id;
            $error=$this->validate($data, "Bank.del");
            if($error!==true){
                
                 return $this->err('9000',$error);
            }
        $data=$this->Bank->get_by_id($this->member_id,$id);
        if($data){
              return $this->suc($data);
        }else{
              return $this->err('900','没有数据');
        }
    }
    //删除银行卡
    public function del_bank_by_idAc($id=""){
        $data["id"]=$id;
            $error=$this->validate($data, "Bank.del");
            if($error!==true){
                
                 return $this->err('9000',$error);
            }
        $data=$this->Bank->del_by_id($this->member_id,$id);
        if($data){
              return $this->suc("删除成功");
        }else{
              return $this->err('9099','删除失败');
        } 
        
        
    }
    //获取所有银行
    public  function get_bank_type_listAc(){
         Config::load(APP_PATH.'/bank_name.php');
        $data= config("bank_name");
        if($data){
              return $this->suc($data);
        }else{
              return $this->err('900','没有数据');
        }
    }
}

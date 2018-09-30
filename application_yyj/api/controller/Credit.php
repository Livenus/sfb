<?php

/**
 * 信用卡
 */

namespace app\api\controller;

class Credit extends \app\api\controller\Home {

    public function _initialize() {

        $this->Credit = model('Credit');
        $this->CreditPlanItem = model('CreditPlanItem');
        $this->CreditPlan = model('CreditPlan');
        $this->CreditSet = model('CreditSet');
        $this->Member = model('Member');
        $this->MemberArea = model('MemberArea');
        $this->BankType2 = model('BankType2');
        $this->form_url="http://xapi.ypt5566.com/api/Quickpay/entryCard";
    }
    public  function get_provinceAc($id=""){
        $Quickpay=new \credit\Quickpay();
        $data=$Quickpay->get_province([]);
        $data1=$data["data"]["data"];
        if(empty($id)){
            return $this->suc($data1);
        }
        $credit=$this->Credit->get_by_id($id);
        $province=$credit["bank_province"];

        $self=[];
        foreach($data1 as $v){
            if($v["merchant_province"]==$province){
                $self=$v;
                break;
            }
        }
        if($self){
                    return $self;
        }else{
            return $data1[0];
        }

    }
    public  function get_cityAc($id="",$province_code=""){
        $Quickpay=new \credit\Quickpay();
        if(empty($id)&&$province_code){
        $para["province_code"]=$province_code;
        $data=$Quickpay->get_city($para);
        $data1=$data["data"]["data"];
            return $this->suc($data1);
        }
        $self=$this->get_provinceAc($id);
        $para["province_code"]=$self["province_code"];
        $data=$Quickpay->get_city($para);
        $data1=$data["data"]["data"];

        $credit=$this->Credit->get_by_id($id);
        $bank_city=$credit["bank_city"];

        $self1=[];
        foreach($data1 as $v){
            if($v["merchant_city"]==$bank_city){
                $self1=$v;
                break;
            }
        }
        if($self1){
           return $self1;
        }else{
            return $data1[0];
        }

    }
    public function addAc() {
        $this->get_user_id();
        if(cache("nameorder".$this->member_id)==$this->member_id){
            return $this->err(9100,"请求限制,间隔10秒");
        }
        cache('nameorder'.$this->member_id, $this->member_id, 6);
        $input = input("post.");
        $mem=$this->Member->get_by_id($this->member_id);
        $data['member_id'] = $this->member_id;
        $member_area = $this->MemberArea->get_by_member_id($this->member_id);
         $county=$member_area['id_county']?$member_area['id_county']:$member_area['county'];
         if(empty($county)){
             return $this->err(9001, "区县待完善");
         }
        $data['bank_name'] = $input['bank_name'];
        $bank_info=$this->BankType2->getBymap(["bank_name"=>$data['bank_name']]);
        $data['bank_card'] = $input['bank_card'];
      
        $data['safe_num'] = $input['safe_num'];
        $data['validity'] = $input['validity'];
        $data['bill_date'] = $input['bill_date'];
        $data['due_date'] = $input['due_date'];
        $data['bank_mobile'] = $input['bank_mobile'];
        $data['bank_province'] = $input['bank_province'];
        $data['bank_city'] = $input['bank_city'];
        $data['bank_branch'] = $input['bank_name'];
        $data['bank_code'] = $bank_info['bank_no'];
        $data['bank_coding'] =$bank_info["short_name"];
        //已存在信息设置标志
        $cred=$this->Credit->get_by_map(["member_id"=>$this->member_id,"bank_card"=>$data['bank_card']]);
        if($cred["subMerchantNo"]){
            $status=$this->Credit->editById(["del"=>0],$cred["id"]);
            $rep["data"]=$cred["id"];
            return $this->suc($cred["id"]);
        }
        $check1 = $this->validate($data, "Credit.add");
        if ($check1 !== true) {
            return $this->err(9001, $check1);
        }

        if(model("CreditFee")->get_count(["member_id"=>$this->member_id])>=10){
            return $this->err(9000, "已超过最大次数，每日10次");
        }
        //四要素验证
        $check["payeeAcc"]=$data["bank_card"];
        $check["payerIdNum"]=$mem["idnum"];
        $check["payerName"]=$mem["truename"];
        $check["merOrderId"]="yz".date("YmdHis");
        $check["payerPhoneNo"]=$data['bank_mobile'];
        $check["tran_time"]=date("Y-m-d H:i:s");
        $Kuaiyunzhong= new \ext\Kuaiyunzhong();
        $check_bank=$Kuaiyunzhong->check_sign3($check);
        if($check_bank!==true){
            return $this->err('9009','发生错误，提交失败,请核对银行卡号，开户身份证，开户姓名是否是您本人'. '(四要素验证错误' .$check_bank["message"].')');
        }
        if ($input) {
            $data['validity'] = strtotime($input['validity']);
            if ($data['validity'] < time()) {
                return $this->err(9000, "有效日期不能小于当前时间");
            }
            $data["order_no"]=date("ymdhis"). rand(1111, 9999);
            $set=$this->CreditSet ->get_by_id();
            $data['rate']=$set["rate"];
            $data['single_payment']=$set["single_payment"];
            $data['single_back']=$set["single_back"];
            $card=$this->Credit->get_by_map(["bank_card"=>$data['bank_card'] ]);
            if($card){
            $ids = $this->Credit->editById($data,$card["id"]);
            $id["data"]=$card["id"];
            }else{
            $id = $this->Credit->addItem_id($data);     
            }
           $city=$this->get_cityAc($id["data"]);
           if($city){
               $area["province_code"]=$city["province_code"];
               $area["city_code"]=$city["city_code"];
               $this->Credit->editById($area,$id["data"]);
           }
            $active=$this->active($id['data']);
            mlog($active);
            if($active['stat']==0){
                return $this->err(9000,$active["errmsg"]);
            }
            return $this->suc($id["data"]);
        }
        return $this->err(900, "没有数据");
    }
    //入网绑卡
    private function active($id){
        $card=$this->Credit->get_by_id($id);
        $member=$this->Member->get_by_id($this->member_id);
        $member_area = $this->MemberArea->get_by_member_id($this->member_id);
        $Quickpay=new \credit\Quickpay();
        if($card&&empty($card["authent_no"])){
            $para["paymer_name"]=$member['truename'];
            $para["paymer_idcard"]=$member['idnum'];
            $para["paymer_bank_no"]=$card['bank_card'];
            $para["paymer_phone"]=$card['bank_mobile'];
            $authent=$Quickpay->authent($para);
        }
        if($authent['stat']==0&&empty($card["authent_no"])){
            return $authent;
        }elseif($authent["stat"]==1){
                $data["authent_no"]=$authent['data']["authent_no"];
                $this->Credit->editById($data,$card["id"]);   
        }
        if(empty($card["user_no"])){
                $para1["auth_order_no"]=$data["authent_no"]?$data["authent_no"]:$card["authent_no"];
                $para1["user_name"]=$member['truename'];
                $para1["id_no"]=$member['idnum'];
                $para1["card_no"]=$card['bank_card'];
                $para1["phone"]=$card['bank_mobile'];
                $para1["bank_name"]=$card['bank_name'];
                $para1["bank_branch"]=$card['bank_branch'];
                $para1["bank_code"]=$card['bank_code'];
                $para1["bank_coding"]=$card['bank_coding'];
                 $para1["province"]=$member_area['id_province']?$member_area['id_province']:$member_area['province'];
                 $para1["city"]=$member_area['id_city']?$member_area['id_city']:$member_area['city'];
                 $para1["county"]=$member_area['id_county']?$member_area['id_county']:$member_area['county'];
                 $para1["address"]=$member_area['id_detail']?$member_area['id_detail']:$member_area['detail'];
                 $para1["validity"]=date("my",$card["validity"]);
                 $para1["cvv2"]=$card['safe_num'];
                 $para1["bank_type"]=6;
                $enterNet=$Quickpay->enterNet($para1);   
        }

        if($enterNet['stat']==0&&empty($card["user_no"])&&empty($enterNet["user_no"])){
            return $enterNet;
        }elseif($enterNet['stat']==1){
                $data1["user_no"]=$enterNet['data']["user_no"];
                $this->Credit->editById($data1,$card["id"]); 
        }

                if(empty($card["subMerchantNo"])){
                //入住
                $para2["legalName"]=$member['truename'];
                $para2["legalMobile"]=$member['phone'];
                $para2["legalIdNo"]=$member['idnum'];
                $para2["legalBankCard"]=$card['bank_card'];
                $para2["legalBankCardType"]="贷记卡";
                $para2["legalBankName"]=$card['bank_name'];
                $para2["legalBankOpenProvince"]=$card['bank_province'];
                $para2["legalBankOpenCity"]=$card['bank_city'];
                $para2["merBankOpenProvince"]=$member_area['bank_province'];
                $para2["merBankOpenCity"]=$member_area['bank_city'];
                $para2["companyAddress"]=$member_area['detail'];
                $para2["notify_url"]=url("api/Credit/notify_url", "", "", true);
                $network=$Quickpay->network($para2);      
                }

        if($network['stat']==0){
            return $network;
        }elseif($network['stat']==1){
                $data2["subMerchantNo"]=$network['data']["subMerchantNo"];
                $this->Credit->editById($data2,$card["id"]);
            
        }
     
                $bindCard=$this->bindCard($card["id"]);
          return $bindCard;
    }
    public function get_bank_listAc() {
                $this->get_user_id();
        $bank = $this->BankType2->select_all2(["status"=>1]);
            foreach($bank as $k=>$v){
                if($v["logo"]){
                $bank[$k]["logo"]="/uploads/".$v["logo"];   
                }

            }
        return $this->suc($bank);
    }

    public function credit_listAc() {
                $this->get_user_id();
         $input = input('post.');
        $map['member_id'] = $this->member_id;
        $map['status']=['in','0,1'];
        $map['del']=0;
        $map["subMerchantNo"]=["neq",0];
        $order="id asc";
        if($input["order"]){
            $order=$input["order"];
        }
        $limit="0,5";
        if($input["limit"]){
            $limit=$input["limit"];
        }
        $bank = $this->Credit->select_all($map,$order,$limit);
        $data["list"]=$bank;
         $data['count']=$this->Credit->get_count($map);
        return $this->suc($data);
    }

    public function delAc($id = "",$sure=1) {
                $this->get_user_id();
        $input = input('post.');
        $rule = ['id' => 'require'];
        $check = $this->validate($input, $rule);
        if ($check !== true) {
            return err(9001, $check);
        }
       $data_old = $this->Credit->get_by_id($input['id']);
            if (empty($data_old)) {
                return $this->err(9003, "没有记录");
            }
            $rep = $this->Credit->editById(["del"=>1],$input['id']);
            return $this->suc("删除成功");
    }

    public function credit_editAc() {
        $this->get_user_id();
        $input = input("post.");
        $data['bill_date'] = $input['bill_date'];
        $data['due_date'] = $input['due_date'];
        $data['id'] = $input['id'];
        $check = $this->validate($data, "Credit.edit");
        if ($check !== true) {
            return $this->err(9001, $check);
        }
        if ($input) {
            $data_old = $this->Credit->get_by_map(["del"=>0,"id"=>$input['id']]);
            if (empty($data_old)) {
                return $this->err(9003, "没有记录或已经删除");
            }
            $id = $this->Credit->editById($data, $input['id']);
            //$active=$this->update_active($input['id']);
            //mlog($active);
            return $this->suc($id);
        }
        return $this->err(900, "没有数据");
    }
    public function update_active($id){
        $card=$this->Credit->get_by_id($id);
        $Quickpay=new \credit\Quickpay();
            $para["user_no"]=$card['user_no'];
            $para["bank_branch"]=$card['bank_branch'];
            $para["bank_code"]=$card['bank_code'];
            $para["validity"]=$card['validity'];
             $para["cvv2"]=$card['safe_num'];
            $modifyInfo=$Quickpay->modifyInfo($para);
        return $modifyInfo;
        
    }
    public function notify_urlAc(){
        exit('success');
        $input=input("post.");
        mlog($input);
        if($input['Resp_code']==40000){
            $stat=$this->update($input['legalIdNo'],$input['subMerchantNo'],$input['merchant_no']);
            if($stat){
                exit('success');
            }
            
        }
        exit('fail');
        
    }
    //前端绑定提现卡
    public function bindCard($id){
             $this->get_user_id();
           $card=$this->Credit->get_by_id($id);     
              $member=$this->Member->get_by_id($this->member_id);
           if($card["subMerchantNo"]&&$card["user_no"]){
                           $stat=$this->update($member['idnum'],$card['subMerchantNo'],$id,1);
                       if($stat){
                          return suc("绑定成功"); 
                       }
           }
           return err(9000,"绑定失败");
        
    }
    private function update($legalIdNo,$subMerchantNo,$id,$ac=0){
        $member=$this->Member->get_by_map(["idnum"=>$legalIdNo]);
        $credit=$this->Credit->get_by_map(["id"=>$id]);
        if($credit&&$ac==0){
            $data['subMerchantNo']=$subMerchantNo;
            $stat=$this->Credit->editById($data,$credit["id"]);
        }
        if($stat['stat']==1||$ac){
            //绑定提现卡
                        $Quickpay=new \credit\Quickpay();
                        $para["subMerchantNo"]=$credit["subMerchantNo"];
                        $para["user_no"]=$credit["user_no"];
                        $bindCard=$Quickpay->bindCard($para);
                        if($bindCard["stat"]==0){
                            return false;
                        }
                    $data1['config_no']=$bindCard["data"]["config_no"];
                    if($data1['config_no']){
                                             $stat=$this->Credit->editById($data1,$credit["id"]);
                    }

            return true;
        }
        return false;
    }
    //绑定交易卡
    public function entryCardAc($id=""){
        if(!is_numeric($id)){
            return $this->err(9000,"id错误");
        }
        $member=$this->Member->get_by_id($this->member_id);
        $credit=$this->Credit->get_by_map(["id"=>$id]);
        $set=$this->CreditSet ->get_by_id();
        //更新费率
        if($credit["rate"]!=$set["rate"]||$credit["single_payment"]!=$set["single_payment"]){
            $dataset["rate"]=$set["rate"];
            $dataset["single_payment"]=$set["single_payment"];
            $this->Credit->editById($dataset,$credit["id"]);
            $credit=$this->Credit->get_by_map(["id"=>$id]);
        }
        if(empty($credit)){
            return $this->err(9000,"绑定失败,信用卡信息错误");
        }
        mlog($id);
        $Quickpay=new \credit\Quickpay();
                        $para["order_no"]=$credit["order_no"];
                        $para["user_no"]=$credit["user_no"];
                        $para["subMerchantNo"]=$credit["subMerchantNo"];
                        $para["rate"]=$credit["rate"];
                        $para["single_payment"]=$credit["single_payment"];
                        $para["openCardReturnURL"]=url("api/Credit/return_entryCard", "", "", true);
                        $para["openCardNotifyURL"]=url("api/Credit/notify_entryCard", "", "", true);
                        $entryCard=$Quickpay->entryCard($para);
                        $str="<form action='".$this->form_url."' method='POST'>";
                        if($entryCard["stat"]==1){
                            $info=$entryCard["data"];
                            foreach($info as $k=>$v){
                                $str.="<input type='text' name='".$k."' value='".$v."'>";
                            }
                            $str.="</form><script>document.form.submit();</script>";
                                    return $this->suc($str);
                        }else{
                            mlog($entryCard);
                        }
            return $this->err(9000,"绑定失败".$entryCard["errmsg"]);
    }
        //修改交易卡费率
    public function updateRateAc($id){
        $member=$this->Member->get_by_id($this->member_id);
        $credit=$this->Credit->get_by_map(["id"=>$id]);
         $set=$this->CreditSet ->get_by_id();
        $Quickpay=new \credit\Quickpay();
                        $para["subMerchantNo"]=$credit["subMerchantNo"];
                        $para["user_no"]=$credit["user_no"];
                        $para["config_no"]=$credit["config_no1"];
                        $para["rate"]=$set["rate"];
                        $para["single_payment"]=$set["single_payment"];
                        $entryCard=$Quickpay->updateRate($para);
                        if($entryCard["stat"]==1){
                            $data["rate"]=$set["rate"];
                            $data["single_payment"]=$set["single_payment"];
                            $this->Credit->editById($data,$credit["id"]);
                                        return $this->suc($entryCard["data"]["Resp_msg"]);
                        }
          return $this->err(9000,"更新失败".$entryCard["errmsg"]);
    }
    //绑定交易卡
    public  function notify_entryCardAc(){
        $input=input("post.");
        $get=input("get.");
        if(count($get)>2){
                    $msg="绑定成功";
                    return $this->display("绑定成功");   
        }
        $input=$input?$input:$get;
        mlog($input);
        if($input["Resp_code"]!=40000){
            return $this->err(9001,$input["Resp_msg"]);
        }
        if(empty($input["order_no"])){
            return $this->err(9001,"订单号出错");
        }
        $credit=$this->Credit->get_by_map(["order_no"=>$input["order_no"]]);
        if(!empty($credit["config_no1"])&&!empty($credit["rate"])&&!empty($credit["single_payment"])){
            exit("success");
        }
        $data['rate']=$input["rate"];
        $data['single_payment']=$input["single_payment"];
        $data['config_no1']=$input["config_no"];
        $stat=$this->Credit->editById($data,$credit["id"]);
         exit("fail");
        return $this->suc($stat);
    }
    //绑定交易卡
    public  function return_entryCardAc(){
          $input=input("post.");
          if($input['Resp_code']!=40000){
              $msg=$input['Resp_msg'];
              echo "<script>alert('{$msg}')</script>";
          }
          return $this->fetch();
        
    }
    //消费通知
    public  function notify_payAc(){
        $input=input("post.");

        mlog($input);
        if($input['Code']==10000&&$input['Resp_code']==40000){
            $stat=$this->update_order($input['order_no'],$input['ypt_order_no'],1,$input);
            if($stat){
                exit('success');
            }
            
        }elseif($input['Code']==10000){
            //付款失败
            $stat=$this->update_order($input['order_no'],$input['ypt_order_no'],6,$input);
        }
        exit('fail');
        
        
    }
    private function update_order($order_no,$ypt_order_no,$status,$input){
             
        $order=$this->CreditPlanItem->get_by_map(["order_no"=>$order_no]);

        if($order&&$order["status"]==3){
            $data['outer_no']=$ypt_order_no;
            $data['status']=$status;
            $data['update_time']=time();
            if($status==6){
                $data['reason']=$input["Resp_msg"];
            }
            $status1=$this->CreditPlanItem->editById($data,$order['id']);
            //设置计划状态
            $count=$this->CreditPlanItem->get_count(["credit_plan_id"=>$order["credit_plan_id"],"type"=>["in","1,2"],"status"=>1]);
            
            $count1=$this->CreditPlanItem->get_count(["credit_plan_id"=>$order["credit_plan_id"],"type"=>["in","1,2"]]);
        if($count>0&&$count==$count1){
            $this->CreditPlan->editById(["status"=>2],$order["credit_plan_id"]);
        }elseif($count==$count1){
            //执行完成
            $this->CreditPlan->editById(["status"=>5],$order["credit_plan_id"]);
            
        }
        //失败记录
        if($status==6){
             //$this->CreditPlan->editById(["status"=>4],$order["credit_plan_id"]);
        }
        }
        if($status1['stat']==1){
            return true;
        }
        return false;
    }
        //余额查询
    public function querybalanceAc($id){
        $credit=$this->Credit->get_by_map(["id"=>$id]);
        $Quickpay=new \credit\Quickpay();
                        $para["subMerchantNo"]=$credit["subMerchantNo"];
                        $entryCard=$Quickpay->querybalance($para);
            return $this->suc($entryCard);
    }
        //余额查询
    public function checkOrderAc($id,$yn){
        $credit=$this->Credit->get_by_map(["id"=>$id]);
        $Quickpay=new \credit\Quickpay();
                        $para["subMerchantNo"]=$credit["subMerchantNo"];
                         $para["ypt_order_no"]=$yn;
                        $entryCard=$Quickpay->checkOrder($para);
            return $this->suc($entryCard);
    }
        //提现查询
    public function checkInsteadPayOrderAc($id,$yn){
        $credit=$this->Credit->get_by_map(["id"=>$id]);
        $Quickpay=new \credit\Quickpay();
                        $para["subMerchantNo"]=$credit["subMerchantNo"];
                        $para["ypt_order_no"]=$yn;
                        $entryCard=$Quickpay->checkInsteadPayOrder($para);
            return $this->suc($entryCard);
    }
    public function get_configAc(){
        $data=model("CreditSet")->get_by_id();
        return $this->suc($data);
        
    }
}

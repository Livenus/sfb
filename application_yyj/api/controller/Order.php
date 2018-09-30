<?php

namespace app\api\controller;

use think\Config;
use think\Response;
use think\Db;
class Order extends \app\api\controller\Home {

    private $appid = "10058784";
    private $key = "45d20cccf34a4e50a984988295ad3ab2";

    public function _initialize() {
        $this->Channel = model("Channel");
        $this->Order = model("Order");
        $this->Msg = model("Msg");
        $this->Area = model("Area");
        $this->MemberGroupLog = model("MemberGroupLog");
        $this->Bank = model("Bank");
        $this->BankType = model("BankType");
        $this->BankType2 = model("BankType2");
        $this->MemberArea = model("MemberArea");
        $this->Member = model("Member");
        $this->MemberGroup = model("MemberGroup");
        $this->Member_login_token = model("Member_login_token");
        $this->MemberMonylog=model("member_monylog");
        $this->notify_url = url("api/order/notify", "", "", true);
        $this->notify_tongming = url("api/order/notify_tongming", "", "", true);
        $this->return_url = url("api/order/return_url", "", "", true);
        $this->notify_zhongshang = url("api/order/notify_zhongshang", "", "", true);
        $this->notify_tongfu = url("api/order/notify_tongfu", "", "", true);
    }

    //生成订单
    public function add_orderAc($money = 0, $bank_card_id = "", $pay_type_id = "") {
         $this->get_user_id();
        if(cache("name".$this->member_id)==$this->member_id){
            return $this->err(9100,"请求限制,间隔1秒");
        }
        cache('name'.$this->member_id, $this->member_id, 1);
        $check_res = $this->validate($_POST, "Order.add_order");
        if ($check_res !== true) {
            return $this->err("9007", $check_res);
        }
        $set=model("Setting")->select_all();
        $member_data = $this->Member->get_by_id($this->member_id);
        if ($member_data["is_rz_2"] == 1 && $member_data["is_rz_1"] == 1) {
            
        } else {
            return $this->err("9009", "未实名");
        }
        $member_area = $this->MemberArea->get_by_member_id($this->member_id);
        $group_data = $this->MemberGroup->get_by_id($member_data['membergroup_id']);
        //刷卡银行卡
        $bank = $this->Bank->get_by_id($this->member_id, $bank_card_id);
        if (empty($bank)) {
            return $this->err("9008", "未选择刷卡银行卡");
        }
        $channel_is=model("channel");
        $t=date("H:i:s");
        $channel_data=$channel_is->get_by_id($pay_type_id);
        if(empty($channel_data['id'])){
            return $this->err("9008", "支付通道未开启");
        }elseif ($money<$channel_data["min_money"]){
            return $this->err("9008", "金额不能小于最小金额");
            
        }elseif ($money>$channel_data["max_money"]){
            return $this->err("9008", "金额不能大于最大金额");
            
        }elseif ($t<$channel_data["start_time"]){
            return $this->err("9008", "未到开始时间");
            
        }elseif ($t>$channel_data["end_time"]){
            return $this->err("9008", "通道已到结束时间");
            
        }
        if ($money && $pay_type_id) {
            $data["member_id"] = $this->member_id;
            $data["amount"] = $money;
            $data["type"] = 1;
            $data["channel_id"] = $pay_type_id;
            $data["reason"] = "";
            $data["bank_num"]=$bank["bank_num"];
            $check_order = $this->validate($data, "Order.add_order_step");
            $order_data = $this->Order->addItem_id($data);
            $order_new = $this->Order->get_by_id($this->member_id, $order_data["data"]);
            if ($check_order !== true) {
                return $this->err("9003", $check_order);
            }

            //渠道
            $pay_class_p = $this->Channel->get_pay_way_parent($money, $pay_type_id);
            //通道
            $pay_class = $this->Channel->get_pay_way($money, $pay_type_id);
            //绑卡
            $class = $pay_class["type_class"];
            $rate_key=$pay_class["rate_type"];
            //实际刷卡费率
            $fee=$group_data[$rate_key];
            $fee_more=$group_data[$rate_key."_money"];
            if($fee_more==0){
                $fee_more=$channel_data["rate_money"];
            }
            $c = "ext\\" . $class;
            //fee03.8-10
            $rule = [
                "card" => "require",
                "name" => "require",
                "idcard" => "require",
                "mobile" => "require",
                "cityCode" => "require",
                "provinceCode" => "require",
                "address" => "require",
                "fee0" => "require",
                "pointsType" => "require"
            ];
            $pay_class_new = new $c;
//pointsType0有积分1无积分
            $mobile=$member_data['phone'];
            if($member_data['bank_phone']){
                $mobile=$member_data['bank_phone'];
            }
            $param = [
                'card' => $member_data['banknum'],
                'name' => $member_data['truename'],
                'idcard' => $member_data['idnum'],
                'mobile' => $mobile,
                'cityCode' => $member_area['bank_city'],
                'provinceCode' => $member_area['bank_province'],
                'address' => $member_area['detail'],
                'fee0' => $fee,
                'd0fee' => $fee_more*100,
                'pointsType' => $pay_class["pointsType"]
            ];
            $msg = [
                "card.require" => "卡号为空，未选择绑卡银行卡"
            ];
            $rule1 = $this->validate($param, $rule, $msg);
            if ($rule1 !== true) {
                return $this->err("9001", $rule1);
            }

            //更新订单
            $newdata["bank_back"]=$member_data['banknum'];
            $newdata["rate"]=$fee;
            $newdata["rate_money"]=$fee_more;
            $newdata["low_rate_money"]=$pay_class["low_rate_money"];
            $newdata["low_rate"]=$pay_class["low_fee"];
            $newdata["pay_type"]=$pay_class_p["name"];
            $newdata["pay_way"]=$pay_class["name"];
            $newdatar=$this->Order->editById($newdata,$order_new["id"]);
            //判断支付方式
            if(strtolower($class)=="kuaiyunzhong"){
                Config::load(APP_PATH."bank_name.php");
                $bank_name_code=config("bank_name_code");
                $para["orderAmt"]=$money*100;
                $para["merOrderId"]=$order_new["sn"];
                $para["returnUrl"]=$this->return_url;
                $para["notifyUrl"]=$this->notify_url;
                $para["goodsName"]="支付标题";
                $para["goodsDetail"]="支付详情";
                $para["rate"]=$fee/1000;
                $para["userFee"]=$fee_more*100;
                $para["payerAcc"]=$bank['bank_num'];
                $para["payerIdNum"]=$member_data['idnum'];
                $para["payerName"]=$member_data['truename'];
          $mobile=$member_data['bank_phone'];
            if($bank['bank_phone']){
                $mobile=$bank['bank_phone'];
            }
                $para["payerPhoneNo"]=$mobile;
                $para["payeeIdNum"]=$member_data['idnum'];
                $para["payeeBankCode"]=$bank_name_code[$member_data['bankname']];
                $para["payeeAcc"]=$member_data['banknum'];
                $para["payeeName"]=$member_data['truename'];
                $mobile=$member_data['phone'];
                 if($member_data['bank_phone']){
                $mobile=$member_data['bank_phone'];
                  }
                $para["payeePhoneNo"]=$mobile;
                $para["ip"]= real_ip();
                $para["timeStamp"]= date("YmdHis");
                $res=$this->Kuaiyunzhong($para);
                if($res["status"]==1){
                $reponse["id"] = $order_data["data"];
                $reponse["content"] = $res["html"];
                return $this->suc($reponse);
                }
                if(strpos($res["message"],"准贷记")){
                    $res["message"]=preg_replace('/[A-z0-9]/i', "", strip_tags($res["message"]));
                }elseif(strpos($res["message"],"暂停服务")){
                    
                    $res["message"]=preg_replace('/[A-z0-9]/i', "", strip_tags($res["message"]));
                    $res["message"]=$res["message"]."今天额度满了";
                    
                }else{
                    $res["message"]=preg_replace('/[A-z0-9]/i', "", strip_tags($res["message"]));
                }
                return $this->err("9009","(支付接口错误".$res["message"].")");
            }else if(strtolower($class)=="tongming"){
                //订单信息
                $para["amount"]=$money*100;
                $para["returnUrl"]=$this->return_url;
                $para["notifyUrl"]=$this->notify_tongming;
                $para["settRate"]=$fee;
                $para["remitServiceFee"]=$fee_more*100;
                $para["merOrderNo"]=$order_new["sn"];
                $para["cardNo"]=$bank['bank_num'];
                $para["cardName"]=$member_data['truename'];
                $para["cardCertNo"]=$member_data['idnum'];
                $mobile=$member_data['phone'];
               if($bank['bank_phone']){
                $mobile=$bank['bank_phone'];
               }
                $para["cardMobile"]=$mobile;
                $para["orderTime"]=date("YmdHis");
                $para["outerMerchId"]=$member_data['phone'];
                $para["merchId"]=$member_data['mem_id_t'];
                $para["acctNo"]=$member_data["banknum"];
                $para["acctBankname"]=$member_data['bankname'];
                $para["bankCardNo"]=$member_data['banknum'];
                $para["contactorNm"]=$member_data['truename'];
                $para["contactorNo"]=$member_data['idnum'];
                $mobile=$member_data['phone'];
               if($member_data['bank_phone']){
                $mobile=$member_data['bank_phone'];
               }
                $para["contactorCell"]=$mobile;
                $para["contactorMail"]="{$mobile}@qq.com";
                $para["name"]=$member_data['truename'];
                $para["merchAddress"]=$member_area['detail'];
                $bank_no=$this->BankType2->get_bankno($member_data["bankname"]);
                $para["bankNo"]=$bank_no;
                $pro=$this->Area->get_city_code($member_area['province']);
                $city=$this->Area->get_city_code($member_area['city']);
                $para["merchantProvinceCode"]=$pro;
                $para["merchantCityCode"]=$city;
                $para["bankName"]=$member_area['detail'];
                $para["t0tradeRate"]=$fee;
                $para["t0drawFee"]=$fee_more*100;
                $res=$this->Tongming($para); 
                if($res["stat"]==1){
                    $reponse["id"] = $order_data["data"];
                    $html= json_decode($res["data"],true);
                    $reponse["content"] = $html["html"];
                    //更新第三方订单
                    $this->Order->editById(["outer_sn" => $html['order_no']], $order_new["id"]);

                    return $this->suc($reponse);
                    
                }
                return $this->err("9010", "(支付接口错误".$res["errmsg"].$res["data"].")");
            }else if(strtolower($class)=="kuaijiezhifu_v2"){
                
                $res=$this->Kuaijiezhifu_v2($class,$pay_class_new,$param,$member_data,$bank,$fee,$fee_more,$order_new,$order_data); 
                return $res;
            }else if(strtolower($class)=="zhongshang"){
                if(empty($bank["bank_phone"])||empty($bank["validity"])||empty($bank["safe_num"])){
                    return $this->err(9005, "此通道信用卡手机号，有效期，安全码必填，请完善");
                }
                $bool=empty($member_area["id_province"])||empty($member_area["id_city"])||empty($member_area["id_county"])||empty($member_area["id_detail"]);
                $bool=(boolean)$bool;
                if(input("post.check_area")&&$bool){
                    return $this->err(9006, "此通道身份证省、市、区或县详细地址需完整");
                }elseif(input("post.check_area")&&!empty($member_area['id_province'])){
                $pro=$this->Area->get_by_name($member_area['id_province']);
                $city=$this->Area->get_by_name($member_area['id_city']);
                $county=$this->Area->get_by_name($member_area['id_county']);  
                }else{
                $pro=$this->Area->get_by_name($member_area['province']);
                $city=$this->Area->get_by_name($member_area['city']);
                $county=$this->Area->get_by_name($member_area['county']);   
                }

                $para["paymer_name"]=$member_data['truename'];
                $para["paymer_idcard"]=$member_data['idnum'];
                $para["paymer_bank_no"]=$member_data['banknum'];
                $para["paymer_phone"]=$member_data['bank_phone']?$member_data['bank_phone']:$member_data['phone'];
                $para["paymer_bank_no1"]=$bank['bank_num'];
                $para["paymer_phone1"]=$bank['bank_phone'];
                
                $para["bank_name"]=$member_data['bankname'];
                $para["bank_name1"]=$bank['bank_name'];
                $para["bank_branch"]=$member_data['bankname'];
                $para["bank_branch1"]=$bank['bank_name'];
                $bank_info=$this->BankType2->getBymap(["bank_name"=>$para['bank_name']]);
                $para["bank_code"]=$bank_info['bank_no'];
                 $para["bank_coding"]=$bank_info['short_name'];
                $bank_info=$this->BankType2->getBymap(["bank_name"=>$para['bank_name1']]);
                $para["bank_code1"]=$bank_info['bank_no'];
                $para["bank_coding1"]=$bank_info['short_name'];
                 $para["province"]=$pro["a_name"];
                 $para["city"]=$city["a_name"];
                 $para["county"]=$county["a_name"];
                 $para["address"]=$member_area['id_detail']?$member_area['id_detail']:$member_area['detail'];
                $para["rate"]=$fee/10;
                $para["single_payment"]=$fee_more;
                  $para["price"]=$money;
                $para["order_no"]=$order_new["sn"];
                  $para["returnUrl"]=$this->return_url;
                 $para["notifyUrl"]=$this->notify_zhongshang;
                 Db::startTrans();
                 $member_lock=$this->Member->get_by_sn_lock($this->member_id);
                $res=$this->Zhongshang($para,$bank);
                if($res['stat']==1){
                    $reponse["id"] = $order_data["data"];
                    $reponse["content"] = $res['data']["url"];
                   Db::commit();
                    return $this->suc($reponse);
                }
                Db::commit();
                return $this->err(9010, "错误信息".$res["errmsg"]);
            }else if(strtolower($class)=="tongfu"){
        $allproduct=[
            "36"=>"工商银行 中国银行 建设银行 光大银行 北京银行 民生银行 广发银行 兴业银行 平安银行 交通银行 农业银行 国家开发银行 中国进出口银行 中国农业发展银行 中信银行 华夏银行 招商银行 恒丰银行 浙商银行 汇丰银行 东亚银行 南洋商业银行 恒生银行 深圳发展银行 杭州银行 南京银行 北京农村商业银行",
            "37"=>"工商银行 中国银行 建设银行 中信银行 广发银行 民生银行 上海银行 平安银行 华夏银行 农业银行 北京银行",
            "38"=>"光大银行 浦发银行 交通银行 邮储银行 兴业银行 招商银行",
             "39"=>"恒丰银行 浙商银行 渤海银行 厦门银行 海峡银行 吉林银行 宁波银行 齐鲁银行 温州银行 广州银行 汉口银行 大连银行 苏州银行 河北银行 杭州银行 南京银行 东莞银行 成都银行 天津银行 宁夏银行 哈尔滨银行 徽商银行 重庆银行 西安银行 青岛银行 吉林市商业银行 长沙银行 泉州银行 内蒙古银行 南宁银行 包商银行 连云港银行 连云港银行 南粤银行 桂林银行 徐州银行 柳州银行 温州市商业银行 武汉市商业银行 江苏银行股份有限公司 江苏银行 稠州银行 厦门国际 海南银行 邯郸银行 三湘银行"
        ];
        $allbank=$allproduct[$pay_type_id];
        if(strpos($allbank, $bank["bank_name"])===FALSE){
            return $this->err(9000,"此通道不支持该银行");
        }
                if(empty(input("post.check_bank"))){
                    return $this->err(9110,"请升级软件");
                }
                if(empty($bank["bank_phone"])||empty($bank["validity"])||empty($bank["safe_num"])){
                    return $this->err(9005, "此通道信用卡手机号，有效期，安全码必填，请完善");
                }
                $bool=empty($member_area["province"])||empty($member_area["city"])||empty($member_area["county"])||empty($member_area["detail"]);
                $bool=(boolean)$bool;
                if(input("post.check_area")&&$bool){
                    return $this->err(9006, "此通道银行卡省、市、区或县详细地址需完整");
                }
                $pro=$this->Area->get_by_name($member_area['province']);
                $city=$this->Area->get_by_name($member_area['city']);
                $county=$this->Area->get_by_name($member_area['county']);   
                if(input("post.check_bank")&&$bank["tongfu"]=="BDC0006"&&input("post.step")==1){
                    return $this->err(9111,"可以刷卡");
                } elseif(input("post.check_bank")&&$bank["tongfu"]!="BDC0006"&&input("post.step")==1) {
                    return $this->err(9112,"未绑定");
                }
                $para["orderNum"]=$order_new["sn"];
                $para["merchantName"]=$member_data['truename'];
                $para["merchantNo"]=$member_data['mem_tongfu'];
                $para["shortName"]=$member_data['truename'];
                $para["idNo"]=$member_data['idnum'];
                $para["address"]=$member_area['id_detail']?$member_area['id_detail']:$member_area['detail'];
                $para["province"]=$pro["a_name"];
                $para["city"]=$city["a_name"];
                $para["subBank"]=$member_data['bankname'];
                $bank_info=$this->BankType2->getBymap(["bank_name"=>$member_data['bankname']]);
                $para["bankChannelNo"]=$bank_info['bank_no'];
                $para["email"]=$bank['bank_phone']."@qq.com";
                $para["mobile"]=$member_data['bank_phone']?$member_data['bank_phone']:$member_data['phone'];
                 $para["bankCard"]=$member_data['banknum'];
                 $para["bankLinked"]=$bank_info['bank_no1'];
                $para["accountName"]=$member_data['truename'];
                $para["creditRate"]=$fee/1000;
                $para["withdrawFee"]=$fee_more*100;
                $bank_info=$this->BankType2->getBymap(["bank_name"=>$bank['bank_name']]);
                $para["bankCard1"]=$bank['bank_num'];
                $para["mobile1"]=$bank['bank_phone'];
                $para["cvn2"]=$bank['safe_num'];
                $para["expired"]=date("ym",$bank["validity"]);
                 $para["bankLinked1"]=$bank_info['bank_no1'];
                 
                 $para["payMoney"]=$money*100;
                 $para["notifyUrl"]=$this->notify_tongfu;
                 $para["productName"]="省付吧";
                  $para["productDesc"]="省付吧";
                $res=$this->Tongfu($para,$bank,$pay_type_id);
                if($res['stat']==10){
                    $reponse["id"] = $order_data["data"];
                    $reponse["content"] = url("api/order/tongfusms", ["id"=>$bank['id'],"bank_num"=>$bank['bank_num'],"sn"=>$order_new['sn']], "", true);
                    return $this->err(9115,$reponse["content"]);
                }elseif($res['stat']==1){
                    
                     return $this->suc("刷卡成功");
                }

                return $this->err(9010, "错误信息".$res["errmsg"]);
            }
            $path= strtolower($class);
            Config::load(APP_PATH.$path."_config.php");
            $path_key=config($path);
            $this->appid=$path_key["appid"];
            $this->key=$path_key["key"];
            $con = $pay_class_new->setAppid($this->appid)->setKey($this->key)->setNotifyUrl($this->notify_url . "?class=" . $class);
            $pay_class_new->setReturnUrl($this->return_url);
            
            //绑卡
            $card_id = $con->bindCard($param);
            if($card_id["stat"]==1){
                $mem_id=$card_id['data'];
                //保存商户ID
                $mem_info["mem_id"]=$mem_id;
                $res=$this->Member->editById($mem_info,$this->member_id);
                $member_data["mem_id"]=$mem_id;
                // mlog($res);
            }elseif($card_id["errcode"]=="00"){
                
                $card_id["data"]=$member_data["mem_id"];
            }else{
             return $this->err("9003", "设置收款银行错误，您提交设置收款银行卡{$param['card']}身份证{$param['idcard']},请核对您实名认证绑定的收款银行卡"."(支付接口错误".$card_id["errmsg"].")" );   
            }

            $blid=$param;
            //修改费率
            $blid["mchId"]=$card_id["data"];
            $blidd = $con->modifyBind($blid,"card");
            //$blidd = $con->modifyBind($blid,"rate");
            if (is_numeric($card_id['data'])&&$blidd["stat"]==1) {
                //更新商户id
                 if(is_numeric($blidd["data"])&&$blidd["data"]>0){
                   $mem_info["mem_id"]=$blidd["data"];
                   $res=$this->Member->editById($mem_info,$this->member_id); 
                    $member_data["mem_id"]=$blidd["data"];
                 }

                
            } else {

                return $this->err("9002", "设置(修改)收款银行错误，您提交设置收款银行卡{$param['card']}身份证{$param['idcard']},请核对您实名认证绑定的收款银行卡"."(支付接口错误".$blidd["errmsg"].")" );
            }
            //$card_id=$con->bindcard($param,'rate');
            $rule2 = [
                "card" => "require",
                "name" => "require",
                "idcard" => "require",
                "mobile" => "require",
                "totalFee" => "require",
                "agentOrderNo" => "require",
                "mchId" => "require"
            ];
            $mobile=$member_data['bank_phone'];
            if($bank['bank_phone']){
                $mobile=$bank['bank_phone'];
            }
            $order_re = [
                'card' => $bank['bank_num'],
                'name' => $member_data['truename'],
                'idcard' => $member_data['idnum'],
                'mobile' => $mobile,
                'fee0' => $fee,
                'd0fee' => $fee_more*100,
                'totalFee' => $order_new["amount"]*100,
                'agentOrderNo' => $order_new["sn"],
                'mchId' => $card_id['data']
            ];
            $rule2_res = $this->validate($order_re, $rule2);
            if ($rule2_res !== true) {
                return $this->err("9000", $rule2_res);
            }
            $r = $con->order($order_re);
            if ($r["data"]["order_outer"]) {
                $out_sn = $r["data"]["order_outer"];
                $id = $order_data["data"];
                $this->Order->editById(["outer_sn" => $out_sn], $id);
            } else {

                return $this->err("9001", $r["errmsg"]);
            }
            if ($r["data"]["html"]) {
                $reponse["id"] = $order_data["data"];
                $reponse["content"] = $r["data"]["html"];
                return $this->suc($reponse);
                //return $this->display($r["data"]["html"]);
            }
        }
    }
    //快捷支付V——2
    public function Kuaijiezhifu_v2($class,$pay_class_new,$param,$member_data,$bank,$fee,$fee_more,$order_new,$order_data){
           $path= strtolower($class);
            Config::load(APP_PATH.$path."_config.php");
            $path_key=config($path);
            $this->appid=$path_key["appid"];
            $this->key=$path_key["key"];
            $con = $pay_class_new->setAppid($this->appid)->setKey($this->key)->setNotifyUrl($this->notify_url . "?class=" . $class);
            $pay_class_new->setReturnUrl($this->return_url);
            
            //绑卡
            $card_id = $con->bindCard($param);
            if($card_id["stat"]==1){
                $mem_id=$card_id['data'];
                //保存商户ID
                $mem_info["mem_id_new"]=$mem_id;
                $res=$this->Member->editById($mem_info,$this->member_id);
                $member_data["mem_id_new"]=$mem_id;
                // mlog($res);
            }elseif($card_id["errcode"]=="00"){
                if(empty($member_data["mem_id_new"])){
                   $mem_info["mem_id_new"]=$member_data["mem_id"];
                   $res=$this->Member->editById($mem_info,$this->member_id); 
                }
                $card_id["data"]=$member_data["mem_id_new"]?$member_data["mem_id_new"]:$member_data["mem_id"];
            }else{
             return $this->err("9003", "设置收款银行错误，您提交设置收款银行卡{$param['card']}身份证{$param['idcard']},请核对您实名认证绑定的收款银行卡"."(支付接口错误".$card_id["errmsg"].")" );   
            }

            $blid=$param;
            //修改费率
            $blid["mchId"]=$card_id["data"];
            $blidd = $con->modifyBind($blid,"card");
            //$blidd = $con->modifyBind($blid,"rate");
            if (is_numeric($card_id['data'])&&$blidd["stat"]==1) {
                //更新商户id
                 if(is_numeric($blidd["data"])&&$blidd["data"]>0){
                   $mem_info["mem_id_new"]=$blidd["data"];
                   $res=$this->Member->editById($mem_info,$this->member_id); 
                    $member_data["mem_id_new"]=$blidd["data"];
                 }

                
            } else {

                return $this->err("9002", "设置(修改)收款银行错误，您提交设置收款银行卡{$param['card']}身份证{$param['idcard']},请核对您实名认证绑定的收款银行卡"."(支付接口错误".$blidd["errmsg"].")" );
            }
            //$card_id=$con->bindcard($param,'rate');
            $rule2 = [
                "card" => "require",
                "name" => "require",
                "idcard" => "require",
                "mobile" => "require",
                "totalFee" => "require",
                "agentOrderNo" => "require",
                "mchId" => "require"
            ];
            $mobile=$member_data['bank_phone'];
            if($bank['bank_phone']){
                $mobile=$bank['bank_phone'];
            }
            $order_re = [
                'card' => $bank['bank_num'],
                'name' => $member_data['truename'],
                'idcard' => $member_data['idnum'],
                'mobile' => $mobile,
                'fee0' => $fee,
                'd0fee' => $fee_more*100,
                'totalFee' => $order_new["amount"]*100,
                'agentOrderNo' => $order_new["sn"],
                'mchId' => $card_id['data']
            ];
            $rule2_res = $this->validate($order_re, $rule2);
            if ($rule2_res !== true) {
                return $this->err("9000", $rule2_res);
            }
            $r = $con->order($order_re);
            if ($r["data"]["order_outer"]) {
                $out_sn = $r["data"]["order_outer"];
                $id = $order_data["data"];
                $this->Order->editById(["outer_sn" => $out_sn], $id);
            } else {

                return $this->err("9001", $r["errmsg"]);
            }
            if ($r["data"]["html"]) {
                $reponse["id"] = $order_data["data"];
                $reponse["content"] = $r["data"]["html"];
                return $this->suc($reponse);
                //return $this->display($r["data"]["html"]);
            }
        
        
    }
    //百世易
    public function Kuaiyunzhong($para){
         $c = "ext\Kuaiyunzhong" ;
         $kufu=new $c();
         $res=$kufu->bindCard($para);
          if($res["status"]!=0){
               $res["status"]=1;
                return $res;
          }
          $res["status"]=0;
          return $res;
        
    }
    //同名
    public function Tongming($para){
         $c = "ext\Tongming" ;
         $kufu=new $c();
          if(empty($para["merchId"])||$para["merchId"]=="500100"){
         $res=$kufu->bindCard($para);
                  //绑卡
            $num= preg_match("/[0-9]+/", $res["data"],$match);
            if($res["stat"]==0){
                return $res;
            }
            $mem_info["mem_id_t"]=$match[0];

            if($mem_info["mem_id_t"]){
              $res=$this->Member->editById($mem_info,$this->member_id);  
            }
            
            $memid=$mem_info["mem_id_t"];
          }else if(!empty($para["merchId"])){
                    //设置卡信息
                   $res1=$kufu->modifyBind($para,'card'); 
                   if($res1["stat"]==1){
                       $num= preg_match("/[0-9]+/", $res1["data"],$match);
                       $mem_info["mem_id_t"]=$match[0];
                       if($mem_info["mem_id_t"]){
                       $memid=$mem_info["mem_id_t"];
                       $para["merchId"]=$memid; 
                       }

                   }else if($res1["errcode"]=='9999'){
                       $r["errmsg"]="换绑失败".$res1["errmsg"];
                        $r["data"]=$res1["data"];
                        $r["stat"]=0;
                        return $r; 
                   }
                   $res2=$kufu->modifyBind($para,'rate'); 
                   if($res2["stat"]==1){
                       $num=preg_match("/[0-9]+/", $res2["data"],$match);
                       $mem_info["mem_id_t"]=$match[0];
                       if($mem_info["mem_id_t"]){
                       $memid=$mem_info["mem_id_t"];
                       $para["merchId"]=$memid;
                       }
                   }
                   

                   $memid=$para["merchId"];
                   //改卡成功
                   if(empty($memid)){
                       $r["errmsg"]="换绑失败".$res1["errmsg"];
                        $r["data"]=$res1["data"];
                        $r["stat"]=0;
                    return $r;
                   }else{
                       $mem_info_e["mem_id_t"]=$memid;
                       $res=$this->Member->editById($mem_info_e,$this->member_id);
                       
                   }     
            }
                //开始刷卡
                if($memid){
                        $para["merchId"]=$memid;
                    $res=$kufu->order($para);
                    return $res;
                }
                return $res;
        
    }
    //同名支付发短信
    public function tongming_secAc($para,$expiredate,$cvvcode){
        if($para&&$expiredate&&$cvvcode){
         $para= json_decode($para,true);
         $para["expiredate"]=$expiredate;
         $para["cvvcode"]=$cvvcode;
         $c = "ext\Tongming" ;
         $kufu=new $c();
         $res=$kufu->order($para);
         if($res["stat"]==1){
          $url= url("order/tongming_sec_2", "", "", true);
          $sdata= json_encode($para);
          $html='<form action="'.$url.'"><input type="hidden" name="para" value="'.$sdata.'"><input name="smsVerifyCode" placeholder="短信验证码"><button>确认刷卡</button></form>';
          return  $this->fetch($html);

         }
         return $res["errmsg"];
        }
        
        $this->redirect("order/return_url",array("out_trade_no"=>$para['merOrderId'])); 
        
        
    }
    //同名支付支付
    public function tongming_sec_2Ac($para,$smsVerifyCode){
        if($para&&$smsVerifyCode){
         $para= json_decode($para,true);
         $para["smsVerifyCode"]=$smsVerifyCode;
         $c = "ext\Tongming" ;
         $kufu=new $c();
         $res=$kufu->order_step($para);
         if($res["stat"]=='1'){
            // mlog($res);
         }
        }
        
        $this->redirect("order/return_url",array("out_trade_no"=>$para['merOrderId'])); 
        
        
    }
    //众商
    public function  Zhongshang($para,$bank=""){
        //储蓄入网
        $member_data = $this->Member->get_by_id($this->member_id);
        $Quickpay=new \ext\Zhongshang();
        if(empty($member_data["authent_no"])){
            $para0["paymer_name"]=$para['paymer_name'];
            $para0["paymer_idcard"]=$para['paymer_idcard'];
            $para0["paymer_bank_no"]=$para['paymer_bank_no'];
            $para0["paymer_phone"]=$para['paymer_phone'];
            $authent=$Quickpay->authent($para0);
        }
        if($authent['stat']==0&&empty($member_data["authent_no"])){
            return $authent;
        }elseif($authent['stat']==1){
                 $data["authent_no"]=$authent['data']["authent_no"];
                $this->Member->editById($data,$member_data["id"]);
                $member_data["authent_no"]=$data["authent_no"];
        }
        if(empty($member_data["user_no"])){

                $para1["auth_order_no"]=$data["authent_no"]?$data["authent_no"]:$member_data["authent_no"];
                $para1["user_name"]=$para['paymer_name'];
                $para1["id_no"]=$para['paymer_idcard'];
                $para1["card_no"]=$para['paymer_bank_no'];
                $para1["phone"]=$para['paymer_phone'];
                $para1["bank_name"]=$para['bank_name'];
                $para1["bank_branch"]=$para['bank_branch'];
                $para1["bank_code"]=$para['bank_code'];
                $para1["bank_coding"]=$para['bank_coding'];
                 $para1["province"]=$para['province'];
                 $para1["city"]=$para['city'];
                 $para1["county"]=$para['county'];
                 $para1["address"]=$para['address'];
                 $para1["bank_type"]=1;
                $enterNet=$Quickpay->enterNet($para1);   

        


        }
       if($enterNet['stat']==0&&empty($member_data["user_no"])&&empty($enterNet["user_no"])){
            return $enterNet;
        }elseif($enterNet['stat']==1){
      $data1["user_no"]=$enterNet['data']["user_no"];
       $this->Member->editById($data1,$member_data["id"]);
                       $member_data["user_no"]=$data1["user_no"];

        }
        if(empty($bank["authent_no"])){
            $para0["paymer_name"]=$para['paymer_name'];
            $para0["paymer_idcard"]=$para['paymer_idcard'];
            $para0["paymer_bank_no"]=$para['paymer_bank_no1'];
            $para0["paymer_phone"]=$para['paymer_phone1'];
            $authent=$Quickpay->authent($para0);
        }
        if($authent['stat']==0&&empty($bank["authent_no"])){
            return $authent;
        }elseif($authent['stat']==1){
                $datab["authent_no"]=$authent['data']["authent_no"];
                $this->Bank->editById($datab,$bank["id"]);
                $bank["authent_no"]=$datab["authent_no"];
        }
               //信用卡入网
        if(empty($bank["user_no"])){
                $para1["auth_order_no"]=$datab["authent_no"]?$datab["authent_no"]:$bank["authent_no"];
                $para1["user_name"]=$para['paymer_name'];
                $para1["id_no"]=$para['paymer_idcard'];
                $para1["card_no"]=$para['paymer_bank_no1'];
                $para1["phone"]=$para['paymer_phone1'];
                $para1["bank_name"]=$para['bank_name1'];
                $para1["bank_branch"]=$para['bank_branch1'];
                $para1["bank_code"]=$para['bank_code1'];
                $para1["bank_coding"]=$para['bank_coding1'];
                 $para1["province"]=$para['province'];
                 $para1["city"]=$para['city'];
                 $para1["county"]=$para['county'];
                 $para1["address"]=$para['address'];
                 $para1["validity"]=date("my",$bank["validity"]);
                 $para1["cvv2"]=$bank['safe_num'];
                 $para1["bank_type"]=6;
                $enterNet=$Quickpay->enterNet($para1);   
        }
        if($enterNet['stat']==0&&empty($bank["user_no"])){
            return $enterNet;
        }elseif($enterNet['stat']==1){
           $datab1["user_no"]=$enterNet['data']["user_no"];
           $this->Bank->editById($datab1,$bank["id"]);
                           $bank["user_no"]=$datab1["user_no"];
        }
        $oldrate=$para["rate"]+$para["single_payment"];
      //进件
        if(empty($member_data["config_no"])||$member_data["lastrate"]!=$oldrate){
       $para2["user_no"]=$member_data["user_no"];
       $para2["rate"]=$para["rate"];
       $para2["single_payment"]=$para["single_payment"];
        $entryCard1=$Quickpay->entryCard1($para2);   
        if($entryCard1['stat']==0){
            return $entryCard1;
        }
      $data11["lastrate"]=$para["rate"]+$para["single_payment"];
      $data11["config_no"]=$entryCard1['data']["config_no"];
       $this->Member->editById($data11,$member_data["id"]);
       $member_data["lastrate"]=$data11["lastrate"];
       $member_data["config_no"]=$data11["config_no"];
        }
        //信用卡进件
        if(empty($bank["config_no"])){
       $para2["user_no"]=$bank["user_no"];
       $para2["rate"]=$para["rate"];
       $para2["single_payment"]=$para["single_payment"];
       $entryCard1=$Quickpay->entryCard1($para2);   
        if($entryCard1['stat']==0){
            return $entryCard1;
        }
      $datab2["config_no"]=$entryCard1['data']["config_no"];
       $this->Bank->editById($datab2,$bank["id"]);
       $bank["config_no"]=$datab2["config_no"];
        }
        //刷卡
       $para22["bank_user_no"]=$bank["user_no"];
       $para22["user_no"]=$member_data["user_no"];
       $para22["config_no"]=$member_data["config_no"];
       $para22["price"]=$para["price"];
       $para22["order_no"]=$para["order_no"];
       $para22["returnUrl"]=$para["returnUrl"];
       $para22["notifyUrl"]=$para["notifyUrl"];
        $Pay1=$Quickpay->Pay1($para22);   
        return $Pay1;
    }
    public function get_order_by_idAc($id = "") {
        $this->get_user_id();
        $rule = [
            "id" => "require|number"
        ];
        $data["id"] = $id;
        $check = $this->validate($data, $rule);
        if ($check !== true) {
            return $this->err("9000", $check);
        }
        $this->get_user_id();
        $data = $this->Order->get_by_id($this->member_id, $id);
        if ($data) {
            return $this->suc($data);
        }
        return $this->err("900", "查询失败");
    }
    public function get_order_by_id_remoteAc($id = "") {
        $rule = [
            "id" => "require|number"
        ];
        $data["id"] = $id;
        $check = $this->validate($data, $rule);
        if ($check !== true) {
            return $this->err("9000", $check);
        }
        $data = $this->Order->get_by_id_o($id);
            $param["agentOrderNo"] = $data["sn"];
            $pay_class_new = new \ext\Kuaijiezhifu;

            $con = $pay_class_new->setAppid($this->appid)->setKey($this->key)->setNotifyUrl($this->notify_url);
            $param["start"]=date("Y-m-d",$data["add_time"]-24*3600*60);
            $param["end"]=date("Y-m-d",$data["add_time"]+24*3600);
            $card_id = $con->getSnbill($param);
            $all=$this->Order->select_all(array("stat"=>1,'channel_id'=>27));
            $all_key= array_column($all, "sn");
            $base= $card_id['data'];
            $data["count"]=count($card_id['data']);
            $data["remote"]=$card_id;
            
        if ($data) {
            return $this->suc($data);
        }
        return $this->err("900", "查询失败");
    }
    //同付
    public function  Tongfu($para,$bank="",$pay_type_id=36){
        $allproduct=[
            "36"=>"工商银行 中国银行 建设银行 光大银行 北京银行 民生银行 广发银行 兴业银行 平安银行 交通银行 农业银行 国家开发银行 中国进出口银行 中国农业发展银行 中信银行 华夏银行 招商银行 恒丰银行 浙商银行 汇丰银行 东亚银行 南洋商业银行 恒生银行 深圳发展银行 杭州银行 南京银行 北京农村商业银行",
            "37"=>"工商银行 中国银行 建设银行 中信银行 广发银行 民生银行 上海银行 平安银行 华夏银行 农业银行 北京银行",
            "38"=>"光大银行 浦发银行 交通银行 邮储银行 兴业银行 招商银行",
             "39"=>"恒丰银行 浙商银行 渤海银行 厦门银行 海峡银行 吉林银行 宁波银行 齐鲁银行 温州银行 广州银行 汉口银行 大连银行 苏州银行 河北银行 杭州银行 南京银行 东莞银行 成都银行 天津银行 宁夏银行 哈尔滨银行 徽商银行 重庆银行 西安银行 青岛银行 吉林市商业银行 长沙银行 泉州银行 内蒙古银行 南宁银行 包商银行 连云港银行 连云港银行 南粤银行 桂林银行 徐州银行 柳州银行 温州市商业银行 武汉市商业银行 江苏银行股份有限公司 江苏银行 稠州银行 厦门国际 海南银行 邯郸银行 三湘银行"
        ];
        $allbank=$allproduct[$pay_type_id];
        if(strpos($allbank, $bank["bank_name"])===FALSE){
            return ['stat'=>0,'errmsg'=>"不支持的银行"];
        }
        //储蓄入网
        $member_data = $this->Member->get_by_id($this->member_id);
        $Tongfu=new \ext\Tongfu();
        if($pay_type_id==37){
            $Tongfu->chanPay="00";
        }elseif($pay_type_id==38){
            $Tongfu->chanPay="01";
        }elseif($pay_type_id==39){
            $Tongfu->chanPay="03";
        }else{
            $Tongfu->chanPay="02";
        }
        if(empty($para["merchantNo"])){
        $para0 = [
            'orderNum'         => $para["orderNum"],
             'merchantName'         => $para["merchantName"],
             'shortName'         => $para["shortName"],
             'idNo'         => $para["idNo"],
             'address'         => $para["address"],
             'province'         => $para["province"],
             'city'         => $para["city"],
             'subBank'         => $para["subBank"],
             'bankChannelNo'         => $para["bankChannelNo"],
              'email'         => $para["email"],
              'mobile'         => $para["mobile"],
              'bankCard'         => $para["bankCard"],
               'bankLinked'         => $para["bankLinked"],
              'accountName'         => $para["accountName"],
               'withdrawFee'         => $para["withdrawFee"],
               'creditRate'         => $para["creditRate"],
        ];
            $blind=$Tongfu->bindCard($para0);
            //更新商户号
            if($blind["stat"]==1){
                $data0["mem_tongfu"]=$blind["data"];
                if($Tongfu->chanPay=="00"){
                   $data0["tongfu_fee_00"]=$para["creditRate"]; 
                }elseif ($Tongfu->chanPay=="01") {
                    $data0["tongfu_fee_01"]=$para["creditRate"]; 
                }elseif ($Tongfu->chanPay=="03") {
                    $data0["tongfu_fee_03"]=$para["creditRate"]; 
                }else{
                    $data0["tongfu_fee_02"]=$para["creditRate"]; 
                }
                
                $this->Member->editById($data0,$member_data["id"]);
            }else{
                
                return $blind;
            }
    }else{
                $para000 = [
            'merchantNo'         => $para["merchantNo"]
        ];
            $query=$Tongfu->queryCard($para000);
        if($query["data"]["idNo"]==$para["idNo"]&&$query["data"]["bankCard"]==$para["bankCard"]){
            
        }else{
            //修改卡信息
                $para001 = [
            'merchantNo'         => $para["merchantNo"],
            'mobile'         => $para["mobile"],
            'bankCard'         => $para["bankCard"],
          'accountName'         => $para["merchantName"],
          'subBank'         => $para["subBank"],
          'bankChannelNo'         => $para["bankChannelNo"],  
          'province'         => $para["province"],  
          'city'         => $para["city"],  
          'bankLinked'         => $para["bankLinked"],      
        ];
            $change=$Tongfu->changeCard($para001);
            if($change['stat']==0){
                return $change;
                
            }
        }
    }
    //开通产品
    if($Tongfu->chanPay=="00"){
        if(empty($member_data["tongfu_fee_00"])){
                    $para0000 = [
            'merchantNo'         => $para["merchantNo"]?$para["merchantNo"]:$blind["data"],
            'creditRate'         => $para["creditRate"],
        ];
                    $status0000=$Tongfu->bindopen($para0000);
                    if($status0000["stat"]==1){
                $data0000["tongfu_fee_00"]=$para["creditRate"];
                $this->Member->editById($data0000,$member_data["id"]);
                    }else{
                        return $status0000;
                    }
        }
        
        
    }elseif($Tongfu->chanPay=="01"){
        if(empty($member_data["tongfu_fee_01"])){
                    $para0000 = [
            'merchantNo'         => $para["merchantNo"]?$para["merchantNo"]:$blind["data"],
            'creditRate'         => $para["creditRate"],
        ];
                    $status0000=$Tongfu->bindopen($para0000);
                    if($status0000["stat"]==1){
                $data0000["tongfu_fee_01"]=$para["creditRate"];
                $this->Member->editById($data0000,$member_data["id"]);
                    }else{
                        return $status0000;
                    }
        }
        
        
    }elseif($Tongfu->chanPay=="02"){
        if(empty($member_data["tongfu_fee_02"])){
                    $para0000 = [
            'merchantNo'         => $para["merchantNo"]?$para["merchantNo"]:$blind["data"],
            'creditRate'         => $para["creditRate"],
        ];
                    $status0000=$Tongfu->bindopen($para0000);
                    if($status0000["stat"]==1){
                $data0000["tongfu_fee_02"]=$para["creditRate"];
                $this->Member->editById($data0000,$member_data["id"]);
                    }else{
                        return $status0000;
                    }
        }
        
        
    }elseif($Tongfu->chanPay=="03"){
        if(empty($member_data["tongfu_fee_03"])){
                    $para0000 = [
            'merchantNo'         => $para["merchantNo"]?$para["merchantNo"]:$blind["data"],
            'creditRate'         => $para["creditRate"],
        ];
                    $status0000=$Tongfu->bindopen($para0000);
                    if($status0000["stat"]==1){
                $data0000["tongfu_fee_03"]=$para["creditRate"];
                $this->Member->editById($data0000,$member_data["id"]);
                    }else{
                        return $status0000;
                    }
        }
        
        
    }
    
    //更新费率
       $member_data = $this->Member->get_by_id($this->member_id);
       if($Tongfu->chanPay=="00"){
           $bld=$bank["tongfu_00"]!="BDC0006";
       }elseif($Tongfu->chanPay=="01"){
            $bld=$bank["tongfu_01"]!="BDC0006";
       }elseif($Tongfu->chanPay=="03"){
            $bld=$bank["tongfu_03"]!="BDC0006";
       }else{
            $bld=$bank["tongfu"]!="BDC0006";
       }
       //绑定交易卡
        if($bld){
        $para1 = [
            'merchantNo'         => $para["merchantNo"]?$para["merchantNo"]:$blind["data"],
             'orderNum'         => $para["orderNum"],
             'bankCard'         => $para["bankCard1"],
             'accountName'         => $para["accountName"],
             'mobile'         => $para["mobile1"],
             'idNo'         => $para["idNo"],
             'cvn2'         => $para["cvn2"],
             'expired'         => $para["expired"],
             'bankLinked'         => $para["bankLinked1"]
        ];
            $blindfee=$Tongfu->bindfeeCard($para1);
            if($blindfee["stat"]==1){

        if($Tongfu->chanPay=="00"){
                             $data1["tongfu_00"]=$blindfee["data"];
                $data1["tongfu_ordernum_00"]=$para["orderNum"];
        }elseif($Tongfu->chanPay=="01"){
                             $data1["tongfu_01"]=$blindfee["data"];
                $data1["tongfu_ordernum_01"]=$para["orderNum"];
        }elseif($Tongfu->chanPay=="03"){
                             $data1["tongfu_03"]=$blindfee["data"];
                $data1["tongfu_ordernum_03"]=$para["orderNum"];
        }else{
                             $data1["tongfu"]=$blindfee["data"];
                            $data1["tongfu_ordernum"]=$para["orderNum"];
        }
                $this->Bank->editById($data1,$bank["id"]);
            }else{
                return $blindfee;
            }
        }
        if($Tongfu->chanPay=="00"){
             $blild=$blindfee["data"]?$blindfee["data"]:$bank["tongfu_00"];
        }elseif($Tongfu->chanPay=="01"){
                    $blild=$blindfee["data"]?$blindfee["data"]:$bank["tongfu_01"];
        }elseif($Tongfu->chanPay=="03"){
                    $blild=$blindfee["data"]?$blindfee["data"]:$bank["tongfu_03"];
        }else{
             $blild=$blindfee["data"]?$blindfee["data"]:$bank["tongfu"];
        }
        if(empty($blild)){
             $blild=$blindfee["data"]?$blindfee["data"]:$bank["tongfu"];
        }

        //开始刷卡
        if($blild=="BDC0006"){
        $para2 = [
            'merchantNo'         => $para["merchantNo"]?$para["merchantNo"]:$blind["data"],
             'orderNum'         => $para["orderNum"],
             'bankCard'         => $para["bankCard1"],
             'cvn2'         => $para["cvn2"],
             'expired'         => $para["expired"],
             'payMoney'         => $para["payMoney"],
             'productName'         => $para["productName"],
             'productDesc'         => $para["productDesc"],
             'notifyUrl'         => $this->notify_tongfu,
        ];
            $blindfee1=$Tongfu->order($para2);
            
            return $blindfee1;
        }else{
            
            return message(10,"信用卡待激活");
        }
        }
        //同付绑定卡
        public function tongfusmsAc(){
            $this->get_user_id();
            $id=input("id");
            if(request()->ispost()){
            if(empty($id)|| !is_numeric($id)){
                return $this->err("9006","卡号必填");
            }

            $orderNum=input("sn");
            $smsCode=input("code");
            if(empty($smsCode)||empty($orderNum)||empty($id)){
                 return $this->err("9006","短信验证码订单号银行卡必填");
            }
        $member_data = $this->Member->get_by_id($this->member_id); 
           $Tongfu=new \ext\Tongfu();
           $order=$this->Order->get_by_sn($orderNum);
           $pay_type_id=$order["channel_id"];
        if($pay_type_id==37){
            $Tongfu->chanPay="00";
        }elseif($pay_type_id==38){
            $Tongfu->chanPay="01";
        }elseif($pay_type_id==39){
            $Tongfu->chanPay="03";
        }else{
            $Tongfu->chanPay="02";
        }
          $bank=$this->Bank->get_by_id($this->member_id,$id);
        $para2 = [
            'merchantNo'         =>$member_data["mem_tongfu"] ,
             'orderNum'         => $orderNum,
             'smsCode'         => $smsCode
        ];
            $blindfee=$Tongfu->bindfeeCard2($para2);
            if($blindfee["stat"]==1){
                        if($Tongfu->chanPay=="00"){
                          $data1["tongfu_00"]=$blindfee["data"];  
                        }elseif($Tongfu->chanPay=="01"){
                            $data1["tongfu_01"]=$blindfee["data"];
                        }elseif($Tongfu->chanPay=="03"){
                            $data1["tongfu_03"]=$blindfee["data"];
                        }else{
                            $data1["tongfu"]=$blindfee["data"];
                        }
                
                $status=$this->Bank->editById($data1,$bank["id"]);
            }
            if($status["stat"]==1){
                return $this->suc("绑卡成功");
            }
            return $this->err(9003,"绑卡失败".$blindfee["errmsg"]); 
            }else{
              $id=input("id");
             $sn=input("sn");
             $bank_num=input("bank_num");
             $this->assign("id",$id);
             $this->assign("sn",$sn);
             $this->assign("bank_num",$bank_num);
               return $this->fetch();
            }

        }
    public function get_order_by_id_remote($id = "") {
        $this->get_user_id();
        $rule = [
            "id" => "require|number"
        ];
        $data["id"] = $id;
        $check = $this->validate($data, $rule);
        if ($check !== true) {
            return $this->err("9000", $check);
        }
        $this->get_user_id();
        $data = $this->Order->get_by_id_o($id);
            $param["agentOrderNo"] = $data["sn"];
            $pay_class_new = new \ext\Kuaijiezhifu;

            $con = $pay_class_new->setAppid($this->appid)->setKey($this->key)->setNotifyUrl($this->notify_url);
            $param["start"]=date("Y-m-d",$data["add_time"]-24*3600*60);
            $param["end"]=date("Y-m-d",$data["add_time"]+24*3600);
            $card_id = $con->getSnbill($param);
            return $card_id;
            $all=$this->Order->select_all(array("stat"=>1,'channel_id'=>27));
            $all_key= array_column($all, "sn");
            $base= $card_id['data'];
            $data["count"]=count($card_id['data']);
            $data["remote"]=$card_id;
            
        if ($data) {
            return $this->suc($data);
        }
        return $this->err("900", "查询失败");
    }
    public function get_order_listAc($stat = 1, $order = "id desc", $limit = "") {
        $this->get_user_id();
        $map = [];
        $map["member_id"]=$this->member_id;
        if (1) {
            $map["stat"] = 1;
        }
        $data = $this->Order->select_all($map, $order, $limit);
        foreach($data as $k=>$v){
            $data[$k]["sn"]=$v["outer_sn"];
            
            
        }
        $count=$this->Order->get_count($map);
        if ($data) {
            $reponse["list"]=$data;
            $reponse["count"]=$count;
            return $this->suc($reponse);
        }
        return $this->err("900", "查询失败");
    }
    public function return_urlAc(){
        $log = model("log");
        $inputg=input("get.");
        $post=input("post.");
        $res = print_r($inputg, true) . print_r($post, true) . print_r($_REQUEST, true);
        
        $loginfo["log_info"] = $res;
        $loginfo["ip"]=0;
        $log->save($loginfo);
        
        
        if($inputg['isSuccess']==1){
            
        }
        //支付宝
         if(strpos($inputg["out_trade_no"], "gq")===0){
                        $product_order_model=model("ProductOrder");
		$order=$product_order_model->get_by_sn($inputg["out_trade_no"]);
         }else{
                     $order=$this->Order->get_by_sn($inputg["out_trade_no"]);
         }

        if(!empty($inputg["out_trade_no"])){
       //支付宝  
         if($order["stat"]==1){
            $msg="支付成功";
        }else{
            $msg="支付失败";
        }
          
        }else{
            //刷卡
            $msg="刷卡成功";
        }
         $url="http://".$_SERVER['HTTP_HOST']."/mobile/main.html";
        return $this->display("<script>alert('".$msg."');setTimeout(function(){location.href='".$url."'},3000);</script>");   

        return $this->suc("支付成功");
        
    }
    public function notify_baiyi(){

                 $data = input("post.");

                 mlog('佰易事(回调):'.json_encode($data, JSON_UNESCAPED_UNICODE));
                 $c = "ext\Kuaiyunzhong" ;
                 $kufu=new $c();
                 $sign_data = $data;
                 unset($sign_data['sign']);
                 $verify_ = $kufu->_gen_sign($sign_data);
                 if(($verify_ != $data['sign'])){
                    echo 'sign ERROR!';die;
                 }
                 
      Db::startTrans();
      try{
                 $order = $this->Order->get_by_sn_lock($data["merOrderId"]);
            if ($order["stat"] === 0) {
                
            } else {
                echo "success";
               Db::commit();    
                exit();
            }
                 if($order["amount"]*100==$data["tranAmt"]&&$data["resCdoe"]==="0000"){
                     $result = $this->Order->editById(array("stat" => 1,"finish_time"=>time(),"outer_sn"=>$data["merId"]), $order["id"]);
                 }
    // 提交事务
    Db::commit();    
    } catch (\Exception $e) {
    // 回滚事务
       Db::rollback();
    }
                 

        if ($result["stat"]==1) {
            
            
            //充值消息
            $msg["type"]=4;
            $msg["to"]=$order["member_id"];
            $msg["content"]=$order;
            $this->Msg->addItem($msg);
            //返利
            $moeny_log = model("MemberMonylog");
           //交易记录
            $mlog["member_id"]=$order["member_id"];
            $mlog["val"]=$order["amount"];
            $mlog["type"]=4;
            $mlog["op_id"]=0;
            $mlog["type_ordersn"]=$order["sn"];
           $moeny_log->addItem($mlog);
            
            $moeny_log->set_pid_money($order['sn']);
            //更改刷卡总额
            $this->Member->update_sk_total($order["member_id"],$order["amount"]);
            echo "success";
        } else {
            echo "ERROR";
        }
    }
    //同名通知
 public function notify_tongmingAc(){

                 $data = input("post.");
                 
                 // $data = json_decode('{"merId":"999791048160001","merOrderId":"sfb2018050302022694956","orderDate":"20180503140228","resCdoe":"0000","resMsg":"\u6210\u529f","settStat":"","sign":"321c6353615d1edffba7b614e50b651e","tranAmt":"50100"}',true);

//                 $data = array(

// [merId] => '999791048160001',
//     [merOrderId] => 'sfb2018050310172150091',
//     [orderDate] => '20180503101723',
//     [resCdoe] => '0000',
//     [resMsg] => '结算中',
//     [settStat] => '',
//     [sign] => '100be0d3cd7a9dcb39d684bf5b466b6e',
//     [tranAmt] => '50000'

//                     );

                 //验签
                 //         
                 $c = "ext\Tongming" ;
                 $kufu=new $c();
                 $sign_data = $data;
                 unset($sign_data['sign']);
                 $verify_ = $kufu->_gen_sign($sign_data);
                 //mlog($verify_ . '**' . $data['sign'] . '**' . ($verify_ == $data['sign']?'1':'0'));
                 //
                 mlog('同名回调通知：'. json_encode($data).'**SIGN('.$verify_.'**'.$data['sign']. '):'.($verify_ == $data['sign']?'OK':'ERR'));
                 if(($verify_ != $data['sign'])){
                    echo 'sign ERROR!';die;
                 }
                 
      Db::startTrans();
      try{
    $order = $this->Order->get_by_sn_lock($data["merOrderId"]);
    if ($order["stat"] === 0) {
                
            } else {
                echo "success";
                  Db::commit();    
                 exit();
            }
         if($order["amount"]*100==$data["tranAmt"]&&$data["resCdoe"]==="0000"){
             $result = $this->Order->editById(array("stat" => 1,"finish_time"=>time(),"sn"=>$data["merOrderId"]), $order["id"]);
         }
    // 提交事务
    Db::commit();    
    } catch (\Exception $e) {
    // 回滚事务
       Db::rollback();
    }
                // print_r($order);
                // $c = "ext\Tongming" ;
                 //$kufu=new $c();
               // $param['merOrderNo']=$order["sn"];
                // $re=$kufu->getSn($param);
                //print_r($re);
                 //exit();

        if ($result["stat"]==1) {
            
            
            //充值消息
            $msg["type"]=4;
            $msg["to"]=$order["member_id"];
            $msg["content"]=$order;
            $this->Msg->addItem($msg);
            //返利
            $moeny_log = model("MemberMonylog");
           //交易记录
            $mlog["member_id"]=$order["member_id"];
            $mlog["val"]=$order["amount"];
            $mlog["type"]=4;
            $mlog["op_id"]=0;
            $mlog["type_ordersn"]=$order["sn"];
           $moeny_log->addItem($mlog);
            
            $moeny_log->set_pid_money($order['sn']);
            //更改刷卡总额
            $this->Member->update_sk_total($order["member_id"],$order["amount"]);
            echo "success";
        } else {
            echo "ERROR";
        }
    }
    //众商
 public function notify_zhongshangAc(){

                 $data = input("post.");
                 $c = "ext\Zhongshang" ;
                 $kufu=new $c();
                 $sign_data = $data;
                 unset($sign_data['sign']);
                 $verify_ = $kufu->_gen_sign($sign_data);
                 mlog('众商回调通知：'. json_encode($data).'**SIGN('.$verify_.'**'.$data['sign']. '):'.($verify_ == $data['sign']?'OK':'ERR'));
                 if(($verify_ != $data['sign'])){
                    echo 'sign ERROR!';die;
                 }
                 
      Db::startTrans();
      try{
    $order = $this->Order->get_by_sn_lock($data["order_no"]);
    if ($order["stat"] === 0) {
                
            } else {
                echo "success";
                  Db::commit();    
                 exit();
            }
         if($data["Resp_code"]=="40000"){
             $result = $this->Order->editById(array("stat" => 1,"finish_time"=>time(),"outer_sn"=>$data["ypt_order_no"]), $order["id"]);
         }
    // 提交事务
    Db::commit();    
    } catch (\Exception $e) {
    // 回滚事务
       Db::rollback();
    }
                // print_r($order);
                // $c = "ext\Tongming" ;
                 //$kufu=new $c();
               // $param['merOrderNo']=$order["sn"];
                // $re=$kufu->getSn($param);
                //print_r($re);
                 //exit();

        if ($result["stat"]==1) {
            
            
            //充值消息
            $msg["type"]=4;
            $msg["to"]=$order["member_id"];
            $msg["content"]=$order;
            $this->Msg->addItem($msg);
            //返利
            $moeny_log = model("MemberMonylog");
           //交易记录
            $mlog["member_id"]=$order["member_id"];
            $mlog["val"]=$order["amount"];
            $mlog["type"]=4;
            $mlog["op_id"]=0;
            $mlog["type_ordersn"]=$order["sn"];
           $moeny_log->addItem($mlog);
            
            $moeny_log->set_pid_money($order['sn']);
            //更改刷卡总额
            $this->Member->update_sk_total($order["member_id"],$order["amount"]);
            echo "success";
        } else {
            echo "ERROR";
        }
    }
    //同付
 public function notify_tongfuAc(){

                 $data = input("post.");
                 $c = "ext\Tongfu" ;
                 $kufu=new $c();
                 $sign_data = $data;
                 unset($sign_data['sign']);
                 $verify_ = $kufu->_gen_sign($sign_data);
                 mlog('同付回调通知：'. json_encode($data).'**SIGN('.$verify_.'**'.$data['sign']. '):'.($verify_ == $data['sign']?'OK':'ERR'));
                 if(($verify_ != $data['sign'])){
                    echo 'sign ERROR!';die;
                 }
                 
      Db::startTrans();
      try{
    $order = $this->Order->get_by_sn_lock($data["orderNum"]);
    if ($order["stat"] === 0) {
                
            } else {
                echo "SUCCES";
                  Db::commit();    
                 exit();
            }
         if($data["platPayResultCode"]=="PTN0004"){

        $member_data = $this->Member->get_by_id($order['member_id']);       
        $bank=$this->Bank->get_by_num($member_data["id"],$order["bank_back"]);
                $bank_info=$this->BankType2->getBymap(["bank_name"=>$member_data['bankname']]);
        $member_area = $this->MemberArea->get_by_member_id($member_data['id']);
                $address=$member_area['id_detail']?$member_area['id_detail']:$member_area['detail'];
            if($member_area['id_province']){
                $pro=$this->Area->get_by_name($member_area['id_province']);
                $city=$this->Area->get_by_name($member_area['id_city']);
                $county=$this->Area->get_by_name($member_area['id_county']);  
                }else{
                $pro=$this->Area->get_by_name($member_area['province']);
                $city=$this->Area->get_by_name($member_area['city']);
                $county=$this->Area->get_by_name($member_area['county']);   
                }
                $chanPay="02";
                if(!empty($member_data["tongfu_fee_02"])){
                    $chanPay="00";
                }
        if($order["channel_id"]==37){
            $kufu->chanPay="00";
        }elseif($order["channel_id"]==38){
            $kufu->chanPay="01";
        }elseif($order["channel_id"]==39){
            $kufu->chanPay="03";
        }
             //代付
                     $param = [
            'merchantNo'         => $member_data["mem_tongfu"],
            'orderNum'         => $order["sn"]. mt_rand(1000, 9999),
            'chanPay'         => $chanPay,
            'bankCard'         => $order["bank_back"],
            'payMoney'         => $order["amount"]*100,
            'accountName'         => $member_data['truename'],
            'subBank'         =>              $member_data['bankname'],
            'bankChannelNo'         => $bank_info['bank_no'],
            'bankLinked'         => $bank_info["bank_no1"],
            'address'         => $address,
            'province'         => $pro["a_name"],
            'city'         => $city["a_name"],
        ];
                     //开通产品   
            $param1=[
                "merchantNo"=>$member_data["mem_tongfu"]
            ];
            //余额
             $statn0=$kufu->hasmoney($param1);
             mlog($statn0);
             $param["payMoney"]=$statn0['data']["amount"];
             //计算提现金额
             $real=$order["amount"]*(1-$order["rate"]/1000)-$order["rate_money"]+1;
             $real=$real*100;
             if($real<=$param["payMoney"]){
                 $param["payMoney"]=number_down($real);
             }else{
                 mlog("会员费率异常");
                 echo "ERROR";
                 Db::commit();
                 exit();
             }
            $statn=$kufu->ordertomoney($param);
            //还款
            mlog($statn);

            if($statn["stat"]==1){
                             $result = $this->Order->editById(array("stat" => 1,"finish_time"=>time(),"outer_sn"=>$data["platOrderNum"]), $order["id"]);
            }
         }
    // 提交事务
    Db::commit();    
    } catch (\Exception $e) {
    // 回滚事务
       Db::rollback();
    }
                // print_r($order);
                // $c = "ext\Tongming" ;
                 //$kufu=new $c();
               // $param['merOrderNo']=$order["sn"];
                // $re=$kufu->getSn($param);
                //print_r($re);
                 //exit();

        if ($result["stat"]==1) {
            
            
            //充值消息
            $msg["type"]=4;
            $msg["to"]=$order["member_id"];
            $msg["content"]=$order;
            $this->Msg->addItem($msg);
            //返利
            $moeny_log = model("MemberMonylog");
           //交易记录
            $mlog["member_id"]=$order["member_id"];
            $mlog["val"]=$order["amount"];
            $mlog["type"]=4;
            $mlog["op_id"]=0;
            $mlog["type_ordersn"]=$order["sn"];
           $moeny_log->addItem($mlog);
            
            $moeny_log->set_pid_money($order['sn']);
            //更改刷卡总额
            $this->Member->update_sk_total($order["member_id"],$order["amount"]);
            echo "SUCCES";
        } else {
            echo "ERROR";
        }
    }
    //同付手动提现
 public function notify_tongfud1Ac(){

                 $data = input("post.");
                 $c = "ext\Tongfu" ;
                 $kufu=new $c();
                 $sign_data = $data;
                 unset($sign_data['sign']);
                 $verify_ = $kufu->_gen_sign($sign_data);
                 mlog('同付回调通知：'. json_encode($data).'**SIGN('.$verify_.'**'.$data['sign']. '):'.($verify_ == $data['sign']?'OK':'ERR'));
                 if(($verify_ != $data['sign'])){
                    echo 'sign ERROR!';die;
                 }
                 
      Db::startTrans();
      try{
    $order = $this->Order->get_by_sn_lock($data["orderNum"]);
    if ($order["stat"] === 0) {
                
            } else {
                echo "SUCCES";
                  Db::commit();    
                 exit();
            }
         if($data["platPayResultCode"]=="PTN0004"){

        $member_data = $this->Member->get_by_id($order['member_id']);       
        $bank=$this->Bank->get_by_num($member_data["id"],$order["bank_back"]);
                $bank_info=$this->BankType2->getBymap(["bank_name"=>$member_data['bankname']]);
        $member_area = $this->MemberArea->get_by_member_id($member_data['id']);
                $address=$member_area['id_detail']?$member_area['id_detail']:$member_area['detail'];
            if($member_area['id_province']){
                $pro=$this->Area->get_by_name($member_area['id_province']);
                $city=$this->Area->get_by_name($member_area['id_city']);
                $county=$this->Area->get_by_name($member_area['id_county']);  
                }else{
                $pro=$this->Area->get_by_name($member_area['province']);
                $city=$this->Area->get_by_name($member_area['city']);
                $county=$this->Area->get_by_name($member_area['county']);   
                }
                $chanPay="02";
                if(!empty($member_data["tongfu_fee_02"])){
                    $chanPay="00";
                }
        if($order["channel_id"]==37){
            $kufu->chanPay="00";
        }elseif($order["channel_id"]==38){
            $kufu->chanPay="01";
        }elseif($order["channel_id"]==39){
            $kufu->chanPay="03";
        }
             //代付
                     $param = [
            'merchantNo'         => $member_data["mem_tongfu"],
            'orderNum'         => $order["sn"]. mt_rand(1000, 9999),
            'chanPay'         => $chanPay,
            'bankCard'         => $order["bank_back"],
            'payMoney'         => $order["amount"]*100,
            'accountName'         => $member_data['truename'],
            'subBank'         =>              $member_data['bankname'],
            'bankChannelNo'         => $bank_info['bank_no'],
            'bankLinked'         => $bank_info["bank_no1"],
            'address'         => $address,
            'province'         => $pro["a_name"],
            'city'         => $city["a_name"],
        ];
                     //开通产品   
            $param1=[
                "merchantNo"=>$member_data["mem_tongfu"]
            ];
            //余额
             $statn0=$kufu->hasmoney($param1);
             mlog($statn0);
             $param["payMoney"]=$statn0['data']["amount"];
             //计算提现金额
             $real=$order["amount"]*(1-$order["rate"]/1000)-$order["rate_money"]+1;
             $real=$real*100;
             if($real<=$param["payMoney"]){
                 $param["payMoney"]=number_down($real);
             }else{
                 mlog("会员费率异常");
                 echo "ERROR";
                 Db::commit();  
                 exit();
             }
            $statn=$kufu->ordertomoneyd1($param);
            //还款
            mlog($statn);

            if($statn["stat"]==1){
                             $result = $this->Order->editById(array("stat" => 1,"finish_time"=>time(),"outer_sn"=>$data["platOrderNum"]), $order["id"]);
            }
         }
    // 提交事务
    Db::commit();    
    } catch (\Exception $e) {
    // 回滚事务
       Db::rollback();
    }
                // print_r($order);
                // $c = "ext\Tongming" ;
                 //$kufu=new $c();
               // $param['merOrderNo']=$order["sn"];
                // $re=$kufu->getSn($param);
                //print_r($re);
                 //exit();

        if ($result["stat"]==1) {
            
            
            //充值消息
            $msg["type"]=4;
            $msg["to"]=$order["member_id"];
            $msg["content"]=$order;
            $this->Msg->addItem($msg);
            //返利
            $moeny_log = model("MemberMonylog");
           //交易记录
            $mlog["member_id"]=$order["member_id"];
            $mlog["val"]=$order["amount"];
            $mlog["type"]=4;
            $mlog["op_id"]=0;
            $mlog["type_ordersn"]=$order["sn"];
           $moeny_log->addItem($mlog);
            
            $moeny_log->set_pid_money($order['sn']);
            //更改刷卡总额
            $this->Member->update_sk_total($order["member_id"],$order["amount"]);
            echo "SUCCES";
        } else {
            echo "ERROR";
        }
    }
      //同付余额查询
 public function query_tongfud123Ac(){
               $map["mem_tongfu"]=["neq",""];
                $users=$this->Member->select_all($map);
                $c = "ext\Tongfu" ;
               $kufu=new $c();
                foreach($users as $v){
                    $member_data=$v;
            $param1=[
                "merchantNo"=>$member_data["mem_tongfu"]
            ];
            //余额
             $statn0=$kufu->hasmoney($param1);
               echo $member_data["truename"].":".$statn0['data']["amount"];
               echo PHP_EOL;
                }


    }
      //同付手动全部提现
 public function notify_tongfud12Ac(){
               $map["mem_tongfu"]=["neq",""];
                $users=$this->Member->select_all($map);
                $c = "ext\Tongfu" ;
               $kufu=new $c();
                foreach($users as $v){
                    $member_data=$v;
                $bank_info=$this->BankType2->getBymap(["bank_name"=>$member_data['bankname']]);
                $member_area = $this->MemberArea->get_by_member_id($member_data['id']);
                $address=$member_area['id_detail']?$member_area['id_detail']:$member_area['detail'];
            if($member_area['id_province']){
                $pro=$this->Area->get_by_name($member_area['id_province']);
                $city=$this->Area->get_by_name($member_area['id_city']);
                $county=$this->Area->get_by_name($member_area['id_county']);  
                }else{
                $pro=$this->Area->get_by_name($member_area['province']);
                $city=$this->Area->get_by_name($member_area['city']);
                $county=$this->Area->get_by_name($member_area['county']);   
                }
             //代付
                     $param = [
            'merchantNo'         => $member_data["mem_tongfu"],
            'orderNum'         => date("Ymdhis"). mt_rand(1000, 9999),
            'chanPay'         => "02",
            'bankCard'         => $member_data["banknum"],
            'payMoney'         => 100,
            'accountName'         => $member_data['truename'],
            'subBank'         =>              $member_data['bankname'],
            'bankChannelNo'         => $bank_info['bank_no'],
            'bankLinked'         => $bank_info["bank_no1"],
            'address'         => $address,
            'province'         => $pro["a_name"],
            'city'         => $city["a_name"],
        ];
                     //开通产品   
            $param1=[
                "merchantNo"=>$member_data["mem_tongfu"]
            ];
            //余额
             $statn0=$kufu->hasmoney($param1);
             mlog($statn0);
             $param["payMoney"]=$statn0['data']["amount"];
             if($param["payMoney"]){
                  $statn=$kufu->ordertomoneyd1($param);   
             }    
                    
                    
                }


    }
    //同名查询订单
    public  function get_infoAc(){
                 $data = input("post.");
                 // $data2 = input("get.");
                 // mlog($data2);
                 // mlog($data);
                 $order = $this->Order->get_by_sn($data["merOrderId"]);
                 print_r($order);
                $c = "ext\Tongming" ;
                $kufu=new $c();
               $param['merOrderNo']=$order["sn"];
                $re=$kufu->getSn($param);
                print_r($re);
                 exit();  
        
        
    }
    public function notifyAc() {
        $log = model("log");
        $data = input("post.");
        $class = input("get.class");
        $res = print_r($_POST, true) . print_r($_GET, true) . print_r($_REQUEST, true). print_r($data, true);
        $loginfo["log_info"] = $res;
        $loginfo["ip"]=0;


        $log->save($loginfo);
        //佰易事支付通知
        if(isset($data["merOrderId"])&&empty($data["settStat"])){
            
            $this->notify_baiyi();
            return ;
        }
        //同名支付通知
        if(isset($data["settStat"])){
            
            $this->notify_tongming();
            return ;
        }
                 $c = "ext\\". $class ;
                 $kufu=new $c();
                 $con = $kufu->setAppid($this->appid)->setKey($this->key)->setNotifyUrl($this->notify_url);
                 $sign_data = $data;
                 
                 unset($sign_data['sign']);
                 $verify_ = $con->_gen_sign($sign_data);
                 if(($verify_ != $data['sign'])){
                    echo 'sign ERROR!';die;
                 }
        $c = "ext\\" . $class;
        if ($data["agentOrderNo"]&&$data["state"]==4) {
            
      Db::startTrans();
      try{
            $order = $this->Order->get_by_sn_lock($data["agentOrderNo"]);
            if ($order["stat"] === 0) {
                
            } else {
                echo "success";
               Db::commit();  
                exit();
            }
            $param["agentOrderNo"] = $data["agentOrderNo"];
            $pay_class_new = new $c;

            $con = $pay_class_new->setAppid($this->appid)->setKey($this->key)->setNotifyUrl($this->notify_url);
            $card_id = $con->getSnbill($param);
            if ($order["amount"] > 0 && $order["amount"]*100== $card_id["data"]["totalFee"]) {
                $result = $this->Order->editById(array("stat" => 1,"finish_time"=>time()), $order["id"]);
            }
    // 提交事务
    Db::commit();    
    } catch (\Exception $e) {
    // 回滚事务
       Db::rollback();
    }
          

        } elseif($data["agentOrderNo"]&&$data["state"]==1) {
            if ($order["stat"] === 20) {
                
            } else {
                echo "success";
                exit();
            }
             //支付失败记录原因
             $order = $this->Order->get_by_sn($data["agentOrderNo"]);
             $result = $this->Order->editById(array("stat" => 20,"finish_time"=>time(),"reason"=>$data["result"]), $order["id"]);
             echo "success";
             exit();
        }
        if ($result["stat"]==1) {
            
            
            //充值消息
            $msg["type"]=4;
            $msg["to"]=$order["member_id"];
            $msg["content"]=$order;
            $this->Msg->addItem($msg);
            //返利
            $moeny_log = model("MemberMonylog");
           //交易记录
            $mlog["member_id"]=$order["member_id"];
            $mlog["val"]=$order["amount"];
            $mlog["type"]=4;
            $mlog["op_id"]=0;
            $mlog["type_ordersn"]=$order["sn"];
           $moeny_log->addItem($mlog);
            
            $moeny_log->set_pid_money($order['sn']);
            //更改刷卡总额
            $this->Member->update_sk_total($order["member_id"],$order["amount"]);
            echo "success";
        } else {
            echo "ERROR";
        }
    }

    public function weixinnotifyAc() {
        $log = model("log");
        $res = print_r($_POST, true) . print_r($_GET, true) . print_r($_REQUEST, true);
        $loginfo["log_info"] = $res;
        $loginfo["ip"]=0;
        $log->save($loginfo);
        $input = file_get_contents("php://input");
        $data = \weixin\Weixinpay::xmlToArray($input);
        mlog($data);
        if ($data["result_code"] == "SUCCESS") {
            $order = $this->Order->get_by_sn($data["out_trade_no"]);
            if ($order["stat"] == 0 && $order["amount"]*100 == $data["total_fee"] && (int) $data["total_fee"] > 0) {
                $status = $this->Order->editByid(array("stat" => 1,"finish_time"=>time()), $order["id"]);
                 $this->MemberGroupLog->editbyorder_id(array("status" => 1),$order["id"]);
            }
        }
        if ($status["stat"]==1) {
           //交易记录
            $moeny_log = model("MemberMonylog");
            $mlog["member_id"]=$order["member_id"];
            $mlog["val"]=$order["amount"];
            $mlog["type"]=5;
            $mlog["op_id"]=0;
            $mlog["type_ordersn"]=$order["sn"];
            $moeny_log->addItem($mlog);
            //充值消息
            $msg["type"]=5;
            $msg["to"]=$order["member_id"];
            $msg["content"]=$order;
            $this->Msg->addItem($msg);
            //升级返利
        $moeny_log = model("MemberMonylog");
        $moeny_log->set_pid_money_up($order["sn"]);
            echo "<xml> 
  <return_code><![CDATA[SUCCESS]]></return_code>
   <return_msg><![CDATA[OK]]></return_msg>
 </xml>
";
        } else {
            echo "<xml> 
  <return_code><![CDATA[FAIL]]></return_code>
   <return_msg><![CDATA[OK]]></return_msg>
 </xml>
";
        }
    }

    //测试返利
    public function test_returnAc($sn) {
        $this->get_user_id();
        $moeny_log = model("MemberMonylog");
        $moeny_log->set_pid_money($sn);
    }

    //缴费
    public function pay_feeAc() {
        $map["id"]=["<",18];
        $map["stat"]=1;
        $group_data = $this->MemberGroup->select_all($map);


        return $this->suc($group_data);
    }
    //缴费订单
    public function pay_fee_orderAc($group_id = "") {
require_once EXTEND_PATH."weixin_sdk/lib/WxPay.Api.php";
require_once EXTEND_PATH."weixin_sdk/example/WxPay.JsApiPay.php";
require_once EXTEND_PATH."weixin_sdk/example/WxPay.Config.php";
        $this->get_user_id();
	$tools = new \JsApiPay();
	$openid = $tools->GetOpenid();
        if(empty($openid)){
            return $this->err(9000, "获取openid失败");
        }
        $input = input("post.");
        if(empty($input)){
            $input= input("param.");
        }
        $input["member_id"]=$this->member_id;
        $check = $this->validate($input, "Order.pay_fee_order");
        if ($check !== true) {
            return $this->err(9000, $check);
        }

        $mem = $this->Member->get_by_id($this->member_id);
        $group_data = $this->MemberGroup->get_by_id($group_id);
        $mem_group = $this->MemberGroup->get_by_id($mem["membergroup_id"]);
        $money = $group_data["money"] - $mem_group["money"];
        if ($money) {
            $data["member_id"] = $this->member_id;
            $data["amount"] = $money;
            $data["type"] = 2;
            $data["channel_id"] = 0;
            $data["reason"] = "";
            $order_data = $this->Order->addItem_id($data);
            $data_group["member_id"] = $this->member_id;
            $data_group["order_id"] = $order_data["data"];
            $data_group["low_group"] = $mem["membergroup_id"];
            $data_group["heigh_group"] = $group_data["id"];
            $data_group["low_money"] = $mem_group["money"];
            $data_group["heigh_money"] = $group_data["money"];
            $this->MemberGroupLog->addItem_id($data_group);
        }
        $new_data = $this->Order->get_by_id($this->member_id, $order_data["data"]);
        $new_data["ip"] = real_ip();
        $str = mt_rand(100, 99999);
        $new_data["nonce_str"] = md5($str);
        $new_data["openid"]=$openid;
        $weixin = new \weixin\Weixinpay($new_data);
        $result = $weixin->get_order();
        if($result["return_code"]!='SUCCESS'){
            return $this->err(9000,$result["return_msg"]);
        }
        if ($order_data["stat"] == 1) {
            $reponse["order"] = $new_data;
            $this->Order->editById(array("outer_sn"=>$result["prepay_id"]),$new_data["id"]);
            $reponse["weixin_order"] = $result;
            $jsApiParameters = $tools->GetJsApiParameters($result);
            $this->assign("jsApiParameters",$jsApiParameters);
            return $this->fetch();
        }

        return $this->err(9000, "生成订单失败");
    }
    //缴费订单阿里
    public function pay_fee_order_aliAc($group_id = "") {
        $this->get_user_id();
        $input = input("post.");
        $input["member_id"]=$this->member_id;
        $check = $this->validate($input, "Order.pay_fee_order");
        if ($check !== true) {
            return $this->err(9000, $check);
        }

        $mem = $this->Member->get_by_id($this->member_id);
        $group_data = $this->MemberGroup->get_by_id($group_id);
        $mem_group = $this->MemberGroup->get_by_id($mem["membergroup_id"]);
        $money = $group_data["money"] - $mem_group["money"];
        if ($money) {
            $data["member_id"] = $this->member_id;
            $data["amount"] = $money;
            $data["type"] = 2;
            $data["channel_id"] = 0;
            $data["reason"] = "";
            $order_data = $this->Order->addItem_id($data);
            $data_group["member_id"] = $this->member_id;
            $data_group["order_id"] = $order_data["data"];
            $data_group["low_group"] = $mem["membergroup_id"];
            $data_group["heigh_group"] = $group_data["id"];
            $data_group["low_money"] = $mem_group["money"];
            $data_group["heigh_money"] = $group_data["money"];
            $this->MemberGroupLog->addItem_id($data_group);
        }
        $new_data = $this->Order->get_by_id($this->member_id, $order_data["data"]);
        $new_data["ip"] = real_ip();
        $str = mt_rand(100, 99999);
        $new_data["nonce_str"] = md5($str);
        if ($order_data["stat"] == 1) {
            $reponse["order"] = $new_data;
            $reponse["url"]= url("order/alipay",array("sn"=>$new_data["sn"]));
            return $this->suc($reponse);
        }

        return $this->err(9000, "生成订单失败");
    }
    //支付宝
    public  function alipayAc($sn=""){
        $rule=[
            "sn"=>"require"
        ];
        $check=$this->validate($_REQUEST, $rule);
        if($check!==true){
            return $this->err(9000, $check);
        }
       
        if(strpos($sn,"gq")===0){
             $order=model("ProductOrder")->get_by_sn($sn);
        }else{
             $order=$this->Order->get_by_sn($sn);
        }
        if(!empty($order)&&$order["stat"]==0){
            
        }else{
            return $this->err(9001, "订单状态不可用");
        }
header("Content-type: text/html; charset=utf-8");

  $path="../extend/ali/wappay/";
require_once $path.'/service/AlipayTradeService.php';
require_once $path.'/buildermodel/AlipayTradeWapPayContentBuilder.php';
require_once '../extend/ali/config.php';
if (!empty($order["sn"])&& trim($order["sn"])!=""){
    //$order["amount"]=0.01;
    //商户订单号，商户网站订单系统中唯一订单号，必填
    $out_trade_no = $order["sn"];

    //订单名称，必填
    $subject = $order["sn"];

    //付款金额，必填
    $total_amount = $order["amount"];

    //商品描述，可空
    $body = $order["sn"];

    //超时时间
    $timeout_express="1m";
    $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
    $payRequestBuilder->setBody($body);
    $payRequestBuilder->setSubject($subject);
    $payRequestBuilder->setOutTradeNo($out_trade_no);
    $payRequestBuilder->setTotalAmount($total_amount);
    $payRequestBuilder->setTimeExpress($timeout_express);

    $payResponse = new \AlipayTradeService($config);
    $result=$payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);

    return ;
    }  
    
} 
    //支付宝查询订单
    public  function get_order_ali($sn="",$config){
header("Content-type: text/html; charset=utf-8");

          $path="../extend/ali/";
require_once $path.'wappay/service/AlipayTradeService.php';
require_once $path.'wappay/buildermodel/AlipayTradeQueryContentBuilder.php';
if (!empty($sn)){

    //商户订单号和支付宝交易号不能同时为空。 trade_no、  out_trade_no如果同时存在优先取trade_no
    //商户订单号，和支付宝交易号二选一
    $out_trade_no = trim($sn);

    //支付宝交易号，和商户订单号二选一
    //$trade_no = trim($_POST['WIDtrade_no']);

    $RequestBuilder = new \AlipayTradeQueryContentBuilder();
    $RequestBuilder->setTradeNo($trade_no);
    $RequestBuilder->setOutTradeNo($out_trade_no);

    $Response = new \AlipayTradeService($config);
    $result=$Response->Query($RequestBuilder);

    if($result->trade_status=="TRADE_SUCCESS"){
        
        return $result->total_amount;
    }
    return false;
} 
        
        
    }
    //支付宝通知   
    public function notify_aliAc(){
        $log = model("log");
        $inputg=input("get.");
        $post=input("post.");
        $res = print_r($inputg, true) . print_r($post, true) . print_r($_REQUEST, true);
        $loginfo["log_info"] = $res;
        $loginfo["log_time"] = time();
        $loginfo["ip"]=0;
        $log->save($loginfo);
          $path="../extend/ali/";
require_once($path."config.php");
require_once $path.'wappay/service/AlipayTradeService.php';


$arr=$_POST;
$alipaySevice = new \AlipayTradeService($config); 
$alipaySevice->writeLog(var_export($_POST,true));
$result = $alipaySevice->check($arr);
/* 实际验证过程建议商户添加以下校验。
1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
4、验证app_id是否为该商户本身。
*/
if(1) {//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代

	
	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
	
    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
	
	//商户订单号

	$out_trade_no = $_POST['out_trade_no'];

	//支付宝交易号

	$trade_no = $_POST['trade_no'];
         //验证订单
        
        $check_order=$this->get_order_ali($out_trade_no,$config);
        if($check_order||1){
            
        }else{
             echo "fail";
            exit();
        }
	//交易状态
	$trade_status = $_POST['trade_status'];


    if($_POST['trade_status'] == 'TRADE_FINISHED') {

		//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//请务必判断请求时的total_amount与通知时获取的total_fee为一致的
			//如果有做过处理，不执行商户的业务程序
				
		//注意：total_amount
		//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
    }
    else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
        //股权
         if(strpos($out_trade_no, "gq")===0){
             
             $this->set_product_order($out_trade_no,$trade_no);
             exit();
         }
		$order=$this->Order->get_by_sn($out_trade_no);
                if(!empty($order)&&$order["stat"]===0){

                 $status = $this->Order->editByid(array("stat" => 1,"outer_sn"=>$trade_no,"finish_time"=>time()), $order["id"]);
                 $this->MemberGroupLog->editbyorder_id(array("status" => 1),$order["id"]);
                 if($status["stat"]==1){
           //交易记录
            $moeny_log = model("MemberMonylog");
            $mlog["member_id"]=$order["member_id"];
            $mlog["val"]=$order["amount"];
            $mlog["type"]=5;
            $mlog["op_id"]=0;
            $mlog["type_ordersn"]=$order["sn"];
            $moeny_log->addItem($mlog);
            //充值消息
            $msg["type"]=5;
            $msg["to"]=$order["member_id"];
            $msg["content"]=$order;
            $this->Msg->addItem($msg);
            //升级返利
        $moeny_log = model("MemberMonylog");
        $moeny_log->set_pid_money_up($order["sn"]);
echo "success";
                 }
                }
    }
	//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
        if(empty($status["stat"])){
          echo "fail";  
        }
			
		
}else {
    //验证失败
    echo "fail";	//请不要修改或删除

}
    
    
     
            
        }
        //股权订单
        public  function set_product_order($sn,$trade_no){
            $product_order_model=model("ProductOrder");
            $red_model=model("Red");
		$order=$product_order_model->get_by_sn($sn);
                if(!empty($order)&&$order["stat"]===0){
                  $red["red"]=$order["red"];
                  $red["member_id"]=$order["member_id"];
                  $red["add_time"]=time();
                  $old=$red_model->get_by_uid($order["member_id"]);
                  if(empty($old)){
                      
                     $status1=$red_model->addItem($red);  
                  }else{
                      $red["red"]=$red["red"]+$old["red"];
                      $status1=$red_model->editById($red,$old["id"]);  
                      mlog($status1);
                  }
                 
                 $status = $product_order_model->editByid(array("stat" => 1,"outer_sn"=>$trade_no,"finish_time"=>time()), $order["id"],$order);
                 mlog($status);
                 echo "success";
                 exit();
                }
                    echo "fail";
        }

        //设置费率
   public  function set_feeAc($content="",$key=""){
       $part=new \app\admin\model\Part();
       $host= strtolower($_SERVER["HTTP_HOST"]);
       $info=$part->get_by_key($key);
       $info["demain"]= strtolower($info["demain"]);
       if($info&&$info["demain"]==$host){
            $data["content"]=$content;
           $status=$part->editById($data,$info["id"]);
       }
       return $this->suc($status["stat"]);
   }
   //设置费率
   public  function get_configAc($key=""){
       $part=new \app\admin\model\Part();
       $host= strtolower($_SERVER["HTTP_HOST"]);
       $info=$part->get_by_key($key);
       $info["demain"]= strtolower($info["demain"]);
       if($info&&$info["demain"]==$host){
         return $this->suc($info);
       }
       return $this->err("获取错误");
   }
   //设置费率
   public  function get_listAc($key=""){
       $host= strtolower($_SERVER["HTTP_HOST"]);
       $part=new \app\admin\model\Part();
       $info=$part->get_by_key($key);
       if($info&&$info["demain"]==$host){
       }else{
           return $this->err(9000,"错误");
       }
        $map=[];
        $a_name=input("get.a_name");
        $res=input("post.");
        if($res["order"]){
            $order="id ".$res["order"];
        }
        if($res["limit"]){
           $limit=$res["offset"].",".$res["limit"];
        }
        if($res["a_name"]){
            $map["outer_sn"]=["like","%{$res['a_name']}%"];
        }
        if($res["type"]==1||$res["type"]==2){
            $map["type"]=$res["type"];
        }
        if($res["start"]&&$res["end"]){
            $start= strtotime($res["start"]);
            $end= strtotime($res["end"]);
            $map["add_time"]=["between","$start,$end"];
        }
        if($res["member_name"]){
             $mapm["uname"]=$res["member_name"];
             $mems=$this->Member->select_all_id($mapm);
             if($mems){
                 $map["member_id"]=["in",$mems];
             }
        }
        if($res["phone"]){
             $mapm["phone"]=$res["phone"];
             $mems=$this->Member->select_all_id($mapm);
             if($mems){
                 $map["member_id"]=["in",$mems];
             }
        }
        if($res["uid"]&& is_numeric($res["uid"])){
            $map["member_id"]=$res["uid"];
            
        }
        //盈利
       $map["stat"]=1;
       $order_model=new \app\admin\model\Order();
       $lists = $order_model->select_all($map,"id desc",$limit);
       foreach ($lists  as $k=>$v){
           if($v["type"]==1){
               $lists[$k]["type_name"]="刷卡";
           }else{
                $lists[$k]["type_name"]="升级消费";
           }
           $mem=$this->Member->get_by_id($v["member_id"]);
           $lists[$k]["uname"]=$mem["uname"];
           $lists[$k]["phone"]=$mem["phone"];
           $lists[$k]["last_money"]=$v["amount"]-$v["amount"]*$v["rate"]/1000-$v["rate_money"];
           $lists[$k]["truename"]=$mem["truename"];
           $lists[$k]["truename"]=$mem["truename"];
           $group=$this->MemberGroup->get_by_id($mem["membergroup_id"]);
            $lists[$k]["member_group"]=$group["name"];
            $channel=$this->Channel->get_by_id($v["channel_id"]);
            $lists[$k]["channel_id"]=$channel["name"];
            //收益
            if($res["self_type"]==13&&$v["type"]==1){
                $channel=$this->Channel->get_by_id($v["channel_id"]);
                //实际支付方式
                $pay_way=$this->Channel->get_pay_way(100,$v["channel_id"]);
                //实际支付费率
                $rate=$group[$pay_way["rate_type"]];
                $lists[$k]["rated"]=$v["amount"]*$v["rate"]/1000;
                 $lists[$k]["rated_low"]=$v["amount"]*$v["low_rate"]/1000;
                 $lists[$k]["rate_real"]=$v["amount"]-$lists[$k]["last_money"]-$lists[$k]["rated_low"]-$lists[$k]["low_rate_money"];
                 $lists[$k]["rate_real"]=round($lists[$k]["rate_real"],3);
                 //订单总返佣
                 $back=$this->MemberMonylog->sum_money_back($v["sn"]);
                 $lists[$k]["back"]=$back;
                  $lists[$k]["last"]=$lists[$k]["rate_real"]-$back;
                  $lists[$k]["last"]=round($lists[$k]["last"],3);
            }elseif($res["self_type"]==13&&$v["type"]==2){
                //升级缴费
                $back=$this->MemberMonylog->sum_money_back($v["sn"]);
                $lists[$k]["rate_real"]=$v["amount"];
                  $lists[$k]["last"]=$lists[$k]["rate_real"]-$back;
            }
       }
               
        echo json_encode(array('rows'=>$lists,"total"=>$order_model->get_count($map)));
   }
   //盈利统计
 public  function get_more_remoteAc($key=""){
       $host= strtolower($_SERVER["HTTP_HOST"]);
       $part=new \app\admin\model\Part();
       $info=$part->get_by_key($key);
       if($info&&$info["demain"]==$host){
       }else{
           return $this->err(9000,"错误");
       }
        $map=[];
        $a_name=input("get.a_name");
        $res=input("post.");
        if($res["order"]){
            $order="id ".$res["order"];
        }
        if($res["limit"]){
           $limit=$res["offset"].",".$res["limit"];
        }
        if($res["a_name"]){
            $map["outer_sn"]=["like","%{$res['a_name']}%"];
        }
        if($res["type"]==1){
            $map["type"]=["in","1,6,8"];
        }
        if($res["type"]==2){
            $map["type"]=["in","2,7,9"];
        }
        if($res["start"]&&$res["end"]){
            $start= strtotime($res["start"]);
            $end= strtotime($res["end"]);
            $map["add_time"]=["between","$start,$end"];
        }
        if($res["member_name"]){
             $mapm["uname"]=$res["member_name"];
             $mems=$this->Member->select_all_id($mapm);
             if($mems){
                 $map["member_id"]=["in",$mems];
             }
        }
        if($res["phone"]){
             $mapm["phone"]=$res["phone"];
             $mems=$this->Member->select_all_id($mapm);
             if($mems){
                 $map["member_id"]=["in",$mems];
             }
        }
        if($res["uid"]&& is_numeric($res["uid"])){
            $map["member_id"]=$res["uid"];
            
        }
        //盈利
        if($res["self_type"]==13){
            $map["stat"]=1;
        }
       $order_model=new \app\admin\model\Order();
        $all_money_make=$order_model->get_sum_all_get(1,$map);
        $all_money_make_2=$order_model->get_sum_all_get(2,$map);
        $all_money=$order_model->get_sum_money($map);
        $order_ids=$order_model->select_all_id($map);
        $map_order["type_ordersn"]=["in",$order_ids];
        $back=$this->MemberMonylog->sum_money_back_all($map_order);

        $all_money_make=$all_money_make+$all_money_make_2;
        $last=$all_money_make-$back;
        $all_money_make= round($all_money_make,2);
        $all_money= round($all_money,2);
        $last= round($last,2);
        $this->assign("all_money_make",$all_money_make);
        $this->assign("all_money",$all_money);
        $this->assign("last",$last);
       $lists = $order_model->select_all($map,"id desc",$limit);
       foreach ($lists  as $k=>$v){
           if($v["type"]==1){
               $lists[$k]["type_name"]="刷卡";
           }else{
                $lists[$k]["type_name"]="升级消费";
           }
           $mem=$this->Member->get_by_id($v["member_id"]);
           $lists[$k]["uname"]=$mem["uname"];
           $lists[$k]["phone"]=$mem["phone"];
           $lists[$k]["last_money"]=$v["amount"]-$v["amount"]*$v["rate"]/1000-$v["rate_money"];
           $lists[$k]["truename"]=$mem["truename"];
           $lists[$k]["truename"]=$mem["truename"];
           $group=$this->MemberGroup->get_by_id($mem["membergroup_id"]);
            $lists[$k]["member_group"]=$group["name"];
            $channel=$this->Channel->get_by_id($v["channel_id"]);
            $lists[$k]["channel_id"]=$channel["name"];
            //收益
            if($res["self_type"]==13&&$v["type"]==1){
                $channel=$this->Channel->get_by_id($v["channel_id"]);
                //实际支付方式
                $pay_way=$this->Channel->get_pay_way(100,$v["channel_id"]);
                //实际支付费率
                $rate=$group[$pay_way["rate_type"]];
                $lists[$k]["rated"]=$v["amount"]*$v["rate"]/1000;
                 $lists[$k]["rated_low"]=$v["amount"]*$v["low_rate"]/1000;
                 $lists[$k]["rate_real"]=$v["amount"]-$lists[$k]["last_money"]-$lists[$k]["rated_low"]-$lists[$k]["low_rate_money"];
                 $lists[$k]["rate_real"]=round($lists[$k]["rate_real"],3);
                 //订单总返佣
                 $back=$this->MemberMonylog->sum_money_back($v["sn"]);
                 $lists[$k]["back"]=$back;
                  $lists[$k]["last"]=$lists[$k]["rate_real"]-$back;
                  $lists[$k]["last"]=round($lists[$k]["last"],3);
            }elseif($res["self_type"]==13&&$v["type"]==2){
                //升级缴费
                $back=$this->MemberMonylog->sum_money_back($v["sn"]);
                $lists[$k]["rate_real"]=$v["amount"];
                  $lists[$k]["last"]=$lists[$k]["rate_real"]-$back;
            }
       }
               
        echo json_encode(array("all_money"=>$all_money,"all_money_make"=>$all_money_make,"last"=>$last,'rows'=>$lists,"total"=>$this->Order->get_count($map)));
        
    }
}

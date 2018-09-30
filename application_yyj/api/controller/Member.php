<?php
/**
 * 会员接口
 */

namespace app\api\controller;
class Member extends \app\api\controller\Home {
        public function _initialize(){
        $this->member = new \app\common\model\Member;
        $this->member_area = model("MemberArea");
        $this->member_group = model("MemberGroup");  
        $this->member_validate = new \app\common\validate\Member;
         $this->Setting = \think\Loader::model('Setting');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }
    private function _gen_rand($length){
        $str = '';
        $length = (int)$length;
        
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        
        $max = strlen($strPol)-1;
        
        for($i=0;$i<$length;$i++){
            $str.=$strPol[rand(0,$max)];
        }
        
        return $str;
    }
    
    
    /**
     * APP端会员登录
     * @return unknown
     */
    public function loginAc(){
        $uname = input('post.uname');
        $upass = input('post.upass');
        $deviceid = (string)input('post.deid');
        
        $time = time();
        $exprire = 3600*24*10;
        
        $member_mod = new \app\common\model\Member();
        
        $r = $member_mod->checkUserPasswrod($uname, $upass);
        
        if($r['stat'] == '1'){
            //验证成功
            $member_info = $r['data'];
            
            $data['client_type'] = 'app';

            $data['member_id'] = $member_info['id'];
            
            
            $login_token_mod = new \app\common\model\Member_login_token();
            //是否已经登录
            $tokeninfo = $login_token_mod->get($data);
            
            $newtoken  = $member_info['id'] . 'app' . $this->_gen_rand(40);
            
            $data['login_ip'] = ip2long(real_ip());
            $data['device_id'] = $deviceid;
            
            
            if(!empty($tokeninfo)){
                //已登录过,更新登录时间，过期时间，token
                try{
                    $updata = [
                        'login_time'    => $time,
                        'exprired_time' => $time + $exprire,
                        'token' => $newtoken,
                        'device_id' => $deviceid,
                         'old_token' => $tokeninfo["token"],
                         'old_device_id' => $tokeninfo["device_id"]
                    ];
                    
                    //记录旧token
                    $r = $login_token_mod->editById($updata, $tokeninfo['id']);

                    
                    if($r['stat'] != '1') throw new \Exception($r['msg']);
                }catch( \Exception $e){
                    $msg = $e->getMessage();
                    return $this->err('9099','系统错误'.$msg);
                }
                
            }else{
                //还未登录
                $data['login_time']    = $time;
                $data['exprired_time'] = $time + $exprire;
                $data['token']         = $newtoken;
                
                try{
                    $r = $login_token_mod->addItem($data);
                    if($r['stat'] != '1') throw new \think\Exception($r['msg']);
                }catch( \Exception $e){
                    $msg = $e->getMessage();
                    return $this->err('9098','系统错误'.$msg);
                }
            }
            
            return $this->suc($newtoken);
        }else{
            return $this->err('1001', $r['msg']);
        }
        
    }
    public  function login_outAc(){
        $MemberLoginToken=model("MemberLoginToken");
        $token=input("post.__token_");
        $info=$MemberLoginToken->get_by_token_info($token);
        if($info){
            $data["exprired_time"]=0;
            $res=$MemberLoginToken->editById($data,$info["id"]);
            if($res["stat"]==1){
                return $this->suc("退出成功");
            }
        }
         return $this->err('9002', "退出异常");
        
    }
    /**
     * 会员注册
     * @return unknown
     */
    public function regAc(){
        $error=array(
            "1001"=>"邀请人不存在",
               "1002"=>"手机号格式不正确",  
                 "1003"=>"手机号已经注册,您可以直接登录",
                "1004"=>"密码格式不正确或太简单",
            "1005"=>"手机验证码不正确",
        );
        //默认用户组
             $set=$this->Setting->get_by_key("member_group");
        //设置默认值
        $params["stat"]=1;
        $params["is_rz_1"]=0;
         $params["is_rz_3"]=0;
         $params["is_rz_3"]=0;
          $params["membergroup_id"]=$set['value'];
           $params["sk_fy_total_sheng"]=0;
           $params["sex"]=0;
           $params["errtimes"]=0;
           
           
        $params["phone"]=(string)input("post.phone");
        $pphone=(string)input("post.pphone");
        $pphone1=(string)input("pphone_g");
        if($pphone1){
            $pphone=$pphone1;
        }
         $params["verify_code"]=(string)input("post.phcode");
         $params["upass"]=(string)input("post.upass");
         $params["uname"]= $this->member->get_member_last().substr(md5($params["phone"]),0,5);
         if(empty($pphone)){
              return $this->err("1001","邀请人不存在");
         }
         //推荐人
         $pphone_id=$this->member->get_member_phone($pphone);
       if($pphone_id>0){
           $params["p_id"]=$pphone_id;
          }else{
                        return $this->err('9000', $error["1001"]); 
          }
         $result = $this->validate($params,'Member.reg');
        if(true !== $result){
           return $this->err("1001",$result);
         }

          unset($params["verify_code"]);
          $ret = $this->member->addItem_id($params);
         if($ret['stat']==1){  
             $status=1;
         }else{
             $status=0;
          }
        if($status){
            //设置上级代理
            $this->member->set_parent_count($pphone_id,$ret["data"]);
            return $this->suc($ret["data"]);
        }else{
             return $this->err('1001','系统错误'.print_r($ret,true));
        }
    }
    /**
     * 通过手机找回密码
     */
    public function forgetpassbyphoneAc(){
        $mobile = input('post.phone');
        $verify_code = (int)input('post.verify_code');
        $password = (string)input('post.upass');
        $params = [
            'phone' => $mobile,
            'verify_code'=> $verify_code,
            'upass'  =>  $password
        ];
        $validate = $this->validate($params, 'Member.findpassbyphone');
        if($validate !== true){
            return $this->err("9000", $validate);
        }
        //验证成功，修改密码
        $member_mod = new \app\common\model\Member();
        $uid = $member_mod->where('phone', $mobile)->field('id')->find();
        try{
            $ret = $member_mod->modifyupass($password,$uid['id']);
            if($ret["stat"]==1){
                return $this->suc($uid['id']);
            }
        }catch (\Exception $e){
            $msg = $e.getMessage();
            return $this->err('9099','系统错误'.$msg);
        }
    }
    
    /**
     * 查询个人信息  获取： 用户名，手机号，当前余额，级别等
     */
    public function infoAc(){
                   $MoneyLog=model("MemberMonylog");
                   $this->get_user_id();
                   $data= $this->member->get_by_id($this->member_id);
                   //已提现金额
                   $sum_money_litttle=$MoneyLog->sum_money_litttle($this->member_id);
                   $data["money"]=$data["money"]-$sum_money_litttle;
                   $data["money"]= number_format($data["money"],2,'.','');
                   $data_group=$this->member_group->get_by_id($data["membergroup_id"]);
                   $table_info=$this->member->table_info();
                   if($data["p_id"]){
                     $parent=$this->member->get_by_id($data["p_id"]);  
                   $data["parent"]=$parent;
                   }else{
                   $data["parent"]="";      
                   }
                   
                   $data["group_name"]=$data_group["name"];
                   $data["group_info"]=$data_group;
                  $data["table_info"]=$table_info;
                  $data["member_area"]=$this->member_area->get_by_member_id($this->member_id);
                   return $this->suc($data);
        
    }
    public function update_infoAc($nickname=""){
        $this->get_user_id();
                  $rule=[
                      "nickname"=>"require|length:1,18"
                  ];
                  $msg=[
                      "nickname"=>"昵称必填"
                  ];
                  $check=$this->validate($_POST, $rule,$msg);
                  if($check!==true){
                      return $this->err("9000", $check);
                  }
                   $this->get_user_id();
                   $data= $this->member->editById(array("nickname"=>$nickname),$this->member_id);
                   if($data["stat"]==1){
                        return $this->suc("修改成功");
                   }
                      return $this->err("9001", "更新失败");
        
    }
    
    //更新身份区域信息
    public function update_memberAc(){
        $this->get_user_id();
        $input=input("post.");
                  $rule=[
                      "id_province"=>"require|between:1,1800000",
                      "id_city"=>"require|between:1,1800000",
                      "id_county"=>"require|between:1,1800000",
                  ];
                  $msg=[
                      "id_province"=>"省必填",
                      "id_city"=>"市必填",
                      "id_county"=>"区或县必填",
                  ];
                  $check=$this->validate($input, $rule,$msg);
                  if($check!==true){
                      return $this->err("9000", $check);
                  }
                  $update["id_province"]=$input["id_province"];
                  $update["id_city"]=$input["id_city"];
                  $update["id_county"]=$input["id_county"];
                  $update["id_detail"]=$input["id_detail"];
                   $data= $this->member_area->editByUid($update,$this->member_id);
                   if($data["stat"]==1){
                        return $this->suc("修改成功");
                   }
                      return $this->err("9001", "更新失败");
        
    }
    public function update_nick_imgAc($path=""){
                  $this->get_user_id();

                  $check=$this->validate($_POST, "Member.update_nick_img");
                  if($check!==true){
                      return $this->err("9000", $check);
                  }
                   $this->get_user_id();
                   $data= $this->member->editById(array("nick_img"=>$path),$this->member_id);
                   if($data["stat"]==1){
                        return $this->suc("修改成功");
                   }
                      return $this->err("9001", "更新失败");
        
    }
    //实名认证
    public  function truename_authAc($idnum="",$truename="",$bankname="",$banknum="",$province="",$city="",$county="",$bank_name_area="",$bank_phone="",$verify_code="",$is_up=0){
        $this->get_user_id();
        $input=input("post.");
        $mem=$this->member->get_by_id($this->member_id);
        if($mem["is_rz_1"]==1&&$is_up==0){
                         return $this->err('9002','认证已通过');
        }else if($mem["is_rz_1"]==2&&$is_up==0){
                         return $this->err('9002','认证等待审核'); 
        }
        $data["is_rz_1"]=2;
        $data["idnum"]=$idnum;
        $data["truename"]=$truename;
        $data["bankname"]=$bankname;
        $data["banknum"]=$banknum;
        $data["member_id"]=$this->member_id;
        $data_area["province"]=$province;
        $data_area["city"]=$city;
        $data_area["county"]=$county;
        $data_area["detail"]=$bank_name_area;
        
         $data_area["id_province"]=$input["id_province"];
         $data_area["id_city"]=$input["id_city"];
          $data_area["id_county"]=$input["id_county"];
          $data_area["id_detail"]=$input["id_detail"];
        $data_area["member_id"]=$this->member_id;
        $data["bank_phone"]=$bank_phone;
        $data["verify_code"]=$verify_code;
    if($is_up){
        $result=$this->validate($data, "Member.truename_auth_update");
    }else{
        $result=$this->validate($data, "Member.truename_auth");
    }
        
        if(true !== $result){
           return $this->err("9000",$result);
         }
        //验证省市
        $result=$this->validate($data_area, "MemberArea");
        if(true !== $result){
           return $this->err("9002",$result);
         }
         //验证手机号验证码
        $result=$this->validate($data, "Member.truename_auth_step2");
        if(true !== $result){
           return $this->err("9001",$result);
         }
        
        $check["payeeAcc"]=$data["banknum"];
        $check["payerIdNum"]=$data["idnum"];
        $check["payerName"]=$data["truename"];
        $check["merOrderId"]="yz".date("YmdHis");
        $check["payerPhoneNo"]=$data["bank_phone"];
        $check["timeStamp"]=date("Y-m-d H:i:s");
        //银行卡四要素
        $Kuaiyunzhong= new \ext\Kuaiyunzhong();
        if(1){
        $check_bank=$Kuaiyunzhong->check_sign($check);
        if($check_bank!==true){
            return $this->err('9009','发生错误，提交失败'. '(四要素错误'.$check_bank["message"].')');
        }
        }
        



         unset($data["verify_code"]);
         unset($data["member_id"]);
         $data["true_name_time"]=time();
         $res=$this->member->editById($data,$this->member_id);
         if($data_area){
         $this->member_area->del_all($this->member_id);    
         }
         if($is_up){
                  

             $res_area=$this->member_area->addItem($data_area);
         }else{
             
             $res_area=$this->member_area->addItem($data_area);    
         }

         if($res_area['stat']==1){
              return $this->suc("提交成功");
         }else{
             return $this->err('9009','发生错误，提交失败');
         }
       
    }
   //修改密码
   public  function update_passwordAc($old="",$new="",$rep=""){
        $this->get_user_id();
        $mem=$this->member->get_by_check($this->member_id);
       $data["old"]=sha1($old);
       $data["upass"]=$mem["upass"];
       $data["new"]=$new;
       $data["rep"]=$rep;
       $check=$this->validate($data, "Member.update_password");
       if($check!==true){
           return $this->err(9000, $check);
       }
              $data_in["upass"]=sha1($rep);
          $status=$this->member->editById($data_in,$this->member_id);
         if($status["stat"]==1){
             return $this->suc("修改成功");
         }
         return $this->err(9001,"修改失败");
   }
}
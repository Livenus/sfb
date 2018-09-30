<?php 
namespace app\common\validate;

use think\Validate;

class Member extends Validate{
    protected $rule = [
        'phone'  =>  '^1\d{10}$|unique:member',
        'uname'  =>'',
        'upass'  =>'',
        'verify_code'  =>'require',
        'idnum'  =>'checkIdnum:jf',
        'membergroup_id'    =>'checkMembergroupId:jf',
        'idnum'  =>'',
        'truename'  =>'',
        'bankname'  =>'',
        'banknum'  =>'',
        'bank_phone'  =>'',
        'type'  =>'',
        'path'  =>'',
        "old"=>"",
        "new"=>"",
        "rep"=>"",
    ];
    protected $message = [
        'uname.require'  => '用户名必填',
        'phone.require'  => '手机号必填',
        'phone'          => '手机号格式不正确',
        'phone.unique'   => '手机号已被人使用,您可以直接登录',
        'uname.unique'   => '用户名已经被别人使用',
        'uname.alphaNum' => '用户名格式不正确',
        'uname.length'   => '用户名长度不正确，请输入3至18位的用户名',
        'upass.require'  => '请设置密码',
        'upass.length'   => '密码长度不正确，请输入6至32位的密码',
        'idnum.checkIdnum'=>'身份证号码格式错误',
        'idnum.require'=>'身份证号码必填',
         'idnum.unique'=>'身份证号码已存在',
         'idnum.check_idnum'=>'身份证号码存在了',
         'idnum.is_has'=>'没有提交过认证信息',
        'membergroup_id.checkMembergroupId'    => '会员组不存在',
        'membergroup_id.require'     => '请选择会员组',
        'verify_code.require'   => '手机验证码不能为空', 
        'verify_code.number'   => '手机验证码必须是数字',
        'verify_code.checkCode'   => '手机验证码不正确',
        'verify_code.checkCode_reg'   => '手机验证码不正确',
        'verify_code.checkCode_truename'   => '手机验证码不正确',
        'truename.require'=>'持卡人姓名必填',
        'truename.length'=>'持卡人姓名不正确', 
        'bankname.require'=>'持卡银行必填',
        'bankname.length'=>'持卡银行不正确', 
        'banknum.require'=>'银行卡号必填',
        'banknum.check_banknum'=>'银行卡号规则不正确,请核对所属银行和卡号', 
        'banknum.unique'=>'银行卡号已绑定', 
         'banknum.length'=>'银行卡号不正确', 
             'province' => '省id不存在',
             'city' => '市id不存在',
             'county' => '县id不存在',
             'bank_name_area' => '开户行不正确',
        'bank_phone.require'=>'银行卡预留手机号必填',
        'bank_phone.length'=>'银行卡预留手机号不正确', 
        'type'=>'发送短信类型必须', 
                      "path.require"=>"路径必填",
                      "path.length"=>"路径长度不正确",
                      "path.is_img"=>"图片格式不正确",
                "old.require"=>"旧密码必填",
                "old.is_right"=>"旧密码输入不正确",
                "rep.is_same"=>"两次输入密码不一致"
    ];
    
    protected $scene = [
        'add'   =>  [
            'phone'=>'require|^1\d{10}$|unique:member', 
            'uname'=>'require|unique:member|alphaNum|length:3,18', 
            'upass'=>'require|length:6,32',
            'membergroup_id' => 'require|checkMembergroupId:jf'
        ],
        'edit'  =>  [
            'phone' => 'require|^1\d{10}$'
        ],
        'findpassbyphone' => [
            'phone' => 'require|^1\d{10}$',
            'verify_code' => 'require|number|checkCode:zl',
            'upass' => 'require|length:6,32'
        ],
        'reg'   =>  [
            'phone'=>'require|^1\d{10}$|unique:member', 
            'uname'=>'require|unique:member|alphaNum|length:3,18', 
            'upass'=>'require|length:6,32',
            'membergroup_id' => 'require|checkMembergroupId:jf',
             'verify_code' => 'require|number|checkCode_reg',
        ],
        'truename_auth'   =>  [
            'idnum'=>'require|checkIdnum|unique:member', 
            'truename'=>'require|length:3,18', 
            'bankname'=>'require|length:3,32',
            'banknum' => 'require|length:6,32|check_banknum|unique:member',
        ],
        'truename_auth_update'   =>  [
            'idnum'=>'require|checkIdnum|check_idnum|is_has', 
            'truename'=>'require|length:3,18', 
            'bankname'=>'require|length:3,32',
            'banknum' => 'require|length:6,32|check_banknum',
        ],
        'truename_auth_step2'   =>  [
            'bank_phone'=>'require|^1\d{10}$', 
             'verify_code' => 'require|number|checkCode_truename',
        ],
        'sms_reg'   =>  [
            'phone'=>'require|^1\d{10}$|unique:member', 
             'type' => 'require|number',
        ],
        'update_nick_img'   =>  [
                      "path"=>"require|length:1,88|is_img"
        ],
        'update_password'   =>  [
                      "old"=>"require|length:3,132|is_right",
                       "new"=>"require|length:6,32",
                       "rep"=>"require|length:6,32|is_same",
        ],
    ];
    protected function checkCode($code,$value,$data){
    $sms=model("SmsLog");
    $res=$sms->get_by_phone($data["phone"],3);
     if($res["captcha"]==$data["verify_code"]){
         $sms->editById(array("used"=>1),$res["id"]);
         return true;
     }else{
         return false;
     }
    }
    protected function check_idnum($code,$value,$data){
        $mem=model("Member");
        if($mem->idnum_num($data["member_id"],$data["idnum"])){
            return false;
        }
      return true;
    }
    protected function checkCode_reg($code,$rule,$data){
    $sms=model("SmsLog");
    $res=$sms->get_by_phone($data["phone"]);
     if($res["captcha"]==$data["verify_code"]){
         $sms->editById(array("used"=>1),$res["id"]);
         return true;
     }else{
         return false;
     }
    }
    public  function is_has($value,$rule,$data){
        $mem=model("Member");
        $map["id"]=$data["member_id"];
        $map["idnum"]=["exp","!=''"];
        if($mem->get_count($map)){
            return true;
        }
      return false;
        
        
    }
    protected function is_img($url){
         if(preg_match("/.*(png|jpg|jpeg|gif)+$/", $url)){
             return true;
         }else{
             return FALSE;
         }
        
    }
    protected function checkCode_truename($code,$rule,$data){
    $sms=model("SmsLog");
    $res=$sms->get_by_phone($data["bank_phone"],5);
     if($res["captcha"]==$data["verify_code"]){
         //$sms->editById(array("used"=>1),$res["id"]);
         return true;
     }else{
         return false;
     }
    }
    //密码
    protected function is_right($code,$rule,$data){
          if($data["old"]==$data["upass"]){
              return true;
          }
          return false;
    }
    //新旧密码
    protected function is_same($code,$rule,$data){
          if($data["new"]==$data["rep"]){
              return true;
          }
          return false;
    }
    protected function check_banknum($value,$rule,$data){
        return true;
       $k=substr($data['banknum'], 0, 1);
       $map["card_bin"]= ["like","%{$k}%"];
       $bank_type=model("BankType");
       $has_num=$bank_type->where($map)->count();
       if($has_num){
           return true;
       }else{
           return false;
       }
    }
    protected  function checkIdnum($idnum, $rule, $data){
        
        $idnum = strtoupper ( $idnum );
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arr_split = array ();
        if (! preg_match ( $regx, $idnum )) {
            return FALSE;
        }
        if (15 == strlen ( $idnum )) {
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
            
            @preg_match ( $regx, $idnum, $arr_split );
            // 检查生日日期是否正确
            $dtm_birth = "19" . $arr_split [2] . '/' . $arr_split [3] . '/' . $arr_split [4];
            if (! strtotime ( $dtm_birth )) {
                return FALSE;
            } else {
                return TRUE;
            }
        } else {
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match ( $regx, $idnum, $arr_split );
            $dtm_birth = $arr_split [2] . '/' . $arr_split [3] . '/' . $arr_split [4];
            if (! strtotime ( $dtm_birth )){ 		// 检查生日日期是否正确
                return FALSE;
            } else {
                // 检验18位身份证的校验码是否正确。
                // 校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                $sign = 0;
                for($i = 0; $i < 17; $i ++) {
                    $b = ( int ) $idnum {$i};
                    $w = $arr_int [$i];
                    $sign += $b * $w;
                }
                $n = $sign % 11;
                $val_num = $arr_ch [$n];
                if ($val_num != substr ( $idnum, 17, 1 )) {
                    return FALSE;
                } 			// phpfensi.com
                else {
                    return TRUE;
                }
            }
        }
    }
    
    protected  function checkMembergroupId($value, $rule, $data){
        if($value <= 0) return false;
        $r = \model('MemberGroup')->where('id',(int)$value)->count();
        if($r <= 0){
            return false;
        }
        return true;
    }
 
}
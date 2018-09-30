<?php
namespace app\api\controller;
/**
 * 短信接口
 */

class Sms extends \app\api\controller\Home {
    
    public function sendAc(){
        //获取发送短信的参数
        $data = $this->get_params();
        //生成随机验证码
        $code = rand(100000,999999);
        //存session
        \think\Session::set('code'.$data['phone'],$code);
        //
        $data['captcha'] = $code;
        
        $result = $this->validate($data);
        if($result !== true){
            return $this->err(0,$result);
        }
        //判断是否是注册
        if($data['type'] != 1&&$data['type'] != 3){
             $this->get_user_id();
            $data['member_id'] = $this->member_id;
        }
        
        $param = [
            'code' => $code,
            'mobile' => $data['phone'],
             'code_id' => "register",
        ];
        //发送短信
        $sms = new \app\api\model\SendSms();
        $ret = $sms->SendSms($param);
        
        //处理短信发送返回结果，同时存入短信发送记录数据库
        $data['errMsg'] = $ret['msg'];
        
        
        if($ret['code'] == 0){
            $data['state'] = 1;
            $this->sms_log($data);
            return $this->suc($data['captcha']);
        }else{
            //需要定义系统出现错误情况下的数字代码
            $this->sms_log($data);
            //
            return $this->err($ret['code'],'发送失败'.$data['errMsg']);
        }
    }
    private function sms_log($data){
        $addlog = new \app\api\model\SmsLog();
        if($data['type'] != 1&&$data['type'] != 3){
             $this->get_user_id();
           $data['member_id'] = $this->member_id;
      }
        
        $addlog->addItem($data);
    }
    private function get_params(){
        $phone= trim(input('post.phone'));
        $params = [
            'phone' =>$phone,
            //'ip'    => real_ip(),
            'type'  =>  (int)input('post.type'),
            'add_time' => time()
        ];
        return $params;
    }
}
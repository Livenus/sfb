<?php 
namespace app\api\model;
use think\Config;
class SendSms extends \think\Model{
    public function getParameter(){
        
    }
    public function SendSms($data){
        $smstpl=model("SmsTpl");
        Config::load(APP_PATH.'/sms.php');
        $smskey= config("sms");
        $tpl=$smstpl->get_by_code($data['code_id']);
        $sms = new \sms\Yunpian();
        $sms->mobile = $data['mobile'];
        $sms->code = $data['code'];
        $sms->tpl_id = $tpl['out_tplid'];
        $sms->apikey=$smskey['apikey'];
        $ret = $sms->sendSMS();
        model("Log")->addItem(array("log_info"=>print_r($ret,true),"ip"=>0));
        if(!$ret){
            return ['code' => 999,'msg' => "系统错误"];
        }
        if(isset($ret['systemErr']) && $ret['systemErr'] == 1){
            return ['code' => 999,'msg' => $ret['msg']];
        }
        if(isset($ret['http_status_code']) && $ret['http_status_code'] == 400){
            return ['code'=>$ret['code'],'msg'=>$ret['msg'].','.$ret['detail']];
        }else{
            return ['code'=>$ret['code'],'msg'=>$ret['msg'].',花费'.$ret['fee'].'元'];
        }
    }
    //还款失败提醒
    public function send_back($data){
        $smstpl=model("SmsTpl");
        Config::load(APP_PATH.'/sms.php');
        $smskey= config("sms");
        $tpl=$smstpl->get_by_code($data['code_id']);
        $sms = new \sms\Yunpian();
        $sms->mobile = $data['mobile'];
        $sms->tpl_id = $tpl['out_tplid'];
        $sms->apikey=$smskey['apikey'];
        $ret = $sms->send_errSMS($data["text"]);
        if(!$ret){
            $datam=['code' => 999,'msg' => "系统错误"];
        }
        if(isset($ret['systemErr']) && $ret['systemErr'] == 1){
            $datam=['code' => 999,'msg' => $ret['msg']];
        }
        if(isset($ret['http_status_code']) && $ret['http_status_code'] == 400){
            $datam=['code'=>$ret['code'],'msg'=>$ret['msg'].','.$ret['detail']];
        }else{
            $datam=['code'=>$ret['code'],'msg'=>$ret['msg'].',花费'.$ret['fee'].'元'];
        }
        $log_data["phone"]=$data['mobile'];
        $log_data["type"]=8;
        $log_data["errMsg"]=$datam["msg"];
        $log_data["state"]=$ret['code'] == 0?1:0;
        $log_data["member_id"]=$data["member_id"];
         $log_data["add_time"]= time();
        $this->sms_log($log_data);
    }
    private function sms_log($data){
        $addlog = new \app\api\model\SmsLog();
        $addlog->addItem($data);
    }
}
?>
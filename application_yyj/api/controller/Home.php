<?php
namespace app\api\controller;

use think\Response;
use think\Db;
class Home extends \think\Controller
{
    public $member_id=0;
    public $member_info=[];
    public function suc($data)
    {
        //记录请求时间
        // 获取基本信息
        if(config("app_trace_mysql")){
        $runtime = number_format(microtime(true) - THINK_START_TIME, 10);
        $reqs    = $runtime > 0 ? number_format(1 / $runtime, 2) : '∞';
        $mem     = number_format((memory_get_usage() - THINK_START_MEM) / 1024, 2);

        // 页面Trace信息
        
        if (isset($_SERVER['HTTP_HOST'])) {
            $uri = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            $uri = 'cmd:' . implode(' ', $_SERVER['argv']);
        }
        $base = [
            'member_id'=>$this->member_id,
            'request'=>$_SERVER['REQUEST_TIME'],
            'url' =>$uri,
            'times' => number_format($runtime, 6)
        ];
        Db::table("yys_debug")->insert($base);  
            
        }
        return Response::create([
            'stat' => 1,
            'data' => $data
        ], 'json')->code(200);
    }

    public function err($errcode, $errmsg)
    {
        if(config("app_trace_mysql")){
        $runtime = number_format(microtime(true) - THINK_START_TIME, 10);
        $reqs    = $runtime > 0 ? number_format(1 / $runtime, 2) : '∞';
        $mem     = number_format((memory_get_usage() - THINK_START_MEM) / 1024, 2);

        // 页面Trace信息
        
        if (isset($_SERVER['HTTP_HOST'])) {
            $uri = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            $uri = 'cmd:' . implode(' ', $_SERVER['argv']);
        }
        $base = [
            'member_id'=>$this->member_id,
            'request'=>$_SERVER['REQUEST_TIME'],
            'url' =>$uri,
            'times' => number_format($runtime, 6)
        ];
        Db::table("yys_debug")->insert($base);  
            
        }
        return Response::create([
            'stat' => 0,
            'errcode' => $errcode,
            'errmsg' => $errmsg
        ], 'json')->code(200);
    }
    public function get_user_id(){
        $token=input("post.__token_");
        if(empty($token)){
          $token= input("param.__token_");  
        }
        
        $Member_login_token= model("Member_login_token");
        $Member_model= model("Member");
        $user=$Member_login_token->get_by_token_info_t($token);
       
        if($user&&$user["exprired_time"]>time()){
            $this->member_info=$Member_model->get_by_id($user["member_id"]);
             $this->member_id=$user["member_id"];
             define("MEMBER_ID", $user["member_id"]);
            return $user["member_id"];
        }else{
            //超时
            $t=time();
            if(is_numeric($user["exprired_time"])&&$t>$user["exprired_time"]){
            $reo=$this->err('90001','登录失效');
            $reo->send();
            exit(); 
                
            }
            //
            $old=$Member_login_token->get_by_old_token($token);
            if($old&&$old["device_id"]!=$old["old_device_id"]){
            $reo=$this->err('90001','账号已在别处登录');
            $reo->send();
            exit();  
            }
            $reo=$this->err('90001','没有登录');
            $reo->send();
              exit();
        }
    }
}

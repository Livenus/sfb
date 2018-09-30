<?php
namespace app\admin\controller;

class Login extends \think\Controller{
    private $count = 0;
    public function _initialize(){
        $this->admin = \think\Loader::model('Admin');
        $this->request = \think\Request::instance();
    }
    public function loginAc(){
        $request = \think\Request::instance();
        if ($request->isAjax()) {
            $username = (string)input('post.username');
            $this->admin->username = $username;
            $this->admin->password = (string)input('post.password');
            $this->admin->code = (string)input('post.code');
            //$this->admin->token = input('post.token');
            if(!captcha_check(input('post.code'))){
                //验证失败
                return message(0,'验证码错误');
            }
            
            $info = $this->admin->field('adminid,errtimes,locktime,salt')->where('username',$username)->find();
            $this->count = $info['errtimes'];
            $this->admin->salt = $info['salt'];
            $leftcount = 5 - $this->count;
            if($leftcount > 0){
                $ret = $this->admin->login();
                if($ret['status'] == 1){
                    $this->admin->editById(['errtimes'=>0,'locktime'=>0],$info['adminid']);
                    return message(1,$ret['msg']);
                }elseif($ret['status'] == 0){
                    $this->count += 1;
                    $leftcount -= 1;
                    $this->admin->editById(['errtimes'=>$this->count],$info['adminid']);
                    //$this->admin->where('')->data(['errtimes',$this->count])->save();
                    if($this->count == 5){
                        $data = [
                            'locktime' => time()
                        ];
                        $this->admin->editById($data,$info['adminid']);
                        //$this->admin->edit(['username'=>input('post.username')],['locktime'=>time()]);
                    }
                    return message(0,$ret['msg'].',您还剩'.$leftcount.'机会');
                }elseif($ret['status'] == -1) {
                    return message(0,$ret['msg']);
                }
            }else{
                $lefttime = ceil(($info['locktime'] + 43200 - time())/3600);
                return message(0,'您已经出错5次，需要'.$lefttime.'小时后才能再次登录');
            }
        }
        return view('user/login');
    }

    

    public function logoutAc(){
        $this->admin->logout();
        $this->success('您已退出！');
    }
}
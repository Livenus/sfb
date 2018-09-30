<?php
namespace app\admin\controller;

class User extends \app\admin\controller\Home{
    public $admin;
    private $count = 0;
    private $lang;
    public function _initialize(){
        parent::_initialize();
        $this->admin = \think\Loader::model('Admin');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }
    /*public function loginAc(){
        $request = \think\Request::instance();
        if ($request->isAjax()) {
            $this->admin->username = input('post.username');
            $this->admin->password = input('post.password');
            $this->admin->code = input('post.code');
            //$this->admin->token = input('post.token');
            if(!captcha_check(input('post.code'))){
                //验证失败
                return ['stat'=>0,'msg'=>'验证码错误'];
            }
            $leftcount = 5 - $this->count;
            if($leftcount >= 0){
                $ret = $this->admin->login();
                if($ret['status'] == true){
                    return ['stat'=>1,'msg'=>$ret['msg']];
                }else{
                    $this->count += 1;
                    if($this->count == 5){
                        $this->admin->edit(['username'=>input('post.username')],['locktime'=>time()]);
                    }
                    return ['stat'=>0,'msg'=>$ret['msg'].',您还剩'.$leftcount.'机会'];
                }
                //echo json_encode($ret);die();
            }else{
                //$lefttime = (time() - $locktime)/3600;
                return ['stat' => 0,'msg' => '您已经出错5次，需要12小时后才能再次登录'];
            }
        }
        return view('login');
    }*/

    /**
     * 修改个人资料
     */
    public function profileAc(){
        $admininfo = \think\Session::get('admininfo');
        $u_id = intval($admininfo['adminid']);
        if($this->request->isAjax()){
            //新密码
            $new_password = (string) input('post.npass');
            //重复密码
            $new_password_c = (string) input('post.confirm_password');
            //旧密码
            $old_password = (string) input('post.opass');
            $adminid = intval(input('post.id'));
            $realname = (string)input('post.realname');
            if($adminid != $u_id){
                return message(0,'There is an error');
            }
            
            $u_mod = model('Admin');
            $u_info = $u_mod->where('adminid',$u_id)->find();
            if (empty($u_info))
                return message(0,'未找到用户');
                
                if(!empty($new_password)){
                    if ($new_password != $new_password_c)
                        return message(0,'两次输入的新密码不一致');
                        //旧密码加密
                        $o_encryption = compile_password(array('password'=>$old_password,'ec_salt' => $u_info['salt']));
                        $salt = $this->admin->getsalt();
                        //新密码加密
                        $n_encryption = compile_password(array('password'=>$new_password,'ec_salt' => $salt));
                        
                        // 验证原密码是否正确
                        if ($o_encryption != $u_info['password'])
                            return message(0,'原密码不正确');
                            
                            if ($new_password == $old_password)
                                return message(0,'新旧密码一样？换一个吧');
                                
                                $data = [
                                    'password' => $n_encryption,
                                    'salt' => $salt,
                                    'realname'=>$realname
                                ];
                }else{
                    $data = [
                        'realname' => $realname
                    ];
                }
                
                
                $ret = $u_mod->editById($data,$u_id);
                if($ret['stat'] == 1){
                    return message(1,$ret['msg']);
                }else{
                    return message(0,$ret['msg']);
                }
        }
        $this->assign('item',$admininfo);
        $group = model('AuthGroup')->select();
        $this->assign('group',$group);
        return view('profile');
    }

    public function logoutAc(){
        $this->admin->logout();
        $this->success('您已退出！');
    }
    public function adminListAc(){
        $id = $this->admin->check('user_adminlist');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        return $this->fetch();
    }
    public function listajaxAc(){
         $where = '';
         $name = (string)input('get.name');
         $realname = (string)input('get.realname');
         $offset = (int)input('get.offset');
         if(!empty($name)){
             $where .= " and username like %".$name."%";
         }
         if(!empty($realname)){
             $where .= " and realname like %".$realname."%";
         }
         $admin_lists = $this->admin->field('adminid,username,realname,isSuper,stat,m_addtime')->where($where)->limit($offset,20)->select();
         
         //$this->assign('adminLists',$lists);
         
         $count = $this->admin->where($where)->limit($offset,20)->count();
         echo json_encode(array('rows'=>$admin_lists,'total'=>$count));
    }
    public function addAc(){
        $id = $this->admin->check('user_add');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        //$ajax = intval(input('post.ajax'));
        if($this->request->isAjax()){
            $data = $this->get_params();
            
            $result = $this->validate($data,'User.add');
            if(true !== $result){
                // 验证失败 输出错误信息
                return message(0,$result);
            }
            
            if($this->admin->where('username',$data['username'])->find()){
                return message(0,'该管理员已经存在');
            }else{
                $data['password'] = compile_password(array('password'=>$data['password'],'ec_salt' => $data['salt']));
                $ret = $this->admin->data($data)->save();
                if($ret){
                    admin_log($data['username'],$this->lang['add'],$this->lang['admin']);
                    return message(1,'管理员添加成功');
                }else{
                    return message(0,'管理员添加失败');
                }
            }
            
        }
        $group = model('AuthGroup')->select();
        $this->assign('group',$group);
        return view('admin_add');
    }
    public function editAc(){
        $id = $this->admin->check('user_edit');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $id = empty(input('get.uid')) ? intval(input('post.id')) : intval(input('get.uid'));
        $admininfo = $this->admin->where('adminid',$id)->find();
        //$ajax = intval(input('post.ajax'));
        if($this->request->isAjax()){
            $realname = (string)input('post.realname');
            $password = (string)input('post.password');
            $photo = (string)input('post.photo');
            $stat = intval(input('post.stat'));
            $group_id = intval(input('post.group_id'));
            $admin_id = intval(input('post.id'));
            $data = [
                'realname' => $realname,
                'password' => $password
            ];
            $result = $this->validate($data,'User.edit');
            if(true !== $result){
                // 验证失败 输出错误信息
                return message(0,$result);
            }
            $salt = $this->admin->getsalt();
            if(empty($password)){
                $params = [
                    'realname' => $realname,
                    'photo' => $photo,
                    'stat' => $stat,
                    'group_id' => $group_id
                ];
            }else{
                $params = [
                    'realname' => $realname,
                    'password' => compile_password(array('password'=>$password,'ec_salt' => $salt)),
                    'stat' => $stat,
                    'photo' => $photo,
                    'group_id' => $group_id,
                    'salt' => $salt
                ];
            }
            //var_dump($admin_id);var_dump($params);die();
            //$ret = $this->admin->save($params,['adminid'=>$admin_id]);
            $ret = $this->admin->editById($params,$admin_id);
            if($ret['stat'] == 1){
                admin_log($admininfo['username'],$this->lang['edit'],$this->lang['admin']);
                return message(1,'管理员信息修改成功');
            }else{
                return message(0,$ret['msg']);
            }
        }
        $group = model('AuthGroup')->select();
        $this->assign('group',$group);
        $this->assign('item',$admininfo);
        return view('admin_view');
    }
    public function delAc(){
        $id = $this->admin->check('user_del');
        if($id){
            $ret = $this->admin_priv($id,$this->request->isAjax());
            if(!$ret) return message(0,'你没有操作权限');
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $idObj = input('post.')['id'];
        $ids = '';
        if(count($idObj) > 1){
            $type = '批量';
        }else{
            $type= '';
        }
        if(in_array(1, $idObj)){
            return message( 0,'超级管理员是不能删除的');
        }
        foreach($idObj as $id){
            $ids .= $id.',';
        }
        $ids = trim($ids,',');
        //var_dump($idObj);die();
        $info = $this->admin->where('adminid','in',$ids)->select();
        if(empty($info)){
            return message(0,'one of the administrator is not found');
        }else{
            $message = '';
            foreach($info as $v){
                $message .= $v['username'].',';
            }
            $message = trim($message,',');
            if($this->admin->where('adminid','in',$ids)->delete()){
                admin_log($message,$type.$this->lang['delete'],$this->lang['admin']);
                return message(1,'管理员删除成功');
            }else{
                return message(0,'管理员删除失败');
            }
        }
    }
    
    private function get_params(){
        $params = array();
        $salt = $this->admin->getsalt();
        $params['username'] = (string)input('post.username');
        $params['password'] = (string)input('post.password');
        $params['realname'] = (string)input('post.realname');
        $params['photo'] = (string)input('post.photo');
        $params['stat'] = intval(input('post.stat'));
        $params['group_id'] = intval(input('post.group_id'));
        $params['m_addtime'] = time();
        $params['salt'] = $salt;
        return $params;
    }
}
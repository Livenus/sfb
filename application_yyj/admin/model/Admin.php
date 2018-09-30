<?php
namespace app\admin\model;
class Admin extends \app\admin\model\Index{
    public $username = '';
    public $password = '';
    public $code = '';
    public $salt = '';
	protected function initialize(){
		parent::initialize();
	}
	
	public function login(){
	    $passmd5 = compile_password(array('password'=>$this->password,'ec_salt' => $this->salt));
	   
	    $admin = $this->find(['username' => $this->username]);
	    if($admin == null){
	        return array('status' => -1,'msg' => '用户不存在');
	    }else{
	        $admininfo = $this->find(['username' => $this->username,'password' => $passmd5]);
	        if($admininfo != null){
	            if($admininfo['stat'] == 1){
	                //$lefttime = 12 - (time() - $admininfo['locktime'])/3600;
	               // if($lefttime > 0){
	                //    return array('status' => false,'msg' => '您因为多次输错密码，账号暂时被系统锁定，需要'.$lefttime.'小时后才允许再次登录');
	                //}else{
	                    \think\Session::set('admininfo',$admininfo);
	                    if($admininfo['adminid'] != 1 || $admininfo['group_id'] != 0){
	                        $action_list = model('AuthGroup')->where('id',$admininfo['group_id'])->find();
	                        \think\Session::set('group',$action_list['title']);
	                        \think\Session::set('action_list',$action_list['rules']);
	                    }else{
	                        \think\Session::set('action_list',0);
							\think\Session::set('group','超级管理员');
	                    }
    	                return array('status' => 1,'msg' => '登录成功');
	               // }
	            }else{
	                return array('status' => -1,'msg' => '您的账号已被限制，不允许登录。');
	            }
	        }else{
	            return array('status' => 0,'msg' => '密码错误');
	        }
	    }
	}
	public function getsalt(){
	    $str = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	    $len = strlen($str) - 1;
	    $count = 1;
	    $salt = '';
	    while($count <= 6){
	        $salt .= substr($str, rand(0,$len),1);
	        $count++;
	    }
	    return $salt;
	}
	public function is_login(){
		if(\think\Session::get('admininfo') != null){
			return true;
		}else{
			return false;
		}
	}
	public function logout(){
	    \think\Session::delete('admininfo');
	}
    public  function get_by_id($id){
        
        $data=$this->where(array("adminid"=>$id))->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
	public function find($params){
	    $admininfo = \think\Db::name('admin')->where($params)->select();
	    if(!empty($admininfo)){
	        return $admininfo[0];
	    }else{
	        return '';
	    }
	    
	}
	
	/*public function getGroupIdAttr($value){
	    $adminGroup = array();
	    if($value != 0){
	        $group = model('AuthGroup')->where('id',$value)->find();
	        return $group['title'];
	    }else{
	        return '超级管理员组';
	    }
	}*/

        
    public function get_count($map){
        
        return $this->where($map)->count();
    }
            }

?>

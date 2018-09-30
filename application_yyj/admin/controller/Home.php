<?php
/**
 * 后台总控制器
 * 
 */

namespace app\admin\controller;
use app\admin\model\Admin;

class Home extends \think\Controller
{
    public function _initialize(){//var_dump(\think\Session::get('action_list'));
        //判断管理员是否登录
		$admin = new Admin;
        if(!$admin->is_login()){
			$this->redirect('Login/login');
        }
        $this->check_url();
    }
    //通过url判断权限
    public  function check_url(){
        $user=session("admininfo");
        if($user["isSuper"]==1){
            
            return true;
        }
        $role= model("AuthGroup");
        $menu= model("menu");
        $rules=$role->get_by_id($user["group_id"]);
        $request= request();
        $path= strtolower($request->path());
        $module= strtolower($request->module());
        $menu_url= str_ireplace($module, "", $path);
        $menu_url= substr($menu_url, 1);
        $menu_url=trim($menu_url);
        $row=$menu->get_by_url($menu_url);
        $rules_g=$rules["rules"];
        //没有设置权限
        if(empty($row)){
            $rules_a= explode(",", $rules_g);
            define("RULES",$rules_g);
            return true;
        }

        if(empty($rules_g)){
            if($request->isAjax()){
                echo json_encode(message(0,'没有权限'));
                exit();
            }
            exit("没有权限");
        }
            $rules_a= explode(",", $rules_g);
            define("RULES", $rules_g);
        if(in_array($row["id"],$rules_a)){
            return true;
        }
            if($request->isAjax()){
                echo json_encode(message(0,'没有权限'));
                exit();
            }
       exit("没有权限");
        
        
    }
    /**
     * 判断管理员对某一个操作是否有权限。
     *
     * 根据当前对应的action_code，然后再和用户session里面的action_list做匹配，以此来决定是否可以继续执行。
     * @param     string    $priv_str    操作对应的priv_str
     * @param     string    $msg_type       返回的类型
     * @return true/false
     */
    public function admin_priv($priv_str, $isAjax = false){
        return true;
        if (\think\Session::get('action_list') == 0){
            return true;
        }//var_dump($priv_str);
        $action_list = explode(',', \think\Session::get('action_list'));
        if(in_array($priv_str, $action_list)){
            return true;
        }else{
            $data['msg'] = '对不起,您没有执行此项操作的权限!';
            $data['links'] = array('text' => '返回上一页', 'href' => 'javascript:history.back(-1)');
            $data['auto_redirect'] = true;
            $data['msg_type'] = 1;
            if (!$isAjax){
                $this->redirect('home/sys_msg',['data'=>$data]);
                //$this->sys_msg('对不起,您没有执行此项操作的权限!', 0, $link);
            }else{
                return false;
            }
            return false;
        }
    }
    /**
     * 系统提示信息
     *
     * @access      public
     * @param       string      msg_detail      消息内容
     * @param       int         msg_type        消息类型， 0消息，1错误，2询问
     * @param       array       links           可选的链接
     * @param       boolen      $auto_redirect  是否需要自动跳转
     * @return      void
     */
    public function sys_msgAc(){
        $data = $_GET['data'];
        //var_dump($data);die();
        if (count($data['link']) == 0){
            $links[0]['text'] = '返回上一页';
            $links[0]['href'] = 'javascript:history.back(-1)';
        }else{
            $links[0] = $data['link'];
        }
        //var_dump($links[0]['href']);die();
        $this->assign('ur_here',     '系统信息');
        $this->assign('msg_detail',  $data['msg']);
        $this->assign('msg_type',    $data['msg_type']);
        $this->assign('links',       $links);
        $this->assign('default_url', $links[0]['href']);
        $this->assign('auto_redirect', $data['auto_redirect']);
        
        return view('public/message');
        
        exit;
    }
}

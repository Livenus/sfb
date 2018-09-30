<?php
namespace app\admin\controller;

class Adminlog extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->adminlog = \think\Loader::model('log');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }

    public function indexAc(){
        $id = $this->adminlog->check('adminlog_index');
        if($id){
            $ret = $this->admin_priv($id);
            if(!$ret) return message(0,'你没有操作权限');
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        return view('user/log');
    }
    
    
    public function ajaxlistAc(){
        $where = '';
        
        $name = (string)input('get.username');
        $start = strtotime((string)input('get.start'));
        $end = strtotime((string)input('get.start'));
        $offset = (int)input('get.offset');
        if(!empty($name)){
            $where .= " AND u_name like %'{$name}'%";
        }
        if(!empty($start)){
            $where .= " AND log_time >= ".$start;
        }
        if(!empty($end)){
            $where .= " AND log_time <= ".$end;
        }
        $where["u_id"]=["neq",0];
        $lists = $this->adminlog->where($where)->order('id', 'desc')->limit($offset,20)->select();
		
        $total = $this->adminlog->where($where)->order('id', 'desc')->limit($offset,20)->count();
        echo json_encode(array('rows'=>$lists,'total'=>$total));
    }
    public function logdelAc(){
        $id = $this->adminlog->check('adminlog_logdel');
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
        foreach($idObj as $id){
            $ids .= $id.',';
        }
        $ids = trim($ids,',');
        $info = $this->adminlog->where('id','in',$ids)->select();
        if(empty($info)){
            return message(0,'smslog is not found');
        }else{
            $message = '';
            foreach($info as $v){
                $message .= $v['id'].',';
            }
            $message = trim($message,',');
            if($this->adminlog->where('id','in',$ids)->delete()){
                admin_log($message,$type.$this->lang['delete'],$this->lang['adminlog']);
                return message(1,'delete success');
            }else{
                return message(0,'delete fail');
            }
        }
    }
    
}
<?php
namespace app\admin\controller;

class Sms extends \app\admin\controller\Home{
    public $sms;
    private $lang;
    public function _initialize(){
        parent::_initialize();
        $this->sms = \think\Loader::model('SmsTpl');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }

    public function indexAc(){
        $id = $this->sms->check('sms_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $lists = $this->sms->where($where)->select();
        $this->assign('lists',$lists);
        $this->assign('empty',"<option value='-1'>您还没有添加模板</option>");
        return $this->fetch();
    }
    public function listajaxAc(){
         $where = '';
         $id = intval(input('get.id'));
         $offset = (int)input('get.offset');
         if($id == 0){
             $lists = $this->sms->limit($offset,20)->select();
             $count = $this->sms->limit($offset,20)->count();
         }else{
             $lists = $this->sms->where('id',$id)->limit($offset,20)->select();
             $count = $this->sms->where('id',$id)->limit($offset,20)->count();
         }
         exit(json_encode(array('rows'=>$lists,'total'=>$count)));  
    }
    public function addAc(){
        $id = $this->sms->check('sms_add');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        if($this->request->isAjax()){
            $data = $this->get_params();
            $validate = $this->validate($data, 'Sms.add');
            if($validate !== true){
                return message(0, $validate);
            }
            
            $ret = $this->sms->addItem($data);
            if($ret['stat'] == 1){
                admin_log($data['name'], $this->lang['add'],$this->lang['sms']);
                return message(1,'添加成功');
            }else{
                return message(0,$ret['msg']);
            }
        }
        $this->assign('action',$this->request->action());
        return view('sms_view');
    }
    public function editAc(){
        $id = $this->sms->check('sms_edit');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $id = empty(input('get.id')) ? (string)input('post.id') : (string)input('get.id');
        
        if($this->request->isAjax()){
            $data = $this->get_params();
            $validate = $this->validate($data, 'Sms.edit');
            if($validate !== true){
                return message(0, $validate);
            }
            $ret = $this->sms->editById($data,$id);
            if($ret['stat'] == 1){
                admin_log($data['name'], $this->lang['edit'],$this->lang['sms']);
                return message(1,'编辑成功');
            }else{
                return message(0,$ret['msg']);
            }
        }
        $info = $this->sms->where('id',$id)->find();
        $this->assign('info',$info);
        $this->assign('action',$this->request->action());
        return view('sms_view');
    }
    public function delAc(){
        $id = $this->sms->check('sms_del');
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
       
        $info = $this->sms->where('id','in',$ids)->select(); 
        if(empty($info)){
            return message(0,'one of the smstpl is not found');
        }else{
            $message = '';
            foreach($info as $v){
                $message .= $v['name'].',';
            }
            $message = trim($message,',');
            if($this->sms->where('id','in',$ids)->delete()){
                admin_log($message,$type.$this->lang['delete'],$this->lang['sms']);
                return message(1,'模板删除成功');
            }else{
                return message(0,'模板删除失败');
            }
        }
    }
    public function smslogAc(){
        $id = $this->sms->check('sms_smslog');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $smslog = new \app\admin\model\SmsLog();
        /*$smslog->whereBetween('add_time',[1354,987])->select();
        \think\Db::listen(function($sql,$time,$explain){
            // 记录SQL
            echo $sql. ' ['.$time.'s]';
            // 查看性能分析结果
            dump($explain);
        });*/
        return $this->fetch();
    }
    
    public function logajaxlistAc(){
        $smslog = new \app\admin\model\SmsLog();
        $where = '';
        
        $phone = (string)input('get.phone');
        $start = strtotime((string)input('get.start'));
        $end = strtotime((string)input('get.start'));
        $offset = (int)input('get.offset');
        if(!empty($phone)){
            $where .= " AND phone like %'{$phone}'%";
        }
        if(!empty($start)){
            $where .= " AND add_time >= ".$start;
        }
        if(!empty($end)){
            $where .= " AND add_time <= ".$end;
        }
        
        $lists = $smslog->where($where)->order('id', 'desc')->limit($offset,20)->select();
        foreach($lists as &$info){
            $info->append(['state_text']);
        }
        $total = $smslog->where($where)->order('id', 'desc')->limit($offset,20)->count();
        //$lists = $smslog->phone()->add_time()->all();
        echo json_encode(array('rows'=>$lists,'total'=>$total));
    }
    public function logdelAc(){
        $id = $this->sms->check('sms_logdel');
        if($id){
            $ret = $this->admin_priv($id,$this->request->isAjax());
            if(!$ret) return message(0,'你没有操作权限');
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $smslog = new \app\admin\model\SmsLog();
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
        $info = $smslog->where('id','in',$ids)->select();
        if(empty($info)){
            return message(0,'smslog is not found');
        }else{
            $message = '';
            foreach($info as $v){
                $message .= $v['captcha'].',';
            }
            $message = trim($message,',');
            if($smslog->where('id','in',$ids)->delete()){
                admin_log($message,$type.$this->lang['delete'],$this->lang['smslog']);
                return message(1,'delete success');
            }else{
                return message(0,'delete fail');
            }
        }
    }
    
    public function exportlogAc(){
        $id = $this->sms->check('sms_exportlog');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $smslog = new \app\admin\model\SmsLog();
        $where = '';
        
        $phone = (string)input('get.phone');
        $start = strtotime((string)input('get.start'));
        $end = strtotime((string)input('get.start'));
        if(!empty($phone)){
            $where .= " AND phone like %'{$phone}'%";
        }
        if(!empty($start)){
            $where .= " AND add_time >= ".$start;
        }
        if(!empty($end)){
            $where .= " AND add_time <= ".$end;
        }
        //$smslog->export();
    }
    private function get_params(){
        $params = [
            'name'  =>  (string)input('post.name'),
            'code'  =>  (string)input('post.code'),
            'content'   =>  (string)input('post.content'),
            'out_tplid' =>  (int)input('post.out_tplid'),
            'stat'  =>  (int)input('post.stat')
        ];
        return $params;
    }
}
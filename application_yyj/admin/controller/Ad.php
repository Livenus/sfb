<?php 
namespace app\admin\controller;
class Ad extends \app\admin\controller\Home{
   
    public function _initialize(){
        parent::_initialize();
        $this->ad = \think\Loader::model('Adv');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
       
    }
    public function indexAc(){
        $id = $this->ad->check('ad_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $adv = new \app\admin\model\Advpos();
        $lists = $adv->select();
        $this->assign('lists',$lists);
        if(!empty(input('get.ap_id'))) $this->assign('ap_id',intval(input('get.ap_id')));
        $this->assign('action',$this->request->action());
        return $this->fetch();
    }
   
    public function listajaxAc(){
        $where = '';
        $ap_id = (int)input('get.ap_id');
        $a_name = (string)input('get.a_name');
        if($ap_id != 0){
            $where = 'ap_id ='.intval($ap_id);
            if(!empty($a_name)){
                $where = " and name like '%".$a_name."%'";
            }
        }
        $offset = (int)input('get.offset');
        $lists = $this->ad->where($where)->limit($offset,20)->select();
        foreach($lists as &$info){
            switch ($info['stat']) {
                case '0':
                   $info['stat'] = '禁用';
                    break;
                 case '1':
                   $info['stat'] = '启用';
                    break;
                default:
                     break;
            }
            $info->append(['position']);
        }
        $total = $this->ad->where($where)->limit($offset,20)->count();
        echo json_encode(array('rows'=>$lists,'total'=>$total));
    }
    public function addAc(){
        $id = $this->ad->check('ad_add');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        if($this->request->isAjax()){
            $data = $this->get_params();
            $validate = $this->validate($data, 'Ad.add');
            if($validate !== true){
                return message(0, $validate);
            }
            $ret = $this->ad->addItem($data);
            if($ret['stat'] == 1){
                admin_log($data['name'],$this->lang['add'],$this->lang['ad']);
                return message(1,'广告添加成功');
            }else {
                return message(0,$ret['msg']);
            }
        }
        $ap_id = input('get.ap_id');
        if(!empty($ap_id)){
            $this->assign('ap_id',(int)$ap_id);
        }
        $this->assign('action',$this->request->action());
        $adv = new \app\admin\model\Advpos();
        $lists = $adv->select();
        $this->assign('lists',$lists);
        $this->assign('action',$this->request->action());
        return view('ad_view');
    }
    public function editAc(){
        $id = $this->ad->check('ad_edit');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $id = empty(input('get.id')) ? (int)input('post.id') : intval(input('get.id'));
        if($this->request->isAjax()){
            $data = $this->get_params();
            $validate = $this->validate($data, 'Ad.edit');
            if($validate !== true){
                return message(0, $validate);
            }
            $ret = $this->ad->editById($data,$id);
            if($ret['stat'] == 1){
                admin_log($data['name'],$this->lang['edit'],$this->lang['ad']);
                return message(1,'广告修改成功');
            }else{
                return message(0,$ret['msg']);
            }
        }
        $adv = new \app\admin\model\Advpos();
        $lists = $adv->select();
        $this->assign('lists',$lists);
        $info = $this->ad->where('id',$id)->find();
        $this->assign('info',$info);
        $this->assign('action',$this->request->action());
        return view('ad_view');
    }
    public function delAc(){
        $id = $this->ad->check('ad_del');
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
        
        $info = $this->ad->where('id','in',$ids)->select();
        if(empty($info)){
            return message(0,'one of the ad is not found');
        }else{
            $message = '';
            foreach($info as $v){
                $message .= $v['name'].',';
            }
            $message = trim($message,',');
            if($this->ad->where('id','in',$ids)->delete()){
                admin_log($message,$type.$this->lang['delete'],$this->lang['ad']);
                return message(1,'广告删除成功');
            }else{
                return message(0,'广告删除失败');
            }
        }
    }
    /**
     * 获取参数
     */
    private function get_params(){
        $params = [
            'name'  => (string)input('post.name'),
            'content'  => (string)input('post.content'),
            'ap_id' =>  (int)input('post.ap_id'),
            'url'  => (string)input('post.url'),
            'stat'  => (int)input('post.stat'),
            'title' => (string)input('post.title'),
            'adv_order'=> (int)input('post.order'),
            'photo' => (string)input('post.photo')
        ];
        
        return $params;
    }
}
?>
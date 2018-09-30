<?php 
namespace app\admin\controller;
class Advpos extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->adv = \think\Loader::model('Advpos');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }
    public function indexAc(){
        $id = $this->adv->check('advpos_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $this->assign('empty',"<option value='0'>您还没有添加广告位置，快去添加吧 </option>");
        $this->assign('action',$this->request->action());
        return $this->fetch();
    }
    
    public function listajaxAc(){
        $where = '';
        $name = (string)input('get.ap_name');
        if(!empty($name)){
            $where .= " name like '%".$name."%'";
        }
        $offset = (int)input('get.offset');
        $lists = $this->adv->where($where)->limit($offset,20)->select();
        foreach($lists as &$item){
            $item->append(['typeText']);
        }
        $total = $this->adv->where($where)->limit($offset,20)->count();
        echo json_encode(array('rows'=>$lists,'total'=>$total));
    }
    public function addAc(){
        $id = $this->adv->check('advpos_add');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        if($this->request->isAjax()){
            $params = $this->get_params();
            $ret = $this->adv->addItem($params);
            if($ret['stat'] == 1){
                admin_log($params['name'],$this->lang['add'],$this->lang['ad_position']);
                return message(1,'广告位添加成功');
            }else{
                return message(0,$ret['msg']);
            }
        }
        $this->assign('action',$this->request->action());
        return view('ad_pview');
    }
    public function editAc(){
        $id = $this->adv->check('advpos_edit');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $id = !empty(input('get.id')) ? intval(input('get.id')) : intval(input('post.id'));
        
        if($this->request->isAjax()){
            $params = $this->get_params();
            $ret = $this->adv->editById($params,$id);
            if($ret){
                admin_log($params['name'],$this->lang['edit'],$this->lang['ad_position']);
                return message(1,'广告位修改成功');
            }else{
                return message(0,'广告位修改失败');
            }
        }
        $this->assign('action',$this->request->action());
        $info = $this->adv->where('id',$id)->find();
        $this->assign('info',$info);
        return view('ad_pview');
    }
    
    public function delAc(){
        $id = $this->adv->check('advpos_del');
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
        
        $info = $this->adv->where('id','in',$ids)->select();
        if(empty($info)){
            return message(0,'one of the ad is not found');
        }else{
            $message = '';
            foreach($info as $v){
                $message .= $v['name'].',';
            }
            $message = trim($message,',');
            if($this->adv->where('id','in',$ids)->delete()){
                admin_log($message,$type.$this->lang['delete'],$this->lang['ad_position']);
                return message(1,'广告位删除成功');
            }else{
                return message(0,'广告位删除失败');
            }
        }
    }
    /**
     * 获取参数
     */
    private function get_params(){
        $params = [
            'name'  => (string)input('post.name'),
            'type'  => (int)input('post.type'),
            'stat'  => (int)input('post.stat'),
            'width' => (float)input('post.ap_width'),
            'height'=> (float)input('post.ap_height')
        ];
        
        return $params;
    }
}
?>
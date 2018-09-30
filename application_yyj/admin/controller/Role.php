<?php
namespace app\admin\controller;

class Role extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->role = \think\Loader::model('AuthGroup');
        $this->Menu = model('Menu');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }
    public function indexAc(){
        $id = $this->role->check('role_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        
        return $this->fetch();
    }
    public function groupajaxAc(){
        $where = '';
        $offset = (int)input('get.offset');
        $parentlist = $this->role->where($where)->limit($offset,20)->select();
        $total = $this->role->where($where)->limit($offset,20)->count();
        exit(json_encode(array('rows'=>$parentlist,'total'=>$total)));  
    }
    public function rule_groupAc(){
        return $this->fetch();
    }
    public function delAc(){
        $id = $this->role->check('role_del');
        if($id){
            $ret = $this->admin_priv($id,$this->request->isAjax());
            if(!$ret) return message(0,'你没有操作权限');
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $group_id = $this->role->check(['name'=>'role_del'],'id');
        if($group_id){
            $this->admin_priv($group_id['id']);
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
        $info = $this->role->where('id','in',$ids)->select();
        if(empty($info)){
            return message(0,'role is not found');
        }else{
            $message = '';
            foreach($info as $v){
                $message .= $v['title'].',';
            }
            $message = trim($message,',');
            if($this->role->where('id','in',$ids)->delete()){
                admin_log($message,$type.$this->lang['delete'],$this->lang['role']);
                return message(1,'delete success');
            }else{
                return message(0,'删除角色失败');
            }
        }
    }
    public function addAc(){
        $id = $this->role->check('role_add');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        if($this->request->isAjax()){
            $title = (string)input('post.title');
            $describe = (string)input('post.describe');
            $validate = $this->validate(['title'=>$title], 'Role.add');
            if($validate !== true){
                return message(0, $validate);
            }
            $ret = $this->role->addItem(['title' => $title,'describe'=>$describe]);
            //$ret = $this->role->data(['title' => $title,'describe'=>$describe])->save();
            if($ret['stat'] == 1){
                admin_log($title,$this->lang['add'],$this->lang['role']);
                return message(1,'权限组添加成功');
            }else{
                return message(0,$ret['msg']);
            }
        }
        $this->assign('action',$this->request->action());
        return view('rule_view');        
    }
    public function editAc(){
        $id = $this->role->check('role_edit');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $id = empty(input('get.id')) ? intval(input('post.id')) : intval(input('get.id'));
        if($this->request->isAjax()){
            $title = (string)input('post.title');
            $describe = (string)input('post.describe');
            if(empty($this->role->find(['id' => $id]))){
                return ['stat' => 0,'msg' => '参数错误'];
            }else{
                $ret = $this->role->editById(['title' => $title,'describe'=>$describe],$id);
                //$this->role->edit(['id' => $id],['title' => $title,'describe'=>$describe])
                if($ret['stat'] == 1){
                    return ['stat' => 1];
                }else{
                    return ['stat' => 0,'msg' => $ret['msg']];
                }
            }
        }
        $this->assign('action',$this->request->action());
        $info = $this->role->where('id',$id)->find();
        $this->assign('info',$info);
        return view('rule_view');
    }
    public function rule_group_listAc(){
        $id = $this->role->check('role_rule_group_list');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $id = (int) input('get.id') ? (int) input('get.id') : (int) input('post.id');
        if(!empty($id)){
            if($id <=0 ) return message(0,'参数错(1)');
        } 
        
        // 获取用户组数据
        $group_data=$this->role->find(['id'=>$id]);
        if($this->request->isAjax()){
            $post =(array) input('post.')['rule_ids'];
            $describe = (string)input('post.role_describe');
            if(empty($post)){
                return message(0,'你没有选择任何权限');
            }
            $rules=implode(',', array_values($post));
            $ret = $this->role->where('id',$id)->update(['rules' => $rules,'describe'=>$describe]);
            
            if($ret){
                admin_log($group_data['title'],$this->lang['edit'],$this->lang['role_permisson']);
                return message(1,'修改成功');
            }else{
                return message(0,'修改失败');
            }
        }
        
        
        
        $group_data['rules']=explode(',', $group_data['rules']);
        // 获取规则数据
        $rule_data=$this->role->getTreeData();
        
        $this->assign('group_data',$group_data);
        $this->assign('rule_data',$rule_data);
        return $this->fetch();
    }
    //使用菜单设置权限
    public function rule_group_menuAc($id){
        $group=$this->role->get_by_id($id);
        $group["rules"]= explode(",", $group["rules"]);
        $menu=$this->Menu->get_tree();
        $this->assign("group_data",$group);
        $this->assign("menu",$menu);
        return $this->fetch();
        
    }
}

<?php
namespace app\admin\controller;

class Area extends \app\admin\controller\Home{
    public $area;
    private $lang;
    public function _initialize(){
        parent::_initialize();
        $this->area = \think\Loader::model('Area');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }
    public function indexAc(){
        $id = $this->area->check('area_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $pid = empty(input('get.a_pid')) ? 0 : intval(input('get.a_pid'));
        $areaList = $this->area->where('a_pid',$pid)->select();
        $p_area = $this->area->where('a_id',$pid)->find();
        $this->assign('p_areaname',$p_area['a_name']);
       
        $d_name = $this->caseinfo($p_area['a_deep']);
        $this->assign('arealist',$areaList);
        $this->assign('pid',$pid);
        $this->assign('deep',$p_area['a_deep']);
        $this->assign('d_name',$d_name);
        return $this->fetch();
    }
    public function listajaxAc(){
        $pid = intval(input('post.a_pid'));
        $areaList = $this->area->select(['a_pid' =>$pid]);
        exit(json_encode(array('rows'=>$areaList,'total'=>1)));
    }
    public function editAc(){
        $id = $this->area->check('area_edit');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $a_id = empty(input('get.a_id')) ? intval(input('post.a_id')) : intval(input('get.a_id'));
        if($this->request->isAjax()){
            $name = (string)input('post.a_name');
            $sort = (int)input('post.a_sort');
            if($a_id < 0){
                return message(0,'参数错误');
            }
            $ret = $this->area->editById(['a_name'=>$name,'a_sort'=>$sort],$id);
            if($ret['stat'] == 1){
                admin_log($name,$this->lang['edit'],$this->lang['area']);
                return message(1,'修改成功');
            }else{
                return message(0,$ret['msg']);
            }
        }
        $info = $this->area->where('a_id',$a_id)->find();
        $this->assign('info',$info);
        $this->assign('actipn',$this->request->action());
        return view('area_view');
    }
    public function delAc(){
        $id = $this->area->check('area_del');
        if($id){
            $ret = $this->admin_priv($id,$this->request->isAjax());
            if(!$ret) return message(0,'你没有操作权限');
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $a_id = intval(input('post.a_id'));
        if($a_id < 0){
            return message(0,'参数错误');
        }
        $areainfo = $this->area->find(['a_id' => $a_id]);
        
        $d_name = $this->caseinfo($areainfo['a_deep'] - 1);
        $ret = $this->area->destroy($a_id);
        if($ret){
            admin_log($areainfo['a_name'],$this->lang['delete'],$d_name.$this->lang['area']);
            return message(1,'删除成功');
        }else{
            return message(0,'删除失败');
        }
    }
    
    public function addAc(){
        $id = $this->area->check('area_add');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $info = input('post.');
        $validate = $this->validate($info, 'Area.add');
        if($validate !== true){
            return message(0, $validate);
        }
        $exist = $this->area->where(['a_pid'=>$info['pid'],'a_name'=>$info['a_name']])->find();
        
        
        $d_name = $this->caseinfo($info['deep']);
        $params = [
            'a_name' => $info['a_name'],
            'a_pid' =>  $info['pid'],
            'a_deep' => $info['deep'] + 1
        ];
        $ret = $this->area->data($params)->save();
        if($ret){
            admin_log($info['name'],$this->lang['add'],$d_name.$this->lang['area']);
            return message(1,'地区添加成功');
        }else{
            return message(0,'地区添加失败');
        }
    }
    
    private function caseinfo($case){
        switch($case){
            case 0:
                $d_name = '一级';
                break;
            case 1:
                $d_name = '二级';
                break;
            case 2:
                $d_name = '三级';
                break;
        }
        return $d_name;
    }
}
?>
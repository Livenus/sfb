<?php 
namespace app\admin\Controller;
class Menu extends \app\admin\controller\Home{
    public $menu;
    private $lang;
    public function _initialize(){
        parent::_initialize();
        $this->menu = \think\Loader::model('Menu');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }
    public function listAc(){
        $id = $this->menu->check('menu_list');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $menulist = $this->menu->getMenu();
        $this->assign('menulist',$menulist);
        return view();
    }
    
    public function listajaxAc(){
        $menulist = $this->menu->getMenu();
        exit(json_encode((array('rows'=>$menulist,'total'=>1))));
    }
    
    private function _get_params(){
        $params = array();
        $params['title'] = (string)input('post.title');
        $params['url'] = (string)input('post.url');
        $params['pid']   =(int)input('post.pid');
        $params['stat'] = (int)input('post.stat');
        $params['is_show'] = (int)input('post.is_show');
        if($params['pid'] == 0){
            $params['deep'] = 1;
        }else{
            $params['deep'] = 2;
        }
        
        $params['icon'] = (string)input('post.icon');
        $params['type'] = (int)input('post.type');//后台菜单
        return $params;
    }
    
    public function addAc(){
        $id = $this->menu->check('menu_add');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        if($this->request->isAjax()){
            $params = $this->_get_params();
            $result = $this->validate($params,'Menu.add');
            if(true !== $result){
                // 验证失败 输出错误信息
                return message(0,$result);
            }
            $r = $this->menu->addItem($params);
            if($r['stat'] == 1){
                admin_log($params['title'],$this->lang['add'],$this->lang['menu']);
                return message(1,'菜单添加成功');
            }else{
                return ['stat' => 0,'msg' => $r['msg']];
            }
        }
        $lists = $this->menu->where('pid',0)->select();
        $list_tree=$this->menu->get_tree();
        $pid1=[];
        foreach($list_tree as $v){
             $p["text"]=$v["title"];
             $p["id"]=$v["id"];
             $a=[];
           foreach($v["pid1"] as $vv){
               $pid["text"]=$vv["title"];
               $pid["id"]=$vv["id"];
               $a[]=$pid;
           }
            $p["nodes"]=$a;
            $pid1[]=$p;
        }
        $data_tree["text"]="顶级";
        $data_tree["id"]=0;
        $data_tree["nodes"]=$pid1;
        $tree=[$data_tree];
        $this->assign('lists',$lists);
        $this->assign('list_tree',$list_tree);
        $this->assign('list_tree_json', json_encode($tree,JSON_UNESCAPED_UNICODE));
        $this->assign('action',$this->request->action());
        return view('menu_view');
    }
    
    public function editAc(){
        $id = $this->menu->check('menu_edit');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $id = empty(input('get.id')) ? intval(input('post.id')) : intval(input('get.id'));
        if($this->request->isAjax()){
            if($id<=0) return ['stat' => 0,'msg'=>'参数错(1)'];
            $params = $this->_get_params();
            $result = $this->validate($params,'Menu.edit');
            if(true !== $result){
                // 验证失败 输出错误信息
                return message(0,$result);
            }
            if($params['pid'] > 0 && $this->menu->where('pid',$id)->count() > 0){
                return message(0,'该菜单下有子菜单，不能修改为二级菜单');
            }
            $ret = $this->menu->editById($params,$id);
            if($ret['stat'] == 1){
                admin_log($params['title'],$this->lang['edit'],$this->lang['menu']);
                return message(1,'菜单编辑成功');
            }else{
                return message(0,$ret['msg']);
            } 	
        }
        $list_tree=$this->menu->get_tree();
        $pid1=[];
        foreach($list_tree as $v){
             $p["text"]=$v["title"];
             $p["id"]=$v["id"];
             $a=[];
           foreach($v["pid1"] as $vv){
               $pid["text"]=$vv["title"];
               $pid["id"]=$vv["id"];
               $a[]=$pid;
           }
            $p["nodes"]=$a;
            $pid1[]=$p;
        }
        $data_tree["text"]="顶级";
        $data_tree["id"]=0;
        $data_tree["nodes"]=$pid1;
        $tree=[$data_tree];
        $this->assign('lists',$lists);
        $this->assign('list_tree',$list_tree);
        $this->assign('list_tree_json', json_encode($tree,JSON_UNESCAPED_UNICODE));
        $lists = $this->menu->where('pid',0)->select();
        $this->assign('lists',$lists);
        $info = $this->menu->where('id',$id)->find();
        $this->assign('info',$info);
        $this->assign('action',$this->request->action());
        return view('menu_view');
        	
    }
    
    public function delAc(){
        $id = $this->menu->check('menu_del');
        if($id){
            $ret = $this->admin_priv($id,$this->request->isAjax());
            if(!$ret) return message(0,'你没有操作权限');
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $id = input('post.')['ids'];
        if($id<=0) return message(0,'参数错');
        if($this->menu->where('pid',$id)->count() > 0){
            return message(0,'请先删除下级');
        }else{
            $ret = $this->menu->delById($id);
            if($ret['stat'] == 1){
                admin_log($params['title'],$this->lang['delete'],$this->lang['menu']);
                return message(1,'菜单删除成功'); 
            }else{
                return message(0,$ret['msg']);
            }
        }
    }
}
?>
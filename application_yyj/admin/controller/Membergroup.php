<?php 
namespace app\admin\controller;
class MemberGroup extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->membergroup = \think\Loader::model('MemberGroup');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }
    
    public function grouplistAc(){
        $id = $this->membergroup->check('membergroup_grouplist');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        return $this->fetch();
    }
    public function grouplistajaxAc(){
        $name = (string)input('get.ap_name');
        $where = ' 1';
        if($name){
            $where .= " and name like '%".$name."%'";
        }
        $memberGroupLists = $this->membergroup->where($where)->select();
        $count = $this->membergroup->where($where)->count();
        echo json_encode(array('rows' => $memberGroupLists,'total' => $count));
    }
    public function gdelAc(){
        $id = $this->membergroup->check('membergroup_gdel');
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
        
        $info = $this->membergroup->where('id','in',$ids)->select();
        if(empty($info)){
            return message(0,'this group is not found');
        }else{
            $message = '';
            foreach($info as $v){
                $message .= $v['name'].',';
            }
            $message = trim($message,',');
            if($this->membergroup->where('id','in',$ids)->delete()){
                admin_log($message,$type.$this->lang['delete'],$this->lang['member_group']);
                return message(1,'delete success');
            }else{
                return message(0,'delete fail');
            }
        }
    }
    public function gaddAc(){
        $id = $this->membergroup->check('membergroup_gadd');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        if($this->request->isAjax()){
            $data = $this->getGroup_params();
            $validate = $this->validate($data,'MemberGroup.gadd');
            if($validate !== true){
                return message(0,$validate);
            }
           
            $ret = $this->membergroup->data($data)->save();
            if($ret){
                admin_log($data['name'],$this->lang['add'],$this->lang['member_group']);
                return message(1,'添加会员组成功');
            }else{
                return message(0,'添加会员组失败');
            }
        }
        $this->assign('action',$this->request->action());
        return view('group_view');
    }
    public function geditAc(){

        $id = $this->membergroup->check('membergroup_gedit');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $id = empty(input('get.id')) ? intval(input('post.id')) : intval(input('get.id'));
        if($this->request->isAjax()){
            $params = $this->getGroup_params();
            $validate = $this->validate($params,'MemberGroup.gedit');
            if($validate !== true){
                return message(0,$validate);
            }
            $rate1=model("Channel")->get_pay_way_key("rate_1");
            $rate2=model("Channel")->get_pay_way_key("rate_2");
            $rate3=model("Channel")->get_pay_way_key("rate_3");
            if($params["rate_1"]<$rate1["low_fee"]||$params["rate_1_money"]<$rate1["low_rate_money"]){
                return message(0,'编辑失败无积分费率或固定金额不对');
            }

            if($params["rate_2"]<$rate2["low_fee"]||$params["rate_2_money"]<$rate2["low_rate_money"]){
                return message(0,'编辑失败有积分费率或固定金额不对');
            }
            if($params["rate_3"]<$rate3["low_fee"]||$params["rate_3_money"]<$rate3["low_rate_money"]){
                return message(0,'编辑失败同名费率或固定金额不对');
            }

            $res=$this->membergroup->save($params,['id'=>$id]);
            if($res||is_numeric($res)){
                admin_log($params['name'],$this->lang['edit'],$this->lang['member_group']);
                return message(1,'编辑成功');
            }else{
                return message(0,'编辑失败'.$res);
            }
        }
        if($id <= 0){
            return message(0,'incorrect parameter');
        }else{
            $data = $this->membergroup->get_by_id($id);
            if(empty($data)){
                return message(0,'not found');
            }
        }
        $this->assign('info',$data);
        $this->assign('action',$this->request->action());
        return view('group_view');
    }
    private function getGroup_params(){
        $params = [
            'name' => (string)input('post.name'),
            'content' => (string)input('post.content'),
            'money' => (float)input('post.money'),
            'intr_num'  => (int)input('post.intr_num'),
            'rate_1'    => (float)input('post.rate_1'),
            'rate_2'    => (float)input('post.rate_2'),
            'rate_3'    => (float)input('post.rate_3'),
            'rate_4'    => (float)input('post.rate_4'), 
            'rate_5'    => (float)input('post.rate_5'),
            'rate_6'    => (float)input('post.rate_6'),
            'rate_7'    => (float)input('post.rate_7'),
            'rate_8'    => (float)input('post.rate_8'),
            'rate_1_money'    => (float)input('post.rate_1_money'),
            'rate_2_money'    => (float)input('post.rate_2_money'),
            'rate_3_money'    => (float)input('post.rate_3_money'),
            'rate_4_money'    => (float)input('post.rate_4_money'),
            'rate_5_money'    => (float)input('post.rate_5_money'),
            'rate_6_money'    => (float)input('post.rate_6_money'),
            'rate_7_money'    => (float)input('post.rate_7_money'),
            'rate_8_money'    => (float)input('post.rate_8_money'),
            'level_1_rate'    => (float)input('post.level_1_rate'),
            'level_2_rate'    => (float)input('post.level_2_rate'),
            'level_3_rate'    => (float)input('post.level_3_rate'),
            'photo'    => (string)input('post.photo')
        ];
        return $params;
    }
    public function update_statusAc($id="",$status){
        $input=input("post.");
        $rule=[
            "id"=>"require|number"
        ];
        $check=$this->validate($input, $validate);
        if($check!==true){
              return message(0,$check);
        }
       //批量更新
        if(is_array($input["id"])){

            $this->membergroup->editByids(array("stat"=>$status),$input["id"]);
                        return message(1,"更新成功");
        }
        $res=$this->membergroup->editById(array("stat"=>$status),$id);
        if($res["stat"]==1){
             admin_log($status,$this->lang['edit'],$this->lang['channel']);
            return message(1,"更新成功");
        }
        return message(0,"更新失败");
        
    } 
}
?>
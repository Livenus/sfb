<?php
namespace app\common\model;
class Member extends \app\common\model\Home{
    protected $insert=['reg_ip', 'add_time'];
    
    //只读
    protected $readonly = ['add_time','reg_ip','uname'];
    
    protected function setAddTimeAttr(){
        return time();
    }
    
    protected function setRegIpAttr(){
        return ip2long(real_ip());
    }
    public function editById($data, $id){
        try{
            db("member")->where(["id"=>$id])->update($data);
        }catch( \Exception $e){
            return $this->err($e->getMessage());
        }
        
        return $this->suc('修改成功');  
        
    }
    protected function getRegIpAttr($value){
        return long2ip($value);
    }
    public function table_info(){
        $sql="SELECT a.COLUMN_NAME as name, a.COLUMN_COMMENT as comment
FROM information_schema.COLUMNS a 
WHERE a.TABLE_NAME = 'yys_member'";
        $data=$this->query($sql);
        $result=array();
        foreach($data as $v){
            $result[$v['name']]=$v["comment"];
        }
        return $result;
    }
    private function _gen_rand($length){
        $str = '';
        $length = (int)$length;
        
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";

        $max = strlen($strPol)-1;
        
        for($i=0;$i<$length;$i++){
            $str.=$strPol[rand(0,$max)]; 
        }
        
        return $str;
    }
    
    
    private function _gen_pass($password, $salt){
        return sha1($password);
        //return md5($password . $salt);
    }

    public function addItem($data){
        $data['salt'] = $this->_gen_rand(6);
        $data['upass'] = $this->_gen_pass($data['upass'], $data['salt']);
        
        $r = parent::addItem($data);
        return $r;     
    }
    //返回ID
        public function addItem_id($data){
        $data['salt'] = $this->_gen_rand(6);
        $data['upass'] = $this->_gen_pass($data['upass'], $data['salt']);
        
        $r = parent::addItem_id($data);
        return $r;     
    }
    //身份证编辑商户ID
    public function editBynum($data, $id){
        try{
            //$this ->isUpdate(true)-> save($data, [$this->getPk() => $id]);
            self::where("banknum", $id)
                    ->update($data);
        }catch( \Exception $e){
            return $this->err($e->getMessage());
        }
        
        return $this->suc('修改成功');  
        
    }
        //手机号查询ID
        public function get_member_phone($phone){
      
            
            
        return $this->where("phone",$phone)->value("id");     
    }
    //锁表
    public  function get_by_sn_lock($id){
        
        $map["id"]=$id;
        $data=$this->where($map)->lock(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
    }
        //数量
        public function get_count($map){
        return $this->where($map)->count();     
    }
            //查询最新
        public function get_member_last(){
      
            
            
        return $this->order("id desc")->value("id")+1;
    }
    //更新余额记录日志
    public  function update_money($member_id,$money,$type=0,$log,$msgs){
        $Sumsite=model("Sumsite");
        $Sumsite->update_num("back_money",$money);
        $Sumsite->update_num("total_money",$money);
        $msg_model=model("Msg");
        $MemberMonylog=model("MemberMonylog");
        $memdata=$this->get_by_id($member_id);
        if($memdata){
            $data["money"]= $memdata["money"]+$money;
        }
        //日志
        $MemberMonylog->addItem($log);
 
        //统计数据
        if($type){
            //升级 
            //返佣
            $msg["type"]=2;
            $msg["from"]=$log["op_id"];
            $msg["to"]=$member_id;
            $msg_d["money"]=$money;
            $msg["content"]=$msg_d;
            $msg["msg"]=$msgs;
            $msg_model->addItem($msg);
            $data["xj_up_fy_total"]= $memdata["xj_up_fy_total"]+$money;
        }else{
            $msg["type"]=1;
            $msg["from"]=$log["op_id"];
            $msg["to"]=$member_id;
            $msg_d["money"]=$money;
            $msg["content"]=$msg_d;
            $msg["msg"]=$msgs;
            $res=$msg_model->addItem($msg);
            $data["xj_sk_fy_total"]= $memdata["xj_sk_fy_total"]+$money;
        }
        return parent::editById($data, $member_id);
    }
    //刷卡返佣
    public  function update_sk_total($member_id,$money){
        
        $this->where(array("id"=>$member_id))->setInc("sk_total",$money);
    }
    //身份证是否唯一
    public  function idnum_num($member_id,$id_num){
        $map["id"]=["neq",$member_id];
        $map["idnum"]=$id_num;
        $count=$this->where($map)->count();
        if($count>0){
            return true;
        }else{
            return false;
        }
    }
    //获取用户信息
    public  function get_by_id($id){
        
        $data=$this->where(array("id"=>$id))->field(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public  function get_by_map($map){
        
        $data=$this->where($map)->field(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(true)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
    //获取用户信息
    public  function get_by_check($id){
        
        $data=$this->where(array("id"=>$id))->field(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    //获取子人数
    public  function get_pid_count($member_id){
        $map["p_id"]=$member_id;
        return $this->where($map)->count();
    }
    //获取子人数
    public  function get_child($member_id){
        $map["p_id"]=$member_id;
        $data=$this->where($map)->field(['id','phone','truename','idnum','money','bankname','banknum','membergroup_id','is_rz_1','is_rz_2','p_id','add_time'])->select();
        if($data){
            return collection($data)->toArray();
        }else{
            return FALSE;
        }
        
    }
    //获取子id集
    public  function get_child_id($member_id){
        if(is_array($member_id)){
            $map["p_id"]=["in",$member_id];
        }else{
            $map["p_id"]=$member_id;
        }
       
        $data=$this->where($map)->column("id");
        if($data){
            return $data;
        }
        return false;
    }
    public function get_children($member_id,$level,$limit="0,10"){
        $sql="select id from (select id,membergroup_id from yys_member where p_id=$member_id union
select id,membergroup_id from yys_member where p_id in (select id from yys_member where p_id=$member_id) union
select id,membergroup_id from yys_member where p_id in (select id from yys_member where p_id in (select id from yys_member where p_id=$member_id))) as g where g.membergroup_id=$level limit $limit";
        $data= $this->query($sql);
        if($data){
            $ids=[];
            foreach($data as $v){
                $ids[]=$v["id"];
            }
            return $ids;
        }else{
            return false;
        }
    }
    //升级会员组
    public  function update_group($member_id,$self){
        //消息提示
        $Msg=model("Msg");
            $msg["type"]=7;
            $msg["to"]=$member_id;
            $msg["content"]=$this->get_by_id($self);
            $Msg->addItem($msg);
        $group= model("MemberGroup");
        $mem=$this->get_by_id($member_id);
        $group_data=$group->get_by_id($mem["membergroup_id"]);
        $group_data_up=$group->get_by_id_up($mem["membergroup_id"]);
        if($group_data_up["id"]>=14){
            return false;
        }
        $count=$mem["down1_count"]+$mem["down2_count"]+$mem["down3_count"];
        if($group_data_up["intr_num"]>0&&$count==$group_data_up["intr_num"]){
            $data["membergroup_id"]=$group_data_up["id"];
            $this->editById($data, $member_id);
        }
    }
    //升值代理人数
    public  function set_parent_count($pid,$member_id){
        $parent=$this->where("id",$pid)->find();
        $down1_count=$parent->down1_count;
         $down1_count= $down1_count+1;
        if($parent){
            $this->where("id",$pid)->update(["down1_count"=>$down1_count]);
            $this->update_group($pid,$member_id);
        }
        $parent1=$this->where("id",$parent->p_id)->find();
         if($parent1){
            $this->where("id",$parent1->id)->update(["down2_count"=>$parent1->down2_count+1]);
            $this->update_group($parent1->id,$member_id);
        }
        $parent2=$this->where("id",$parent1->p_id)->find();
         if($parent2){
            $this->where("id",$parent2->id)->update(["down3_count"=>$parent2->down3_count+1]);
            $this->update_group($parent2->id,$member_id);
         }
        
    }

    public function modifyupass($upass,$id){
        $data['salt'] = $this->_gen_rand(6);
        $data ['upass'] = $this->_gen_pass($upass, $data['salt']);
        return parent::editById($data,$id);
    }
    /**
     * 
     * @param string $uname 用户名
     * @param string $upass 密码
     * @return [] 
     */
    public function checkUserPasswrod($uname, $upass){
        $name  = (string)$uname;
        $upass = (string)$upass;
        
        $maxErrTimes = 10;      //最大错误次数
        $maxErrTime  = 60; //错误次数达大最大值，锁定12小时
        
        $field = ['id','uname','upass','sex','salt','errtimes', 'errlasttime','stat'];
        
        //用户名为手机,需要绑定过手机
        $mobile_regex = "/^1\d{10}$/";
        if(preg_match($mobile_regex, $name)){
            $member_info = $this->where(['phone'=>$name])->field($field)->find();
        }
        
        //用户名为邮箱，需要 绑定过邮箱
        if(empty($member_info) && (strpos($name, '@') > 0)){
//            $member_info = $this->getOne(array('email'=>$name,'is_bind_email'=>1));
        }
        
        //用户名
        if(empty($member_info)){
            $member_info = $this->where(['uname'=>$name])->field($field)->find();//($name, 'name');
        }

        if(!empty($member_info)){
            if($member_info['errtimes'] > $maxErrTimes && (($member_info['errlasttime'] + $maxErrTime) > time())){
                return $this->err('错误次数太多，请'. date('Y-m-d H:i:s', $member_info['errlasttime'] + $maxErrTime) . '之后再试');
            }elseif((int)$member_info['stat'] !== 1){
                return $this->err('您已被限制登录，请联系管理员');
            }
        }
        
        
        if(!empty($member_info) && $this->_gen_pass($upass, $member_info->salt) === $member_info->upass){
            //登录成功，清除登录错误次数
            $r = $this->editById(['errtimes'=> 0 ], $member_info->id);
            $arr_memberinfo = $member_info->toArray();
            unset($arr_memberinfo['upass'], $arr_memberinfo['salt'], $arr_memberinfo['errtimes'], $arr_memberinfo['errlasttime']);
            return $this->suc($arr_memberinfo);
        }else{
            //登录失败，增加登录错误次数
            $r = $this->editById(['errtimes'=> (int)($member_info->errtimes + 1), 'errlasttime'=>time()], $member_info->id);
            
            return $this->err('用户名或密码错误');
        }
        
        
        
    }
}
?>
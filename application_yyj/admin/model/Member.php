<?php
namespace app\admin\model;
class Member extends \app\admin\model\Index{
    protected $insert=['reg_ip', 'add_time'];
    
    //只读
    protected $readonly = ['add_time','reg_ip','uname'];
    
    protected function setAddTimeAttr(){
        return time();
    }
    
    protected function setRegIpAttr(){
        return ip2long(real_ip());
    }
    
    protected function getRegIpAttr($value){
        return long2ip($value);
    }
    protected function getAddTimeAttr($value){
        return date("Y-m-d H:i:s",$value);
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
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(true)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
   //取出所有id
    public function select_all_id($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->column("id");
        if($data){
            return $data;
        }
        return false;
    }
    //下三级
    public function get_children($id){
        $sql="select id from yys_member where p_id=$id union
select id from yys_member where p_id in (select id from yys_member where p_id=$id) union
select id from yys_member where p_id in (select id from yys_member where p_id in (select id from yys_member where p_id=$id))";
        $data=$this->query($sql);
        $ids=[];
        if($data){
            foreach ($data as $v){
                $ids[]=$v["id"];
            }
        }
        if($ids){
            return $ids;
        }else{
            return false;
        }
        
    }
    //获取用户信息
    public  function get_by_id($id){
        
        $data=$this->where(array("id"=>$id))->field(['id','phone','truename','uname','nickname','nick_img','idnum','money','sk_total','xj_sk_fy_total','xj_up_fy_total','bankname','banknum','membergroup_id','is_rz_1','is_rz_2','is_rz_3','p_id','down1_count','down2_count','down3_count','add_time'])->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    //获取用户信息
    public  function get_by_name($name){
        
        $data=$this->where(array("uname"=>$name))->field(['id','phone','truename','uname','nickname','nick_img','idnum','money','sk_total','xj_sk_fy_total','xj_up_fy_total','bankname','banknum','membergroup_id','is_rz_1','is_rz_2','is_rz_3','p_id','down1_count','down2_count','down3_count','add_time'])->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    public function get_count_area($id,$city){
        $sql="select m.id from yys_member as m
join yys_member_area as a
on m.id=a.member_id
join yys_member_group as g
on g.id=m.membergroup_id
where m.id!=$id and a.city=$city and g.id=17";
        $r=$this->query($sql);
        if($r){
            return $r[0]["id"];
        }else{
            return false;
        }
    }
    public function get_count_area_pro($id,$city){
        $sql="select m.id from yys_member as m
join yys_member_area as a
on m.id=a.member_id
join yys_member_group as g
on g.id=m.membergroup_id
where m.id!=$id and a.province=$city and g.id=18";
        $r=$this->query($sql);
        if($r){
            return $r[0]["id"];
        }else{
            return false;
        }
    }
    public function get_count_day(){
        
        $count=$this->whereTime('add_time', 'today')->count();
        return $count+0;
    }
    //会员每日新增
    public function get_count_day_group($map){
        
        $data=$this->where($map)->field(['count(id) sum'])->order($order)->limit($limit)->find();
        $sum=$data->toArray();
        return $sum["sum"]+0;
    }
    //会员组每日人数
    public function get_count_day_ggroup($map){

        $data=$this->where($map)->field(['count(id) sum'])->order($order)->limit($limit)->find();
        $sum=$data->toArray();
        return $sum["sum"]+0;
    }
    public function bank_info()
    {
        return $this->hasOne('MemberArea');
    }
    public function editByids($data,$ids){
        $map["id"]=["in",$ids];
       return  $this->isUpdate(true)->save($data,$map);
    }
    public  function editById($data,$uid){
        if($data["upass"]){
            $data["upass"]=$this->_gen_pass($data["upass"], "111");
        }
        
        return parent::editById($data, $uid);
    }
        public  function down1_count($uid){ 
            $this->where(array("id"=>$uid))->setInc("down1_count",1);
    }
         public  function down2_count($uid){ 
            $this->where(array("id"=>$uid))->setInc("down2_count",1);
    }
           public  function down3_count($uid){ 
            $this->where(array("id"=>$uid))->setInc("down3_count",1);
    }
    //总余额
    public function sum_money(){
        $sum=$this->sum("money");
        return $sum+0;
    }
    //总余额
    public function sum_money_group(){
        $sum=$this->sum("money");
        return $sum+0;
    }
}
?>
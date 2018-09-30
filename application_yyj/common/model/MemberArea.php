<?php
namespace app\common\model;
use think\Config;
use think\Db;
class MemberArea extends \app\common\model\Home{
    protected $auto = [];
    protected $insert = ['add_time'];  
    protected $update = [];  
        
    protected function setAddtimeAttr()
    {
        return time();
    } 
    public  function get_by_id($id){
        
        $data=$this->where(array("id"=>$id))->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public function select_all($map=[],$order="id desc",$limit=""){
        $map['is_del']=0;
        $data=$this->where($map)->field(['member_id','bank_name','bank_num','status'])->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
    public function get_count(){
        
        return $this->count();
    }
    public  function get_by_member_id($member_id){
        $map["member_id"]=$member_id;
        $data=$this->where($map)->order("id desc")->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public  function editByUid($data,$id){
        $map['member_id']=$id;
        $status=$this->save($data,$map);
        return suc($status);
    }
    public  function del_by_id($member_id,$id){
        $map['member_id']=$member_id;
        $map['id']=$id;
        return $this->save(['is_del'=>1],$map);
    }
    public function addItem($data) {
        $area= model("area");
        $bank_area= model("bank_area");
        $city=$area->get_by_name($data["city"]);
        if($city){
            $data["bank_city"]=$city['areacode'];
             $data["bank_province"]=$city['parentcode'];
        }     
        return parent::addItem($data);
    }
    public  function del_all($member_id){
        $map['member_id']=$member_id;
        return $this->where($map)->delete();
    }
     //市地区总代理
     public  function get_city_user($city){
         if(!is_numeric($city)){
              return false;
             
         }
         $sql="select a.city,m.id,g.id as gid,g.rate_1,g.level_1_rate from yys_member_area as a
join yys_member as m
on a.member_id=m.id
join yys_member_group as g
on m.membergroup_id=g.id
where a.city=$city and g.id=17 limit 1";
         $data= Db::query($sql);
          if($data){
              return $data[0];
          }else{
              return false;
          }
     }
     //市地区总代理
     public  function get_city_user_key($city,$key){
         if(!is_numeric($city)){
              return false;
             
         }
         $sql="select a.city,m.id,g.id as gid,g.{$key} as rate,g.level_1_rate from yys_member_area as a
join yys_member as m
on a.member_id=m.id
join yys_member_group as g
on m.membergroup_id=g.id
where a.city=$city and g.id=17 limit 1";
         $data= Db::query($sql);
          if($data){
              return $data[0];
          }else{
              return false;
          }
     }
     //升级总代理
     public  function get_pro_user($city){
         if(!is_numeric($city)){
              return false;
             
         }
         $sql="select a.city,m.id,g.id as gid,g.rate_1,g.level_1_rate from yys_member_area as a
join yys_member as m
on a.member_id=m.id
join yys_member_group as g
on m.membergroup_id=g.id
where a.province=$city and g.id=18 limit 1";
         $data= Db::query($sql);
          if($data){
              return $data[0];
          }else{
              return false;
          }
     }
     //升级总代理
     public  function get_pro_user_key($city,$key){
         if(!is_numeric($city)){
              return false;
             
         }
         $sql="select a.city,m.id,g.id as gid,g.{$key} as rate,g.level_1_rate from yys_member_area as a
join yys_member as m
on a.member_id=m.id
join yys_member_group as g
on m.membergroup_id=g.id
where a.province=$city and g.id=18 limit 1";
         $data= Db::query($sql);
          if($data){
              return $data[0];
          }else{
              return false;
          }
     }
}
?>
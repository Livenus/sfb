<?php
namespace app\common\model;
use think\Config;
class Msg extends \app\common\model\Home{
    protected $auto = [];
    protected $insert = ['add_time'];  
    protected $update = [];  
        
    protected function setAddtimeAttr()
    {
        return time();
    }  
    protected function getAddtimeAttr($value)
    {
        return date("Y-m-d H:i:s",$value);
    }  
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(true)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
    public function select_by_type($map){
        $data=$this->where($map)->field(['type','count(id) as num'])->group("type")->select();
        return collection($data)->toArray();
    }
    public function table_info(){
        $sql="SELECT a.COLUMN_NAME as name, a.COLUMN_COMMENT as comment
FROM information_schema.COLUMNS a 
WHERE a.TABLE_NAME = 'yys_msg'";
        $data=$this->query($sql);
        $result=array();
        foreach($data as $v){
            $result[$v['name']]=$v["comment"];
        }
        return $result;
    }
    public function get_count($map){
        
        return $this->where($map)->count();
    }
    public function addItem($data) {
        
        $member=model("Member");
        if($data["type"]==5){
            $content=$data["content"];
            $data["content"]="您的升级会员订单{$content['sn']},支付成功";
        }elseif($data["type"]==4){
            $content=$data["content"];
            $data["content"]="您的刷卡消费订单{$content['sn']},支付成功";  
        }elseif($data["type"]==2){
            $content=$data["content"];
            $data["content"]=$data["msg"]."您获得返佣{$content['money']}元";  
        }elseif($data["type"]==1){
            $content=$data["content"];
            $data["content"]=$data["msg"]."您获得分润{$content['money']}";  
        }elseif($data["type"]==6){
            if($data["content"]==1){
                $data["content"]="您获得认证信息审核已通过";   
            }else{
                 $data["content"]="您获得认证信息审核已拒绝";  
            }

        }elseif($data["type"]==9){
            if($data["content"]==1){
                $data["content"]="您获得照片审核已通过";   
            }else{
                 $data["content"]="您获得照片审核已拒绝";  
            }

        }elseif($data["type"]==10){
            if($data["content"]==1){
                $data["content"]="您获得提现申请已通过";   
            }else{
                 $data["content"]="您获得提现申请已拒绝";  
            }

        }elseif($data["type"]==7){
            $content=$data["content"];
            $data["content"]="您成功推荐用户{$content['uname']}注册";  
        }
        unset($data["msg"]);
        return parent::addItem($data);
    }
    public  function get_by_id($member_id,$id){
        $map['to']=$member_id;
        $map['id']=$id;
        $this->editById(array("is_read"=>1), $id);
        $data=$this->where($map)->field(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public  function del_by_id($member_id,$id){
        $map['member_id']=$member_id;
        $map['id']=$id;
        return $this->save(['is_del'=>1],$map);
    }
    public  function editByIds($map){
        
        return $this->isUpdate(true)->save(["is_read"=>1],$map);
    }
    public  function del_all($ids){
        
        return $this->where("id","in",$ids)->delete();
    }

}
?>
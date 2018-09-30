<?php
namespace app\admin\model;
class MemberGroup extends \app\admin\model\Index{
    
    /**
     * 删除会员组
     * @param unknown $id
     * @return unknown
     */
    public function delById($id){
        $id = (int)$id;
        $r = \model('member')->where('membergroup_id', $id)->count();
        if($r > 0){
            return $this->err('会员组中还包含会员，请先转移会员后再尝试删除！');
        }
        $r = parent::delById($id);
        return $r;     
    }
    // 获取会员组列表
    public function select_all($map=array()){
        $data=$this->where($map)->select();
        return collection($data)->toArray();
    }
    public  function get_by_id($id){
        
        $data=$this->where(array("id"=>$id))->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    
    
}
?>
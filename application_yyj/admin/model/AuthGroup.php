<?php
namespace app\admin\model;
class AuthGroup extends \app\admin\model\Index{
    public function getTreeData(){
        $res = \think\Db::name('auth_rule')->where(['pid'=>0])->select();
        foreach($res as $key => $rows){
            $priv_arr[$rows['id']] = $rows;
        }
        $result = \think\Db::name('auth_rule')->whereIn('pid',implode(',',array_keys($priv_arr)))->select();
        foreach($result as $key => $priv){
            $priv_arr[$priv['pid']]['children'][$priv['name']] = $priv;
        }
        foreach ($priv_arr as $action_id => $action_group){
            if(empty($action_group['children'])){
                $priv_arr[$action_id]['priv_list'] = '';
            }else{
                $priv_arr[$action_id]['priv_list'] = implode(',', @array_keys($action_group['children']));
            }
        }
        return $priv_arr;
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
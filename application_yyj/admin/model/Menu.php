<?php 
namespace app\admin\model;
class Menu extends \app\admin\model\Index{
    public function getMenu(){
        $params = ['stat' => 1,'type' => 2,'pid' => 0];
        $menu = \think\Db::name('menu')->where($params)->select();
        $menuList = array();
        $rules_a= explode(",", RULES);
        foreach($menu as $k => $v){
            $menuList[$k] = $v;
            $menuList[$k]['son'] = \think\DB::name('menu')->where(['is_show'=>1,'stat'=>1,'pid' => $v['id'],'type'=>2])->select();
            

        }
        foreach($menuList as $key => &$value){
            if(!empty($value['url'])){
                $value['url'] = url($value['url']);
            }else{
                $value['url'] = '#';
            }
            if(!empty($value['son'])){
                foreach($value['son'] as $k => &$v){

                    if(!empty($v['url'])){
                        $v['url'] = url($v['url']);
                    }else{
                        $value['url'] = '#';
                    }
                }
            }
        }
     //超级管理员直接返回
        $user=session("admininfo");
        if($user["isSuper"]==1){
            
            return $menuList;
        }
        //过滤菜单
        foreach($menuList as $k=>$v){
              foreach($v["son"] as $kk=>$vv){
              if(in_array($vv["id"], $rules_a)){
                  
              }else{
                  unset($menuList[$k]["son"][$kk]);
              }
                  
                  
                  
              }
              if(in_array($v["id"], $rules_a)){
                  
              }else{
                  unset($menuList[$k]);
              }
            
        }
        return $menuList;
    }
    public function getMenuall(){
        $params = ['stat' => 1,'type' => 2,'pid' => 0];
        $menu = \think\Db::name('menu')->where($params)->select();
        $menuList = array();
        foreach($menu as $k => $v){
            $menuList[$k] = $v;
            $menuList[$k]['son'] = \think\DB::name('menu')->where(['stat'=>1,'pid' => $v['id'],'type'=>2])->select();
        }
        foreach($menuList as $key => &$value){
            if(!empty($value['url'])){
                $value['url'] = url($value['url']);
            }else{
                $value['url'] = '#';
            }
            if(!empty($value['son'])){
                foreach($value['son'] as $k => &$v){
                    if(!empty($v['url'])){
                        $v['url'] = url($v['url']);
                    }else{
                        $value['url'] = '#';
                    }
                }
            }
        }
        return $menuList;
    }
    public  function get_by_url($url){
        
        $data=$this->where(array("url"=>$url))->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public function select_all($map){
        $data=$this->where($map)->select();
        if(is_array($data)){
            return collection($data)->toArray();
        }else{
            return false;
        }
        
    }
    public  function get_tree(){
           $pid=$this->select_all(array("pid"=>0));
           foreach($pid as $k=>$v){
                $pid_1=$this->select_all(array("pid"=>$v["id"]));
                 foreach($pid_1 as $kk=>$vv){
                     $pid_2=$this->select_all(array("pid"=>$vv["id"]));
                     $pid_1[$kk]["pid_2"]=$pid_2;
                 }
                $pid[$k]["pid1"]=$pid_1;
           }
        
        return $pid;
    }
}
?>
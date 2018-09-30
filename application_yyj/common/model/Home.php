<?php 
/**
 * 文章模型
 */
namespace app\common\model;

use think\Model;

class Home extends Model{

    public function addItem($data){
        try{
            $this ->data($data)->isUpdate(false)->save();
        }catch( \Exception $e){
            return $this->err($e->getMessage());
        }

       return $this->suc('添加成功');  
    }
    
    public function editById($data, $id){
        try{
            //$this ->isUpdate(true)-> save($data, [$this->getPk() => $id]);
            self::where($this->getPk(), $id)
                    ->update($data);
        }catch( \Exception $e){
            return $this->err($e->getMessage());
        }
        
        return $this->suc('修改成功');  
        
    }
    
    public function delById($id){
        try{
            $this -> where($this->getPk(),$id)-> delete();
        }catch( \Exception $e){
            return $this->err($e->getMessage());
        }
        
        return $this->suc('删除成功');  
    }
    
    public function suc($data){
        return ['stat'=>1, 'data'=>$data];
    }
    public function err($msg){
        return ['stat'=>0, 'msg'=>$msg];
    }
    //返回ID
    public function addItem_id($data){
      
        try{
            $this ->data($data)->save();
        }catch( \Exception $e){
            return $this->err($e->getMessage());
        }

       return $this->suc($this->id);  
    }
    public function check($name = ''){
        $auth = new \app\admin\model\AuthRule();
        $info = $auth->where('name',$name)->field('id')->find();
        $group_id = $info['id'];
        
        if($group_id){
            return $group_id;
        }else{
            return false;
        }
    }
}
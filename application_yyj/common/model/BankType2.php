<?php
namespace app\common\model;
class BankType2 extends \app\common\model\Home{
  
      
     
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(['bank_name'])->group("bank_name")->select();
        return collection($data)->toArray();
    }
        public function select_all2($map=[],$order="sorts desc",$limit=""){
        $data=$this->where($map)->select();
        return collection($data)->toArray();
    }
    public function select_all_item($map=[],$order="sorts desc",$limit=""){
        $data=$this->where($map)->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
    public function edit($data,$map){
        return db("BankType2")->where($map)->update($data);
    }
        public function add($data){
        return db("BankType2")->insert($data);
    }
    public  function getBymap($map){
        $data=$this->where($map)->field(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public function get_bankno($bank_name){
        $data=$this->where(array("bank_name"=>$bank_name))->value("bank_no");
        return $data;
    }
        public function get_count($map=[]){
        
        return $this->where($map)->count();
    }
}
?>
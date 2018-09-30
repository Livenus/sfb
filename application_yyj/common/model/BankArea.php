<?php
namespace app\common\model;
class BankArea extends \app\common\model\Home{
  
      
     
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(['bank_name'])->group("bank_name")->select();
        return collection($data)->toArray();
    }   
    public  function get_by_name($name){
        
        $data=$this->where(array("areaname"=>$name))->find();
        if($data){
            $row=$data->toArray();
             return $row;
        }else{
            return false;
        }
       
    }
    //同步
    public  function set_area(){
        //繁体转简体
        require_once '../application_yyj/extend/jianti/ZhConversion.php';
        require_once '../application_yyj/extend/jianti/ZhConverter.php';
        require_once '../application_yyj/extend/jianti/helper.php';

        $area=model("Area");
         $data=$this->where($maps)->select();
        $datas=collection($data)->toArray();
        foreach($datas as $v){
             $map=[];
             $name=$v['areaname'];
             $name=traditional2Simplified($name, $variant = 'zh-cn');
             $map['a_name']=["like","%{$name}%"];
             $mdata["areacode"]=$v["areacode"];
             $mdata["parentcode"]=$v["parentcode"];
             $status=$area::where($map)->update($mdata);
             if($status===false){
                 echo $v['areaname'];
                 echo PHP_EOL;   
             }else{

             }
            
        }
    }
}
?>
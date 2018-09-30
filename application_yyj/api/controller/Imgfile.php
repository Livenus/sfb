<?php
namespace app\api\controller;
use think\Config;
use think\Response;
use think\Validate;
class Imgfile  extends \app\api\controller\Home {
        public function _initialize(){
        $this->Imgfile = model("Imgfile");
         $this->Member = model("Member");
        $this->Member_login_token = model("Member_login_token");
        $this->get_user_id();
        }
        public  function upload_imgAc($img="",$type=""){
            $rule=[
                "img"=>"require",
                "type"=>"require|number"
            ];
            $msg=[
                "img"=>"图片数据必须",
                "type"=>"图片类型必须"
                
            ];
if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result)){
$filetype = $result[2];
            $res=$this->validate($_POST, $rule,$msg);
            if($res!==true){
                return $this->err("9001", $res);
            }
            $dir=date("Ymd");
            $file_name=date("YmdHis").mt_rand(1000, 9999).".$filetype";
            $file_path="./uploads/truename/$dir/";
            if(!file_exists($file_path)){
                $dirs=mkdir($file_path,0777,true);
            }
            $file_path_r=$file_path.$file_name;
            $res=file_put_contents($file_path_r, base64_decode(str_replace($result[1], '', $img)));
         if($res){
             $data["member_id"]=$this->member_id;
             $data["type"]=$type;
             $data["path"]= substr($file_path_r, 1);
             $reponse=$this->Imgfile->addItem_id($data);
             if($reponse['stat']==1){
                 $data["id"]=$reponse["data"];
                             return $this->suc($data); 
             }

         }
}
                      return $this->err("9001", "创建文件失败");
        } 
     //完成确认使用的图片
        public function active_imgAc($imgs=""){
            $imgss= json_decode($imgs,true);
           if(count($imgss)!==4){
               return $this->err(9001, "数据错误");
           }
          $map["id"]=["in",$imgss];
          $map["member_id"]=$this->member_id;
         if($this->Imgfile->get_count($map)!=4){
             
             return $this->err(9002, "图片ID不存在");  
         }
        $mem=$this->Member->get_by_id($this->member_id);
        if($mem["is_rz_2"]==1){
                         return $this->err('9002','认证已通过');
        }else if($mem["is_rz_2"]==2){
                         return $this->err('9002','认证等待审核'); 
        }
           $this->Imgfile->del_by_type($this->member_id,$imgss);
           $res=$this->Member->editByid(array("is_rz_2"=>2,"rz_img"=>$imgs,"true_img_time"=>time()),$this->member_id);
            if($res['stat']==1){
                return $this->suc("提交成功");
            }
            return $this->err(9000, "提交失败");
        }
        //查询图片
        public function get_imgsAc($ids=""){
            $rules=[
                "ids"=>"require|length:1,200"
                
            ];
            $check=$this->validate($_POST, $rules);
            if($check!==true){
                 return $this->err(9000, $check);
            }
            $ids= json_decode($ids);
            $map["id"]=["in",$ids];
            $map["member_id"]=$this->member_id;
            $data=$this->Imgfile->select_all($map);
            if($data){
                $reponse["table_info"]=$this->Imgfile->table_info();
                $reponse["list"]=$data;
                return $this->suc($reponse);
                
            }
            return $this->err(900, "没有数据");
            
        }
}

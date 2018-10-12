<?php
namespace app\api\controller;
use think\Config;
use think\Response;
class Setting  extends \app\api\controller\Home {
        public function _initialize(){
        $this->Setting = model("Setting");
        
        }
        public function indexAc(){
          $this->get_user_id();
            $data=$this->Setting->select_all();
            if($data){
            $data["pic_cash"]=DS."uploads".DS.$data["pic_cash"];
            $data["pic_income"]=DS."uploads".DS.$data["pic_income"];
            $data["pic_member"]=DS."uploads".DS.$data["pic_member"];
            $data["pic_share"]=DS."uploads".DS.$data["pic_share"];
            $data["pic_share_2"]=DS."uploads".DS.$data["pic_share_2"];
            $data["pic_wallet"]=DS."uploads".DS.$data["pic_wallet"];
                return $this->suc($data);
            }

            return $this->err(900, "没有数据");
            
        }
    public  function get_codeAc($phone=""){
          require('../extend/qrcode/qrlib.php'); 
          $base="http://".$_SERVER["HTTP_HOST"]."/static/webpage/html/webpage.php?pphone_g=".$phone;
         //\QRcode::png($base, false, QR_ECLEVEL_L, 4.9, 4.9);
          
        $dir='./uploads/qr/'; 
        if(!is_dir($dir)){
            mkdir($dir);
        }
    $fileName = md5($base).'.png'; 
    $pngAbsoluteFilePath=$dir.$fileName;
    if (!file_exists($pngAbsoluteFilePath)) { 
        $res=\QRcode::png($base, $pngAbsoluteFilePath, QR_ECLEVEL_L, 4.9, 4.9); 
    } else { 
    } 
     echo substr($pngAbsoluteFilePath, 1);
    }
    //生成二维码
    public  function get_code_1($phone=""){
          require('../extend/qrcode/qrlib.php'); 
          $base="http://".$_SERVER["HTTP_HOST"]."/static/webpage/html/webpage.php?pphone_g=".$phone;
         //\QRcode::png($base, false, QR_ECLEVEL_L, 4.9, 4.9);
          
        $dir='./uploads/qr/'; 
        if(!is_dir($dir)){
            mkdir($dir);
        }
    $fileName = md5($base. rand(14, 15)).'.png'; 
    $pngAbsoluteFilePath=$dir.$fileName;
    if (!file_exists($pngAbsoluteFilePath)) { 
        $res=\QRcode::png($base, $pngAbsoluteFilePath, QR_ECLEVEL_L, 7.4, 0); 
    } else { 
    } 
     return substr($pngAbsoluteFilePath, 1);
    }
    //分享页面
    public function get_shareAc($phone=""){
        
        $url=url("setting/get_code",array("phone"=>$phone));
        $this->assign("phone",$phone);
        $str= file_get_contents("./static/webpage/html/code001.html");
        $pattern = '/<!--替换-->.+?<!--e替换-->/ms';
        $str=preg_replace($pattern, "", $str);
        $pattern = '/<!--2替换-->.+?<!--3替换-->/ms';
        $str=preg_replace($pattern, "", $str);
        $pattern = '/(<img src=\")(.*?)(\".*?>)/i';
        $replacement = '${1}'.$url.'$3';
       echo preg_replace($pattern, $replacement, $str);

    }
    public  function get_downAc($phone=""){
          require('../extend/qrcode/qrlib.php'); 
          $url=$this->Setting->get_by_key("android_url");
         \QRcode::png($url, false, QR_ECLEVEL_L, 4.9, 0);
         
        
    }
    public function get_img_sAc($phone=""){
        $dir='./uploads/qr/'; 
        if(!is_dir($dir)){
            mkdir($dir);
        }
        $img=$this->get_code_1($phone);
        $imgpos=$this->Setting->get_by_key("imgpos");
        $imgpos_d= explode(",", $imgpos["value"]);
        $pos=array($imgpos_d[0],$imgpos_d[1]);
        $back='.'.$imgpos_d[2];
        $image = \think\Image::open($back);
        // 给原图左上角添加水印并保存water_image.png
        $out=$dir.md5($phone.$imgpos['value'].rand(14, 15)).".png";
        if(file_exists($out)){
            $img=substr($out,1);
            $img=$img."?t=".time();
        }else{
        $up='.'.$img;
        $res=$image->water($up,$pos)->save($out);  
        $img=substr($out,1);
        $img=$img."?t=".time();
        }
        return $this->suc($img);
    }
}

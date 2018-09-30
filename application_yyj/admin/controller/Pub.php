<?php 
namespace app\admin\controller;
use think\Config;
class Pub  extends \think\Controller{
    
    
    public function _initialize(){
        parent::_initialize();
      
    }
    public  function LogAc(){
        $dir = "../runtime/log/201801/";

// 打开目录，然后读取其内容
if (is_dir($dir)){
  if ($dh = opendir($dir)){
    while (($file = readdir($dh)) !== false){
      echo "filename:" . $file . "<br>";
    }
    closedir($dh);
  }
}
        echo file_get_contents("../runtime/log/201801/1517372018-31.log");
        
        
    }

}
?>
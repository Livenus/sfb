<?php 
namespace app\admin\controller;

class Fileupload extends \app\admin\controller\Home{
    public function _initialize(){
        $this->fileupload = \think\Loader::model('FileUpload');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }
    
    public function uploadAc(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = $this->request->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH. DS .'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 jpg
               // echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                echo $info->getSaveName();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
                //echo $info->getFilename();
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
    public function upload_backAc(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = $this->request->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH. DS .'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 jpg
               // echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                $img=$info->getSaveName();
                $image = \think\Image::open("./uploads/".$img);
                $width = $image->width();
                if(1){
                    echo json_encode(message(1,$img));
                }else{
                     echo json_encode(message(0,"图片尺寸为952*1695,此图片尺寸为".$width));
                }
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
}
?>
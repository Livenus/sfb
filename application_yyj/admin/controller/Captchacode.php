<?php
namespace app\admin\controller;

class Captchacode extends \think\Controller{

    public function SetCaptchaAc(){
        $config = [
            'fontSize' => 50,
            'length' => 3,
            'expire' => 120,
            'imageW' => 120,
            'useNoise' => false
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }
}
?>
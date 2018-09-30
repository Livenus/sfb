<?php
namespace app\admin\model;
class Advpos extends \app\admin\model\Index{
    public function getTypeTextAttr($value,$data){
        $type = ['0'=>'文字','1'=>'图片'];
        return $type[$data['type']];
    }
}

?>

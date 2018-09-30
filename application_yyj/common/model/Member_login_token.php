<?php
namespace app\common\model;
class Member_login_token extends \app\common\model\Home{
    protected $auto = [];
    protected $insert = ['member_name'];  
    protected $update = [];  
    protected function setMemberNameAttr()
    {
        return "";
    }  
}
?>
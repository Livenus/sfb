<?php
namespace app\admin\model;
class Adv extends \app\admin\model\Index{
    public function getPositionAttr($value,$data){
        $adv_lists = model('advpos')->field('id,name')->select();
        $position = array();
        foreach($adv_lists as $item){
            $position[$item['id']] = $item['name'];
        }
        
        return $position[$data['ap_id']];
    }
}
?>
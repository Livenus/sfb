<?php
/**
 * 广告接口
 */

namespace app\api\controller;

class Adv extends \app\api\controller\Home {
    public function _initialize(){
        $this->adv = model('Adv');

    }
    
    /**
     * 根据广告位ID获取广告列表
     * @return unknown
     */
    public function listAc(){
        $where = [];
        $apid = (int)input('post.apid');
        if($apid <= 0){
            return $this->err('1001','参数错误');
        }
        $where['ap_id'] = $apid;
        $where['stat']  = 1;
        
        $lists = $this->adv->where($where)->field('id,title,photo,url,setting')->order('adv_order ASC')->select();
        //echo $lists->toJson(array('stat' => 1,'data'=>$lists));
        return $this->suc($lists);
    }
    //详情
    public  function detailAc($id=0){
        $this->get_user_id();
        $data=$this->adv->get_by_id($id);
        if($data){
          return $this->suc($data);   
        }
       
         return $this->err(9000,"默认关闭弹窗");
        
    }
}
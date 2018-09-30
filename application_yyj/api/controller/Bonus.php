<?php
namespace app\api\controller;
use think\Db;

/**
 * 股权
 */
class Bonus extends \app\api\controller\Home {

	public function _initialize(){
        $this->Product = model("Product");
        $this->ProductOrder = model("ProductOrder");
        $this->Red = model('Red');
        $this->RedLog = model('RedLog');
        $this->Member = model('Member');
        //$this->get_user_id();
        }
    /**
     * 根据会员id(member_id)获取股权列表
     * @return array
     */
	public function listAc(){
		$lists = [];
		$res = input('post.');
		$limit = $res['limit'];
		$map['member_id'] = $res['member_id'];
		if($res['member_id'] <= 0){
            return $this->err('1001','参数错误');
        }
        $count = $this->RedLog->get_count($map);
        $data = $this->RedLog->select_all($map,$order="add_time desc",$limit);
        $info = $this->Red->get_by_uid($res['member_id']);
        $lists['total_num'] = $info['num']; 
        $lists['total_amount'] = $info['amount'];
        $lists['total_red'] = $info['red'];
        $lists['count'] = $count;
        $lists['data']  = $data;

        return $this->suc($lists);
	}


	//执行业绩积分周期任务
	public function autoBonusAc(){
		$member_id = $this->Red->field('member_id')->select();
		foreach ($member_id as $value) {
			$member_id = $value['member_id'];
			$re = $this->get_bonus($member_id);
			
		}
		
	}
	/**
	 * 会员业绩积分,股权数量,金额,分红算法
	 * 
	 */
	public function get_bonus($member_id,$ratio = 0.01){
		$product_id=8;
		$data = $this->Red->get_by_uid($member_id);
		$id = $data['id'];
		$info = $this->Member->get_by_id($member_id);
		$product=$this->Product->get_by_id($product_id);
		//需要减少的业绩积分
		$more = $data['num']*$product['red']*$ratio;
		//判断是否需要返利分红
		if ($data['amount'] == 0 ) {
			return $this->suc('股权金额为0无可返金额!');
		}
		//剩余业绩积分
		$red_data['red'] = $data['red']-$more;
		//分红
		$bonus = $data['amount']*$ratio;
		$red_data['bonus'] = $data['bonus']+$bonus;
		//添加分红到会员余额
		$info['money'] = $bonus + $info['money'];
		//计算股权数量和金额是否需要减少
		if ($data['num'] == 1) {
			if ($red_data['red'] > 0 ) {
				$red_data['num'] = 1;
				$red_data['amount'] = $product['price'];
			}else{
				$num = 1;
				$amount = $product['price'];
				$red_data['num'] = 0;
				$red_data['amount'] = 0;
			}
		}else{
			$mod = ($data['num']-1)*$product['red'];
			if ($red_data['red'] <= $mod) {
				$red_data['num'] = $data['num']-1;
				$red_data['amount'] = $data['amount']-$product['price'];
				$num = 1;
				$amount = $product['price'];
			}
		}
		//修改表中的数据
		$red_data['add_time'] = time();
		Db::startTrans();
		try{
			$result = $this->Red->editById($red_data,$id,$more,$member_id,0,$num,$amount,$bonus);

			$res  = $this->Member->where('id',$member_id)->update($info);
			
			Db::commit();
		}catch(\Exception $e){
			Db::rollback();
			return $this->err($e->getMessage());
		}
		
		
	}
}		
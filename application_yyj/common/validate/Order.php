<?php 
namespace app\common\validate;

use think\Validate;
class Order extends Validate{
    protected $rule = [
        'member_id'  =>  'require|number',
        'amount'  =>  'require|number',
        'channel_id'  =>  'require|number',
        'money'  =>  'require|number',
        'bank_card_id'  =>  'require|number',
        'pay_type_id'  =>  'require|number',
        'group_id'  =>  '',
    ];
    protected $message = [
        'member_id.require'  => '用户ID必填',
        'amount.require'  => '刷卡金额必填',
        'channel_id.require'  => '刷卡渠道必填',
        'money.require'  => '刷卡金额必填1',
        'bank_card_id.require'  => '绑定卡号ID必填',
        'pay_type_id.require'  => '刷卡通道ID必填',
          "group_id.require"=>"升级的组ID必须",  
          "group_id.check_is"=>"此ID不存在或与当前ID相同",  
          "channel_id.is_can"=>"通道未开放或金额与通道不符合",  
    ];
    
    protected $scene = [
        "add_order"=>['money','bank_card_id','pay_type_id'],
        "add_order_step"=>['member_id','amount','channel_id'=>'require|is_can'],
        "pay_fee_order"=>['group_id'=>"require|number|check_is"]
    ];
    protected  function check_is($value,$rule,$data){
        $Member= model("Member");
        $MemberGroup= model("MemberGroup");
        $mem = $Member->get_by_id($data["member_id"]);
        $group_data = $MemberGroup->get_by_id($data["group_id"]);
        if($group_data&&$group_data["id"]>$mem["membergroup_id"]){
            return true;
        }else{
            return false;
        }
    }
    //通道是否可刷
    protected  function is_can($value,$rule,$data){
        $channel=model("channel");
        $money=$data["amount"];
        $map["stat"]=1;
        $t=date("H:i:s");
        $map["min_money"]=["elt",$money];
        $map["max_money"]=["egt",$money];
        $map["start_time"]=["elt",$t];
        $map["end_time"]=["egt",$t];
        $count=$channel->get_count($map);
        if($count){
            return true;
        }else{
            return false;
        }
    }
}
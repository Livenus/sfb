<?php
namespace app\api\controller;
use think\Config;
use think\Response;
class MemberMonylog  extends \app\api\controller\Home {
        public function _initialize(){
        $this->MemberMonylog = model("MemberMonylog");
        $this->Member = model("Member");
        $this->MemberGroup = model("MemberGroup");
        $this->get_user_id();
        }
        //收益
        public  function indexAc(){
            $mem=$this->Member->get_by_id($this->member_id);
             //已提现金额
            $sum_money_back_all_member=$this->MemberMonylog->sum_money_back_all_member($this->member_id);
            $sum_money_litttle=0;
            $mem["money"]=$mem["money"]-$sum_money_litttle;
            $data["money"]=$sum_money_back_all_member;
            $data["money"]= number_format ($data["money"],2,".","");
            $mem["money"]= number_format($mem["money"],2,".","");
            $data["sum"]=$this->MemberMonylog->sum_money($this->member_id);
            $data["sum"]= number_format($data["sum"],2,".","");
            $data["count"]=$this->MemberMonylog->count_id($this->member_id);
            $map["id"]=["<",14];
            $map["stat"]=1;
            $data["group"]=$this->MemberGroup->select_all($map);
            foreach($data["group"] as $k=>$v){
                
            }
            $child=$this->Member->get_child($this->member_id);
            $count_int=0;
            $child_group10=0;
            $child_group10_1=0;
            $child_group10_2=0;
            $child_group11=0;
            $child_group11_1=0;
            $child_group11_2=0;
            $child_group12=0;
            $child_group12_1=0;
            $child_group12_2=0;
            $child_group13=0;
            $child_group13_1=0;
            $child_group13_2=0;
            $child1=[];
            $child2=[];
            //日期区间
        $day=date("Y-m-d 00:00:02");
        $night=date("Y-m-d 23:59:59");
        $day_int= strtotime($day);
        $night_int= strtotime($night);
            //二级
            if($child){
                foreach($child as $k=>$v){
                    if($v["membergroup_id"]==10){
                        ++$child_group10;
                       ++$child_group10_1;
                    }elseif($v["membergroup_id"]==11){
                         ++$child_group11;
                        ++$child_group11_1;
                    }elseif($v["membergroup_id"]==12){
                         ++$child_group12;
                         ++$child_group12_1;
                    }elseif($v["membergroup_id"]==13){
                         ++$child_group13;
                         ++$child_group13_1;   
                    }
                    //新增
                    if($v["add_time"]>$day_int){
                        
                        $count_int++;
                    }
                    $child0=$this->Member->get_child($v["id"]);
                    foreach($child0 as $vv){
                    if($vv["membergroup_id"]==10){
                        ++$child_group10;
                       ++$child_group10_2;
                    }elseif($vv["membergroup_id"]==11){
                         ++$child_group11;
                       ++$child_group11_2;
                    }elseif($vv["membergroup_id"]==12){
                         ++$child_group12;
                        ++$child_group12_2;
                    }elseif($vv["membergroup_id"]==13){
                         ++$child_group13;
                       ++$child_group13_2;
                    }
                    //新增
                    if($vv["add_time"]>$day_int){
                        
                        $count_int++;
                    }
                        $child1[]=$vv;
                         $child00=$this->Member->get_child($vv["id"]);
                         foreach($child00 as $vvv){
                    if($vvv["membergroup_id"]==10){
                        ++$child_group10;
                       ++$child_group10_2;
                    }elseif($vvv["membergroup_id"]==11){
                         ++$child_group11;
                       ++$child_group11_2;
                    }elseif($vvv["membergroup_id"]==12){
                         ++$child_group12;
                        ++$child_group12_2;
                    }elseif($vvv["membergroup_id"]==13){
                         ++$child_group13;
                       ++$child_group13_2;
                    }
                    //新增

                    if($vvv["add_time"]>$day_int){
                        
                        $count_int++;
                    }
                              $child2[]=$vvv;
                         }
                    }
               
                 }
                
            }
              $count=count($child)+count($child1)+count($child2);
            $data["count_mem"]=$count_int;  
            $data["child_group10"]=$child_group10;  
            $data["child_group11"]=$child_group11;  
            $data["child_group12"]=$child_group12; 
            $data["child_group13"]=$child_group13;  
            $data["child_group10_1"]=$child_group10_1;  
            $data["child_group11_1"]=$child_group11_1;  
            $data["child_group12_1"]=$child_group12_1; 
            $data["child_group13_1"]=$child_group13_1;  
            $data["child_group10_2"]=$child_group10_2;  
            $data["child_group11_2"]=$child_group11_2;  
            $data["child_group12_2"]=$child_group12_2; 
            $data["child_group13_2"]=$child_group13_2;  
            $result["list"]=$data;
            $result["table_info"]=$this->MemberGroup->table_info();
            $result["table_info"]["sum"]="本日流水";
            $result["table_info"]["count"]="交易单数";

            //分润的余额 与 可用余额相同
            if(1 || $this->member_id == '102'){
                $result['list']['money'] = $mem["money"];
                return $this->suc($result);
            }

            return $this->suc($result);
        }
        //明细
        public function detailAc($level="",$limit="0,10"){
            $input=input("post.");
            $rule=[
                "level"=>"require|number"
                
            ];
            $check=$this->validate($input, $rule);
            if($check!==true){
                return $this->err(9000, $check);
            }
             $mem=$this->Member->get_by_id($this->member_id);
            $data["sum"]=$this->MemberMonylog->sum_money($this->member_id);
             //已提现金额
            $sum_money_litttle=$this->MemberMonylog->sum_money_litttle($this->member_id);
            $sum_money_cash=$this->MemberMonylog->sum_money_cash($this->member_id);
            $data["money"]=$mem["money"]-$sum_money_litttle;
            $data["money"]= number_format($data["money"],2,'.','');
             $data["cash"]=$sum_money_cash;
            $data["cash"]= number_format($data["cash"],2,'.','');
            //$child=$this->MemberMonylog->select_member_level($this->member_id,$level);
            //$map["p_id"]=$this->member_id;
            $child_id=$this->Member->get_children($this->member_id,$level,$limit);
            if($child_id){
                $map["id"]=["in",$child_id];
            }else{
                $map["id"]=0;
            }
            $child=$this->Member->select_all($map);
            foreach($child as $k=>$v){
                if($v["p_id"]==$this->member_id){
                    $child[$k]["is_parent"]="直接推荐";
                }else{
                    $child[$k]["is_parent"]="间接推荐"; 
                }
                if($v["is_rz_1"]==1&&$v["is_rz_2"]==1){
                     $child[$k]["is_rz"]="已实名"; 
                }else{
                     $child[$k]["is_rz"]="未认证";   
                }
                
            }
            $data["child"]=$child;
            return $this->suc($data);
        }
    //提现
    public  function add_money_logAc(){
            $site=model("Setting");
            $mem=$this->Member->get_by_id($this->member_id);
            $last=$this->MemberMonylog->get_last_money($mem);
            $mem_group=$this->MemberGroup->get_by_id($mem["membergroup_id"]);
            $data["member_id"]=$mem["id"];
           $last= number_format($last,2,'.','');
            $data["last"]=$last;
            $data["name"]=$mem["truename"];
            $data["group"]=$mem_group["name"];
            $data["idnum"]=$mem["idnum"];
            $data["banknum"]=$mem["banknum"];
            $data["bankname"]=$mem["bankname"];
            $data["config"]=$site->select_all();
            $data["config_info"]=array(
                "rate"=>"提现费率%",
                "rate_min_lose"=>"最低手续费",
                "rate_max_lose"=>"最高手续费",
                "rate_min"=>"最少提现",
                "last"=>"可提现金额",
            );
           return $this->suc($data);
    }
    public  function add_money_log_subAc($money="",$verify_code=""){
            $mem=$this->Member->get_by_id($this->member_id);
            if((int)$mem["money"]<=0){
                          return $this->err(9001, "提交失败,没有余额");
            }
            $mem_group=$this->MemberGroup->get_by_id($this->member_id);
            $input=input("post.");
            $input["phone"]=$mem["phone"];
            $input["member_id"]=$mem["id"];
            $input["has_money"]=$mem["money"];
           $check=$this->validate($input, "MemberMonylog");
           if($check!==true){
                return $this->err(9000, $check);
           }
           $data["member_id"]=$this->member_id;
           $data["val"]=$money;
           $data["type"]=3;
           $data["type_ordersn"]="tx".time(). mt_srand(1000,999);
           $data["op_id"]=$this->member_id;
            $res=$this->MemberMonylog->addItem_id($data);
            if($res["stat"]==1){
                
                 return $this->suc($res["data"]);
            }
          return $this->err(9000, "提交失败");
    }
    //提现记录
    public  function get_listAc($order="",$limit=""){
          $map["type"]=3;
          $map["member_id"]=$this->member_id;
          $data= $this->MemberMonylog->select_all($map,$order,$limit);
          $count=$this->MemberMonylog->get_count($map);
          if($data){
              $reponse["count"]=$count;
              $reponse["data"]=$data;
              return $this->suc($reponse);
              
          }
          return $this->err(900, "没有数据");
        
    }
    //分润
    public  function get_list_allAc($order="",$limit=""){
             $mem=$this->Member->get_by_id($this->member_id);
            $data["sum"]=$this->MemberMonylog->sum_money($this->member_id);
            $sum_money_litttle=$this->MemberMonylog->sum_money_litttle($this->member_id);
             $data["money"]=$mem["money"]-$sum_money_litttle;
             $data["money"]= number_format($data["money"],2,'.','');
             $data["cash"]=$this->MemberMonylog->sum_money_cash($this->member_id);
             $data["cash"]= number_format($data["cash"],2,'.','');
              $map["member_id"]=$this->member_id;
              $map["type"]=["in","1,2,6,7,8,9"];
             $data["count"]=$this->MemberMonylog->select_all_day_count($map);
             $list=$this->MemberMonylog->select_all_day($map,$order,$limit);
             foreach($list as $k=>$v){
                 $list[$k]['sum']= number_format($v['sum'],2,'.','');
             }
             $data["list"]=$list;
             $data["table_info"]=[
                 "sum"=>"今日新增",
                  "money"=>"用户余额", 
                   "cash"=>"累计提现",
             ];
             return $this->suc($data);
        
    }
    //今日流水
    public  function get_list_todayAc($order="",$limit=""){
             $mem=$this->Member->get_by_id($this->member_id);
            $data["sum"]=$this->MemberMonylog->sum_money($this->member_id);
             $data["money"]=$mem["money"];
             $data["money"]= number_format($data["money"],2,'.','');
             $data["cash"]=$this->MemberMonylog->sum_money_cash($this->member_id);
             $data["cash"]= number_format($data["cash"],2,'.','');
        $day=date("Y-m-d 00:00:02");
        $night=date("Y-m-d 23:59:59");
        $day_int= strtotime($day);
        $night_int= strtotime($night);
        $map["member_id"]=$this->member_id;
        $map["add_time"]=["between",[$day_int,$night_int]];
             $data["count"]=$this->MemberMonylog->get_count($map);

             $list=$this->MemberMonylog->select_all($map,$order,$limit);
             foreach($list as $k=>$v){
                 $list[$k]['val']= number_format($v['val'],2,'.','');
             }
             $data["list"]=$list;
             $data["table_info"]=$this->MemberMonylog->table_info();
             return $this->suc($data);
        
    }
}

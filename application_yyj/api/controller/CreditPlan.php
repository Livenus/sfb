<?php

/**
 * 信用卡
 */

namespace app\api\controller;
use Think\Db;
class CreditPlan extends \app\api\controller\Home {
    private $notify="http://payapi.shengfuba.com/index.php/api/credit/notify_pay";
    public function _initialize() {
        $demain=config("demain");
        $this->notify="http://".$demain."/index.php/api/credit/notify_pay";
        $this->CreditPlan = model('CreditPlan');
        $this->CreditPlanItem = model('CreditPlanItem');
        $this->Credit = model('Credit');
        $this->Member = model('Member');
        $this->CreditSet = model('CreditSet');
        $this->BankType2 = model('BankType2');
    }

    public function addAc() {
        $this->get_user_id();
        $input = input("post.");
        $data['credit_id'] = $input['credit_id'];
        $data['credit_bank'] = $input['credit_bank'];
        $data['credit_num'] = $input['credit_num'];
        $data['back_date'] = $input['back_date'];
        $data['amount'] = $input['amount'];
        $data['province'] = $input['province'];
        $data['province_name'] = $input['province_name'];
        $data['city'] = $input['city'];
        $data['city_name'] = $input['city_name'];
        $data['per'] = $input['per'];
         $data['low_money'] = $input['per']*$data['amount']/100;
        $data['member_id'] = $this->member_id;
        $check = $this->validate($data, "CreditPlan.add");
        if ($check !== true) {
            return $this->err(9001, $check);
        }
        if ($input) {
            $credit=$this->Credit->get_by_map(["id"=>$data['credit_id'],"member_id"=>$this->member_id]);
            if (empty($credit)) {
                return $this->err(9000, "未选择信用卡");
            }
            if(empty($credit["subMerchantNo"])||empty($credit["user_no"])||empty($credit["config_no"])||empty($credit["config_no1"])){
                                return $this->err(9000, "信用卡信息出错，绑定失败，信息未绑定上");
            }
            $old=$this->CreditPlan->get_by_map(["credit_id"=>$data['credit_id'],"status"=>["in","1,4"] ]);

            $allday= json_decode($data['back_date'],true);
            if(strtotime($allday[0])<time()){
                return $this->err(9000, "不能小于当前时间");
            }
            if($old){
                $old_time= json_decode($old['back_date'],true);
                if(array_intersect($old_time,$allday)){
                     return $this->err(9000, "同一张卡时间不能重复");
                }
            }
            if($this->CreditPlan->get_count(["credit_id"=>$data['credit_id'],"back_date"=>$data['back_date'],"status"=>["in","1,2"]])){
                return $this->err(9000, "计划已经生成");
            }
            //清空未激活
            $this->CreditPlan->del_empty($this->member_id,$data['credit_id']);
            $set=$this->CreditSet ->get_by_id();
            $date= json_decode($data['back_date'],true);
           for($j=1;$j<=$set["many_pay"];$j++){
            for($i=1;$i<=$set["many_pay"];$i++){
                 $day_money_p=$this->self_rand_1($data['amount'],count($date),$set,$data['per'],$j,$i);
                 if($day_money_p['stat']==1){
                     break 2;
                 }
            }    
               
               
           }

            if($day_money_p["stat"]==0){
                return $this->err(9000,$day_money_p["msg"]);
            }

            $day_money=$day_money_p['data'];
            if(empty($day_money['buy'])){
                 return $this->err(9000,"消费计划生成失败".$day_money_p["msg"]);
            }
            Db::startTrans();
            $id = $this->CreditPlan->addItem_id($data);
            $j=1;
            $i=0;
            $task_amount=0;
            $buy_amount=0;
            $service_money=0;
            $t=0;
            foreach ($date as $k=>$v){
                $item=array();
                $item['credit_plan_id']=$id['data'];
                $item['num']=$k;
                $item['order_no']="item3".date("YmdHis"). rand(1111, 9999). rand(1111, 9999).uniqid();
                $item['plan_date']= strtotime($v);
                $item['type']= 3;
                $item['amount']= $day_money['plan'][$k];
                $item['member_id'] = $this->member_id;
                $status=$this->CreditPlanItem->addItem_id($item);
                $item2=array();

                foreach( $day_money['back'][$k] as $kk=>$vv){
                $item2['credit_plan_id']=$id['data'];
                $item2['num']=0;
                $item2['rate']=$set["rate"];
                $item2['single_payment']=$set["single_payment"];
                $item2['low_rate']=$set["low_rate"];
                $item2['low_single']=$set["low_single"];
                $item2['order_no']="item2".date("YmdHis"). rand(1111, 9999). rand(1111, 9999).uniqid();
                $item2['plan_date']= strtotime($v);
                $item2['type']= 2;
                $item2['type2_id']= $status;
                $item2['amount']= $vv;
                $item2['single_back']= $set["single_back"]; 
                $item2['member_id'] = $this->member_id;
                $status2=$this->CreditPlanItem->addItem_id($item2);
                $buy=0;
                
                foreach( $day_money['buy'][$i] as $kkk=>$vvv){
                $item2=array();
                $item2['credit_plan_id']=$id['data'];
                $item2['num']=$j;
                $item2['rate']=$set["rate"];
                $item2['single_payment']=$set["single_payment"];
                $item2['low_rate']=$set["low_rate"];
                $item2['low_single']=$set["low_single"];
                $item2['order_no']="item1".date("YmdHis"). rand(1111, 9999). rand(1111, 9999).uniqid();
                if($kk==0&&$kkk==0){
                    $t=$v." ".$set['time_start'];
                    $t= strtotime($t);
                }else{
                    $r=rand(0,$set['interval_large']);
                    $t=$t+$set['interval_time']*60+$r*60;
                }
                $item2['plan_date']= $t;
                $item2['type']= 1;
                $item2['type2_id']= $status2;
                if($kkk==0){
                    $item2['single_back']= $set["single_back"]; 
                    $service_money=$service_money+$item2['single_back'];
                }else{
                    $item2['single_back']= 0;
                }
                //
                $item2['amount']= $vvv;
                $buy=$buy+$vvv;
                $task_amount=$item2['amount']+$task_amount;
                 $item2['member_id'] = $this->member_id;
                $status3=$this->CreditPlanItem->addItem_id($item2);
                 $j++;
                $buy_amount=$buy_amount+$vvv*$set["rate"]/100+$item2['single_payment'];
                }

                    $r=rand(0,$set['interval_large']);
                    $t=$t+$set['interval_time']*60+$r*60;
                $this->CreditPlanItem->editById(["num"=>$j,"plan_date"=>$t],$status2);
                 $j++;
                $buy=$buy-$vv;

                $i++;
                }
            }
            if($status3){
                $update["task_amount"]=$task_amount;
                $update["buy_amount"]=$buy_amount;
                $update["service_money"]=$service_money;
                $update["task_buy_money"]=$task_amount;
                $this->CreditPlan->editById($update,$id["data"]);
                Db::commit();
                 return $this->suc($id);
            }else{
                Db::rollback();
            }
           
            return $this->err(9003, "添加失败");
        }
        return $this->err(900, "没有数据");
    }
    public function detailAc(){
        $this->get_user_id();
         $input = input('post.');
        $rule = ['id' => 'require'];
        $check = $this->validate($input, $rule);
        if ($check !== true) {
            return $this->err(9001, $check);
        }
        $order="num asc";
        if($input["order"]){
            $order=$input["order"];
        }
        $limit="0,5";
        if($input["limit"]){
            $limit=$input["limit"];
        }
        $map=[];
        $map["del"]=0;
        $map["credit_plan_id"]=$input['id'];
         $map["type"]=["in","1,2"];
         if($input["status"]){
             $map["status"]=$input["status"];
         }
         if($input["start"]){
             $start= strtotime($input['start']);
             $end= $start+3600*24;
             $map["plan_date"]=["between","{$start},{$end}"];
         }

        $data['plan']=$this->CreditPlan->get_by_map(["id"=>$input['id'],"del"=>0]);
        if($data['plan']['buy_amount']){
            $maps=$map;
            $maps["type"]=1;
            $single_payment=$this->CreditPlanItem->where($maps)->sum("single_payment");
            $first=$this->CreditPlanItem->where($maps)->find();
            //$data['plan']['buy_amount']=$data['plan']['buy_amount']-$single_payment;
            $data['plan']['buy_amount']=$data['plan']['amount']*$first["rate"]/100;
            $data['plan']['buy_amount']= number_format($data['plan']['buy_amount'],2,".","");
        }
        $data['item']=$this->CreditPlanItem->select_all($map,$order,$limit);
         $data['count']=$this->CreditPlanItem->get_count($map);
         foreach($data['item'] as $k=>$v){
             if($v['type']==1){
                 $data['item'][$k]["fee"]=$v["amount"]*$v["rate"]/100+$v["single_payment"];
                 $data['item'][$k]["fee"]=round($data['item'][$k]["fee"],2);
             $data['item'][$k]["type_count"]=$this->CreditPlanItem->get_count(["credit_plan_id"=>$map["credit_plan_id"],"type"=>1,"id"=>["<=",$v["id"]]]);
             }elseif($v['type']==2){
                 $data['item'][$k]["fee"]=$v["single_back"];
                 $data['item'][$k]["type_count"]=$this->CreditPlanItem->get_count(["credit_plan_id"=>$map["credit_plan_id"],"type"=>2,"id"=>["<=",$v["id"]]]);
                 //未成功
                 if($v['status']==6&&$this->CreditPlanItem->get_count(["type2_id"=>$v['id'],"type"=>4,"status"=>1])){
                     $data['item'][$k]["success_amount"]=$this->CreditPlanItem->where(["type2_id"=>$v['id'],"type"=>4,"status"=>1])->value("amount");
                 }
             }
         }
        return $this->suc($data);
    }
    public function editAc(){
        $this->get_user_id();
         $input = input('post.');
        $rule = ['id' => 'require'];
        $check = $this->validate($input, $rule);
        if ($check !== true) {
            return $this->err(9001, $check);
        }     
        $this->CreditPlan->editById(["status"=>1],$input['id']);
        $status=$this->CreditPlanItem->edit(["status"=>4],["credit_plan_id"=>$input['id'],"status"=>0]);
        return $this->suc($status);
    }
    public function delAc(){
        $this->get_user_id();
         $input = input('post.');
        $rule = ['id' => 'require'];
        $check = $this->validate($input, $rule);
        if ($check !== true) {
            return $this->err(9001, $check);
        }     
        $credit= $this->CreditPlan->get_by_id($input['id']);
        if(empty($credit)){
            return $this->err(9001, "未找到");
        }
        if($credit["status"]==0||$credit["status"]==1){
            return $this->err(9001, "执行中不可删除");
        }
        $this->CreditPlan->editById(["del"=>1],$input['id']);
        $status=$this->CreditPlanItem->edit(["del"=>1],["credit_plan_id"=>$input['id']]);
        if($status){
          return $this->suc("删除成功");
        }
       
        return $this->err(9001, "没有数据");
    }
    public function edit_cancelAc(){
        $this->get_user_id();
         $input = input('post.');
        $rule = ['id' => 'require'];
        $check = $this->validate($input, $rule);
        if ($check !== true) {
            return $this->err(9001, $check);
        }     
        $this->CreditPlan->editById(["status"=>3],$input['id']);
        $status=$this->CreditPlanItem->edit(["status"=>5],["credit_plan_id"=>$input['id'],"status"=>["neq",1]]);
        if($status){
            return $this->suc("取消成功");
        }
          return $this->err(9001, "取消失败");
    }
    public function listAc(){
         $input = input('post.');
        $this->get_user_id();
        $order="id asc";
        if($input["order"]){
            $order=$input["order"];
        }
        $limit="0,5";
        if($input["limit"]){
            $limit=$input["limit"];
        }
        $map['member_id'] = $this->member_id;
        $map["del"]=0;
         if($input["status"]){
             $map["status"]=$input["status"];
         }
         if($input["credit_num"]){
             $map["credit_num"]=$input["credit_num"];
         }
        $data['plan']=$this->CreditPlan->select_all($map,$order,$limit);
       $data['count']=$this->CreditPlan->get_count($map);
       foreach($data['plan'] as $k=>$v){

         $maps["type"]=1;
         $maps["status"]=6;
         $maps["credit_plan_id"]=$v["id"];
         $data['plan'] [$k]['fail_count']=$this->CreditPlanItem->where($maps)->count()+0;
         $data['plan'] [$k]['fail_amount']=$this->CreditPlanItem->where($maps)->sum("amount")+0;
         $maps["type"]=["in","2,4"];
         $maps["status"]=1;
         $maps["credit_plan_id"]=$v["id"];
         $data['plan'] [$k]['success_amount']=$this->CreditPlanItem->where($maps)->sum("amount")+0;
         $data['plan'] [$k]['first']=$this->CreditPlanItem->get_first(["credit_plan_id"=>$v["id"],"type"=>["in","1,2"]]);
         $data['plan'] [$k]['end']=$this->CreditPlanItem->get_end(["credit_plan_id"=>$v["id"],"type"=>["in","1,2"]]);

       }
       
        return $this->suc($data);
    }
    private function self_rand($money, $num,$set,$per){

        $day_m=$money*$per/100;
        if($day_m<$set['min']){
            return err("预留值不能小于最低金额");
        }
        $datas=getRandomArray($money,$num,$set['min'],$set['max']);
       //return $datas;
        $res=[];
        $res_day=[];
        $money1=$money-$set['min']*$num;
       for($i=0;$i<$num;$i++){
           $last=$money1-array_sum($res);
           $share=$last>$set['max']?$set['max']:$last;
            $res[]= mt_rand(0, $share);


           if(count($res)==$num&&$money1>array_sum($res)){
               $last=$money1-array_sum($res);
               $key=array_rand($res);
               $res[$key]= $res[$key]+$last;
           }
       }
       foreach($res as $k=>$v){
           $res[$k]=$v+$set['min'];
       }
       foreach($res as $v){
           $nums= ceil($v/$day_m);
           $single=$v*$set["per_max"]/100/$set["many_buy"];
           $day=[];
           for($i=0;$i<$nums;$i++){
               $last=$v-array_sum($day);
                $share=$last>$day_m?$day_m:$last;
                $min=$single>$set['min']?$single:$set['min'];
                $day[]= mt_rand($min, $share);
           if(count($day)==$nums&&$v>array_sum($day)){
               $last=$v-array_sum($day);
               $key=array_rand($day);
               $day[$key]= $day[$key]+$last;
           }
           }
           
           $res_day[]=$day;
       }
       $data['res']=$res;
       $data['res_day']=$res_day;
       return suc($data);
    }
    public  function self_rand_1($money, $num,$set,$per,$many_pay,$nums){
        $day_m=$money*$per/100;
        if($day_m<$set['min']){
            return err("生成失败，请调整还款时间或请选择其他还款方式比例，还款方式比例太小");
        }
        $svg=$money/$num;
        $min=$svg*(1-$set['day_max']/100);
        $min2=$set['min']*$set['num']*$many_pay;
        $min=$min>$min2?$min:$min2;
        $max=$svg*(1+$set['day_max']/100);
        $mm=$day_m*$many_pay;
        $max=$max>$mm?$mm:$max;
        $set_max=$set["max"]*$set["num"]*$nums;
        $max=$max>$set_max?$set_max:$max;
        $all_money=($money+$set["single_payment"])/(1-$set["rate"]/100);
        $all_more=($money*$set["rate"]/100+$set["single_payment"])*$set["many_buy"];
        $all_more=$all_more/$num;
        $max=$max-$all_more;
        $min=$min+$all_more;
        $datas=getRandomArray($money,$num,$min,$max);
        if(empty($datas)){
            mlog("每天还款金额最大随机浮动比例不正确，均值{$svg},最小{$min},最大{$max},浮动".$set['day_max']/100);
            return err("生成失败，请调整还款时间或请选择其他还款方式比例");
        }

        $buy=array();
        $back=array();
        $back_count=[];
         foreach($datas as $v){
         $vv1=$v;
         //包含手续费
          $more=($vv1*$set["rate"]/100+$set["single_payment"]);
          $real=$vv1+$more;
          $num2= $nums;
          $num2=(int)$num2;
          $svg2=$vv1/$num2;
         $more_day_max=$set['more_day_max']>50?$set['more_day_max']:$set['more_day_max'];
          $min2=$svg2*(1-$more_day_max/100);
          $max2=$svg2*(1+$more_day_max/100);
          $set_max=$set["max"]*$set["num"];
           $max2=$max2>$day_m?$day_m:$max2;
           $max2=$max2>$set_max?$set_max:$max2;
           $more=$more/$num2;
           $max2=$max2-$more;
           $min2=$min2+$more;
           $min3=$set['min']*$set['num'];
           $min2=$min2>$min3?$min2:$min3;
           $svg1_more=$svg2*$set["rate"]/100+$set["single_payment"]+$set["single_back"];
           $max2=$max2-$svg1_more;
           $vvv=getRandomArray($vv1,$num2,$min2,$max2);
            if(empty($vvv)){
                mlog("单笔还款金额浮动比例出错均值{$svg2},最小{$min2},最大{$max2},浮动".$set['more_day_max']/100);
            return err("生成失败，请调整还款时间或请选择其他还款方式比例");
            }
             $back[]=$vvv;
            $back_count[]=count($vvv);
         
         }
         if(max($back_count)>$set['many_pay']){
             mlog("每天最大还款次数错误允许次数{$set['many_pay']},本次计算".max($back_count));
             return err("生成失败，请调整还款时间或请选择其他还款方式比例");
         }
         $buy_count=[];
         foreach($back as $v){
             $c=0;
             foreach($v as $vv){
          $vv2=($vv+$set["single_payment"])/(1-$set["rate"]/100);
          $num=$set["num"];
          $svg1=$vv2/$num;
         $per_max=$set['per_max']>50?$set['per_max']:$set['per_max'];
          $min1=$svg1*(1-$per_max/100);
          $max1=$svg1*(1+$per_max/100);
         $max1=$max1>$day_m?$day_m:$max1;
           $min1=$min1>$set['min']?$min1:$set['min'];
           $max1=$max1>$set['max']?$set['max']:$max1;
           $svg1_more=$svg1*$set["rate"]/100+$set["single_payment"]+$set["single_back"];
           $max1=$max1-$svg1_more;
           $vvvv=getRandomArray($vv,$num,$min1,$max1);
            if(empty($vvvv)){
                mlog("单笔消费金额最大随机浮动出错均值{$svg1},次数{$num}最小{$min1},最大{$max1},浮动".$set['per_max']/100);
            return err("生成失败，请调整还款时间或请选择其他还款方式比例");
           }
           foreach ($vvvv as $kb=>$bb){
               //第一笔加上还款手续费
               if($kb==0){
                   $bb=$bb+$set["single_back"];
                  $vvvv[$kb]=($bb+$set["single_payment"])/(1-$set["rate"]/100);
                  $vvvv[$kb]= number_up($vvvv[$kb]);
               }else{
                  $vvvv[$kb]=($bb+$set["single_payment"])/(1-$set["rate"]/100);     
                  $vvvv[$kb]= number_up($vvvv[$kb]);
               }

           }
             //第一笔加上还款服务费
             $buy[]=$vvvv; 

             $c=$c+count($vvvv);
             }
             $buy_count[]=$c;
         }
        if(max($buy_count)>$set['many_buy']){
             return err("生成失败，请调整还款时间或预留款金额(每天最大消费次数错误允许次数{$set['many_buy']},本次计算".max($buy_count));
         }
         $data["plan"]=$datas;
         $data["buy"]=$buy;
         $data["back"]=$back;
        return suc($data);
    }
    public function updateRate($id,$item){
        $credit=$this->Credit->get_by_map(["id"=>$id]);
        $Quickpay=new \credit\Quickpay();
                        $para["subMerchantNo"]=$credit["subMerchantNo"];
                        $para["user_no"]=$credit["user_no"];
                        $para["config_no"]=$credit["config_no1"];
                        $para["rate"]=$item["rate"];
                        $para["single_payment"]=$item["single_payment"]+$item["single_back"];
                        $entryCard=$Quickpay->updateRate($para);
                        if($entryCard["stat"]==1){
                                        return suc($entryCard["data"]["Resp_msg"]);
                        }
          return err("更新失败".$entryCard["errmsg"]);
    }
    //手动还款
    public function insteadPayhandAc($id){
        $status=$this->insteadPayh($id);
        return $this->suc($status);
    }
    //手动消费
    public function payhAc($id=""){
        $item=$this->CreditPlanItem->get_by_id($id);
        if(empty($item)){
            return err("为空");
        }
        $plan=$this->CreditPlan->get_by_id($item["credit_plan_id"]);
        $credit=$this->Credit->get_by_id($plan["credit_id"]);
                $item1=array();
                $item1['credit_plan_id']=$item['credit_plan_id'];
                $item1['num']=0;
                $item1['order_no']="item5".date("YmdHis"). rand(1111, 9999). rand(1111, 9999).uniqid();
                $item1['plan_date']= time();
                $item1['type']= 3;
                $item1['amount']= 10;
                $item1['member_id'] = $item['member_id'];
                $id1=$this->CreditPlanItem->addItem_id($item1);
                        $Quickpay=new \credit\Quickpay();
                        $para["subMerchantNo"]=$credit["subMerchantNo"];
                        $para["user_no"]=$credit["user_no"];
                        $para["config_no"]=$credit["config_no1"];
                        $para["order_no"]=$item1["order_no"];
                        $para["areaCode"]= substr($credit["city_code"], 0,4);
                        $para["notifyUrl"]=$this->notify;
                        $para["is_single_payment"]="YES";  
                         $para["price"]=$item1["amount"];
                        $Pay=$Quickpay->Pay($para);
                        if($Pay["stat"]==1){
                            $status=$this->CreditPlanItem->edit(["status"=>3,"outer_no"=>$Pay["data"]["ypt_order_no"]],$id1);
                        }else{
                             $status=$this->CreditPlanItem->edit(["status"=>6,"reason"=>$Pay["errmsg"]],$id1);
                              $this->fail_record_buy($item);
                        }
                        if($status){
                          return suc($status);  
                        }else{
                            return err($status);  
                        }
                        
                        
    }
    public function pay($id=""){
        $item=$this->CreditPlanItem->get_by_id($id);
        if(empty($item)){
            return err("为空");
        }
        $plan=$this->CreditPlan->get_by_id($item["credit_plan_id"]);
        if($plan["status"]!=1&&0){
            $msg_status="失败";
            if($plan["status"]==4){
                $msg_status="失败";
            }elseif($plan["status"]==3){
                
                $msg_status="取消";
                
            }
            $status=$this->CreditPlanItem->edit(["status"=>6,"reason"=>"计划状态为".$msg_status],$id);
             return err("计划已停止");
        }
        $credit=$this->Credit->get_by_id($plan["credit_id"]);
                        $Quickpay=new \credit\Quickpay();
                        $para["subMerchantNo"]=$credit["subMerchantNo"];
                        $para["user_no"]=$credit["user_no"];
                        $para["config_no"]=$credit["config_no1"];
                        $para["order_no"]=$item["order_no"];
                        $para["areaCode"]= substr($credit["city_code"], 0,4);
                        if($plan['city']){
                         $para["areaCode"]= substr($plan["city"], 0,4);
                        }
                        $para["notifyUrl"]=$this->notify;
                        $para["is_single_payment"]="YES";  
                        if(1){
                            //修改费率
                            $update=$this->updateRate($credit["id"],$item);
                            if($update["stat"]==0){
                                 mlog("修改付费失败".$update["msg"]);
                                //$this->CreditPlan->editById(["status"=>4],$item["credit_plan_id"]);
                                $status=$this->CreditPlanItem->edit(["status"=>6,"reason"=>"修改付费失败".$update["msg"]],$id);
                                 $this->fail_record_buy($item);
                                 return err("修改付费失败");
                            }else{
                            }

                        }
                         
                         $para["price"]=$item["amount"];
                        $Pay=$Quickpay->Pay($para);
                        if($Pay["stat"]==1){
                            $status=$this->CreditPlanItem->edit(["status"=>3,"outer_no"=>$Pay["data"]["ypt_order_no"]],$id);
                        }else{
                            //$this->CreditPlan->editById(["status"=>4],$item["credit_plan_id"]);
                             $status=$this->CreditPlanItem->edit(["status"=>6,"reason"=>$Pay["errmsg"]],$id);
                              $this->fail_record_buy($item);
                        }
                        if($status){
                          return suc($status);  
                        }else{
                            return err($status);  
                        }
                        
                        
    }
    public function insteadPay($id=""){
        $item=$this->CreditPlanItem->get_by_id($id);
        if(empty($item)){
            return err("为空");
        }

        $plan=$this->CreditPlan->get_by_id($item["credit_plan_id"]);
        if($plan["status"]!=1&&0){
            $status=$this->CreditPlanItem->edit(["status"=>6,"reason"=>"计划状态为已停止"],$id);
             return err("计划已停止");
        }
        $credit=$this->Credit->get_by_id($plan["credit_id"]);
                        $Quickpay=new \credit\Quickpay();
         $para0["subMerchantNo"]=$credit["subMerchantNo"];
         //消费失败
         $pay_count=$this->CreditPlanItem->get_count(["status"=>["neq",1],"type2_id"=>$id]);
         if($pay_count){
                             //$this->CreditPlan->editById(["status"=>4],$item["credit_plan_id"]);
                             $status=$this->CreditPlanItem->edit(["status"=>6,"reason"=>"消费失败"],$id);
                             $this->fail_record($item);
                               //清空账户
                              $this->insteadPayh($id);
                              return false;
             
         }
         $last=$Quickpay->querybalance($para0);
         if($last["data"]&&$last["data"]["amount"]>0){
             
         }else{
                             //$this->CreditPlan->editById(["status"=>4],$item["credit_plan_id"]);
                             $status=$this->CreditPlanItem->edit(["status"=>6,"reason"=>"余额为0"],$id);
                             $this->fail_record($item);
                               //清空账户
                              $this->insteadPayh($id);
             mlog("余额为0");
             return false;
         }

                        $para["subMerchantNo"]=$credit["subMerchantNo"];
                        $para["user_no"]=$credit["user_no"];
                        $para["config_no"]=$credit["config_no"];
                        $para["order_no"]=$item["order_no"];
                        $para["notifyUrl"]=$this->notify;
                         $para["is_single_payment"]="YES";
                         $para["price"]=$item["amount"];
                        $Pay=$Quickpay->insteadPay($para);
                       
                        if($Pay["stat"]==1){
                            $status=$this->CreditPlanItem->edit(["status"=>3],$id);
                        }else{
                             //$this->CreditPlan->editById(["status"=>4],$item["credit_plan_id"]);
                             $status=$this->CreditPlanItem->edit(["status"=>6,"reason"=>$Pay["errmsg"]],$id);
                             $this->fail_record($item);
                               //清空账户
                              $this->insteadPayh($id);
                        }
                        if($status){
                          return suc($status);  
                        }else{
                            return err($status);  
                        }
                        
    }
    //失败发送短信
    public function fail_record($item=[]){
     $plan=$this->CreditPlan->get_by_id($item["credit_plan_id"]);
    $plan['plan_date']=date("Y-m-d H:i",$item['plan_date']);
     $credit=$this->Credit->get_by_id($plan["credit_id"]);
      $mem=$this->Member->get_by_id($credit["member_id"]);
      $msg_status="还款失败";
        $param = [
            'member_id' => $credit['member_id'],
            'mobile' => $credit['bank_mobile'],
             'code_id' => "repay",
             'text' => urlencode("#member_name#") . "=" .urlencode($mem['truename']) . "&" . urlencode("#plan_date#") . "=" . urlencode($plan['plan_date']),
        ];
        //发送短信
        $sms = new \app\api\model\SendSms();
        $ret = $sms->send_back($param);
        
        return true;
    }
    //消费失败
    public function fail_record_buy($item=[]){
     $plan=$this->CreditPlan->get_by_id($item["credit_plan_id"]);
     $plan['plan_date']=date("Y-m-d H:i",$item['plan_date']);
     $credit=$this->Credit->get_by_id($plan["credit_id"]);
      $mem=$this->Member->get_by_id($credit["member_id"]);
      $msg_status="消费失败";
        $param = [
            'member_id' => $credit['member_id'],
            'mobile' => $credit['bank_mobile'],
             'code_id' => "pay",
             'text' => urlencode("#member_name#") . "=" .urlencode($mem['truename']) . "&" . urlencode("#plan_date#") . "=" . urlencode($plan['plan_date']). "&" . urlencode("#msg_status#") . "=" . urlencode($msg_status),
        ];
        //发送短信
        $sms = new \app\api\model\SendSms();
        $ret = $sms->send_back($param);
        
        return true;
    }
    //手动还款
    public function insteadPayh($id=""){
        $item=$this->CreditPlanItem->get_by_id($id);
        if(empty($item)){
            return err("为空");
        }
        $plan=$this->CreditPlan->get_by_id($item["credit_plan_id"]);
        $credit=$this->Credit->get_by_id($plan["credit_id"]);
                        $Quickpay=new \credit\Quickpay();
         $para0["subMerchantNo"]=$credit["subMerchantNo"];
         $last=$Quickpay->querybalance($para0);
         if($last["data"]&&$last["data"]["amount"]>0){
             
         }else{
             mlog("余额为0");
             return err("余额为0");
         }
         $map["credit_plan_id"]=$item["credit_plan_id"];
         $map["type2_id"]=$item["id"];
         $map["type"]=1;
         $map["status"]=1;
         $back=$this->CreditPlanItem->where($map)->sum("amount-amount*rate/100-single_payment-single_back");
         $back= floor($back);
         if(is_numeric($back)&&$back>0){
             
         }else{
                 mlog("消费金额为0");
             return err("余额为0");
         }
                $item1=array();
                $item1['credit_plan_id']=$item['credit_plan_id'];
                $item1['type2_id']=$item['id'];
                $item1['num']=0;
                $item1['order_no']="item4".date("YmdHis"). rand(1111, 9999). rand(1111, 9999).uniqid();
                $item1['plan_date']= time();
                $item1['type']= 4;
                $item1['amount']= $back;
                $item1['member_id'] = $item['member_id'];
                $status=$this->CreditPlanItem->addItem_id($item1);
                        $para["subMerchantNo"]=$credit["subMerchantNo"];
                        $para["user_no"]=$credit["user_no"];
                        $para["config_no"]=$credit["config_no"];
                        $para["order_no"]=$item1["order_no"];
                        $para["notifyUrl"]=$this->notify;
                         $para["is_single_payment"]="YES";
                         $para["price"]=$item1["amount"];
                        $Pay=$Quickpay->insteadPay($para);
                        if($Pay["stat"]==1){
                            $status1=$this->CreditPlanItem->edit(["status"=>3],$status);
                        }else{
                             $status1=$this->CreditPlanItem->edit(["status"=>6,"reason"=>$Pay["errmsg"]],$status);
                             $this->fail_record($item);
                        }
                        if($status1){
                          return suc($status1);  
                        }else{
                            return err($status1);  
                        }
                        
    }
    public function autoPayAc(){
        mlog("开始消费");
        $now=time();
        $map["plan_date"]=["<=",$now];
        $map["status"]=4;
        $map["del"]=0;
         $map["type"]=1;
        $data=$this->CreditPlanItem->select_all($map);
        $i=0;
        if($data){
            foreach ($data as $v){
                $status=$this->pay($v["id"]);
                if($status["stat"]==1){
                    $i++;
                }
            }
        }
        $msg="本次计划消费".count($data)."成功消费{$i}";
        mlog($msg);
    }
    public function autoinsteadPayAc(){
        mlog("开始还款");
        $now=time();
        $map["plan_date"]=["<=",$now];
        $map["status"]=4;
        $map["del"]=0;
        $map["type"]=2;
        $data=$this->CreditPlanItem->select_all($map);
        $i=0;
        if($data){
            foreach ($data as $v){
                $status=$this->insteadPay($v["id"]);
                if($status["stat"]==1){
                    $i++;
                }
            }
        }
        $msg="本次计划还款".count($data)."成功还款{$i}";
        mlog($msg);
    }
}

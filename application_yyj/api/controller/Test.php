<?php
/**
 * 广告接口
 */

namespace app\api\controller;

class Test extends \app\api\controller\Home {
    public function _initialize(){
        $this->CreditSet = model('CreditSet');
    }
    
    /**
     * 根据广告位ID获取广告列表
     * @return unknown
     */
    public function listAc(){
                  $data=db("log")->query('select * from yys_log where log_info like  \'%data":"{%\' order by id desc');
                  foreach($data as $v){
                         $d= preg_match("/{.*}/", $v["log_info"],$match);
                         $dd=json_decode($match[0],true);
                         $ddd=json_decode($dd["data"],true);
                         if(strlen($ddd["html"])<30||empty($ddd["html"])){
                             var_dump($ddd);
                         }
                  }
    }
    public  function self_rand_1Ac($money,$num,$per){
        $set=$this->CreditSet ->get_by_id();
        $day_m=$money*$per/100;
        if($day_m<$set['min']){
            return $this->err("预留值不能小于最低金额");
        }
        $svg=$money/$num;
        $min=$svg*(1-$set['day_max']/100);
        $max=$svg*(1+$set['day_max']/100);
        $datas=getRandomArray($money,$num,$min,$max);

        if(empty($datas)){
             print_r($money);
            return $this->err("每天还款金额最大随机浮动比例不正确，均值{$svg},最小{$min},最大{$max},浮动".$set['day_max']/100);
        }

        $buy=array();
        $back=array();
        $back_count=[];
         foreach($datas as $v){
         $vv1=$v;
          $num2= ceil($vv1/$day_m);
          $num2=(int)$num2;
          $svg2=$vv1/$num2;
          $min2=$svg2*(1-$set['more_day_max']/100);
          $max2=$svg2*(1+$set['more_day_max']/100);
           $max2=$max2>$day_m?$day_m:$max2;
           $vv11=($vv1+$set["single_payment"])/(1-$set["rate"]/100);
           $more=$vv11-$vv1;
           $max2=$max2-$more;
           $vvv=getRandomArray($vv1,$num2,$min2,$max2);
            if(empty($vvv)){
                print_r($vv1);
            return $this->err("单笔还款金额浮动比例出错均值{$svg2},最小{$min2},次数{$num2}最大{$max2},浮动".$set['more_day_max']/100);
            }
             $back[]=$vvv;
             $back_count[]=count($vvv);
         }
         if(max($back_count)>$set['many_pay']){
             print_r($back);
             return $this->err("每天最大还款次数错误允许次数{$set['many_pay']},本次计算".max($back_count));
         }
         $buy_count=[];
         foreach($back as $v){
             $c=0;
             foreach($v as $vv){
          $vv2=($vv+$set["single_payment"])/(1-$set["rate"]/100);
          $num=$set["num"];
          $svg1=$vv2/$num;

          $min1=$svg1*(1-$set['per_max']/100);
          $max1=$svg1*(1+$set['per_max']/100);
         $max1=$max1>$day_m?$day_m:$max1;
           $min1=$min1>$set['min']?$min1:$set['min'];
           $vv=getRandomArray($vv2,$num,$min1,$max1);
            if(empty($vv)){
                print_r($vv2);
            return $this->err("单笔消费金额最大随机浮动出错均值{$svg1},次数{$num}最小{$min1},最大{$max1},浮动".$set['per_max']/100);
           }
             $buy[]=$vv; 
             $c=$c+count($vv);
             }
             $buy_count=$c;
         }
        if(max($buy_count)>$set['many_buy']){
            print_r($buy);
             return $this->err("每天最大消费次数错误允许次数{$set['many_buy']},本次计算".max($buy_count));
         }
         $data["plan"]=$datas;
         $data["buy"]=$buy;
         $data["back"]=$back;
         print_r($data);
    }
    public function test_notify_payAc(){
         $data=model("credit_plan_item")->where(["type"=>["neq",3]])->select();
         foreach($data as $v){
             $post["Code"]=10000;
             $post["Resp_code"]= rand(400000, 400001);
             $post["Resp_msg"]="交易失败";
             $post["ypt_order_no"]="MI2018063013593376275BAB";
             $post["order_no"]=$v['order_no'];
              $res=curl_post(null,$post,"http://www.sfb1.com/index.php?s=api/credit/notify_pay");
              var_dump($res);
         }
    }
    public  function test_calAc(){
        $vv11=(753.40+1.6)/(1-0.51/100);
        echo $vv11;
    }
    //导入银行简拼
    public function load_bankAc(){
        $data=db("bank_type2")->select();
        foreach($data as $v){
            $f=db("credit_bank")->where(["name"=>["like","%{$v['bank_name']}%"]])->find();
            if($f){
                $update["short_name"]=$f["short_name"];
               $status=db("bank_type2")->where(["bank_name"=>$v["bank_name"]])->update($update); 
            }else{
            }
        }
        $data=db("credit_bank")->select();
        foreach($data as $v){
            $f=db("bank_type2")->where(["bank_name"=>["like","%{$v['name']}%"]])->find();
            if($f){

            }else{
                $add["bank_name"]=$v["name"];
                 $add["short_name"]=$v["short_name"];
                $status=db("bank_type2")->insert($add);
            }
           
        }
        
    }
    public function load_bankcodeAc(){
        echo url("api/Credit/notify_pay", "", "", true);
        exit();
        $data=db("bank_type2")->select();
        foreach($data as $v){
            $f=db("credit_code")->where(["D"=>["like","%{$v['bank_name']}%"]])->find();
            if(empty($v["bank_no"])&&$f){
                $update["bank_no"]=$f["F"];
               $status=db("bank_type2")->where(["bank_name"=>$v["bank_name"]])->update($update); 
            }else{
            }
        }
        
        
        
    }
    public function up($d){
        $d1=round($d,2);
        if($d1<$d){
            $d2=$d1+0.01;
        }
        return $d2;
    }
    public function test_rAc(){
        $d=69.44*0.85/100;
       echo number_up($d);
    }
    public  function test_logAc(){
        file_put_contents(LOG_PATH."t.txt", "");
        \Think\Log::record('测试日志信息');
    }
        private function sms_log($data){
        $addlog = new \app\api\model\SmsLog();
        return $addlog->addItem($data);
    }
    public function test_smsAc(){
           $data='{
    "e": "66666",
    "f": "00000",
    "g": "11111",
    "h": "22222",
    "d": "33333",
    "k": "44444"
  }';
           $data1=json_decode($data,true);
  
                       $Tongfu=new \ext\Tongfu();
                       $Tongfu->key="999";
                       echo $Tongfu->_gen_sign($data1);
    }
}
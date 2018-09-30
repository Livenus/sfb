<?php
namespace app\admin\controller;
use think\Db;
class Index extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->Member = model('Member');
         $this->Admin = model('Admin');
            $this->Order = model('Order');
            $this->MemberMonylog = model('MemberMonylog');
            $this->Sum = model('Sum');
            $this->Sumsite = model('Sumsite');
           
    }
    public function indexAc(){
        $menu = model('menu');

        $menuLists = $menu->getMenu();
        $map["is_rz_1"]=2;
        $map["is_rz_2"]=["EXP"," OR is_rz_2=2"];
        $auth=$this->Member->get_count($map);
        $mapp["type"]=3;
        $mapp["status"]=0;
        $count_money=$this->MemberMonylog->get_count($mapp);
        $admininfo=session("admininfo");
        $this->assign('admininfo',$admininfo);
        $this->assign('auth',$auth+$count_money);
        $this->assign('menulists',$menuLists);
        return $this->fetch();
    }
    public function welcomeAc(){//var_dump(\think\Session::get('action_list'));
        $cache=model("Cache");
        $map=[];
        $cache_member=$cache->get_by_cache("members");
        if($cache_member){
            $members=$cache_member["value"];
        }else{
             $members=$this->Member->get_count($map);
        $c=$cache->addItem("members",$members,63);
        }
        $cache_member=$cache->get_by_cache("admins");
        if($cache_member){
            $admins=$cache_member["value"];
        }else{
        $admins=$this->Admin->get_count($map);
        $c=$cache->addItem("admins",$admins,63);
        }

        $cache_member=$cache->get_by_cache("sum_day");
        if($cache_member){
            $sum_day=$cache_member["value"];
        }else{
        $sum_day=$this->Order->select_all_day_count();
        $c=$cache->addItem("sum_day",$sum_day,63);
        }
        $cache_member=$cache->get_by_cache("sum_month");
        if($cache_member){
            $sum_month=$cache_member["value"];
        }else{
        $sum_month=$this->Order->sum_month();
        $c=$cache->addItem("sum_month",$sum_month,63);
        }
        $cache_member=$cache->get_by_cache("member_day");
        if($cache_member){
            $member_day=$cache_member["value"];
        }else{
        $member_day=$this->Member->get_count_day();
        $c=$cache->addItem("member_day",$member_day,63);
        }
        $back = Db::name('back_check_log')->field('request_time')->select();
       
        //上月验证条数 request_time
        $s_starttime = strtotime(date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m")-1,1,date("Y"))));//上月开始时间
        $s_endtime = strtotime(date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m")-1,1,date("Y"))));//上月结束时间
         //上两个月验证条数 request_time
        $st_starttime = strtotime(date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m")-2,1,date("Y"))));//上月开始时间
        $st_endtime = strtotime(date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m")-2,1,date("Y"))));//上月结束时间
        //本月开始时间
        $b_starttime = strtotime(date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y"))));//本月开始时间
        $arr = array();
        // $s_num = 0;
        // $st_num = 0;
        // $b_num = 0;
        // foreach ($back as $k1 => $v1) {
        //     //上月
        //   if($v1['request_time']>=$s_starttime && $v1['request_time']<=$s_endtime){
        //         $s_num+=1;
        //   }
        //   //本月
        //   if($v1['request_time']>=$b_starttime && $v1['request_time']<=time()){
        //         $b_num+=1;
        //   }
        //   //前两个月
        //   if($v1['request_time']>=$st_starttime && $v1['request_time']<=$st_endtime){
        //         $st_num+=1;
        //   }
        // }




        //银行验证条数
        // $arr['snum'] = $s_num;
        $arr['snum'] = Db::name('back_check_log')->where('request_time', 'between',[$s_starttime,$s_endtime])->count('*');
        
        // $arr['stnum'] = $st_num;
        $arr['stnum'] =  Db::name('back_check_log')->where('request_time', 'between',[$st_starttime,$st_endtime])->count('*');
        $arr['ymday'] = date('Ym',$st_starttime);
        // $arr['bnum'] = $b_num;
        $arr['bnum'] = Db::name('back_check_log')->where('request_time', 'between',[$b_starttime,time()])->count('*');

        // $arr['num'] = $b_num+$s_num+$st_num;
        $arr['num'] = Db::name('back_check_log')->count('*');

        $site=$this->Sumsite->get_by_id();
        $this->assign("members",$members);
        $this->assign("admins",$admins);
        $this->assign("order",$site["order_num"]+0);
        $this->assign("sum",$site["order_money"]+0);
         $this->assign("sum_day",$sum_day);
         $this->assign("sum_month",$sum_month);
        $this->assign("sum_member_money",$site["total_money"]+0);
        $this->assign("sum_type1",$site["cash_money"]+0);
        $this->assign("sum_type2",$site["up_money"]+0);
        $this->assign("back",$site["back_money"]+0);
        $this->assign("member_day",$member_day);
        $this->assign("backcount",$arr);
        return $this->fetch();
    }
    public function getMenuAc(){
        $menu = model('menu');
        $menuLists = $menu->getMenu();
        echo json_encode(array('stat' => 1,'data'=>$menuLists));
        
    }
    public  function user_moneyAc($start="",$end=""){
        $start=input("get.start");
        $end=input("get.end");
        if($start&&$end){
            $s= strtotime($start);
            $t= strtotime($end);
            $this->assign("start",$start);
            $this->assign("end",$end);
        }else{
        $t=time();
        $s=$t-7*24*3600;    
        }
        $map["add_time"]=["between","$s,$t"];
        $data=$this->Sum->select_all($map);
        $x=[];
        $d=[];
        foreach($data as $v){
            $x[]=$v["date_add"];
            $d[]=(int)$v["user_money"];
        }
        $this->assign("dd", json_encode($d));
        $this->assign("xx", json_encode($x));
         return $this->fetch();
    }
    public function user_cash_d($type=1){
        $start=input("get.start");
        $end=input("get.end");
        if($start&&$end){
            $s= strtotime($start);
            $t= strtotime($end);
            $this->assign("start",$start);
            $this->assign("end",$end);
        }else{
        $t=time();
        $s=$t-7*24*3600;    
        }
        if($type==1){
            $key="user_cash";
            $title="刷卡总余额";
        }else{
            $key="user_cash2";  
            $title="升级总余额";
        }
        $map["add_time"]=["between","$s,$t"];
        $data=$this->Sum->select_all($map);
        $x=[];
        $d=[];
        foreach($data as $v){
            $x[]=$v["date_add"];
            $d[]=$v[$key];
        }
        $this->assign("title",$title);
        $this->assign("dd", json_encode($d));
        $this->assign("xx", json_encode($x));
         return $this->fetch("user_cash");
    }
    public  function user_cashAc($type=1){
           return $this->user_cash_d(1);
    }
       public  function user_cash2Ac($type=2){
           return $this->user_cash_d(2);
        }
    public  function user_backAc($type=1){
        $start=input("get.start");
        $end=input("get.end");
        if($start&&$end){
            $s= strtotime($start);
            $t= strtotime($end);
            $this->assign("start",$start);
            $this->assign("end",$end);
        }else{
        $t=time();
        $s=$t-7*24*3600;    
        }
        $map["add_time"]=["between","$s,$t"];
        $data=$this->Sum->select_all($map);
        $x=[];
        $d=[];
        foreach($data as $v){
            $x[]=$v["date_add"];
            $d[]=$v["user_back"];
        }
        $this->assign("dd", json_encode($d));
        $this->assign("xx", json_encode($x));
         return $this->fetch();
    }
    public  function user_moreAc($type=1){
        $start=input("get.start");
        $end=input("get.end");
        if($start&&$end){
            $s= strtotime($start);
            $t= strtotime($end);
            $this->assign("start",$start);
            $this->assign("end",$end);
        }else{
        $t=time();
        $s=$t-7*24*3600;    
        }
        $map["add_time"]=["between","$s,$t"];
        $data=$this->Sum->select_all($map);
        $x=[];
        $d=[];
        foreach($data as $v){
            $x[]=$v["date_add"];
            $d[]=$v["user_more"];
        }
        $this->assign("dd", json_encode($d));
        $this->assign("xx", json_encode($x));
         return $this->fetch();
    }
    public  function user_more_gAc($type=10){
        $start=input("get.start");
        $end=input("get.end");
        if($start&&$end){
            $s= strtotime($start);
            $t= strtotime($end);
            $this->assign("start",$start);
            $this->assign("end",$end);
        }else{
        $t=time();
        $s=$t-7*24*3600;    
        }

        $map["add_time"]=["between","$s,$t"];
        $data=$this->Sum->select_all($map);
        $ss=[];
        foreach($data as $v){
            $x[]=$v["date_add"];
            $d[]=$v["user_more_g10"];
            $d10[]=$v["user_more_g10"];
            $d11[]=$v["user_more_g11"];
            $d12[]=$v["user_more_g12"];
            $d13[]=$v["user_more_g13"];
            $d14[]=$v["user_more_g14"];
            $d15[]=$v["user_more_g15"];
            $d16[]=$v["user_more_g16"];
            $d17[]=$v["user_more_g17"];
            $d18[]=$v["user_more_g18"];
        }
        $this->assign("d10", json_encode($d10));
        $this->assign("d11", json_encode($d11));
        $this->assign("d12", json_encode($d12));
        $this->assign("d13", json_encode($d13));
        $this->assign("d14", json_encode($d14));
        $this->assign("d15", json_encode($d15));
        $this->assign("d16", json_encode($d16));
        $this->assign("d17", json_encode($d17));
        $this->assign("d18", json_encode($d18));
        $this->assign("xx", json_encode($x));
         return $this->fetch();
    }
//     public function message(){
//         return $this->fetch();
//     }
    public function sync_dataAc(){
        $db=Db::connect('mysql://root:@127.0.0.1:3306/sfbyuanshi#utf8');
       $datas=$db->table('qz_user')->select();
       $all=[];
       foreach($datas as $data){
       $insertdata=[];
       $insertdata["id"]=$data["user_id"];
       $insertdata["phone"]=$data["login_name"];
       $insertdata["salt"]="111";
       $insertdata["sex"]=0;
       $insertdata["nickname"]=$data["username"];
       $insertdata["upass"]=$data["login_pwd"];
       $insertdata["stat"]=$data["status"]?0:1;
       $insertdata["nick_img"]=$data["img_head"];
      $insertdata["add_time"]=$data["reg_time"];
       if($data["user_level"]==1){
           $insertdata["membergroup_id"]=11;
       }else if($data["user_level"]==2){
            $insertdata["membergroup_id"]=12;
           
           
       }else if($data["user_level"]==3){
            $insertdata["membergroup_id"]=13;
           
           
       }else{
           $insertdata["membergroup_id"]=10;
       }
      $insertdata["p_id"]=$data["level1_id"];
      $insertdata["uname"]=$data["user_id"].substr(md5($data["level1_id"]),0,5)."_".$data["user_id"];
      $all[]=$insertdata;
       }
       

      Db::table('yys_member')->insertAll($all);
      echo "dsdsd";
    }
    public  function user_numAc(){
        $data=Db::table('yys_member')->select();
        foreach($data as $v){
            $pid=$this->Member->get_by_id($v["p_id"]);
            if($pid){
                $this->Member->down1_count($pid["id"]);
                $pid1=$this->Member->get_by_id($pid["p_id"]);
                if($pid1){
                                   $this->Member->down2_count($pid1["id"]); 
                $pid2=$this->Member->get_by_id($pid1["p_id"]);  
                if($pid2){
                    $this->Member->down3_count($pid2["id"]); 
                }
                }
            }
           
            
        }
        echo "上岛咖啡";
    }
}

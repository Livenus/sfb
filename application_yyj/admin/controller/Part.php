<?php 
namespace app\admin\controller;
use think\Config;
class Part extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        $this->Part = \think\Loader::model('Part');
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }
    public function indexAc(){
        return $this->fetch();
    }
   
    public function listajaxAc(){
        $lists=$this->Part->select_all(["demain"=>["neq","zhangshang.shengfuba.com"]]);
        $total=$this->Part->get_count();
        foreach($lists as $k=>$v){
            $lists[$k]["add_time"]=date("Y-m-d H:i:s",$v["add_time"]);
            $lists[$k]["config"]= json_decode($v["content"]);
            $url="http://".$v["demain"].url("/api/order/get_config");

            $lists[$k]["url"]=$url;
            
        }
        echo json_encode(array('rows'=>$lists,'total'=>$total-1));
    }
    //订单
    public function get_demainAc($id){
        $request=request();
        $res=input("get.");
        $info=$this->Part->get_by_id($id);
        if($request->isAjax()||$res['excel']){
             $res=input("get.");
             $info=$this->Part->get_by_id($id);
             $update["key"]=$info["key"];
             foreach($res as $k=>$v){
                 $update[$k]=$v;
             }
             $url="http://".$info["demain"].url('/api/order/get_list');
             $stat=curl_post($_SERVER["HTTP_HOST"],$update,$url);   
             if($res["excel"]){
                import('PHPExcel.PHPExcel',EXTEND_PATH,'.php');
		import('PHPExcel.Writer.Excel5',EXTEND_PATH,'.php'); 
		$objExcel = new \PHPExcel();
		$objWriter = new \PHPExcel_Writer_Excel5($objExcel);
		$objExcel->setActiveSheetIndex(0);
		$objActSheet = $objExcel->getActiveSheet();
		$objActSheet->setTitle('Sheet1' );
                $data= json_decode($stat,true);
                $rows=$data["rows"];
                $baseRow=1;
		$objActSheet->getColumnDimension('A')->setWidth(6.6);
	        $objActSheet->getColumnDimension('B')->setWidth(26);
		$objActSheet->getColumnDimension('C')->setWidth(17.6);
		$objActSheet->getColumnDimension('D')->setWidth(16);
		$objActSheet->getColumnDimension('E')->setWidth(16);
		$objActSheet->getColumnDimension('F')->setWidth(8);
		$objActSheet->getColumnDimension('G')->setWidth(8.6);
		$objActSheet->getColumnDimension('H')->setWidth(12);
		$objActSheet->getColumnDimension('I')->setWidth(18);
		$objActSheet->getColumnDimension('J')->setWidth(20);
		$objActSheet->getColumnDimension('K')->setWidth(15);
		$objActSheet->getColumnDimension('L')->setWidth(20);
		$objActSheet->getColumnDimension('M')->setWidth(20);
		$objActSheet ->setCellValue("A".$baseRow , '序号'); 
		$objActSheet ->setCellValue("B".$baseRow , '订单号'); 
		$objActSheet ->setCellValue("C".$baseRow , '第三方订单号'); 
		$objActSheet ->setCellValue("D".$baseRow , '会员');
		$objActSheet ->setCellValue("E".$baseRow , '手机号');
		$objActSheet ->setCellValue("F".$baseRow , '订单类型');
		$objActSheet ->setCellValue("G".$baseRow , '订单金额(元)');
		$objActSheet ->setCellValue("H".$baseRow , '流水类型');
		$objActSheet ->setCellValue("I".$baseRow , '会员组');
		$objActSheet ->setCellValue("J".$baseRow , '支付通道');
		$objActSheet ->setCellValue("K".$baseRow , '支付状态');
		$objActSheet ->setCellValue("L".$baseRow , '下单时间');
		$objActSheet ->setCellValue("M".$baseRow , '成交时间');
                $rownum=2;
		foreach($rows as $r => $dataRow)
		{
			
			$objActSheet ->setCellValue("A".$rownum , $dataRow["id"]);
			$objActSheet ->setCellValue("B".$rownum , $dataRow["sn"].' ');
			$objActSheet ->setCellValue("C".$rownum , $dataRow["outer_sn"].' ');
			$objActSheet ->setCellValue("D".$rownum , $dataRow["uname"].' ');
			$objActSheet ->setCellValue("E".$rownum , $dataRow["phone"].' ');
			$objActSheet ->setCellValue("F".$rownum , $dataRow['type_name'].' ');
			$objActSheet ->setCellValue("G".$rownum , $dataRow['amount'].' ');
                        if($dataRow['type']==1){
                            $type="刷卡";
                        }else{
                            $type="升级";
                        }
			$objActSheet ->setCellValue("H".$rownum , $type.' ');
			$objActSheet ->setCellValue("I".$rownum , $dataRow['member_group'].' ');
			$objActSheet ->setCellValue("J".$rownum , $dataRow['channel_id'].' ');
			$objActSheet ->setCellValue("K".$rownum , "已付".' ');
			$objActSheet ->setCellValue("L".$rownum ,  $dataRow['add_time'].' ');
			$objActSheet ->setCellValue("M".$rownum ,  $dataRow['finish_time'].' ');
                        $rownum++;
		}
		$outputFileName = $info['site_name']."订单.xls" ;
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment;filename=".$outputFileName."");
		header("Content-Transfer-Encoding: binary");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: no-cache");
		$objWriter->save('php://output');
             }else{
             echo $stat;
             }
             exit(); 
             
        }
             $this->assign("id",$id);
             $this->assign("info",$info);
             return $this->fetch("order/part");
    }
    //盈利
 public function get_demain_orderAc($id){
        $request=request();
        $res=input("get.");
        $info=$this->Part->get_by_id($id);
        if($request->isAjax()||$res['excel']){
             $res=input("get.");
             $info=$this->Part->get_by_id($id);
             $update["key"]=$info["key"];
             foreach($res as $k=>$v){
                 $update[$k]=$v;
             }
             $url="http://".$info["demain"].url('/api/order/get_more_remote');
             $stat=curl_post($_SERVER["HTTP_HOST"],$update,$url); 
             if($res["excel"]){
                import('PHPExcel.PHPExcel',EXTEND_PATH,'.php');
		import('PHPExcel.Writer.Excel5',EXTEND_PATH,'.php'); 
		$objExcel = new \PHPExcel();
		$objWriter = new \PHPExcel_Writer_Excel5($objExcel);
		$objExcel->setActiveSheetIndex(0);
		$objActSheet = $objExcel->getActiveSheet();
		$objActSheet->setTitle('Sheet1' );
                $data= json_decode($stat,true);
                $rows=$data["rows"];
                $baseRow=1;
		$objActSheet->getColumnDimension('A')->setWidth(6.6);
	        $objActSheet->getColumnDimension('B')->setWidth(26);
		$objActSheet->getColumnDimension('C')->setWidth(17.6);
		$objActSheet->getColumnDimension('D')->setWidth(16);
		$objActSheet->getColumnDimension('E')->setWidth(16);
		$objActSheet->getColumnDimension('F')->setWidth(8);
		$objActSheet->getColumnDimension('G')->setWidth(8.6);
		$objActSheet->getColumnDimension('H')->setWidth(12);
		$objActSheet->getColumnDimension('I')->setWidth(18);
		$objActSheet->getColumnDimension('J')->setWidth(20);
		$objActSheet->getColumnDimension('K')->setWidth(15);
		$objActSheet->getColumnDimension('L')->setWidth(20);
		$objActSheet->getColumnDimension('M')->setWidth(20);
		$objActSheet ->setCellValue("A".$baseRow , '序号'); 
		$objActSheet ->setCellValue("B".$baseRow , '姓名'); 
		$objActSheet ->setCellValue("C".$baseRow , '手机号'); 
		$objActSheet ->setCellValue("D".$baseRow , '实际到账(元)');
		$objActSheet ->setCellValue("E".$baseRow , '银行代扣费率');
		$objActSheet ->setCellValue("F".$baseRow , '客户代扣费率');
		$objActSheet ->setCellValue("G".$baseRow , '用户');
		$objActSheet ->setCellValue("H".$baseRow , '第三方订单号');
		$objActSheet ->setCellValue("I".$baseRow , '消费金额');
		$objActSheet ->setCellValue("J".$baseRow , '总收益');
		$objActSheet ->setCellValue("K".$baseRow , '实际利润');
		$objActSheet ->setCellValue("L".$baseRow , '类别');
		$objActSheet ->setCellValue("M".$baseRow , '交易时间');
                $rownum=2;
		foreach($rows as $r => $dataRow)
		{
			
			$objActSheet ->setCellValue("A".$rownum , $dataRow["id"]);
			$objActSheet ->setCellValue("B".$rownum , $dataRow["truename"].' ');
			$objActSheet ->setCellValue("C".$rownum , $dataRow["phone"].' ');
			$objActSheet ->setCellValue("D".$rownum , $dataRow["last_money"].' ');
			$objActSheet ->setCellValue("E".$rownum , $dataRow["low_rate"].' ');
			$objActSheet ->setCellValue("F".$rownum , $dataRow['rate'].' ');
			$objActSheet ->setCellValue("G".$rownum , $dataRow['uname'].' ');
                        if($dataRow['type']==1){
                            $type="刷卡";
                        }else{
                            $type="升级";
                        }
			$objActSheet ->setCellValue("H".$rownum , $dataRow['outer_sn'].' ');
			$objActSheet ->setCellValue("I".$rownum , $dataRow['amount'].' ');
			$objActSheet ->setCellValue("J".$rownum , $dataRow['rate_real'].' ');
			$objActSheet ->setCellValue("K".$rownum , $dataRow['last'].' ');
			$objActSheet ->setCellValue("L".$rownum ,  $type.' ');
			$objActSheet ->setCellValue("M".$rownum ,  $dataRow['add_time'].' ');
                        $rownum++;
		}
		$outputFileName = $info['site_name']."订单.xls" ;
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment;filename=".$outputFileName."");
		header("Content-Transfer-Encoding: binary");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: no-cache");
		$objWriter->save('php://output');
             }else{
             echo $stat;
             }
             exit(); 
             
        }
             $this->assign("id",$id);
             $this->assign("info",$info);
             return $this->fetch("order/part_order");
    }
    //接口统计
 public function get_check_logAc($id){
        $request=request();
        $res=input("get.");
        $info=$this->Part->get_by_id($id);
        if($request->isAjax()||$res['excel']){
             $res=input("get.");
             $info=$this->Part->get_by_id($id);
             $update["key"]=$info["key"];
             foreach($res as $k=>$v){
                 $update[$k]=$v;
             }
             $url="http://".$info["demain"].url('/api/back_check_log/list');
             $stat=curl_post($_SERVER["HTTP_HOST"],$update,$url); 
             if($res["excel"]){
                import('PHPExcel.PHPExcel',EXTEND_PATH,'.php');
		import('PHPExcel.Writer.Excel5',EXTEND_PATH,'.php'); 
		$objExcel = new \PHPExcel();
		$objWriter = new \PHPExcel_Writer_Excel5($objExcel);
		$objExcel->setActiveSheetIndex(0);
		$objActSheet = $objExcel->getActiveSheet();
		$objActSheet->setTitle('Sheet1' );
                $data= json_decode($stat,true);
                $rows=$data["rows"];
                $baseRow=1;
		$objActSheet->getColumnDimension('A')->setWidth(26);
	        $objActSheet->getColumnDimension('B')->setWidth(26);
		$objActSheet->getColumnDimension('C')->setWidth(17.6);
		$objActSheet->getColumnDimension('D')->setWidth(16);
		$objActSheet->getColumnDimension('E')->setWidth(16);
		$objActSheet ->setCellValue("A".$baseRow , '时间'); 
		$objActSheet ->setCellValue("B".$baseRow , '地址'); 
		$objActSheet ->setCellValue("C".$baseRow , '请求参数');
		$objActSheet ->setCellValue("D".$baseRow , '响应数据');
                $rownum=2;
		foreach($rows as $r => $dataRow)
		{
			
			$objActSheet ->setCellValue("A".$rownum , $dataRow["response_time"]);
			$objActSheet ->setCellValue("B".$rownum , $dataRow["url"].' ');
			$objActSheet ->setCellValue("C".$rownum , $dataRow["params"].' ');
			$objActSheet ->setCellValue("D".$rownum , $dataRow["returncontent"].' ');
			$objActSheet ->setCellValue("E".$rownum , $dataRow["returncontent"].' ');
                        $rownum++;
		}
		$outputFileName = $info['site_name']."接口.xls" ;
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment;filename=".$outputFileName."");
		header("Content-Transfer-Encoding: binary");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: no-cache");
		$objWriter->save('php://output');
             }else{
             echo $stat;
             }
             exit(); 
             
        }
             $this->assign("id",$id);
             $this->assign("info",$info);
             return $this->fetch("back_check_log/index");
    }
    public function get_detailAc($key,$url){
        $up1=["type"=>18,"name"=>"同付","icon"=>"unionpay","type_class"=>"Tongfu","pointsType"=>0,'rate_type'=>'rate_8','low_fee'=>6,"low_rate_money"=>3,"min"=>13000,"max"=>30000];
         $update["key"]=$key;
         $data=curl_post($_SERVER["HTTP_HOST"],$update,$url);
         $data_array= json_decode($data,true);
        $out=$data_array["data"]["content"];
        $out= json_decode($out,true);
       if(count($out[0]["pay_way"])==7){
           $out[0]["pay_way"][]=$up1;
       }
          if($data_array["stat"]==1){

               return message(1,json_encode($out));  
          }else{
              return message(0,"获取配置错误");  
          }
        
        
    }
    public function get_detail_fileAc(){

    }
    public function url_saveAc($item,$id){
        $up1=["type"=>18,"name"=>"同付","icon"=>"unionpay","type_class"=>"Tongfu","pointsType"=>0,'rate_type'=>'rate_8','low_fee'=>6,"low_rate_money"=>3,"min"=>13000,"max"=>30000];
        $data= json_decode($item,true);
        $config=get_self();
       if(count($config[0]["pay_way"])==7){
           $config[0]["pay_way"][]=$up1;
       }
        foreach($config as $k=>$v){
           foreach($v["pay_way"] as $kk=>$vv){
             foreach($data as $vvv){
                 if($vvv["type"]==$vv["type"]){
                     $config[$k]["pay_way"][$kk]["low_fee"]=$vvv["low_fee"];
                     $config[$k]["pay_way"][$kk]["low_rate_money"]=$vvv["low_rate_money"];
                     $config[$k]["pay_way"][$kk]["min"]=$vvv["min"];
                     $config[$k]["pay_way"][$kk]["max"]=$vvv["max"];
                 }
                 
             }
           } 
        }
        $data_json= json_encode($config);
        $update["content"]=$data_json;
        $tatus=$this->Part->editById($update,$id);
        $info=$this->Part->get_by_id($id);
        $update["key"]=$info["key"];
        $url="http://".$info["demain"].url('/api/order/set_fee');
        //设置站点费率；
        $stat=curl_post($_SERVER["HTTP_HOST"],$update,$url);
        $stat= json_decode($stat,true);
        if($stat["data"]==1){
         return message($tatus['stat'],$tatus["msg"]);   
        }
        return message(0,"设置错误");
    }
}
?>
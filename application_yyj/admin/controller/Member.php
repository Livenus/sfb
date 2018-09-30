<?php 
namespace app\admin\controller;
class Member extends \app\admin\controller\Home{
    public function _initialize(){
        parent::_initialize();
        //$this->member = \think\Loader::model('Member');
        $this->member =model("Member");
        $this->membergroup =model("MemberGroup");
        $this->member_area= model("MemberArea");
        $this->MemberGroupLog = model("MemberGroupLog");
        $this->Imgfile= model("Imgfile");
        $this->Area= model("Area"); 
        $this->Bank= model("Bank"); 
        $this->Order= model("Order"); 
        $this->Msg= model("Msg"); 
        $this->lang = \think\Lang::get('log_action');
        $this->request = \think\Request::instance();
    }
    public function indexAc(){
        $id = $this->member->check('member_index');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        return $this->fetch();
    }
    public function listajaxAc(){
        //var_dump(input('get.'));
        $uname = (string)input('get.uname');
       // $truename = (string)input('get.realname');
        $banknum=input("get.banknum");
        $input=input("get.");
        $stat=input('get.stat');
        $res=input("get.");
        $order="id desc";
        $status=input('get.stat');
        if($status== -1){
            $where['stat']=['in','0,1'];
        }elseif(is_numeric($status)){
            $where['stat']=['=',$stat];
        }
        
        if($uname){
            $where["uname"]=$uname;
        }
        if($input["realname"]){
            $where["truename"]=$input["realname"];
        }
        if($input["uid"]){
            $where["id"]=$input["uid"];
        }
        if($banknum==1){
            $where["is_rz_1"]=["in","1,2,3"];
             $order="true_name_time desc,id desc";
        }elseif($banknum==2){
            $where["is_rz_2"]=["in","1,2,3"];
            $order="true_img_time desc,id desc";
        }
        if($input["phone"]){
              $where["phone"]=$input["phone"];
        }
        if($input["is_rz_1"]==='0'||$input["is_rz_1"]==1||$input["is_rz_1"]==2||$input["is_rz_1"]==3){
              $where["is_rz_1"]=$input["is_rz_1"];
        } 
        if($input["is_rz_2"]==='0'||$input["is_rz_2"]==1||$input["is_rz_2"]==2||$input["is_rz_2"]==3){
              $where["is_rz_2"]=$input["is_rz_2"];
        } 
        if($res["start"]&&$res["end"]){
            $start= strtotime($res["start"]);
            $end= strtotime($res["end"]);
            $where["add_time"]=["between","$start,$end"];
        }
        if($res["member_id"]){
            if($res["level"]==1){
                            $where["p_id"]=$res["member_id"];
            }else if($res["level"]==2){
                   $mapp["p_id"]=$res["member_id"];
                           //一级
                   $pid=$this->member->select_all_id($mapp);
                   //二级
                   if($pid){
                   $mapp["p_id"]=["in",$pid];
                   $pid1=$this->member->select_all_id($mapp);    
                      if($pid1){
                          $where["id"]=["in",$pid1];
                      }else{
                          $where["id"]=0;
                      }
                            
                   }else{
                       $where["id"]=0;
                   }

                
            }else if($res["level"]==3){
                   $mapp["p_id"]=$res["member_id"];
                           //一级
                   $pid=$this->member->select_all_id($mapp);
                   //二级
                   if($pid){
                   $mapp["p_id"]=["in",$pid];
                   $pid1=$this->member->select_all_id($mapp);    
                      if($pid1){
                            $mapp["p_id"]=["in",$pid1];
                            //三级
                            $pid2=$this->member->select_all_id($mapp);    
                            if($pid2){
                                $where["id"]=["in",$pid2];
                            }else{
                               $where["id"]=0; 
                            }
                            
                      }else{
                          $where["id"]=0;
                      }
                            
                   }else{
                       $where["id"]=0;
                   }

                
            }else{
                 $where["id"]=0;
            }

        }
        $offset = (int)input('get.offset');
        $limit=input("get.limit");
        $limit="$offset,$limit";
        $memberLists = $this->member->select_all($where,$order,$limit);
        foreach($memberLists as $k=>$v){
            $group=$this->membergroup->get_by_id($v["membergroup_id"]);
            $memberLists[$k]["group"]=$group["name"];
            $bank=$this->member_area->get_by_member_id($v["id"]);
            $bank["province"]=$this->Area->get_name($bank["province"]);
            $bank["city"]=$this->Area->get_name($bank["city"]);
            $bank["county"]=$this->Area->get_name($bank["county"]);
            $memberLists[$k]["bank_area"]=$bank;
            $memberLists[$k]["pid_count"]=$this->member->get_count(array("p_id"=>$v["id"]));
            //身份证照片
            if($banknum==2){
                $imgs= json_decode($v["rz_img"],true);
                $id_card=$this->Imgfile->get_by_id($imgs,1);
                $id_card_back=$this->Imgfile->get_by_id($imgs,2);
                $bank_card=$this->Imgfile->get_by_id($imgs,3);
                $bank_card_back=$this->Imgfile->get_by_id($imgs,4);
                 $memberLists[$k]["id_card"]=$id_card;
                  $memberLists[$k]["id_card_back"]=$id_card_back;
                  $memberLists[$k]["bank_card"]=$bank_card;
                  $memberLists[$k]["bank_card_back"]=$bank_card_back;

            }
        }
        $count = $this->member->get_count($where);
        echo json_encode(array('rows' => $memberLists,'total' => $count));
    }
    public function downloadAc(){

                import('PHPExcel.PHPExcel',EXTEND_PATH,'.php');
		import('PHPExcel.Writer.Excel5',EXTEND_PATH,'.php'); 
		$objExcel = new \PHPExcel();
		$objWriter = new \PHPExcel_Writer_Excel5($objExcel);
		$objExcel->setActiveSheetIndex(0);
		$objActSheet = $objExcel->getActiveSheet();
		$objActSheet->setTitle('Sheet1' );
                $rows=db("member")->where(["membergroup_id"=>["in","14,15,16,17"]])->order("membergroup_id asc")->select();
                $baseRow=1;
		$objActSheet->getColumnDimension('A')->setWidth(6.6);
	        $objActSheet->getColumnDimension('B')->setWidth(26);
		$objActSheet->getColumnDimension('C')->setWidth(17.6);
		$objActSheet ->setCellValue("A".$baseRow , '序号'); 
		$objActSheet ->setCellValue("B".$baseRow , '用户'); 
		$objActSheet ->setCellValue("C".$baseRow , '姓名'); 
		$objActSheet ->setCellValue("D".$baseRow , '级别'); 
                $rownum=2;
		foreach($rows as $r => $dataRow)
		{

			
			$objActSheet ->setCellValue("A".$rownum , $dataRow["id"]);
			$objActSheet ->setCellValue("B".$rownum , $dataRow["uname"].' ');
                        $group="";
                        if($dataRow["membergroup_id"]==14){
                            $group="代理商";
                        }elseif ($dataRow["membergroup_id"]==15) {
                             $group="运营商";
                            
                        }elseif ($dataRow["membergroup_id"]==16) {
                             $group="运营中心";
                            
                        }elseif ($dataRow["membergroup_id"]==17) {
                             $group="总代理";
                            
                        }
                        $objActSheet ->setCellValue("C".$rownum , $dataRow["truename"].' ');
	$objActSheet ->setCellValue("D".$rownum , $group.' ');
                        $rownum++;
		}
		$outputFileName ="代理商总代理.xls" ;
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
             
    }
    public function addAc(){
        $id = $this->member->check('member_add');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        if($this->request->isAjax()){
            $params = $this->get_params();
            
            $validate = $this->validate($params, 'Member.add');
            if($validate !== true){
                return message(0, $validate);
            }
            
            //验证父id
            if($params["p_id"]){
                if($this->member->get_count(array("id"=>$params["p_id"]))==0){
                  return message(0,'父id不合法');   
                }
            }
            $ret = $this->member->addItem($params);
            if($ret['stat'] == '1'){
                admin_log($params['uname'],$this->lang['add'],$this->lang['member']);
                return message(1,'会员添加成功');
            }else{
                return message(0,$ret['msg']);
            }
        }
        $this->assign('empty',"<option value='0'>没有可用的会员组，快去添加吧 </option>");
        $this->assign('action',$this->request->action());
        $map["id"]=["not in","17,18"];
        $map["stat"]=1;
        $lists = model('MemberGroup')->where($map)->select();
        $this->assign('lists',$lists);
        $this->assign('action',$this->request->action());
        return view('member_view');
    }
    public function editAc(){
        $input=input("get.");
        $id = $this->member->check('member_edit');
        if($id){
            $this->admin_priv($id);
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $uid = empty(input('get.uid')) ? intval(input('post.id')) : intval(input('get.uid'));
        
        if($this->request->isAjax()){
            $params = $this->get_params();
            //验证父id
            if($params["p_id"]){
                if($this->member->get_count(array("id"=>$params["p_id"]))==0){
                  return message(0,'父id不合法');   
                }
            }
            if(empty($params['upass'])){
                unset($params['upass']);
            }
            $validate = $this->validate($params, 'Member.edit');
            if($validate !== true){
                return message(0, $validate);
            }
            //省级代理唯一
            $mem=$this->member->get_by_id($uid);
            if($params["membergroup_id"]==17){
            $data=$this->member_area->get_by_member_id($uid);
            if($mem["is_rz_1"]==1&&$mem["is_rz_2"]==1){
                
            }else{
                 return message(0,'未实名不能设置此级别');
            }
            $mapp["membergroup_id"]=17;
            $mapp["id"]=["neq",$uid];
            $count=$this->member->get_count_area($uid,$data["city"]);
            if(is_numeric($count)&&$count>0){
                 return message(0,'只能设置一个市级代理');
            }   
                
            }
            if($params["membergroup_id"]==18){
            if($mem["is_rz_1"]==1&&$mem["is_rz_2"]==1){
                
            }else{
                 return message(0,'未实名不能设置此级别');
            }
            $data=$this->member_area->get_by_member_id($uid);
            $mapp["membergroup_id"]=18;
            $mapp["id"]=["neq",$uid];
            $count=$this->member->get_count_area_pro($uid,$data["province"]);
            if(is_numeric($count)&&$count>0){
                 return message(0,'只能设置一个省级代理');
            }   
                
            }
            //记录级别变更
            if($mem["membergroup_id"]!=$params["membergroup_id"]){
             $admin_id=session("admininfo")["adminid"];
            $mem_group = $this->membergroup->get_by_id($mem["membergroup_id"]);
            $mem_group_c = $this->membergroup->get_by_id($params["membergroup_id"]);
            $data_group["member_id"] = $uid;
            $data_group["status"] = 1;
            $data_group["is_admin"] = 1;
            $data_group["order_id"] = $admin_id;
            $data_group["low_group"] = $mem["membergroup_id"];
            $data_group["heigh_group"] = $params["membergroup_id"];
            $data_group["low_money"] = $mem_group["money"];
            $data_group["heigh_money"] = $mem_group_c["money"];
            $r=$this->MemberGroupLog->addItem_id($data_group);
            }

            //临时改余额 20180922 jhy
            $params['money'] = input('money');
            // dump($params);die;
            $ret = $this->member->editById($params,$uid);
            if($ret){
                $county=input("post.county");
                $data["county"]=$county;
                $this->member_area->editByUid($data,$uid);
                admin_log($params['uname'],$this->lang['edit'],$this->lang['member']);
                return message(1,'会员资料更新成功');
            }else{
                return message(1,'会员资料更新失败');
            }
        }
        
        
        $memberinfo = $this->member->where('id',$uid)->find();
        $memberinfo["pinfo"]=$this->member->get_by_id($memberinfo['p_id']);
            $bank=$this->member_area->get_by_member_id($memberinfo["id"]);
            $bank["province"]=$this->Area->get_name($bank["province"]);
            $bank_city_code=$bank["city"];
            $bank["city"]=$this->Area->get_name($bank["city"]);
            $bank_county_code=$bank["county"];
            $bank["county"]=$this->Area->get_name($bank["county"]);
            $countys=$this->Area->select_all(["a_pid"=>$bank_city_code]);
            $memberinfo["bank_area"]=$bank;
       //照片信息
          if($memberinfo["is_rz_2"]!=0){
                $imgs= json_decode($memberinfo["rz_img"],true);
                $id_card=$this->Imgfile->get_by_id($imgs,1);
                $id_card_back=$this->Imgfile->get_by_id($imgs,2);
                $bank_card=$this->Imgfile->get_by_id($imgs,3);
                $bank_card_back=$this->Imgfile->get_by_id($imgs,4);
                 $memberinfo["id_card"]=$id_card;
                  $memberinfo["id_card_back"]=$id_card_back;
                  $memberinfo["bank_card"]=$bank_card;
                  $memberinfo["bank_card_back"]=$bank_card_back;
          }
        $this->assign('bank_county_code',$bank_county_code);
        $this->assign('countys',$countys);
        $this->assign('info',$memberinfo);
        $map["stat"]=1;
        $lists = model('MemberGroup')->select_all($map);
        $this->assign('lists',$lists);
        $this->assign('empty',"<option value='0'>没有可用的会员组，快去添加吧 </option>");
        $this->assign('action',$this->request->action());
        if($input["disable"]==1){
                 return view('member_view_disable');   
        }
        return view('member_view');
    }
    public  function detailAc($id=0){
        $data=$this->member->get_by_id($id);
            $group=$this->membergroup->get_by_id($data["membergroup_id"]);
            $bank=$this->member_area->get_by_member_id($data["id"]);
            $bank["province"]=$this->Area->get_name($bank["province"]);
            $bank["city"]=$this->Area->get_name($bank["city"]);
            $bank["county"]=$this->Area->get_name($bank["county"]);
            $data["bank"]=$bank;
            $data["group"]=$group;
            $chilren=$this->member->get_children($data["id"]);
           $map["id"]=["in",$chilren];
         $child=$this->member->select_all($map);
         foreach($child as $k=>$v){
             $group=$this->membergroup->get_by_id($v["membergroup_id"]);
             $child[$k]["group"]=$group;
         }
         $data["child"]=$child;
        $this->assign("data",$data);
        return $this->fetch();
    }
    public function delAc(){
        $id = $this->member->check('member_del');
        if($id){
            $ret = $this->admin_priv($id,$this->request->isAjax());
            if(!$ret) return message(0,'你没有操作权限');
        }else{
            var_dump('数据库还不完善，稍等');die();
        }
        $idObj = input('post.')['id'];
        $ids = '';
        if(count($idObj) > 1){
            $type = '批量';
        }else{
            $type= '';
        }
        foreach($idObj as $id){
            $ids .= $id.',';
        }
        
        
        
        $ids = trim($ids,',');
        $info = $this->member->where('id','in',$ids)->select();
        if(empty($info)){
            return message(0,'member is not found');
        }else{
            $message = '';
            foreach($info as $v){
                $message .= $v['uname'].',';
            }
            $message = trim($message,',');
            if($this->member->where('id','in',$ids)->delete()){
                $this->Bank->where('member_id','in',$ids)->delete();
                $this->Order->where('member_id','in',$ids)->delete();
                $this->Imgfile->where('member_id','in',$ids)->delete();
                $this->member_area->where('member_id','in',$ids)->delete();
                $this->Msg->where('to','in',$ids)->delete();
                admin_log($message,$type.$this->lang['delete'],$this->lang['member']);
                return message(1,'delete success');
            }else{
                return message(0,'delete fail');
            }
        }
    }
    private function get_params(){
        $params = [
            'uname'  =>  (string)input('post.username'),
            'upass'  =>  (string)input('post.password'),
            'phone'  =>  (string)input('post.phone'),
            'p_id'  =>  (int)input('post.pid'),
            'truename'  =>  (string)input('post.realname'),
            'idnum'  =>  (string)input('post.idnum'),
            'bankname'  =>  (string)input('post.bankname'),
            'banknum'  =>  (string)input('post.banknum'),
            'nickname'  =>  (string)input('post.nickname'),
            'membergroup_id'  =>  (int)input('post.group_id'),
            'sex'   =>  (int)input('post.sex'),
            'stat'  => (int)input('post.stat')
        ];
        return $params;
    }
    
    
    //更新状态
    public function update_self_statusAc($id="",$status){
        $input=input("post.");
        $rule=[
            "id"=>"require|number"
        ];
        $check=$this->validate($input, $validate);
        if($check!==true){
              return message(0,$check);
        }
       //批量更新

        $data["stat"]=$status;   
        if(is_array($input["id"])){
            
            $this->member->editByids($data,$input["id"]);
                        return message(1,"更新成功");
        }
        $res=$this->member->editById($data,$id);
        if($res["stat"]==1){
            admin_log($id,"更改会员状态",$this->lang['member']);
            return message(1,"更新成功");
        }
        return message(0,"更新失败");
        
    }
    //更新认证状态
    public function update_statusAc($id="",$status){
        $input=input("post.");
        $rule=[
            "id"=>"require|number"
        ];
        $check=$this->validate($input, $validate);
        if($check!==true){
              return message(0,$check);
        }
        if($status==6){
             //删除实名
            if(is_array($input["id"])){
               $map["id"]=array("in",$input["id"]); 
            }else{
               $map["id"]= $input["id"];
            }
            $map["is_rz_1"]=1;
            $meminfo=$this->member->get_count($map);
            if($meminfo){
             return message(0,"更新失败,用户认证已通过不能删除用户信息");   
            }
             $data["is_rz_1"]=0;
             $data["truename"]="";
             $data["idnum"]="";
             $data["bankname"]="";
             $data["banknum"]="";
         }else{
             $data["is_rz_1"]=$status;
         }
           $data["is_rz_1_content"]=$input["is_rz_1_content"];
           $data["is_rz_1_time"]=time();
       //批量更新
        if(is_array($input["id"])){

            $this->member->editByids($data,$input["id"]);
                        return message(1,"更新成功");
        }
        $res=$this->member->editById($data,$id);
        if($res["stat"]==1){
            $msg["type"]=6;
            $msg["to"]=$id;
            $msg["content"]=$status;
            model("Msg")->addItem($msg);
            admin_log($id,"审核信息".$id,$this->lang['member']);
            return message(1,"更新成功");
        }
        return message(0,"更新失败");
        
    }
    
    public function update_status_rz2Ac($id="",$status){
        $input=input("post.");
        $rule=[
            "id"=>"require|number"
        ];
        $check=$this->validate($input, $validate);
        if($check!==true){
              return message(0,$check);
        }
        if($status==9){
            if(is_array($input["id"])){
               $map["id"]=array("in",$input["id"]); 
            }else{
               $map["id"]= $input["id"];
            }
            $map["is_rz_2"]=1;
            $meminfo=$this->member->get_count($map);
            if($meminfo){
             return message(0,"更新失败,用户认证已通过不能删除用户信息");   
            }
            $data["is_rz_2"]=0;
            $data["rz_img"]="";
         }else{
             $data["is_rz_2"]=$status;
         }
           $data["is_rz_2_content"]=$input["is_rz_2_content"];
           $data["is_rz_2_time"]=time();
       //批量更新
        if(is_array($input["id"])){

            $this->member->editByids($data,$input["id"]);
                        return message(1,"更新成功");
        }

        $res=$this->member->editById($data,$id);
        if($res["stat"]==1){
            $msg["type"]=9;
            $msg["to"]=$id;
            $msg["content"]=$status;
            model("Msg")->addItem($msg);
            admin_log($id,"审核照片",$this->lang['member']);
            return message(1,"更新成功");
        }
        return message(0,"更新失败");
        
    }
    
    public function truenameAc(){
        
        return $this->fetch();
    }
    public function trueimgAc(){
        
        return $this->fetch();
    } 
    public function del_truenameAc(){
            return message(1,"删除成功");
    }
}
?>
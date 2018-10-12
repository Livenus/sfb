<?php
namespace app\admin\controller;
use think\Config;
/**
 * 定时任务开启或关闭
 * 
 *
 */
class StartTask extends \app\admin\controller\Home {
	public function _initialize(){
        parent::_initialize();
        $this->task=model("task");
    }
	public function indexAc(){
    
      return $this->fetch();
    }
	public function editAc(){
        
      return $this->fetch();
    }
	
	//定时任务列表
    public  function listajaxAc(){
        $req=input("get.");
        $task = include(APP_PATH.'/task.php');
		$task = $task['0']['task'];
		$task[0]['re']=$task[1]['re']=$task[2]['re']='否';
		$task[3]['re']='是';
		$task[3]['name']='会员返现任务';
		
      echo json_encode(array('rows'=>$task));
        
    }
	//保存定时任务参数配置
	public function saveAc(){
		
       $input = input("post.");
	   $frequent = $input['frequent'];
	   $start = $input['start'];
	   if($start){
		   $start='已开启';
	   }else{
		   $start='已关闭';
	   }
	   $task=array([
				"task"=>[
						[
						"name"=>"order",
						"frequent"=>"3600"
						],
						[
						"name"=>"autoPay",
						"frequent"=>"300"
						],
						[
						"name"=>"autoinsteadPay",
						"frequent"=>"300"
						],
						[
						"name"=>"autoBonus",
						"frequent"=>"$frequent",
						"start"=>"$start"
						]
        
				]
			]);

		$content  = "<?php\nreturn ".var_export($task,true).";\n?>";
		file_put_contents('../application_yyj/task.php',$content);
		echo json_encode(['stat'=>1]);
    }
}
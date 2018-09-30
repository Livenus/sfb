<?php 
/**
 * 文章模型
 */
namespace app\common\model;


class Article extends \app\common\model\Home{
    protected $auto = [];
    protected $insert=['addtime', 'sort'=>50];
    protected $update=[];
    
    protected function setAddtimeAttr(){
        return time();
    }
    protected function getAddtimeAttr($value){
        return date("Y-m-d H:i:s",$value);
    }
    public function getArticle(){
        //$params = ['stat'=>1];
        $params = [];
        $article = \think\Db::name('article')->where($params)->order('sort Asc')->select();
       
        foreach($article as $key => &$value){
            if(!empty($value['url'])){
                $value['url'] = url($value['url']);
            }else{
                $value['url'] = '#';
            }
         
        }
        return $article;
    }
    public function delById($id){
        if(is_array($id)){
            try{

                $this -> where($this->getPk(),array('IN',$id))-> delete();
            }catch( \Exception $e){
                return $this->err($e->getMessage());
            }
        }else{
            try{
                $this -> where($this->getPk(),$id)-> delete();
            }catch( \Exception $e){
                return $this->err($e->getMessage());
            }
        }
        
        return $this->suc('删除成功');  
    }
    public function searchTitle($searc,$type){
    	//var_dump($searc);
    	if(($type=='-1') && ($searc!='all')){
        	$article = \think\Db::name('article')->where('title','like','%'.trim($searc).'%')->order('sort Asc')->select();
       	}elseif(($type!='-1') && ($searc!='all')){
       		$article = \think\Db::name('article')->where('title','like','%'.trim($searc).'%')->where('type','=',$type)->order('sort Asc')->select();	
       	}else{
       		$article = \think\Db::name('article')->where('type','=',$type)->order('sort Asc')->select();
       	}
        foreach($article as $key => &$value){
            if(!empty($value['url'])){
                $value['url'] = url($value['url']);
            }else{
                $value['url'] = '#';
            }
         
        }
        return $article;
    }
    public function select_all($map=[],$order="id desc",$limit=""){
        $data=$this->where($map)->field(['id','title','type','addtime','stat','des'])->order($order)->limit($limit)->select();
        return collection($data)->toArray();
    }
    public function get_count($map){
        return $this->where($map)->count();
    }
    public function table_info(){
        $sql="SELECT a.COLUMN_NAME as name, a.COLUMN_COMMENT as comment
FROM information_schema.COLUMNS a 
WHERE a.TABLE_NAME = 'yys_article'";
        $data=$this->query($sql);
        $result=array();
        foreach($data as $v){
            $result[$v['name']]=$v["comment"];
        }
        return $result;
    }
    public  function get_by_id($id){
        $map["id"]=$id;
        $data=$this->where($map)->field(true)->find();
        if($data){
             return $data->toArray();
        }else{
            return false;
        }
       
    }
    public  function del_by_id($id){
        
        return $this->where(array("id"=>$id))->delete();
    }
    public  function del_all($ids){
        
        return $this->where("id","in",$ids)->delete();
    }
}
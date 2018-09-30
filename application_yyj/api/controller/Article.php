<?php
namespace app\api\controller;
use think\Config;
use think\Response;
class Article  extends \app\api\controller\Home {
        public function _initialize(){
        $this->Arcitle = model("Article");
        $this->get_user_id();
        }

    //获取文章列表
    public  function get_article_listAc($type="",$order="id desc",$limit="0,10"){
        if($type){
            $map["type"]=$type;
        }
        $data=$this->Arcitle->select_all($map,$order,$limit);
        $info=$this->Arcitle->table_info();
        if($data){
             $reponse["list"]=$data;
             $reponse["count"]=$this->Arcitle->get_count($map);
             $reponse["table_info"]=$info;
              return $this->suc($reponse);
        }else{
              return $this->err('900','没有数据');
        }
    }
    //获取文章详情
    public  function get_articleAc($id=""){
        $rule=[
            "id"=>"require|number"
        ];
        $check=$this->validate($_POST, $rule);
        if($check!==true){
            
            return $this->err(9000, $check);
        }
        if($type){
            $map["type"]=$type;
        }
        $data=$this->Arcitle->get_by_id($id);
         $data["content"]=html_entity_decode($data["content"]);
        if($data){
              return $this->suc($data);
        }else{
              return $this->err('900','没有数据');
        }
    }
    public  function get_article21Ac($id=""){
        $rule=[
            "id"=>"require|number"
        ];
        $check=$this->validate($_POST, $rule);
        if($check!==true){
            
            return $this->err(9000, $check);
        }
        if($type){
            $map["type"]=$type;
        }
        $data=$this->Arcitle->get_by_id($id);
        $data["content"]=strip_tags($data["content"]);
        preg_match_all("/【(.*?)】([^【]{2,})/u", $data["content"],$match);
        if($match){
              return $this->suc($match);
        }else{
              return $this->err('900','没有数据');
        }
    }
}

<?php
namespace app\index\controller;

class Index extends \app\index\controller\Home
{
    
    private $a;
    
    public function _initialize(){
        // $this->a = new \ext\Kuaijiezhifu();
        
        // $this->a->setAppid('10058784')->setKey('45d20cccf34a4e50a984988295ad3ab2')->setNotifyUrl('http://www.domain.com/aaa');
        
    }
    
    public function indexAc()
    {
        return 'hello !  <a href="/admin.php">admin</a>';
    }
    
    public function testAc(){
        $a = new \ext\Kuaijiezhifu();
        $check["payeeAcc"]='60014534578465';
        $check["payerIdNum"]='14532';
        $check["payerName"]='zss';
        $check["merOrderId"]="yz".date("YmdHis");
        $r = $a ->bindCard($check);
        return 'ok' . json_encode($r);
    }
    
    /**
     * 
     * @return unknown[]|number[]|string[]
     */
    public function debitAc(){
        
        $a = new \ext\Kuaijiezhifu();
        
        $a->setAppid('10058784')->setKey('45d20cccf34a4e50a984988295ad3ab2')->setNotifyUrl('http://www.domain.com/aaa');
        
//        $a->setKey('192006250b4c09247ec02edce69f6a2d');
        
//        $r = $a->bindCard('622007789012345679', '张三', '230882199008183916','13211115555','610000','610100','地址');
//        $r = $a->bindCard('622007789012345677', '张三', '230882199008183916','13211115555','610000','610100','地址'); //11876879
//        $r = $a->bindCard('622007789012345676', '张三', '230882199008183916','13211115555','610000','610100','地址'); //11875509
//         $r = $a->bindCard('622007789012345675', '张三', '230882199008183916','13211115555','610000','610100','地址'); //11875509
//         $r = $a->bindCard('6217233700002161121', '张青华', '230882199008183916','13399188031','610000','610100','西安大兴西路分理处'); //11877605
//          dump($r);
//          die;
  
        
        //修改卡
//         $r = $a->modifyBind([
//             'card' => '6217233700002161121',
//             'name' => '张青华',
//             'idcard'=>'610404198604064038',
//             'mobile'=>'13399188031',
//             'cityCode'=>'610100',
//             'provinceCode'=>'610000',
//             'address'   =>'西安大兴西路分理处',
//             'mchId'     => '11877605'
//         ],'card');
        
//         dump($r);die;
        
        $r = $a->bindCard([
            'card' => '6217233700002161121',
            'name' => '张青华',
            'idcard'=>'610404198604064038',
            'mobile'=>'13399188031',
            'cityCode'=>'610100',
            'provinceCode'=>'610000',
            'address'   =>'西安大兴西路分理处'
        ]);
        dump($r);die;
        
        $r = $a->order([
            'card' => '6217233700002161121',
            'name' => '张青华',
            'idcard'=>'610404198604064038',
            'mobile'=>'13399188031',
            'totalFee'=>'10000',
            'agentOrderNo'=>'20171214002',
            'mchId' => '11877605'
           
        ]);
        dump($r);die;
        
    }
    
    public function bindcardAc(){
        
        $param = array(
            'name'         => input('post.name'),
            'mobile'       => input('post.mobile'),
            'idcard'       => input('post.idcard'),
            'card'         => input('post.card'),
            'provinceCode' => input('post.provinceCode'),
            'cityCode'     => input('post.cityCode'),
            'address'      => input('post.address'),
            'fee0'         => input('post.fee0'),
            'd0fee'        => (int)(input('post.d0fee') * 100),
            'pointsType'   => 0
            
        );
//        dump($param);
        $r = $this->a->bindCard($param);
        if($r['stat'] == '1'){
            return $this->suc($r['data']);
        }
        //商户号：
        preg_match_all("/\\(([^\\)]*)\\)/", $r['errmsg'], $matches);
        $mchid = $matches[1][0];
        return $this->err($r['errcode'], $r['errmsg'], $r['data']?$r['data']:$mchid);
    }
    
    public function modifyfeeAc(){
        $param = array(
            'mchId'         => input('post.mchId'),
            'fee0'          => input('post.fee0'),
            'd0fee'         => (int)(input('post.d0fee') * 100)
        );
        $r = $this->a->modifyBind($param,'rate');
        if($r['stat'] == '1'){
            return $this->suc($r['data']);
        }
        return $this->err($r['errcode'], $r['errmsg']);
    }
    public function orderAc(){
        $param= array(
            'name'     => input('post.name'),
            'mobile'   => input('post.mobile'),
            'idcard'   => input('post.idcard'),
            'card'     => input('post.card'),
            'mchId'    => input('post.mchId'),
            'totalFee' => (int)(input('post.totalFee') * 100),
            'agentOrderNo' => 't201801040001' //交易单号
        );
        
        
        $r = $this->a->order($param);
        if($r['stat'] == '1'){
            return $this->suc($r['data']);
        }
        return $this->err($r['errcode'], $r['errmsg']);
    }
    
}

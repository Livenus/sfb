<?php
namespace app\index\controller;

use think\Response;

class Home extends \think\Controller
{

    public function suc($data)
    {
        return Response::create([
            'stat' => 1,
            'data' => $data
        ], 'json')->code(200);
    }

    public function err($errcode, $errmsg, $data)
    {
        return Response::create([
            'stat' => 0,
            'errcode' => $errcode,
            'data' => $data,
            'msg' => $errmsg
        ], 'json')->code(200);
    }
}

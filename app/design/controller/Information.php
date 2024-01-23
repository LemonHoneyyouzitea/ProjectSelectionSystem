<?php

namespace app\design\controller;

use think\facade\Db;
use think\facade\View;

class Information{
    public function index()
    {
        $where = array();
        $id=get_login_admin('id');
        $data = Db::name('Admin')->where(['id'=>$id])->find();
        /*$data['selection_time'] = date('Y-m-d H:i:s', $data['selection_time']);*/
        View::assign('data', $data);
        return view();
    }
    public function data()
    {
        $param = get_params();
        $where = array();
        $id=get_login_admin('id');
        $data = Db::name('Admin')->where(['id'=>$id])->find();
        return to_assign(0, '', $data);
    }
    public function save()
    {
        $param = get_params();
        //var_dump($param);
        $id=get_login_admin('id');
        Db::name('Admin')->where(['id'=>$id])->strict(false)->update($param);  //strict此处可以不加，详见https://doc.thinkphp.cn/v8_0/strict.html
        return to_assign();
    }
}

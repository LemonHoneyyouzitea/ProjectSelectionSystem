<?php

namespace app\design\controller;

use think\facade\Db;
use think\facade\View;

class Parameter{
    public function index()
    {
        $where = array();
        $where=['selection_time'!=null];
        $data = Db::name('sp_parameter')->where($where)->find();
        $data['selection_time'] = date('Y-m-d H:i:s', $data['selection_time']);
        View::assign('data', $data);
        return view();
    }
    public function data()
    {
        $param = get_params();
        $where = array();
        $data = Db::name('sp_parameter')->where($where)->find();
        foreach ($data as $k => $v) {
            $item = $v;
            $item['selection_time'] = empty($item['selection_time']) ? '-' : date('Y-m-d H:i', $item['selection_time']);
            $data->offsetSet($k, $item);
        }
        return to_assign(0, '', $data);
    }
    public function save()
    {
        $param = get_params();
        //var_dump($param);
        $param['selection_time'] = strtotime($param['selection_time']); //日期转换为时间戳
        $max_student=$param['max_students'];
        Db::name('sp_parameter')->where('parameter_id', $param['parameter_id'])->strict(false)->update($param);  //strict此处可以不加，详见https://doc.thinkphp.cn/v8_0/strict.html
        return to_assign();
    }
}

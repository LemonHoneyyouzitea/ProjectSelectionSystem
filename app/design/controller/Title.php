<?php

namespace app\design\controller;

use think\facade\Db;
use think\facade\View;

class Title
{
    public function index()
    {
        return view();
    }

    public function data()
    {
        $param = get_params();
        $where = array();
        if (!empty($param['keywords'])) {
            $where[] = ['title_name', 'like', '%' . $param['keywords'] . '%'];
        }
        $data = Db::name('sp_title')->where($where)->select();
        foreach ($data as $k => $v) {
            $item = $v;
            $item['create_time'] = empty($item['create_time']) ? '-' : date('Y-m-d H:i', $item['create_time']);
            $data->offsetSet($k, $item);
        }
        return to_assign(0, '', $data);
    }

    public function add()
    {
        return view();
    }

    public function edit()
    {
        $id = get_params("id");
        $data = Db::name('sp_title')->where(['title_id' => $id])->find();
        View::assign('data', $data);
        return view();
    }

    public function save()
    {
        $param = get_params();
        if (!empty($param['title_id']) && $param['title_id'] > 0) {  //当前操作是编辑保存
            $where = array();
            $where[] = ['title_name', '=', $param['title_name']];
            $where[] = ['title_id', '<>', $param['title_id']];
            $count = Db::name('sp_title')->where($where)->count();
            if ($count > 0)
                return to_assign(1, '操作失败，原因：该职称已经存在');
            Db::name('sp_title')->where('title_id', $param['title_id'])->strict(false)->update($param);  //strict此处可以不加，详见https://doc.thinkphp.cn/v8_0/strict.html
            return to_assign();
        } else {   //当前操作是新建保存
            $count = Db::name('sp_title')->where('title_name', '=', $param['title_name'])->count();
            if ($count > 0)
                return to_assign(1, '操作失败，原因：该职称已经存在');

            $param['create_time'] = time();
            Db::name('sp_title')->insert($param);
            return to_assign();
        }
    }

    //删除
    public function delete()
    {
        $param = get_params();
        $where = [
            ['title_id', '=', $param['title_id']]
            //,['type_id', '<>', $param['type_id']]
        ];
        $count = Db::name('Admin')->where($where)->count();
        if ($count > 0) {
            return to_assign(1, "该职称下还有老师，无法删除");
        }
        if (Db::name('sp_title')->delete($param['title_id']) !== false) {
            return to_assign(0, "删除职称成功");
        } else {
            return to_assign(1, "删除失败");
        }
    }
}
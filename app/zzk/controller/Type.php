<?php

namespace app\zzk\controller;

use think\facade\Db;
use think\facade\View;

class Type
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
            $where[] = ['type_name', 'like', '%' . $param['keywords'] . '%'];
        }
        $data = Db::name('med_type')->where($where)->select();

        /*$data = Db::name('book_type')
            ->field('cms_book_type.*,(select count(*) from cms_book where cms_book.type_id=cms_book_type.id) as num')
            ->where($where)->select();*/
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
        $data = Db::name('med_type')->where(['type_id' => $id])->find();
        View::assign('data', $data);
        return view();
    }

    public function save()
    {
        $param = get_params();
        if (!empty($param['type_id']) && $param['type_id'] > 0) {  //当前操作是编辑保存
            $where = array();
            $where[] = ['type_name', '=', $param['type_name']];
            $where[] = ['type_id', '<>', $param['type_id']];
            $count = Db::name('med_type')->where($where)->count();
            if ($count > 0)
                return to_assign(1, '操作失败，原因：该类别已经存在');
            Db::name('med_type')->where('type_id', $param['type_id'])->strict(false)->update($param);  //strict此处可以不加，详见https://doc.thinkphp.cn/v8_0/strict.html
            return to_assign();
        } else {   //当前操作是新建保存
            $count = Db::name('med_type')->where('type_name', '=', $param['type_name'])->count();
            if ($count > 0)
                return to_assign(1, '操作失败，原因：该类别已经存在');

            /*$type_name = Db::name('BookType')->where(['type_name' => $param['username']])->find();
            if (empty($type_name))
                return to_assign(1, '操作失败，原因：该类别已经存在');*/

            $param['create_time'] = time();
            Db::name('med_type')->insert($param);
            return to_assign();
        }
    }

    //删除
    public function delete()
    {
        $param = get_params();
        $where = [
            ['type_id', '=', $param['type_id']]
            //,['type_id', '<>', $param['type_id']]
        ];
        $count = Db::name('med_equipment')->where($where)->count();
        if ($count > 0) {
            return to_assign(1, "该类别下还有设备，无法删除");
        }
        if (Db::name('med_type')->delete($param['type_id']) !== false) {
            return to_assign(0, "删除类别成功");
        } else {
            return to_assign(1, "删除失败");
        }
    }
}
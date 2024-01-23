<?php

namespace app\design\controller;

use think\facade\Db;
use think\facade\View;

class Education
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
            $where[] = ['education_name', 'like', '%' . $param['keywords'] . '%'];
        }
        $data = Db::name('sp_education')->where($where)->select();

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
        $data = Db::name('sp_education')->where(['education_id' => $id])->find();
        View::assign('data', $data);
        return view();
    }
    public function save()
    {
        $param = get_params();
        if (!empty($param['education_id']) && $param['education_id'] > 0) {  //当前操作是编辑保存
            $where = array();
            $where[] = ['education_name', '=', $param['education_name']];
            $where[] = ['education_id', '<>', $param['education_id']];
            $count = Db::name('sp_education')->where($where)->count();
            if ($count > 0)
                return to_assign(1, '操作失败，原因：该学历已经存在');
            Db::name('sp_education')->where('education_id', $param['education_id'])->strict(false)->update($param);  //strict此处可以不加，详见https://doc.thinkphp.cn/v8_0/strict.html
            return to_assign();
        } else {   //当前操作是新建保存
            $count = Db::name('sp_education')->where('education_name', '=', $param['education_name'])->count();
            if ($count > 0)
                return to_assign(1, '操作失败，原因：该学历已经存在');
            $param['create_time'] = time();
            Db::name('sp_education')->insert($param);
            return to_assign();
        }
    }

    //删除
    public function delete()
    {
        $param = get_params();
        $where = [
            ['education_id', '=', $param['education_id']]
        ];
        $count = Db::name('Admin')->where($where)->count();
        if ($count > 0) {
            return to_assign(1, "该学历下还有学生，无法删除");
        }
        if (Db::name('sp_education')->delete($param['education_id']) !== false) {
            return to_assign(0, "删除学历成功");
        } else {
            return to_assign(1, "删除失败");
        }
    }
}
<?php

namespace app\design\controller;

use http\Params;
use think\facade\Db;
use think\facade\View;

class Projects
{
    public function index()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $wheres = array();
            $where = array();
            if (!empty($param['keywords'])) {
                $wheres[] = ['b.project_name', 'like', '%' . $param['keywords'] . '%'];
                $wheres[] = ['d.nickname', 'like', '%' . $param['keywords'] . '%'];
            }
            if (!empty($param['type_id'])) {
                $where[] = ['b.type_id', '=', $param['type_id']];
            }
            $where[]=['b.status','=','1'];
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $content = Db::name('sp_projects')->alias('b')
                ->whereOr($wheres)
                ->where($where)
                ->field('b.*,c.type_name,d.nickname')
                ->join('sp_type c', 'b.type_id = c.type_id')
                ->join('Admin d','d.id = b.teacher_id')
                ->order('project_time asc')
                ->paginate($rows);
            return table_assign(0, '', $content);
        } else {
            $type = Db::name('sp_type')->select();
            View::assign('sp_type', $type); //要加上 use think\facade\View;拷贝时会有提示
            return view();
        }
    }
}
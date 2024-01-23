<?php

namespace app\design\controller;

use http\Params;
use think\facade\Db;
use think\facade\View;

class Resubmit
{
    public function index()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = array();
            if (!empty($param['keywords'])) {
                $where[] = ['b.project_name', 'like', '%' . $param['keywords'] . '%'];
            }
            if (!empty($param['type_id'])) {
                $where[] = ['b.type_id', '=', $param['type_id']];
            }
            $uid=get_login_admin('id');
            $where[]=['d.id','=',$uid];
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $content = Db::name('sp_projects')->alias('b')
                ->where($where)
                ->field('b.*,c.type_name,d.nickname')
                ->join('sp_type c', 'b.type_id = c.type_id')
                ->join('Admin d','d.id = b.teacher_id')
                ->order('project_time asc')
                ->paginate($rows);
            return table_assign(0, '', $content);
        } else {
            $type = Db::name('sp_type')->select();
            View::assign('sp_type', $type);
            return view();
        }
    }


    public function history()
    {
        if (request()->isAjax()) {
            $params=get_params();
            $project_id=$params['project_id'];
            $count = Db::name('sp_audit')->where(['project_id' => $project_id])->count();
            if ($count <= 0){
                return to_assign(1, "该题目还没有审核记录");
            }else{
                $data=Db::name('sp_audit')->alias('b')
                    ->where(['project_id' => $project_id])
                    ->field('b.*,c.nickname')
                    ->join('Admin c', 'b.admin_id = c.id')
                    ->order('b.create_time asc')
                    ->select();
                foreach ($data as $k => $v) {
                    $item = $v;
                    $item['create_time'] = empty($item['create_time']) ? '-' : date('Y-m-d H:i', $item['create_time']);
                    $data->offsetSet($k, $item);
                }
                return to_assign(0, '', $data);
            }
        }else{
            $project_id = get_params("project_id"); // 获取部门id
            View::assign('project_id', $project_id);
            return view();
        }
    }
    public function edit()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        $data = Db::name('sp_projects')->where(['project_id' => $id])->find();
        View::assign('data', $data);
        $sp_type = Db::name('sp_type')->select();
        View::assign('sp_type', $sp_type);
        return view();
    }

    public function resave()
    {
        $param = get_params();
        $param['status'] = 0;
        $time=time();
        $param['create_time']=$time;
        Db::name('sp_projects')->where('project_id', $param['project_id'])->strict(false)->update($param);  //strict此处可以不加，详见https://doc.thinkphp.cn/v8_0/strict.html

        return to_assign();
    }

    public function add()
    {
        View::assign('sp_type', Db::name('sp_type')->select());
        return view();
    }
    public function save()
    {
        $param = get_params();

        if (!empty($param['project_id']) && $param['project_id'] > 0) {
            Db::name('sp_projects')->where('project_id', $param['project_id'])->strict(false)->update($param);  //strict此处可以不加，详见https://doc.thinkphp.cn/v8_0/strict.html
            return to_assign();
        } else {
            $count = Db::name('sp_projects')->where('project_name', '=', $param['project_name'])->count();
            if ($count > 0)
                return to_assign(1, '操作失败，原因：该题目已经存在');
            $t_id=get_login_admin('id');
            $param['teacher_id']=$t_id;
            $param['project_time'] = time();
            Db::name('sp_projects')->strict(false)->insert($param);
            return to_assign(0, "操作成功！");
        }
    }

    public function del()
    {
        $project_id = get_params('project_id');

        if (Db::name('sp_projects')->delete($project_id) !== false) {
            return to_assign(0, "删除题目成功");
        } else {
            return to_assign(1, "删除失败");
        }
    }
    public function access()
    {
        $teacher_id=get_login_admin('id');
        $now_student=Db::name('sp_select')->alias('b')
            ->where('select_status','=','2')
            ->where('c.teacher_id','=',$teacher_id)
            ->join('sp_projects c','c.project_id=b.project_id')
            ->count();
        $max_students=Db::name('sp_parameter')
            ->where('parameter_id','=',1)->value('max_students');
        if ($now_student>=$max_students){
            return to_assign(0,"您带的学生已达上限，操作失败！");
        }else{
            $project_id=get_params('project_id');
            $select_id=get_params('select_id');
            //var_dump($project_id);
            $stu_id=DB::name('sp_select')->where(['select_id'=>$select_id])->value('stu_id');
            //var_dump($stu_id);
            DB::name('sp_select')
                ->where('stu_id', $stu_id)
                ->where('select_id', '<>', $select_id)
                ->update(['select_status' => 0]);
            $time=time();
            $where=[];
            $where[]=['select_id','=',$select_id];
            //同意该同学
            Db::name('sp_select')
                ->where($where)
                ->update(['accept_time' => $time,'select_status'=>2]);
            //拒绝其他同学
            Db::name('sp_select')
                ->where('select_id', '<>', $select_id)
                ->where('project_id', '=', $project_id)
                ->update(['accept_time' => $time,'select_status' => -1]);
            return to_assign(0,"同意成功！");
        }

    }
    public function refuse()
    {
        $time=time();
        $select_id=get_params('select_id');
        Db::name('sp_select')
            ->where('select_id', '=', $select_id)
            ->update(['accept_time' => $time,'select_status' => -1]);
        return to_assign(0,"拒绝成功！");
    }
    public function read()
    {
        if (request()->isAjax()) {
            $params=get_params();
            $project_id=$params['project_id'];
            $count = Db::name('sp_select')->where(['project_id' => $project_id])->count();
            if ($count <= 0){
                return to_assign(1, "该题目还没有学生选择");
            }else{
                $data=Db::name('sp_select')->alias('b')
                    ->where(['project_id' => $project_id])
                    ->where('b.select_status','<>',0)
                    ->field('b.*,c.nickname,d.education_name')
                    ->join('Admin c', 'b.stu_id = c.id')
                    ->join('sp_education d','d.education_id=c.education_id')
                    ->order('b.create_time asc')
                    ->select();
                foreach ($data as $k => $v) {
                    $item = $v;
                    $item['create_time'] = empty($item['create_time']) ? '-' : date('Y-m-d H:i', $item['create_time']);
                    $data->offsetSet($k, $item);
                }
                return to_assign(0, '', $data);
            }
        }else{
            $project_id = get_params("project_id");
            View::assign('project_id', $project_id);
            return view();
        }
    }
}
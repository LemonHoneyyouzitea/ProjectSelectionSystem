<?php

namespace app\design\controller;

use http\Params;
use think\facade\Db;
use think\facade\View;

class Mychoose
{
    public function index()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = array();
            $wheres = array();
            if (!empty($param['keywords'])) {
                $wheres[] = ['c.project_name', 'like', '%' . $param['keywords'] . '%'];
                $wheres[] = ['f.nickname', 'like', '%' . $param['keywords'] . '%'];

            }
            if (!empty($param['type_id'])) {
                $where[] = ['b.type_id', '=', $param['type_id']];
            }
            $stu_id=get_login_admin('id');
            $where[]=['d.id','=',$stu_id];
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $content = Db::name('sp_select')->alias('b')
                ->whereOr($wheres)
                ->where($where)
                ->field('b.*,c.*,e.type_name,f.nickname')
                ->join('sp_projects c', 'b.project_id = c.project_id')
                ->join('Admin d','d.id = b.stu_id')
                ->join('Admin f','f.id = c.teacher_id')
                ->join('sp_type e','e.type_id=c.type_id')
                ->order('b.create_time asc')
                ->paginate($rows);
            foreach ($content as $k => $v) {
                $item = $v;
                $item['create_time'] = empty($item['create_time']) ? '-' : date('Y-m-d H:i', $item['create_time']);
                $item['accept_time'] = empty($item['accept_time']) ? '-' : date('Y-m-d H:i', $item['accept_time']);

                $content->offsetSet($k, $item);
            }
            return table_assign(0, '', $content);
        } else {
            $type = Db::name('sp_type')->select();
            View::assign('sp_type', $type); //要加上 use think\facade\View;拷贝时会有提示
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

    //退选操作
    public function cancel()
    {
        $select_id = get_params('select_id');  //获得前台传来的数据，内容为[1,3,7]
        Db::name('sp_select')->where('select_id', $select_id)->strict(false)->update(['select_status' => '0']);
        return to_assign(0, "退选成功");
    }
    public function resave()
    {
        $param = get_params();
        $param['status'] = 0;
        Db::name('sp_projects')->where('project_id', $param['project_id'])->strict(false)->update($param);
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

        if (!empty($param['equipment_id']) && $param['equipment_id'] > 0) {
            Db::name('med_equipment')->where('equipment_id', $param['equipment_id'])->strict(false)->update($param);  //strict此处可以不加，详见https://doc.thinkphp.cn/v8_0/strict.html
            return to_assign();
        } else {
            $t_id=get_login_admin('id');
            $param['teacher_id']=$t_id;
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

    public function show()
    {
        $param = get_params();
        $select_id=$param['id'];
        $project_id=Db::name('sp_select')->where(['select_id'=>$select_id])->value('project_id');
        //var_dump($project_id);
        $data = Db::name('sp_projects')->where(['project_id' => $project_id])->find();
        View::assign('data', $data);
        $teacher_id=Db::name('sp_projects')->where(['project_id' => $project_id])->value('teacher_id');
        $teacher=Db::name('Admin')->where(['id' => $teacher_id])->find();
        View::assign('teacher', $teacher);
        //var_dump($teacher);
        $type_id = Db::name('sp_projects')->where(['project_id' => $project_id])->value('type_id');
        $type =Db::name('sp_type')->where(['type_id' => $type_id])->find();
        View::assign('type', $type);
        $cc = Db::name('sp_select')->where(['project_id' => $project_id])->count();
        if ($cc>0){
            $count = Db::name('sp_select')->where(['project_id' => $project_id,'select_status'=>2])->count();
            if ($count>0){
                $select=Db::name('sp_select')->alias('b')
                    ->where('select_id','=',$select_id)
                    ->field('c.nickname,b.*')
                    ->join('Admin c','c.id=b.stu_id')
                    ->find();
            }else{
                $select=Db::name('sp_select')->alias('b')
                    ->where('select_id','=',$select_id)
                    ->field('c.nickname,b.*')
                    ->join('Admin c','c.id=b.stu_id')
                    ->find();
            }
        }else{
            $select['select_status']=0;
        }
        View::assign('select', $select);
        return view();

    }
}
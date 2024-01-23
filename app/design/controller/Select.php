<?php

namespace app\design\controller;

use http\Params;
use think\facade\Db;
use think\facade\View;

class Select
{
    public function index()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = array();
            $wheres = array();
            if (!empty($param['keywords'])) {
                $wheres[] = ['b.project_name', 'like', '%' . $param['keywords'] . '%'];
                $wheres[] = ['d.nickname', 'like', '%' . $param['keywords'] . '%'];
            }
            if (!empty($param['type_id'])) {
                $where[] = ['b.type_id', '=', $param['type_id']];
            }
            $student_id=get_login_admin('id');
            $where[]=['b.status','=','1'];
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];

            $content = Db::name('sp_projects')->alias('b')
                ->whereOr($wheres)
                ->where($where)
                ->field('b.*,c.type_name,d.nickname')
                ->join('sp_type c', 'b.type_id = c.type_id')
                ->join('Admin d','d.id = b.teacher_id')
                ->order('type_id asc')
                ->paginate($rows);
            $modifiedData = array(); // 新建一个空数组用于存储修改后的数据

            foreach ($content as $item) {
                $now_id=get_login_admin('id');
                $count=Db::name('sp_select')
                    ->where('project_id','=',$item['project_id'])
                    ->where('select_status','=','2')->count();
                $waiting_count=Db::name('sp_select')
                    ->where('project_id','=',$item['project_id'])
                    ->where('stu_id','=',$now_id)
                    ->where('select_status','=','1')->count();
                if ($count>0){
                    $item['select_status'] = 2; // 添加字段并赋值
                    $modifiedData[] = $item; // 将修改后的数据添加到数组中
                }else if ($waiting_count>0){
                    $item['select_status'] = 1; // 添加字段并赋值
                    $modifiedData[] = $item; // 将修改后的数据添加到数组中
                } else{
                    $item['select_status'] = 0; // 添加字段并赋值
                    $modifiedData[] = $item; // 将修改后的数据添加到数组中
                }
            }
            return to_assign(0, '', $modifiedData);
            //return table_assign(0, '', $content);
        } else {
            $type = Db::name('sp_type')->select();
            View::assign('sp_type', $type); //要加上 use think\facade\View;拷贝时会有提示
            return view();
        }
    }
    public function select()
    {
        $now_time=time();//当前时间
        $param = get_params();
        $param_time=Db::name('sp_parameter')->where('parameter_id','=','1')->value('selection_time');
        if ($now_time<$param_time){
            $project_id=$param['project_id'];
            $teacher_id=Db::name('sp_projects')
                ->where('project_id','=',$project_id)
                ->value('teacher_id');
            //var_dump($teacher_id);
            $stu_id=get_login_admin('id');
            $already_accept=Db::name('sp_select')->where('stu_id', '=', $stu_id)->where('select_status','=','2')->count();
            if ($already_accept==0){
                $max_students=Db::name('sp_parameter')->where('parameter_id','=','1')->value('max_students');
                $now_students=Db::name('sp_projects')->alias('b')
                    ->where('b.teacher_id','=',$teacher_id)
                    ->where('c.select_status','=','1')
                    ->join('sp_select c','c.project_id=b.project_id')
                    ->count();
                if ($max_students>$now_students){

                    $stu_id=get_login_admin('id');
                    //该学生的选题数量
                    $count = Db::name('sp_select')->where('stu_id', '=', $stu_id)->where('select_status','>','0')->count();
                    $max_projects=Db::name('sp_parameter')->where('parameter_id','=','1')->value('max_projects');
                    //var_dump($count);
                    if ($count<$max_projects){
                        $time=time();
                        $data = ['project_id' => $project_id, 'stu_id' => $stu_id,
                            'create_time' => $time,
                            'select_status' => 1,
                        ];
                        Db::name('sp_select')->insert($data);
                        return to_assign('0',"选题成功！");
                    }else{
                        return to_assign('0',"你选的题目数量已达上限！");
                    }
                }else{
                    return to_assign('0',"该老师指导的学生已达上限！");
                }
            }else{
                return to_assign('0',"你的题目已经选定，无法再选择！");
            }
        }else{
            return to_assign('0',"已到选题截止时间，无法再选择！");
        }
    }
    public function show()
    {
        $param = get_params();
        $project_id=$param['id'];
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
                    ->where(['project_id' => $project_id,'select_status'=>2])
                    ->field('c.nickname,b.*')
                    ->join('Admin c','c.id=b.stu_id')
                    ->find();
            }else{
                $select=Db::name('sp_select')->alias('b')
                    ->where(['project_id' => $project_id])
                    ->field('c.nickname,b.*')
                    ->join('Admin c','c.id=b.stu_id')
                    ->find();
            }
        }else{
            $select['select_status']=0;
        }
        //var_dump($select);
        View::assign('select', $select);
        return view();
    }
}
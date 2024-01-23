<?php

namespace app\design\controller;

use app\admin\validate\AdminCheck;
use http\Params;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class Audit
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
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $content = Db::name('sp_projects')->alias('b')
                ->whereOr($wheres)
                ->where($where)
                ->field('b.*,c.type_name,d.nickname')
                ->join('sp_type c', 'b.type_id = c.type_id')
                ->join('Admin d','d.id = b.teacher_id')
                ->order('project_id asc')
                ->paginate($rows);
            foreach ($content as $k => $v) {
                $item = $v;
                $item['project_time'] = empty($item['project_time']) ? '-' : date('Y-m-d H:i', $item['project_time']);
                $content->offsetSet($k, $item);
            }
            return table_assign(0, '', $content);
        } else {
            $type = Db::name('sp_type')->select();
            View::assign('sp_type', $type); //要加上 use think\facade\View;拷贝时会有提示
            return view();
        }
    }
    //修改密码
    public function edit_password()
    {
        return view('admin/edit_password', [
            'admin' => get_login_admin(),
        ]);
    }
    //保存密码修改
    public function password_submit()
    {
        if (request()->isAjax()) {
            $param = get_params();
            try {
                validate(AdminCheck::class)->scene('editpwd')->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $admin = get_login_admin();
            if (set_password($param['old_pwd'], $admin['salt']) !== $admin['pwd']) {
                return to_assign(1, '旧密码不正确!');
            }
            unset($param['username']);
            $param['salt'] = set_salt(20);
            $param['pwd'] = set_password($param['pwd'], $param['salt']);
            Db::name('Admin')->where([
                'id' => $admin['id'],
            ])->strict(false)->field(true)->update($param);
            $session_admin = get_config('app.session_admin');
            Session::set($session_admin, Db::name('admin')->find($admin['id']));
            return to_assign();
        }
    }
    public function refuse()
    {
        $project_id=get_params('project_id');
        $where=[];
        $reason=get_params('reason');
        //var_dump($project_id);
        //var_dump($reason);
        $time=time();
        $admin_id=get_login_admin('id');
        $data = ['create_time' => $time, 'project_id' => $project_id,'admin_id'=>$admin_id,
            'reasons'=>$reason,'status'=>-1];
        Db::name('sp_audit')->insert($data);
        $where[]=['project_id','=',$project_id];
        Db::name('sp_projects')
            ->where($where)
            ->update(['status' => '-1']);

        return to_assign(0,"操作成功！");
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
    public function access_one()
    {
        $project_id=get_params('project_id');
        //var_dump($project_id);
        $where=[];
        $where[]=['project_id','=',$project_id];
        Db::name('sp_projects')
            ->where($where)
            ->update(['status' => '1']);
        $time=time();
        $admin_id=get_login_admin('id');
        $data = ['create_time' => $time, 'project_id' => $project_id,'admin_id'=>$admin_id,
            'status'=>1];
        Db::name('sp_audit')->insert($data);
        return to_assign(0,"操作成功！");
    }
    public function access()
    {
        $project_id = get_params('id');
        foreach ($project_id as $id) {
            $where=[];
            $where[]=['project_id','=',$id];
            Db::name('sp_projects')
                ->where($where)
                ->update(['status' => '1']);
            $time=time();
            $admin_id=get_login_admin('id');
            $data = ['create_time' => $time, 'project_id' => $id,'admin_id'=>$admin_id,
                'status'=>1];
            Db::name('sp_audit')->insert($data);
        }
    }
}
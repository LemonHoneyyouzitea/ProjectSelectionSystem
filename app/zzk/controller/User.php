<?php

namespace app\zzk\controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Db;

class User
{

    public function index()
    {
        if (request()->isAjax()) {
            //只显示与当前管理系统相关的用户信息
            $uid=get_login_admin('id');

            $group_id=Db::name('AdminGroupAccess')->where(['uid'=>$uid])-> column('group_id');
            //var_dump($group_id);
            //$result =Db::name('AdminGroup')->where('id', '=',5)->whereOr('id', '=', 6)->select();
            $param = get_params();
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];

            $data = Db::name('admin')->alias('a')
                ->where('group_id', '=',5)->whereOr('group_id', '=', 6)
                ->field('a.*')
                ->join('AdminGroupAccess b','b.uid = a.id')
                ->order('create_time asc')
                ->paginate($rows);
            foreach ($data as $k => $v) {
                $item = $v;
                $groupId = Db::name('AdminGroupAccess')->where(['uid' => $item['id']])->column('group_id');
                $groupName = Db::name('AdminGroup')->where('id', 'in', $groupId)->column('title');
                $item['groupName'] = implode(',', $groupName);
                $item['last_login_time'] = empty($item['last_login_time']) ? '-' : date('Y-m-d H:i', $item['last_login_time']);
                $data->offsetSet($k, $item);
            }
            return table_assign(0, '', $data);
        } else {
            return view();
        }

    }

    public function add_user()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $param['salt'] = set_salt(20);
            $param['pwd'] = set_password($param['pwd'], $param['salt']);
            $groupId=$param['group_id'];
            //var_dump($groupId);
            // 启动事务
            Db::startTrans();
            try {
                $uid = Db::name('Admin')->strict(false)->field(true)->insertGetId($param);
                //$groupId = Db::name('AdminGroup')->where(['title' => '医疗设备管理员'])->value('id');
                $data = [
                    'uid' => $uid,
                    'group_id' => $groupId
                ];
                Db::name('AdminGroupAccess')->strict(false)->field(true)->insert($data);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return to_assign(1, '提交失败:' . $e->getMessage());
            }
            return to_assign();
        } else {
            return view();
        }
    }

    //导入用户
    public function import_user()
    {
        $file = request()->file('file');
        if (!$file) {
            print_r('请选择需要导入的文件');die;
        }
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $row=$sheet->getHighestRow();  //得到总行数
        $success=0;
        for($i=2;$i<=$row;$i++) {
            $data= [];
            $data['username']=$sheet->getCell('A'.$i)->getValue();
            $data['nickname']=$sheet->getCell('B'.$i)->getValue();
            $result=Db::name('admin')->insert($data);
            if($result>0) $success++;
        }
        return to_assign(0,"成功导入".$success."条数据！");
    }

}
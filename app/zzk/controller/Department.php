<?php

namespace app\zzk\controller;

use think\facade\Db;
use think\facade\View;
class Department
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
            $where[] = ['department_name', 'like', '%' . $param['keywords'] . '%'];
        }
        $data = Db::name('med_department')->where($where)->select();
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
        $data = Db::name('med_department')->where(['department_id' => $id])->find();
        View::assign('data', $data);
        return view();

    }

    public function save()
    {
        $param = get_params();
        if (!empty($param['department_id']) && $param['department_id'] > 0) {  //当前操作是编辑保存
            $where = array();
            $where[] = ['department_name', '=', $param['department_name']];
            $where[] = ['department_id', '<>', $param['department_id']];
            $count = Db::name('med_department')->where($where)->count();
            if ($count > 0)
                return to_assign(1, '操作失败，原因：该类别已经存在');
            Db::name('med_department')->where('department_id', $param['department_id'])->strict(false)->update($param);  //strict此处可以不加，详见https://doc.thinkphp.cn/v8_0/strict.html
            return to_assign();
        } else {   //当前操作是新建保存
            $count = Db::name('med_department')->where('department_name', '=', $param['department_name'])->count();
            if ($count > 0)
                return to_assign(1, '操作失败，原因：该类别已经存在');

            /*$type_name = Db::name('BookType')->where(['type_name' => $param['username']])->find();
            if (empty($type_name))
                return to_assign(1, '操作失败，原因：该类别已经存在');*/

            $param['create_time'] = time();
            Db::name('med_department')->insert($param);
            return to_assign();
        }
    }

    //删除
    public function delete()
    {
        $param = get_params();
        $where = [
            ['department_id' ,'=' ,$param['department_id']]

            //,['type_id', '<>', $param['type_id']]
        ];
        $count = Db::name('med_belong')->where($where)->count();
        if ($count > 0) {
            return to_assign(1, "该部门下还有设备，无法删除");
        }
        if (Db::name('med_department')->delete($param['department_id']) !== false) {
            return to_assign(0, "删除类别成功");
        } else {
            return to_assign(1, "删除失败");
        }
    }
    //查看部门下所有的设备
    public function read()
    {
        if (request()->isAjax()) {
            $params=get_params();
            $department_id=$params['department_id'];
            $count = Db::name('med_belong')->where(['department_id' => $department_id])->count();
            if ($count <= 0){
                return to_assign(1, "该部门下还没有设备，无法查看");
            }else{
                $data=Db::name('med_belong')->alias('b')
                    ->where(['department_id' => $department_id])
                    ->field('b.*,c.equipment_name')
                    ->join('med_equipment c', 'b.equipment_id = c.equipment_id')
                    ->order('equipment_id asc')
                    ->select();
                foreach ($data as $k => $v) {
                    $item = $v;
                    $item['create_time'] = empty($item['create_time']) ? '-' : date('Y-m-d H:i', $item['create_time']);
                    $data->offsetSet($k, $item);
                }
               return to_assign(0, '', $data);
            }
        }else{
            $department_id = get_params("id"); // 获取部门id
            View::assign('department_id', $department_id);
            return view();

        }
    }
    public function enable()
    {
        $params=get_params();
        $department_id=$params['department_id'];
        $data = Db::name('med_equipment')->select();
        $modifiedData = array(); // 新建一个空数组用于存储修改后的数据

        foreach ($data as $item) {
            $item['department_id'] = $department_id; // 添加belong_id字段并赋值
            $modifiedData[] = $item; // 将修改后的数据添加到数组中
        }
        //var_dump($modifiedData);
        return to_assign(0, '', $modifiedData);
    }

    public function take()
    {
        $params=get_params();
        $department_id=$params['department_id'];
        $equipment_id=$params['equipment_id'];
        $status=Db::name('med_equipment')->where(['equipment_id'=>$equipment_id])->value('status');
        $enable=Db::name('med_equipment')->where(['equipment_id'=>$equipment_id])->value('enable_number');
        if ($status==0||$enable==0){
            return to_assign(1, "无法征用");
        }else{
            Db::name('med_equipment')
                ->where('equipment_id', $equipment_id)
                ->update([
                    'enable_number'		=>	Db::raw('enable_number-1'),
                ]);

            $create_time= time();
            $data = [
                'department_id' => $department_id,
                'create_time' => $create_time,
                'equipment_id'=>$equipment_id,
                'number'=> 1 ,
                'status'=> 1 ,
            ];
            Db::name('med_belong')->insert($data);
            return to_assign(0, "征用成功");
        }

    }
}
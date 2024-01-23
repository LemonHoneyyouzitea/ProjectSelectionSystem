<?php

namespace app\zzk\controller;

use think\facade\Db;
use think\facade\View;

class Maintain
{
    public function index()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $where=[];
            $where[]=['a.worker_id','=',get_login_admin('id')];
            //$where[]=['a.order_no','=',NULL];
            $content = Db::name('med_repair')->alias('a')
                ->field('a.*,department_name,equipment_name,nickname')
                ->where($where)
                ->join('med_belong b', 'a.belong_id = b.belong_id','left')
                ->join('med_department c', 'b.department_id = c.department_id','left')
                ->join('med_equipment d', 'd.equipment_id = b.equipment_id','left')
                ->join('Admin e','e.id = a.user_id')
                ->order('a.repair_id asc')
                ->paginate($rows);
            foreach ($content as $k => $v) {
                $item = $v;
                $item['order_date'] = empty($item['order_date']) ? '-' : date('Y-m-d H:i', $item['order_date']);
                $content->offsetSet($k, $item);
            }
            return table_assign(0, '', $content);
        } else {
            return view();
        }
    }
    public function fix()
    {
        //维修belong 修改status ,
        $param = get_params();
        $belong_id=Db::name('med_repair')->where(['repair_id'=>$param['repair_id']])->column('belong_id');

        Db::name('med_belong')
            ->where(['belong_id'=>$belong_id])
            ->data(['status' => 1])
            ->update();
        Db::name('med_repair')
            ->where('repair_id', $param['repair_id'])
            ->data(['status' => 1])
            ->update();
        return to_assign(0, "维修成功");
    }



}
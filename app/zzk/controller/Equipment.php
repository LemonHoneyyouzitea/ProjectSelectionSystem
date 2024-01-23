<?php

namespace app\zzk\controller;

use http\Params;
use think\facade\Db;
use think\facade\View;

class Equipment
{
    public function index()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = array();
            if (!empty($param['keywords'])) {
                $where[] = ['b.equipment_name', 'like', '%' . $param['keywords'] . '%'];
            }
            if (!empty($param['type_id'])) {
                $where[] = ['b.type_id', '=', $param['type_id']];
            }
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            //select b.*,type_name from book b,book_type c where b.type_id=c.id
            //select * from book join book_type on book.type_id=book_type.id
            $content = Db::name('med_equipment')->alias('b')
                ->where($where)
                ->field('b.*,c.type_name')
                ->join('med_type c', 'b.type_id = c.type_id')
                ->order('type_id asc')
                ->paginate($rows);
            return table_assign(0, '', $content);
        } else {
            $type = Db::name('med_type')->select();
            View::assign('med_type', $type); //要加上 use think\facade\View;拷贝时会有提示
            return view();
        }

    }

    public function edit()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        $data = Db::name('med_equipment')->where(['equipment_id' => $id])->find();
        View::assign('data', $data);
        $med_type = Db::name('med_type')->select();
        View::assign('med_type', $med_type);
        return view();
    }

    public function repair()
    {
        $id = get_params("id");
        $data = Db::name('med_equipment')->where(['equipment_id' => $id])->find();
        View::assign('data', $data);
        return view();
    }

    public function worker()
    {
        if (request()->isAjax()) {
            $params=get_params();
            $belong_id=$params['belong_id'];
            $where = array();
            $where[] = ['b.id', '=', '6'];
            $data = Db::name('AdminGroup')->alias('b')
                ->where($where)
                ->field('b.title,d.*')
                ->join('AdminGroupAccess c', 'b.id = c.group_id')
                ->join('Admin d','d.id=c.uid')
                ->order('d.id asc')
                ->select();

            $modifiedData = array(); // 新建一个空数组用于存储修改后的数据

            foreach ($data as $item) {
                $item['belong_id'] = $belong_id; // 添加belong_id字段并赋值
                $modifiedData[] = $item; // 将修改后的数据添加到数组中
            }
            //var_dump($modifiedData);
            return to_assign(0, '', $modifiedData);
        }else{
            $belong_id = get_params('id');
            $data = Db::name('med_belong')->where(['belong_id' => $belong_id])->find();
            View::assign('belong_id',$belong_id);
            return view();
        }
    }

    public function order()
    {   $param = get_params();

        $data=[];
        $order_no=get_no();  //生成订单号，在common.php文件中
        $data['order_no']=$order_no;  //在common.php文件中
        $data['belong_id']=$param['belong_id'];
        $data['worker_id']=$param['id'];;
        $data['user_id']=get_login_admin('id');
        $data['order_date']=time();
        Db::name('med_repair')->insert($data);
        //return to_assign(0, $order_no);
        Db::name('med_belong')->update(['status' => '0','belong_id' => $param['belong_id']]);
        return to_assign(0, '操作成功！');
    }
    public function add()
    {
        View::assign('med_type', Db::name('med_type')->select());
        return view();
    }

    public function up_down()  //图书上下架
    {
        $param = get_params();
        if ($param['status'] == '1') $param['status'] = '0';
        else $param['status'] = '1';
        Db::name('med_equipment')->update($param);
        return to_assign(0, '操作成功！');
    }

    public function save()
    {
        $param = get_params();

        if (!empty($param['equipment_id']) && $param['equipment_id'] > 0) {
            Db::name('med_equipment')->where('equipment_id', $param['equipment_id'])->strict(false)->update($param);  //strict此处可以不加，详见https://doc.thinkphp.cn/v8_0/strict.html
            return to_assign();
        } else {
            //$param['user_id'] = get_login_admin('id');
            //$param['order_no'] = get_no();
            $param['enable_number']=$param['number'];
            Db::name('med_equipment')->strict(false)->insert($param);
            /*$insertId = Db::name('book')->strict(false)->insertGetId($param);*/
            return to_assign(0, "操作成功！");
        }
    }

    public function del()
    {
        $equipment_id = get_params('equipment_id');  //获得前台传来的数据，内容为[1,3,7]
        $count=Db::name('med_belong')->where(['equipment_id' => $equipment_id])->count();
        if ($count > 0) {
            return to_assign(1, "该设备有部门正在使用，无法删除");
        }
        if (Db::name('med_equipment')->delete($equipment_id) !== false) {
            return to_assign(0, "删除设备成功");
        } else {
            return to_assign(1, "删除失败");
        }

    }
    //添加完设备后还可以补仓
    public function add_num()
    {
        $id = get_params("id");
        $data = Db::name('med_equipment')->where(['equipment_id' => $id])->find();
        View::assign('data', $data);
        return view();
    }

    public function belong()
    {
        $params=get_params();
        $equipment_id=$params['equipment_id'];

        $count = Db::name('med_belong')->where(['equipment_id' => $equipment_id])->count();
        if ($count <= 0){
            return to_assign(1, "该设备还没有被使用");
        }else{
            $where = array();
            $where[] = ['b.equipment_id', '=', $params['equipment_id']];
            $data = Db::name('med_belong')->alias('b')
                ->where($where)
                ->field('b.*,c.department_name')
                ->join('med_department c', 'b.department_id = c.department_id')
                ->order('department_id asc')
                ->select();
            return to_assign(0, '', $data);
        }
    }

    public function enable()
    {
        $params=get_params();
        $equipment_id=$params['equipment_id'];
/*        $where = array();
        $data=Db::name('med_belong')->alias('b')
            ->where($where)
            ->field('b.*,c.equipment_name')
            ->join('med_equipment c','b.equipment_id=c.equipment_id')
            ->order('')*/
        $enable = Db::name('med_equipment')->where(['equipment_id' => $equipment_id])->select();
        return to_assign(0,'',$enable);

    }
    public function save_add()
    {

            $params=get_params();
            $equipment_id=$params['equipment_id'];
            $account=$params['number'];
            //增加数量不为0
            if($account > 0){
                $num=Db::name('med_equipment')->where(['equipment_id' => $equipment_id])->value('number');
                $enable=Db::name('med_equipment')->where(['equipment_id' => $equipment_id])->value('enable_number');

                $enable+=$account;
                $account+=$num;
                Db::name('med_equipment')->where(['equipment_id' => $equipment_id])->strict(false)->update(['number'=>$account]);
                Db::name('med_equipment')->where(['equipment_id' => $equipment_id])->strict(false)->update(['enable_number'=>$enable]);
                return to_assign();
            }

    }
    public function add_cart()  //加入购物车
    {
        $book_id = get_params('id');

        $data=[];
        $data['book_id']=$book_id;

        $where=[];
        $where[] = ['user_id', '=', get_login_admin('id')];
        $where[] = ['book_id', '=', $book_id];
        $where[] = ['order_no', '=', null];
        $cart=Db::name('book_cart')->where($where)->find();  //查看购物车中是否有加入的图书数据

        if($cart==null){
            $book=Db::name('book')->where('id',$book_id)->find();
            $data['user_id']=get_login_admin('id');
            $data['num']=1;
            $data['money']=$book['price'];
            Db::name('book_cart')->insert($data);
        }else{
            $data['num']=$cart['num']+1;
            $data['cart_id']=$cart['cart_id'];
            Db::name('book_cart')->update($data);
        }
        return to_assign(0, "加入购物车成功！");
    }
}
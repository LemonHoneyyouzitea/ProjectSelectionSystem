<?php

namespace app\teach\controller;

use think\facade\Db;
use think\facade\View;

class Cart
{
    public function index()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $where=[];
            $where[]=['a.user_id','=',get_login_admin('id')];
            $where[]=['a.order_no','=',NULL];
            $content = Db::name('book_cart')->alias('a')
                ->field('a.*,b.*,type_name')
                ->where($where)
                ->join('book b', 'a.book_id = b.id','left')
                ->join('book_type c', 'b.type_id = c.id','left')
                ->order('a.cart_id asc')
                ->paginate($rows);
            return table_assign(0, '', $content);
        } else {
            return view();
        }
    }

    public function data()
    {
        $order_no=get_params("order_no");

        $data = Db::name('book_cart')->where(['order_no' => $order_no])->select();
        return to_assign('0','', $data);
}

    public function read()
    {
        if (request()->isAjax()) {
                $params=get_params();
                $order_no=$params['order_no'];
                $data = Db::name('book_cart')->where(['order_no' => $order_no])->select();
                return to_assign(0, '', $data);
        }else{
            $order_id = get_params("id"); // 获取订单id
            if ($order_id==null) {
                $params=get_params();
                $order_no=$params['order_no'];
                $data = Db::name('book_cart')->where(['order_no' => $order_no])->find();
                return to_assign(0, '', $data);
            } else{
                $order =Db::name('book_order')->where(['id' => $order_id])->find();
                $order_no = $order['order_no'];
                $data = Db::name('book_cart')->where(['order_no' => $order_no])->find();
                View::assign('data', $data);
                return view();
            }
        }

    }
    public function buy()
    {
        $book_id = get_params('id');  //获得所购商品的id(购物车表的),格式为[1,3,6]
        $money = 0;
        $order_no=get_no();  //生成订单号，在common.php文件中
        //注意此处要修改book表中的库存，还要判断是否有这么多库存
        foreach ($book_id as $id) {
            $where=[];
            $where[]=['book_id','=',$id];
            $where[]=['order_no','=',null];
            $book = Db::name('book_cart')->where($where)->find();
            $money += $book['num'] * $book['money'];
            $book['order_no']=$order_no;
            Db::name('book_cart')->update($book);
        }
        $data=[];
        $data['order_no']=$order_no;  //在common.php文件中
        $data['money']=$money;
        $data['user_id']=get_login_admin('id');
        $data['order_date']=time();
        Db::name('book_order')->insert($data);
        return to_assign(0, $order_no);
    }

    public function order()
    {
        if(request()->isAjax()){
            $data = Db::name('book_order')->select();
            foreach ($data as $k => $v) {
                $item = $v;
                $item['order_date'] = empty($item['order_date']) ? '-' : date('Y-m-d H:i', $item['order_date']);
                $data->offsetSet($k, $item);
            }
            return to_assign(0, '', $data);
        }
        else{
            return view();
        }
    }


}
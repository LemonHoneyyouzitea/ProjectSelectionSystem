<?php

namespace app\teach\controller;


use think\facade\Db;
use think\facade\View;

class Book
{
    public function index()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = array();
            if (!empty($param['keywords'])) {
                $where[] = ['b.book_name', 'like', '%' . $param['keywords'] . '%'];
            }
            if (!empty($param['type_id'])) {
                $where[] = ['b.type_id', '=', $param['type_id']];
            }
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            //select b.*,type_name from book b,book_type c where b.type_id=c.id
            //select * from book join book_type on book.type_id=book_type.id
            $content = Db::name('book')->alias('b')
                ->where($where)
                ->field('b.*,c.type_name')
                ->join('book_type c', 'b.type_id = c.id')
                ->order('id asc')
                ->paginate($rows);
            return table_assign(0, '', $content);
            return to_assign(0, '', $content);


        } else {
            $book_type = Db::name('book_type')->select();
            //return to_assign(0, '', $book_type);
            View::assign('book_type', $book_type); //要加上 use think\facade\View;拷贝时会有提示

            return view();
        }

    }

    public function edit()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        $data = Db::name('book')->where(['id' => $id])->find();
        View::assign('data', $data);
        $book_type = Db::name('book_type')->select();
        View::assign('book_type', $book_type);
        return view();
    }

    public function add()
    {
        View::assign('book_type', Db::name('book_type')->select());
        return view();
    }

    public function up_down()  //图书上下架
    {
        $param = get_params();
        if ($param['status'] == '1') $param['status'] = '0';
        else $param['status'] = '1';
        Db::name('book')->update($param);
        return to_assign(0, '操作成功！');
    }

    public function save()
    {
        $param = get_params();
        if (!empty($param['id']) && $param['id'] > 0) {
            Db::name('book')->where('id', $param['id'])->strict(false)->update($param);  //strict此处可以不加，详见https://doc.thinkphp.cn/v8_0/strict.html
            return to_assign();
        } else {
            $param['user_id'] = get_login_admin('id');
            $param['order_no'] = get_no();
            Db::name('book')->strict(false)->insert($param);
            /*$insertId = Db::name('book')->strict(false)->insertGetId($param);*/
            return to_assign(0, "操作成功！");
        }
    }

    public function del()
    {
        $book_id = get_params('id');  //获得前台传来的数据，内容为[1,3,7]
        Db::name('book')->delete($book_id);
        return to_assign();
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
<?php
/**
 * @copyright Copyright (c) 2021 勾股工作室
 * @license https://opensource.org/licenses/Apache-2.0
 * @link https://www.gougucms.com
 */
 
declare (strict_types = 1);

namespace app\home\controller;

use app\home\BaseController;
use app\admin\model\Pages as PagesModel;
use think\facade\Db;
use think\facade\View;

class Pages extends BaseController
{
    public function detail()
    {
		$param = get_params();
		$id = isset($param['id']) ? $param['id'] : 0;
		if (isset($param['s'])) {
			$id = Db::name('Pages')->where(['name'=>$param['s']])->value('id');
			if(empty($id)){
				throw new \think\exception\HttpException(406, '找不到相关记录');
			}
		}
		$detail = (new PagesModel())->getPagesById($id);
		if(empty($detail)){
			throw new \think\exception\HttpException(406, '找不到相关记录');
		}
		//轮播图
		if(!empty($detail['banner'])) {
			$detail['banner_array'] = explode(',',$detail['banner']);
		}
		//关键字
		$keyword_array = Db::name('PagesKeywords')
			->field('i.aid,i.keywords_id,k.title')
			->alias('i')
			->join('keywords k', 'k.id = i.keywords_id', 'LEFT')
			->order('i.create_time asc')
			->where(array('i.aid' => $id, 'k.status' => 1))
			->select()->toArray();

		$detail['keyword_ids'] = implode(",", array_column($keyword_array, 'keywords_id'));
		$detail['keyword_names'] = implode(',', array_column($keyword_array, 'title'));		
		$detail['keyword_array'] = $keyword_array;
		PagesModel::where('id', $id)->inc('read')->update();
		View::assign('detail', $detail);
		return view($detail['template']);
    }
}

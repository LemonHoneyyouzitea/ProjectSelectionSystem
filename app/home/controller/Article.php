<?php
/**
 * @copyright Copyright (c) 2021 勾股工作室
 * @license https://opensource.org/licenses/Apache-2.0
 * @link https://www.gougucms.com
 */
 
declare (strict_types = 1);

namespace app\home\controller;

use app\home\BaseController;
use app\admin\model\Article as ArticleModel;
use think\facade\Db;
use think\facade\View;

class Article extends BaseController
{
    public function detail()
    {
		$param = get_params();
		$id = isset($param['id']) ? $param['id'] : 0;
		$detail = (new ArticleModel())->detail($id);
		ArticleModel::where('id', $param['id'])->inc('read')->update();
		View::assign('detail', $detail);
		return view();
    }
}

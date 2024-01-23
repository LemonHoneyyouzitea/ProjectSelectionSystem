<?php
/**
 * @copyright Copyright (c) 2021 勾股工作室
 * @license https://opensource.org/licenses/Apache-2.0
 * @link https://www.gougucms.com
 */

namespace app\admin\validate;
use think\Validate;

class GoodsCateValidate extends Validate
{
    protected $rule = [
    'title' => 'require',
];

    protected $message = [
    'title.require' => '分类名称不能为空',
];
}
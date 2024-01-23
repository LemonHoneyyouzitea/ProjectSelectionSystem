<?php
/**
 * @copyright Copyright (c) 2021 勾股工作室
 * @license https://opensource.org/licenses/Apache-2.0
 * @link https://www.gougucms.com
 */

namespace app\admin\validate;

use think\Validate;

class AdminCheck extends Validate
{
    protected $regex = [ 'checkUser' => '/^[A-Za-z]{1}[A-Za-z0-9_-]{4,19}$/'];

    protected $rule = [
        'username' => 'require|unique:admin',
        'pwd' => 'require|confirm',
        'edit_pwd' => 'confirm',
        'group_id' => 'require',
        'id' => 'require',
        'status' => 'require|checkStatus:-1,1',
        'old_pwd' => 'require|different:pwd',
    ];

    protected $message = [
        'username.require' => '登录账号不能为空',
        'username.unique' => '同样的登录账号已经存在',
        'pwd.confirm' => '两次密码不一致',
        'edit_pwd.confirm' => '两次密码不一致',
        'mobile.require' => '手机不能为空',
        'group_id.require' => '至少要选择一个用户角色',
        'id.require' => '缺少更新条件',
        'status.require' => '状态为必选',
        'status.checkStatus' => '系统所有者不能被禁用',
        'old_pwd.require' => '请提供旧密码',
        'old_pwd.different' => '新密码不能和旧密码一样',
    ];

    protected $scene = [
        'add' => ['mobile', 'group_id', 'pwd', 'username', 'status'],
        'edit' => ['mobile', 'group_id', 'edit_pwd','id', 'username', 'status'],
        'editPersonal' => ['mobile', 'nickname'],
        'editpwd' => ['old_pwd', 'pwd'],
    ];

    // 自定义验证规则
    protected function checkStatus($value, $rule, $data)
    {
        if ($value == -1 and $data['id'] == 1) {
            return $rule == false;
        }
        return $rule == true;
    }
}
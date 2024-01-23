<?php

//获取后台模块当前登录用户的信息
use think\facade\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

function get_login_admin($key = "")
{
    $session_admin = get_config('app.session_admin');
    if (\think\facade\Session::has($session_admin)) {
        $gougu_admin = \think\facade\Session::get($session_admin);
        if (!empty($key)) {
            if (isset($gougu_admin[$key])) {
                return $gougu_admin[$key];
            } else {
                return '';
            }
        } else {
            return $gougu_admin;
        }
    } else {
        return '';
    }
}

function get_no(){
    $str = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return $str;
}

function upload()
{
    $param = get_params();

        if(request()->file('file')){
            $file = request()->file('file');
        }
        else{
            return to_assign(1, '没有选择上传文件');
        }


    // 获取上传文件的hash散列值
    $sha1 = $file->hash('sha1');
    $md5 = $file->hash('md5');
    $rule = [
        'image' => 'jpg,png,jpeg,gif',
        'doc' => 'doc,docx,ppt,pptx,xls,xlsx,pdf',
        'file' => 'zip,gz,7z,rar,tar',
        'video' => 'mpg,mp4,mpeg,avi,wmv,mov,flv,m4v',
    ];
    $fileExt = $rule['image'] . ',' . $rule['doc'] . ',' . $rule['file'] . ',' . $rule['video'];
    //1M=1024*1024=1048576字节
    $fileSize = 100 * 1024 * 1024;
    if (isset($param['type']) && $param['type']) {
        $fileExt = $rule[$param['type']];
    }
    if (isset($param['size']) && $param['size']) {
        $fileSize = $param['size'];
    }
    $validate = \think\facade\Validate::rule([
        'image' => 'require|fileSize:' . $fileSize . '|fileExt:' . $fileExt,
    ]);
    $file_check['image'] = $file;
    if (!$validate->check($file_check)) {
        return to_assign(1, $validate->getError());
    }
    // 日期前綴
    $dataPath = date('Ym');
    $filename = \think\facade\Filesystem::disk('public')->putFile($dataPath, $file, function () use ($md5) {
        return $md5;
    });
    if ($filename) {

        $path = get_config('filesystem.disks.public.url');
        $filepath = $path . '/' . $filename;


        //写入到附件表
        $data = [];
        $data['filepath'] = $filepath;
        $data['name'] = $file->getOriginalName();
        $data['mimetype'] = $file->getOriginalMime();
        $data['fileext'] = $file->extension();
        $data['filesize'] = $file->getSize();
        $data['filename'] = $filename;
        $data['sha1'] = $sha1;
        $data['md5'] = $md5;
        $data['module'] = \think\facade\App::initialize()->http->getName();
        $data['action'] = app('request')->action();
        $data['uploadip'] = app('request')->ip();
        $data['create_time'] = time();
        $data['user_id'] = get_login_admin('id') ? get_login_admin('id') : 0;
        if ($data['module'] = 'admin') {
            //通过后台上传的文件直接审核通过
            $data['status'] = 1;
            $data['admin_id'] = $data['user_id'];
            $data['audit_time'] = time();
        }
        $res['id'] = Db::name('file')->insertGetId($data);
        $res['filepath'] = $data['filepath'];
        $res['name'] = $data['name'];
        $res['filename'] = $data['filename'];
        return to_assign(0, '上传成功', $res);

    } else {
        return to_assign(1, '上传失败，请重试');
    }
}




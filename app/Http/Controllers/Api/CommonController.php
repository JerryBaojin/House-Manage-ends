<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class CommonController extends Controller
{
      public function remove(Request $request){
        Storage::disk('public')->delete($request->input('name'));
      }

      public function upload(Request $request){
        $file = $request->file('file');

        // 文件是否上传成功
        if ($file->isValid()) {

            // 获取文件相关信息
            $originalName = $file->getClientOriginalName(); // 文件原名
            $ext = $file->getClientOriginalExtension();     // 扩展名
            $realPath = $file->getRealPath();   //临时文件的绝对路径
            $type = $file->getClientMimeType();     // image/jpeg

            // 上传文件
            $filename = date('Y-m-d-H-i-s') . '-' . uniqid() . '.' . $ext;
            // 使用我们新建的uploads本地存储空间（目录）
            //这里的uploads是配置文件的名称
            $bool = Storage::disk('public')->put($filename, file_get_contents($realPath));

            return  [

                        "name"=>$filename,
                        "url"=>asset('storage/'.$filename),
                        "uid"=>time(),
                    ];

        }
      }
}

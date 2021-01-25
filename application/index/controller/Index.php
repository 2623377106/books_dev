<?php
namespace app\index\controller;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Controller;
use think\Db;

class Index extends Controller
{
    public function index()
    {
//        展示新书添加页面
        return view('index');
    }
    public function uni(){

//        添加书籍方法
        $article=input('article');
//        查询出库里有没有名字相同的
        $data=Db::table('article')->where('article',$article)->find();
        if($data){
            return json(['code'=>403,'msg'=>'书名重复','data'=>null]);
        }
    }
//    书籍添加方法
    public function save(){
        $input=input();
//        验证非空
        $result = $this->validate(
            $input,
            [
                'name'  => 'require',
                'article'   => 'require',
            ]);
        if(true !== $result){
            // 验证失败 输出错误信息
            $this->error($result);
        }
//        文件上传
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size'=>1024*1024*3,'ext'=>'jpeg,png'])->move(ROOT_PATH . 'public' . DS . 'uploads');
        if($info){
            // 成功上传后 获取上传信息
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            $info= $info->getSaveName();
            $input['file']=$info;
            $info='./uploads/'.$info;
//            生成一个随机名字
            $res= 'uploads/20210125/'.time().rand(1,9999999).'.png';
            $image = \think\Image::open($info);
// 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.png
            $image->thumb(100, 150)->save($res);
            $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
            // 上传到七牛后保存的文件名
            $key =substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
           $ak="VEahUYz0rivkJsfe7wP_p8VlgMMT0JfBtDySt6Hk";
           $sk="AnG6wmPNhBDSphPA3BndAtr2Jh8rf7LEmVEkFbDX";
           $auth=new Auth($ak,$sk);
           $token=$auth->uploadToken('zxd1');
           $upload=new UploadManager();
          $upload->putFile($token,$key,$info);
//            执行入库
            $data=Db::table('article')->insert($input,true);
//            保存后跳转到书籍页列表
            if($data){
                $dat=Db::table('article')->where('status',1)->paginate(5);
                return view('sel',compact('dat'));
            }
        }else{
            // 上传失败获取错误信息
            echo $file->getError();
        }
    }
    public function order(){
        $order=input('order');
//        排序
        $dat=Db::table('article')->where('status',1)->order($order,'desc')->paginate(5);
        return view('sel',compact('dat'));
    }
}

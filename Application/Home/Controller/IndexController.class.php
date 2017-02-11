<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
        session('app_nav',null);
    	$Model = D('News');
        $TypeModel = D('Type');
        $TypeList = $TypeModel ->getOnType();
        $HeadLines = $Model ->getHeadLines();
        $List = $Model -> getTop10();
        $this->assign('TypeList',$TypeList);
        $this->assign('HeadLines',$HeadLines);
        
        $this->assign('List',$List);
    	$this->assign('title',"campusleader_������ǿ��У����");
        $this->display();
    }
    Public function verify(){
        $Verify =  new \Think\Verify();
        $Verify->fontSize = 200;
        $Verify->length   = 4;
        $Verify->useNoise = false;
        $Verify->entry();
    }

    public function upl(){
        $this->display('upl');
    }
    public function QRcode(){
        $data = "http://".$_SERVER['HTTP_HOST'].U('Index/download','',false,false);
        vendor("phpqrcode.phpqrcode");
        // ������L��M��Q��H
        $level = 'H';
        // ��Ĵ�С��1��10,�����ֻ���4�Ϳ�����
        $size = 10;
        // ����ע���˰Ѷ�ά��ͼƬ���浽���صĴ���,���Ҫ����ͼƬ,��$fileName�滻�ڶ�������false
        //$path = "images/";
        // ���ɵ��ļ���
        //$fileName = $path.$size.'.png';
        $QRcode = new \QRcode();
        $QRcode->png($data, false, $level, $size);

    }

    public function download(){
        
        $Agent = $_SERVER['HTTP_USER_AGENT'];
        preg_match('/android|iphone/i',$Agent,$matches);
        if(strtolower($matches[0])=='android'){
            if(!is_weixin())
                header("Location: "."http://".$_SERVER['HTTP_HOST']."/Data/tuanzi.apk");
            else{
                
                echo '<h1>΢��ɨһɨ�ݲ�֧�����أ���ʹQQɨһɨ����������ά��ɨ�衣</h1>';
            }
        }
        else if(strtolower($matches[0])=='iphone'){
            if(!is_weixin())
                header("Location:https://itunes.apple.com/cn/app/tuan-zi-gao-xiao-tuan-guan/id1106865889?l=en&mt=8");
            else{
                echo "<img src ='http://".$_SERVER['HTTP_HOST']."/Public/img/weixindown1.jpg' style='width:100%'><img src ='http://".$_SERVER['HTTP_HOST']."/Public/img/weixindown2.jpg' style='width:100%'>";
            }
        }else{
            header("Content-type: text/html; charset=utf-8"); 
            echo '<h1>�޷�ʶ���ֻ�����ϵͳ����ʹ��qqɨһɨ��ȡ������ά��ɨ������</h1>';
        }
    }
}
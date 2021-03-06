<?php
namespace Admin\Model;
use Think\Model\RelationModel;
class NewsModel extends RelationModel{

	protected $_auto = array(
	    array('content', 'htmlspecialchars_decode', self::MODEL_BOTH, 'function'),
	);
	//自动检验
	protected $_validate = array(
	    array('title','require','标题必须！'),
	);
	/**
	 * [$_link 关联属性]
	 * @var array
	 */
	protected $_link = array(
	    'type'  =>  array(
	    	'mapping_type' =>self::BELONGS_TO,
	        'class_name' => 'Type',
	        'foreign_key'=>'type',
	        'mapping_fields'=>'type',
	        'as_fields'=>'type'
	    ),
	    'user'  =>  array(
	    	'mapping_type' =>self::BELONGS_TO,
	        'class_name' => 'Login',
	        'foreign_key'=>'contributor',
	        'mapping_fields'=>'icon,nickname'
	    ),
	    'sections' => array(
	    	'mapping_type' =>self::BELONGS_TO,
	    	'class_name' => 'Sections',
	        'foreign_key'=>'sections',
	        'mapping_fields'=>'id,sections',
	    )
	);

	/**
	 * [getList 获取列表]
	 * @param  [Integer] $type     [类型]
	 * @param  [bool] $upline   [是否为头条]
	 * @param [Integer] $page [传入的页数]
	 * @return [list]           [查询到的列表]
	 */
	public function getList($type,$upline,$page,&$count){
		$data['delete_tag'] = false;
		$data['state'] = 0;
		if($type!=0)
			$data['type'] = $type;
		if($upline){
			$data['state'] = '1';
			$List = $this->where($data)->field('id,title,image,image_thumb,type,publish_time,contributor,sections,state,content')->order('publish_time desc')->relation(true)->select();
		}else if(!$upline){
			$page = ($page-1)*10;
			$List = $this->where($data)->limit("$page,10")->field('id,title,image,image_thumb,type,publish_time,contributor,sections,state,content')->order('publish_time desc')->relation(true)->select();
			$count = $this->where($data)->count();
		}
		for ($i=0; $i < count($List); $i++) {
			if($List[$i]['user']['icon'] == ''){
				$List[$i]['user']['icon']='default.jpg';
			}
			if($List[$i]['image'] == ''){
				$img = getNewsImg($List[$i]['content']);
				$List[$i]['image'] = $img;
				$List[$i]['image_thumb'] = $img;
			}
		}
		return $List;
	}


	public function getListByUserId($user_id,$page,&$count){
		$data['delete_tag'] = false;
        $data['state'] = 0;
        $data['contributor'] = $user_id;
		$List = $this->where($data) ->order('publish_time desc')->relation(true)->page($page,$count)->select();
		$count = $this->where($data)->count();
		for ($i=0; $i < count($List); $i++) {
			if($List[$i]['user']['icon'] == ''){
				$List[$i]['user']['icon']='default.jpg';
			}
			if($List[$i]['image'] == ''){
				$img = getNewsImg($List[$i]['content']);
				$List[$i]['image'] = $img;
				$List[$i]['image_thumb'] = $img;
			}
		}
		return $List;
	}

	/**
	 * [search 新闻搜索]
	 * @param  [string] $key    [传入的关键字]
	 * @param  [Integer] $page   [传入的页数]
	 * @param  [Integer] &$count [返回的总数]
	 * @return [List]         [查询到的列表]
	 */
	public function search($key,$page,&$count){
		$page = ($page-1)*10;
		$List = $this->where("title like '%$key%' and delete_tag=0 and state = 0")->limit("$page,10")->field('id,title,image,image_thumb,type,publish_time,contributor,sections')->order('publish_time desc')->relation(true)->select();
		$count = $this->where("title like '%$key% and delete_tag=0 and state = 0'")->count();
		return $List;
	}
	/**
	 * [setType 修改分类]
	 * @param [Integer] $o_id [要修改的分类的ID]
	 * @param [Integer] $[n_id] [修改后的分类ID>]
	 * @return [bool] [返回成功与否]
	 */
	public function setType($o_id,$n_id){
		$data['type']=$n_id;
		$this->where("type = $o_id")->save($data);
		$result = $this->where("type = $o_id  and delete_tag=0 and state = 0")->count();
		if($result==0) return true;
		return false;
	}

	/**
	 * [delectById 通过id删除新闻]
	 * @param  [Integer] $id [传入的id]
	 * @return [Boolean]     [删除是否成功]
	 */
	public function deleteById($id){
		$result = $this->where("id = $id and delete_tag=0 and state = 0")->delete();
		if($result!=0) return true;
		else return false;
	}

	/**
	 * [upload 图片上传 设置图片名字]
	 */
	public function upload(){
		$config = array(
				'maxSize' => 23145728,// 设置附件上传大小
				'exts' => array('jpg', 'gif', 'png', 'jpeg'),// 设置附件上传类型
				'savePath'=>'news/',// 设置附件上传目录
				'subName'    =>    array('date','Y-m-d'),
				'rootPath'=> './Data/'
			);
		$upload = new \Think\Upload($config);// 实例化上传类
		$info = $upload->uploadOne($_FILES['file']);
		if(!$info){
			return '上传错误';
		}else{
			$saveName = $info['savepath'].$info['savename'];
			$Image = new \Think\Image(\Think\Image::IMAGE_GD);
			$Image->open('./Data/'.$saveName);
			unlink('./Data/'.$saveName);
			$Image->thumb(600,600)->save('./Data/'.$saveName);
			$thumbName = str_replace('news','news_thumb',$saveName);
			$dir = str_replace('news','news_thumb',$info['savepath']);
			if(!is_readable("./Data/$dir")){
				is_file("./Data/$dir") or mkdir("./Data/$dir",0700);
			}
			$Image->thumb(300,300)->save('./Data/'.$thumbName);
			$this->image = $saveName;
			$this->image_thumb = $thumbName;
		}

	}
}

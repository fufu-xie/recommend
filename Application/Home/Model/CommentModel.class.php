<?php
namespace Home\Model;
use Think\Model\RelationModel;

class CommentModel extends RelationModel{


    protected $_link = array(
        'user'  =>  array(
	    	'mapping_type' => self::BELONGS_TO,
	        'class_name' => 'Login',
	        'foreign_key'=>'user_id',
	        'mapping_fields'=>'nickname,icon'
	    ),
        'newsInfo' => array(
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'News',
            'foreign_key'=>'news_id',
            'mapping_fields'=>'title',
            'as_fields'=>'title',
            'condition' => 'delete_tag = 0'
        ),
        'commentType' => array(
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'News',
            'foreign_key'=>'news_id',
            'mapping_fields'=>'type',
            'as_fields'=>'type',
            'condition' => 'delete_tag = 0'
        )
    );


    /**
	 * [getMessageCount 获取新闻评价数量]
	 * @param  [Integer] $id [传入的ID]
	 * @return [Integer]     [返回评价数量]
	 */
	public function getCommentCount($id){
		return $this->where(array('news_id'=>$id))->count();
	}

    /**
     * [getList 获取列表]
     * @param  [Integer] $page  [description]
     * @param  [Integer] $count [description]
     * @param  [Integer] $order [description]
     * @return [type]        [description]
     */
    public function getList($news_id ,$user_id = 0,$page = 1,$count = 5,$order='newest',$relationNews=false){
        $DB_PREFIX = C('DB_PREFIX');
        if( $user_id != 0){
            $condition['user_id'] = $user_id;
        }
        if( $news_id != 0 ){
            $condition['news_id'] = $news_id;
        }
        $relationArr = array('user');
        if($relationNews){
            array_push($relationArr,'newsInfo');
        }else{
            $condition['delete_tag'] = (bool)0;
        }
        $order = $order == 'newest' ? 'time desc,zan_count desc' : 'zan_count desc,time desc';
        $result = $this ->relation($relationArr)->field('id,time,content,user_id,reply,zan_count,news_id,delete_tag') -> where($condition) -> order($order) -> page($page,$count) -> select();
        foreach ($result as &$value) {
            if($value['reply'] != '0'){
                $reply_content = M('')->query('select c.user_id user_id, l.nickname nickname,c.content content from '.$DB_PREFIX.'comment c,'.$DB_PREFIX.'login l where c.delete_tag = 0 and l.id = c.user_id and c.id = '.$value['reply']);
                $value['reply_content'] = $reply_content[0];
            }else{
                $value['reply_content'] = '';
            }
        }
        return $result;
    }


    public function getCommentCountByContributor($user_id){
        $DB_PREFIX = C('DB_PREFIX');
        $subQuery  = "select id from {$DB_PREFIX}news WHERE contributor = $user_id and delete_tag = 0";
        $condition['_string'] = "news_id in ({$subQuery})";
        $condition['delete_tag'] = false;
        $condition['_logic'] = 'and';
        return $this->where($condition)->count();
    }

    public function getListByIssue($user_id,$page,$count,$order = 'newest'){
        $DB_PREFIX = C('DB_PREFIX');
        $subQuery = "select id from {$DB_PREFIX}news where contributor = $user_id and delete_tag = 0";
        $condition['_string'] = "news_id in ({$subQuery})";
        $condition['delete_tag'] = false;
        $condition['_logic'] = 'and';

        $order = $order == 'newest'? 'time desc,zan_count desc':'zan_count desc,time desc';
        $result = $this->where($condition)->relation(['user'])->page($page,$count)->order($order)->select();
        $newsModel = M('News');

        foreach($result as &$item){
            $item['newsInfo'] = $newsModel->where(array('id'=>$item['news_id'],'delete_tag'=>false))->field('id,content,image,title')->find();
            $item = $this->imageGen($item);
        }
        return $result;

    }

    /**
     * [getDynamics2 description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getDynamics2($id){
        $result = $this->relation(['user'])->where(array('id'=>$id))->find();
        $newsModel = M('News');
        $result['newsInfo'] = $newsModel->where(array('id'=>$result['news_id'],'delete_tag'=>false))->field('id,content,image,title')->find();
        $result = $this->imageGen($result);
        return $result;
    }
    public function imageGen($result){
        $DB_PREFIX = C('DB_PREFIX');
        if($result['newsInfo']['image'] == '' || $result['newsInfo']['image']== null){
            $img = getNewsImg($result['newsInfo']['content']);
            if( $img == '' || $img == null ) {
                $result['newsInfo']['image'] = '';
            }else{
                $result['newsInfo']['image'] = getNewsImg($result['newsInfo']['content']);
            }
        }
        if($result['newsInfo']['image'] !== '' ){
            $result['newsInfo']['image'] = U('Image/img',array('image'=>urlencode($result['newsInfo']['image']).'!feature'),false,false);
        }else{
            $result['newsInfo']['image'] = __ROOT__.'/Public/img/链接.png';
        }
        if( (int)$result['reply'] ){
            $reply_content = $this->query('select c.user_id user_id, l.nickname nickname,c.content content from '.$DB_PREFIX.'comment c,'.$DB_PREFIX.'login l where l.id = c.user_id and c.id = '.$result['reply']);
            $result['replyContent'] = $reply_content[0];
        }else{
            $result['replyContent'] = '';
        }
        return $result;
    }


    public function getCommentListByUserIdAndBeginTime($user_id,$allowRecommendBeginTime){
        return $this->relation('commentType')-> where(array( 'user_id' => $user_id ,'time'=> array('gt',$allowRecommendBeginTime))) ->order('time desc') -> field('news_id,time') -> select();
    }

    public function getMessage2($id){
        return $this->getDynamics2($id);
    }

    public function getMessage3($id){
        return $this->getDynamics2($id);
    }

}
?>

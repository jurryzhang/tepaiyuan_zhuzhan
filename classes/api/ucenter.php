<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file notice.php
 * @brief 用户中心api方法
 * @author chendeshan
 * @date 2014/10/12 13:59:44
 * @version 2.7
 */
class APIUcenter
{

	///用户中心-账户余额
	public function getUcenterAccoutLog($userid)
	{
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('account_log');
		$query->where="user_id = ".$userid;
		$query->order = 'id desc';
		$query->page  = $page;
		return $query;
	}
	//用户中心-我的建议
	public function getUcenterSuggestion($userid)
	{
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('suggestion');
		$query->where="user_id = ".$userid;
		$query->page  = $page;
		$query->order = 'id desc';
		return $query;
	}
	//用户中心-商品讨论
	public function getUcenterConsult($userid)
	{
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('refer as r');
		$query->join   = "join goods as go on r.goods_id = go.id ";
		$query->where  = "r.user_id =". $userid;
		$query->fields = "time,name,question,status,answer,admin_id,go.id as gid,reply_time";
		$query->page   = $page;
		$query->order = 'r.id desc';
		return $query;
	}
	//用户中心-商品评价
	public function getUcenterEvaluation($userid,$status = '')
	{
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('comment as c');
		$query->join   = "left join goods as go on c.goods_id = go.id ";
		$query->where  = ($status === '') ? "c.user_id = ".$userid : "c.user_id = ".$userid." and c.status = ".$status;
		$query->fields = "go.name,c.*";
		$query->page   = $page;
		$query->order = 'c.id desc';
		return $query;
	}

	//用户中心-用户信息
	public function getMemberInfo($userid){
		$tb_member = new IModel('member as m,user as u');
		$info = $tb_member->getObj("m.user_id = u.id and m.user_id=".$userid);
		$info['group_name'] = "";
		if($info['group_id'])
		{
			$userGroup = new IModel('user_group');
			$groupRow  = $userGroup->getObj('id = '.$info['group_id']);
			$info['group_name'] = $groupRow ? $groupRow['group_name'] : "";
		}
		return $info;
	}
	//用户中心-个人主页统计
	public function getMemberTongJi($userid){
		$result = array();

		$query = new IQuery('order');
		$query->fields = "count(id) as num";
		$query->where  = "user_id = ".$userid." and if_del = 0";
		$info = $query->find();
		$result['num'] = $info[0]['num'];

		$query->fields = "sum(order_amount) as amount";
		$query->where  = "user_id = ".$userid." and status = 5 and if_del = 0";
		$info = $query->find();
		$result['amount'] = $info[0]['amount'];

		return $result;
	}
	//用户中心-代金券统计
	public function getPropTongJi($propIds){
		$query = new IQuery('prop');
		$query->fields = "count(id) as prop_num";
		$query->where  = "id in (".$propIds.") and type = 0";
		$info = $query->find();
		return $info[0];
	}
	//用户中心-积分列表
	public function getUcenterPointLog($userid,$c_datetime)
	{
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('point_log');
		$query->where  = "user_id = ".$userid." and ".$c_datetime;
		$query->page   = $page;
		$query->order= "id desc";
		return $query;
	}
	//用户中心-信息列表
	public function getUcenterMessageList($msgIds){
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('message');
		$query->where= "id in(".$msgIds.")";
		$query->order= "id desc";
		$query->page = $page;
		return $query;
	}
	//用户中心-订单列表
	public function getOrderList($userid){
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$status=IReq::get('sid')?IReq::get('sid'):0;
		switch ($status) {
			case '1'://待付款
				$w=' and status=1 and pay_status=0';
				break;
			case '2'://待发货
				$w=' and status=2 and distribution_status=0';
				break;
			case '3'://待收货
				$w=' and status=8';
				break;
			case '4'://待评价
				$w=' and status=5';
				break;
			default:
				$w='';
				break;
		}
		$query = new IQuery('order');
		$query->where = "user_id =".$userid." and if_del= 0".$w;
		$query->order = "id desc";
		echo $query->getSql();exit();
		$query->page  = $page;
		return $query;
	}
	public function getOrderMarketList($userid)
	{
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$status=IReq::get('sid')?IReq::get('sid'):0;
		switch ($status) {
			case '1'://待付款
				$w=' and om.status=1 and om.pay_status=0';
				break;
			case '2'://待发货
				$w=' and om.status in (2,8,9) and om.freight_status in(0,1)';
				break;
			case '3'://待收货
				$w=' and om.status=9 and om.freight_status in (2,3)';
				break;
			case '4'://待评价
				$w=' and om.status=5';
				break;
			default:
				$w='';
				break;
		}


// select om.*,m.name,o.id as oid
// from iwebshop_order_market as om
// LEFT JOIN iwebshop_market  as m on m.id=om.market_id
// LEFT JOIN iwebshop_order	as o on o.id in(om.orderid_str)
// where om.uid =7 and o.status=5   order by om.id desc


		$query = new IQuery('order_market as om');
		$query->fields = "om.*,m.name";
		// $query->join = "LEFT JOIN market as m on m.id=om.market_id LEFT JOIN order	as o on o.id in(om.orderid_str)";
		$query->join = "LEFT JOIN market as m on m.id=om.market_id";
		$query->where = "om.uid =".$userid.$w;
		$query->order = "om.id desc";
		// echo $query->getSql();exit();
		$query->page  = $page;
		return $query;
	}





	//用户中心-我的代金券
	public function getPropList($ids){
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('prop');
		$query->where  = "id in(".$ids.") and is_send = 1";
		$query->page   = $page;
		return $query;
	}
	//用户中心-退款记录
	public function getRefundmentDocList($userid){
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('refundment_doc');
		$query->where = "user_id = ".$userid;
		$query->order = "id desc";
		$query->page  = $page;
		return $query;
	}
	//用户中心-退款记录
	public function getReturnmentDocList($userid){
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('returnment_doc');
		$query->where = "user_id = ".$userid;
		$query->order = "id desc";
		$query->page  = $page;
		return $query;
	}
	//用户中心-提现记录
	public function getWithdrawList($userid){
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('withdraw');
		$query->where = "user_id = ".$userid." and is_del = 0";
		$query->order = "id desc";
		$query->page  = $page;
		return $query;
	}

    //[收藏夹]获取收藏夹数据
	public function getFavorite($userid,$cat = '')
    {
		//获取收藏夹信息
	    $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
	    $cat_id = IFilter::act($cat,'int');

		$favoriteObj = new IQuery("favorite as f");
		$favoriteObj->join  = "left join goods as go on go.id = f.rid";
		$favoriteObj->fields= " f.*,go.name,go.id as goods_id,go.img,go.img_pathtype,go.store_nums,go.sell_price,go.market_price";

		$where = 'user_id = '.$userid;
		$where.= $cat_id ? ' and cat_id = '.$cat_id : "";

		$favoriteObj->where = $where;
		$favoriteObj->page  = $page;
		return $favoriteObj;
    }
}
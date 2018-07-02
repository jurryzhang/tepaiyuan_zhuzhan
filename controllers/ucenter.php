<?php

/**
 * @brief 用户中心模块
 * @class Ucenter
 * @note  前台
 */
class Ucenter extends IController implements userAuthorization {

	public $layout = 'ucenter';

	public function init()
	{

	}

	public function getCarCount()
	{
		$user_id = ISafe::get('user_id');
		$carObj  = new IModel('goods_car');
		$data    = $carObj->getObj('user_id='.$user_id);
		$count   = 0;
		if ($data)
		{
			$car   = JSON::decode(str_replace(array('&', '$'), array('"', ','), $data['content']));
			$goods = isset($car['goods']) ? $car['goods'] : [];
			foreach ($goods as $good)
			{
				$count += $good;
			}
		}

		echo json_encode(['count' => $count]);
	}

	public function index()
	{
		//获取用户基本信息
		$user = Api::run('getMemberInfo', $this->user['user_id']);

		//获取用户各项统计数据
		$statistics = Api::run('getMemberTongJi', $this->user['user_id']);

		//获取用户站内信条数
		$msgObj = new Mess($this->user['user_id']);
		$msgNum = $msgObj->needReadNum();

		//获取用户代金券
		$propIds  = trim($user['prop'], ',');
		$propIds  = $propIds ? $propIds : 0;
		$propData = Api::run('getPropTongJi', $propIds);

		$this->setRenderData(array(
			"user"       => $user,
			"statistics" => $statistics,
			"msgNum"     => $msgNum,
			"propData"   => $propData,
		));

		$this->initPayment();
		$this->redirect('index');
	}

	function user_ico_ajaxUpload()
	{
		$result = array(
			'code' => 0,
		);
		// echo '<pre>';var_dump($_FILES);exit();
		if (isset($_FILES['uploadimg']['name']) && $_FILES['uploadimg']['name'] != '')
		{
			$photoObj = new PhotoUpload();
			$photo    = $photoObj->run();

			if (isset($photo['uploadimg']['img']) && $photo['uploadimg']['img'])
			{
				$user_id   = IReq::get('lookid', 'post');
				$user_obj  = new IModel('user');
				$dataArray = array(
					'head_ico' => $photo['uploadimg']['img'],
				);
				$user_obj->setData($dataArray);
				$where  = 'id = '.$user_id;
				$isSuss = $user_obj->update($where);

				if ($isSuss !== FALSE)
				{
					$result['code'] = 1;
					$result['data'] = IUrl::creatUrl().$photo['uploadimg']['img'];
					ISafe::set('head_ico', $dataArray['head_ico']);
				}
				else
				{
					$result['msg'] = '上传失败';
				}
			}
			else
			{
				$result['msg'] = '上传失败';
			}
		}
		else
		{
			$result['msg'] = '请选择图片';
		}
		echo JSON::encode($result);
	}

	//[用户头像]上传
	function user_ico_upload()
	{
		$result = array(
			'isError' => TRUE,
		);

		if (isset($_FILES['attach']['name']) && $_FILES['attach']['name'] != '')
		{
			$photoObj = new PhotoUpload();
			$photo    = $photoObj->run();

			if (isset($photo['attach']['img']) && $photo['attach']['img'])
			{
				$user_id   = $this->user['user_id'];
				$user_obj  = new IModel('user');
				$dataArray = array(
					'head_ico' => $photo['attach']['img'],
				);
				$user_obj->setData($dataArray);
				$where  = 'id = '.$user_id;
				$isSuss = $user_obj->update($where);

				if ($isSuss !== FALSE)
				{
					$result['isError'] = FALSE;
					$result['data']    = IUrl::creatUrl().$photo['attach']['img'];
					ISafe::set('head_ico', $dataArray['head_ico']);
				}
				else
				{
					$result['message'] = '上传失败';
				}
			}
			else
			{
				$result['message'] = '上传失败';
			}
		}
		else
		{
			$result['message'] = '请选择图片';
		}
		echo '<script type="text/javascript">parent.callback_user_ico('.JSON::encode($result).');</script>';
	}

	/**
	 * @brief 我的订单列表
	 */
	public function order()
	{
		$this->initPayment();
		$status = IReq::get('sid') ? IReq::get('sid') : 0;
		$this->setRenderData(array('status' => $status));
		$this->redirect('order');
	}

	/**
	 * @brief 初始化支付方式
	 */
	private function initPayment()
	{
		$payment         = new IQuery('payment');
		$payment->fields = 'id,name,type';
		$payments        = $payment->find();
		$items           = array();
		foreach ($payments as $pay)
		{
			$items[$pay['id']]['name'] = $pay['name'];
			$items[$pay['id']]['type'] = $pay['type'];
		}
		$this->payments = $items;
	}

	//@brief 订单详情
	public function order_detail()
	{
		$id       = IFilter::act(IReq::get('id'), 'int');
		$omid     = IFilter::act(IReq::get('omid'), 'int');
		$orderObj = new order_class();
		if ($omid)
		{
			$orderObj->auto_refund_order();
			$this->order_info = $orderObj->getOrderMarketShow($omid, $this->user['user_id']);
		}
		else
		{
			if ($id)
			{
				$this->order_info = $orderObj->getOrderShow($id, $this->user['user_id']);
			}
		}
		if ( ! $this->order_info)
		{
			IError::show(403, '订单信息不存在');
		}
		$this->redirect('order_detail', FALSE);
	}

	public function order_status()
	{
		$op         = IFilter::act(IReq::get('op'));
		$id         = IFilter::act(IReq::get('order_id'), 'int');
		$omid       = IFilter::act(IReq::get('omid'), 'int');
		$orderidstr = IFilter::act(IReq::get('orderidstr'));

		$model = new IModel('order');

		switch ($op)
		{
			case "cancel":
				{
					$model->setData(array('status' => 3));
					if ($model->update("id = ".$id." and distribution_status = 0 and status = 1 and user_id = "
						.$this->user['user_id']))
					{
						order_class::resetOrderProp($id);

						// 判断市场订单是否完全取消   如果完全取消更改市场状态
						order_class::updateOrderMStatus($orderidstr, $omid);
					}
				}
				break;
		}
		$this->redirect("order_detail/omid/$omid");
	}

	//操作订单状态
	public function order_status2()
	{
		$op   = IFilter::act(IReq::get('op'));
		$omid = IFilter::act(IReq::get('omid'), 'int');
		// 查询
		$mquery        = new IQuery('order_market');
		$mquery->where = 'id='.$omid;
		$marketD       = $mquery->find();
		// echo '<pre>';var_dump($marketD);exit();

		if ( ! $marketD)
		{
			// echo '4444';exit();
			IError::show(403, '订单信息不存在');
		}
		$orderid_str = $marketD[0]['orderid_str'];

		$mmodel = new IModel('order_market');
		$model  = new IModel('order');
		$fmodel = new IModel('freight');

		switch ($op)
		{
			// 取消
			case "cancel":
				{
					$mmodel->setData(array('status' => 3));
					$mmodel->update("id = ".$omid);

					$model->setData(array('status' => 3));
					if ($model->update("id in(".$orderid_str
						.")  and distribution_status = 0 and status = 1 and user_id = ".$this->user['user_id']))
					{
						// echo $model->getSql();exit;
						$orderidA = explode(',', $orderid_str);
						foreach ($orderidA as $k => $v)
						{
							order_class::resetOrderProp($v);
						}
					}
					// echo '66666666';exit();
				}
				break;
			case "fahuo":
				{
					$mmodel->setData(array('status' => 9));
					$mmodel->update("id = ".$omid);

					// 获取没有修改的商户
					$oquery         = new IQuery('order');
					$oquery->fields = "id";
					$oquery->where  = "id in(".$orderid_str.")  and status=2 and user_id = ".$this->user['user_id'];
					$oqData         = $oquery->find();
					if ($oqData)
					{
						foreach ($oqData as $k => $v)
						{
							// 取消
							$model->setData(array('status' => 3));
							if ($model->update("id=".$v['id']))
							{
								order_class::resetOrderProp($v['id']);
							}
						}
					}
				}
				break;
			case "songda":
				{
					// 送达 没有满足条件错误提示
					// 满足 更新市场订单状态   更新市场订单状态
					$curData = $marketD[0];

					if ($curData['status'] == 9 && $curData['freight_id'] != 0 && $curData['freight_status'] == 3)
					{
						$newtime = date('Y-m-d H:i:s');
						$mmodel->setData(array('status' => 11, 'confirm_sonfda_time' => $newtime));
						$mmodel->update("id = ".$omid);
						// echo $mmodel->getSql().'<br>';

						$model->setData(array('status' => 11, 'confirm_sonfda_time' => $newtime));
						$model->update("id in(".$orderid_str.") and status = 8 and freight_status=3 and user_id = "
							.$this->user['user_id']);
						// echo $model->getSql().'<br>';
						// exit();
						// 获取实际可获得的运费
						
						$ogDB = new IQuery('order_goods as og'); //goods是表名，这里不需要加前缀
						$ogDB->where = "`og`.`order_id` IN ('".$orderid_str."') AND `o`.`status` <> 12";
						$ogDB->fields = "sum(og.goods_nums * og.goods_weight) AS weight";
						$ogDB->join = "LEFT JOIN `iwebshop_order` `o` ON `o`.`id` = `og`.`order_id`";
						$ogDB->group = "og.order_id";
						$ogData = $ogDB->find(); //find方法就是执行查询，返回的是一个数组

						$weight=$ogData[0]['weight'];
						$juli=$curData['juli'];

						$yunfei=round((($juli/1000)*$this->_siteConfig->freight_rate_value),2)*($weight/1000);

						// 将运费加入到特派员余额中
						$freight_id = $curData['freight_id'];
						$fRow       = $fmodel->getObj('id='.$freight_id);
						// echo '<pre>';var_dump($fRow);
						// echo (float)$fRow['amount'].'------------'.(float)$curData['yunfei'];
						// $amount = (float) $fRow['amount'] + (float) $curData['yunfei'];
						$amount = (float) $fRow['amount'] + (float) $yunfei;
						// var_dump($amount);
						$fmodel->setData(array('amount' => $amount));
						$fmodel->update("id = ".$freight_id);
						// echo $fmodel->getSql();exit();
						
						//生成记录
						$paylogdata=array(
							'add_time' => time(),
							'trade_type'=> 3,//平台
							'body' => '配送订单'.$curData['ordermarket_no'].'获取运费',
							'total_fee' => $yunfei,
							'out_trade_no'=> $curData['ordermarket_no'],
							'spbill_create_ip'  => $_SERVER['REMOTE_ADDR'],
							'type' => 5,//平台增加
							'uid' => $freight_id,
							'status' => 1,
							'cur_amount' => $amount
						);
						$fpaylogObj = new IModel('freight_paylog');
						$fpaylogObj->setData($paylogdata);
						$fpaylogObj->add();

					}
					else
					{
						IError::show(403, '暂不满足操作要求');
					}
					// exit();
				}
				break;

			case "confirm":
				{
					$mmodel->setData(array('status' => 5, 'completion_time' => ITime::getDateTime()));
					$mmodel->update("id = ".$omid);

					// 最终确认 获取可改变状态的订单
					$oquery         = new IQuery('order');
					$oquery->fields = "id";
					$oquery->where  = "id in(".$orderid_str.")  and status=11 and user_id = ".$this->user['user_id'];
					$oqData         = $oquery->find();
					// echo '<pre>';var_dump($oqData);
					if ($oqData)
					{
						foreach ($oqData as $k => $v)
						{
							// 改变状态  并增加评论机会
							$model->setData(array('status' => 5));
							if ($model->update("id=".$v['id']))
							{
								// echo '333333';
								// echo $v['id'].'--------'.$omid.'<br>';
								//增加用户评论商品机会
								Order_Class::addGoodsCommentChange($v['id'], $omid);
							}
						}
						//确认收货以后直接跳转到评论页面
						// $this->redirect("evaluation");
					}
				}
				break;
		}
		// exit();
		$this->redirect("order_detail/omid/$omid");
	}

	/**
	 * @brief 我的地址
	 */
	public function address()
	{
		//取得自己的地址
		$query        = new IQuery('address');
		$query->where = 'user_id = '.$this->user['user_id'];
		$query->order = 'id desc';
		$address      = $query->find();
		$areas        = array();

		if ($address)
		{
			foreach ($address as $ad)
			{
				$temp = area::name($ad['province'], $ad['city'], $ad['area']);
				if (isset($temp[$ad['province']]) && isset($temp[$ad['city']]) && isset($temp[$ad['area']]))
				{
					$areas[$ad['province']] = $temp[$ad['province']];
					$areas[$ad['city']]     = $temp[$ad['city']];
					$areas[$ad['area']]     = $temp[$ad['area']];
				}
			}
		}

		$this->areas   = $areas;
		$this->address = $address;
		$this->redirect('address');
	}

	/**
	 * @brief 收货地址管理
	 */
	public function address_edit()
	{
		$id          = IFilter::act(IReq::get('id'), 'int');
		$accept_name = IFilter::act(IReq::get('accept_name'), 'name');
		$province    = IFilter::act(IReq::get('province'), 'int');
		$city        = IFilter::act(IReq::get('city'), 'int');
		$area        = IFilter::act(IReq::get('area'), 'int');
		$address     = IFilter::act(IReq::get('address'));
		$zip         = IFilter::act(IReq::get('zip'), 'zip');
		$telphone    = IFilter::act(IReq::get('telphone'), 'phone');
		$mobile      = IFilter::act(IReq::get('mobile'), 'mobile');
		$default     = IReq::get('is_default') != 1 ? 0 : 1;
		$user_id     = $this->user['user_id'];

		$model = new IModel('address');
		$data  = array('user_id' => $user_id, 'accept_name' => $accept_name, 'province' => $province, 'city' => $city, 'area' => $area, 'address' => $address, 'zip' => $zip, 'telphone' => $telphone, 'mobile' => $mobile, 'is_default' => $default);

		//如果设置为首选地址则把其余的都取消首选
		if ($default == 1)
		{
			$model->setData(array('is_default' => 0));
			$model->update("user_id = ".$this->user['user_id']);
		}

		$model->setData($data);

		if ($id == '')
		{
			$model->add();
		}
		else
		{
			$model->update('id = '.$id);
		}
		$this->redirect('address');
	}

	/**
	 * @brief 收货地址删除处理
	 */
	public function address_del()
	{
		$id    = IFilter::act(IReq::get('id'), 'int');
		$model = new IModel('address');
		$model->del('id = '.$id.' and user_id = '.$this->user['user_id']);
		$this->redirect('address');
	}

	/**
	 * @brief 设置默认的收货地址
	 */
	public function address_default()
	{
		$id      = IFilter::act(IReq::get('id'), 'int');
		$default = IFilter::act(IReq::get('is_default'));
		$model   = new IModel('address');
		if ($default == 1)
		{
			$model->setData(array('is_default' => 0));
			$model->update("user_id = ".$this->user['user_id']);
		}
		$model->setData(array('is_default' => $default));
		$model->update("id = ".$id." and user_id = ".$this->user['user_id']);
		$this->redirect('address');
	}

	/**
	 * @brief 退款申请页面
	 */
	public function refunds_update()
	{
		$order_id   = IFilter::act(IReq::get('order_id'), 'int');
		$user_id    = $this->user['user_id'];
		$content    = IFilter::act(IReq::get('content'), 'text');
		$omid       = IFilter::act(IReq::get('omid'), 'int');
		$orderidstr = IFilter::act(IReq::get('orderidstr'));
		$message    = '';
		// echo '<pre>';var_dump($_POST);exit();
		if ($user_id <= 0)
		{
			$message = "请先登录";
			$this->redirect('login', FALSE);
			Util::showMessage($message);
		}

		if ( ! $content)
		{
			$message = "请填写退款理由";
			$this->redirect('refunds', FALSE);
			Util::showMessage($message);
		}

		$orderDB  = new IModel('order');
		$orderRow = $orderDB->getObj("id = ".$order_id." and user_id = ".$user_id);

		$ogDB           = new IQuery('order_goods');
		$ogDB->where    = "order_id = ".$order_id;
		$ogDB->fields   = 'id';
		$ogData         = $ogDB->find();
		$order_goods_id = array();
		foreach ($ogData as $k => $val)
		{
			$order_goods_id[] = $val['id'];
		}

		$refundResult = Order_Class::isRefundmentApply($orderRow, $order_goods_id);

		//判断退款申请是否有效
		if ($refundResult === TRUE)
		{
			//退款单数据
			$updateData = array(
				'order_no'       => $orderRow['order_no'],
				'order_id'       => $order_id,
				'user_id'        => $user_id,
				'time'           => ITime::getDateTime(),
				'content'        => $content,
				'seller_id'      => $orderRow['seller_id'],
				'order_goods_id' => join(",", $order_goods_id),
			);

			//写入数据库
			$refundsDB = new IModel('refundment_doc');
			$refundsDB->setData($updateData);
			$refundsDB->add();
			// echo $refundsDB->getSql().'<br>';

			$orderDB->setData(array('status' => 3));
			$orderDB->update('id='.$order_id);

			// 判断市场订单是否完全取消   如果完全取消或者拒绝获取确定更改市场状态
			order_class::updateOrderMStatus($orderidstr, $omid);
			// exit();
			$this->redirect('refunds');
		}
		else
		{
			$message = $refundResult;
			$this->redirect('refunds', FALSE);
			Util::showMessage($message);
		}
	}

	public function refunds_update2()
	{

		$omid    = IFilter::act(IReq::get('omid'), 'int');
		$user_id = $this->user['user_id'];
		$content = IFilter::act(IReq::get('content'), 'text');
		$message = '';
		if ($user_id <= 0)
		{
			$message = "请先登录";
			$this->redirect('login', FALSE);
			Util::showMessage($message);
		}

		if ( ! $content)
		{
			$message = "请填写退款理由";
			$this->redirect('refunds', FALSE);
			Util::showMessage($message);
		}
		$omDB     = new IModel('order_market');
		$omRow    = $omDB->getObj("id = ".$omid." and uid = ".$user_id);
		$orderidA = explode(',', $omRow['orderid_str']);
		$i        = 0;
		foreach ($orderidA as $k => $v)
		{
			$order_id = $v;
			$orderDB  = new IModel('order');
			$orderRow = $orderDB->getObj("id = ".$order_id." and user_id = ".$user_id);

			$ogDB           = new IQuery('order_goods');
			$ogDB->where    = "order_id = ".$order_id;
			$ogDB->fields   = 'id';
			$ogData         = $ogDB->find();
			$order_goods_id = array();
			foreach ($ogData as $k => $val)
			{
				$order_goods_id[] = $val['id'];
			}

			// 获取当前订单下的所有商品
			$refundResult = Order_Class::isRefundmentApply($orderRow, $order_goods_id);

			//判断退款申请是否有效
			if ($refundResult === TRUE)
			{
				//退款单数据
				$updateData = array(
					'order_no'       => $orderRow['order_no'],
					'order_id'       => $order_id,
					'user_id'        => $user_id,
					'time'           => ITime::getDateTime(),
					'content'        => $content,
					'seller_id'      => $orderRow['seller_id'],
					'order_goods_id' => join(",", $order_goods_id),
				);

				// 写入数据库
				$refundsDB = new IModel('refundment_doc');
				$refundsDB->setData($updateData);
				$refundsDB->add();

				// 将订单状态修改为取消
				$orderDB->setData(array('status' => 3));
				$orderDB->update('id='.$order_id);
				// echo $orderDB->getSql();
				if ($i == 0)
				{
					$omDB->setData(array('status' => 3));
					$omDB->update('id='.$omid);
					// echo $omDB->getSql();
				}
			}
			else
			{
				$message = $refundResult;
				$this->redirect('refunds', FALSE);
				Util::showMessage($message);
			}
			$i ++;
		}
		// exit();
		$this->redirect('refunds');
	}

	/**
	 * @brief 退款申请删除
	 */
	public function refunds_del()
	{
		$id    = IFilter::act(IReq::get('id'), 'int');
		$model = new IModel("refundment_doc");
		$model->del("id = ".$id." and user_id = ".$this->user['user_id']);
		$this->redirect('refunds');
	}

	/**
	 * @brief 查看退款申请详情
	 */
	public function refunds_detail()
	{
		$id        = IFilter::act(IReq::get('id'), 'int');
		$refundDB  = new IModel("refundment_doc");
		$refundRow = $refundDB->getObj("id = ".$id." and user_id = ".$this->user['user_id']);
		if ($refundRow)
		{
			//获取商品信息
			$orderGoodsDB   = new IModel('order_goods');
			$orderGoodsList = $orderGoodsDB->query("id in (".$refundRow['order_goods_id'].")");
			if ($orderGoodsList)
			{
				$refundRow['goods'] = $orderGoodsList;
				$this->data         = $refundRow;
			}
			else
			{
				$this->redirect('refunds', FALSE);
				Util::showMessage("没有找到要退款的商品");
			}
			$this->redirect('refunds_detail');
		}
		else
		{
			$this->redirect('refunds', FALSE);
			Util::showMessage("退款信息不存在");
		}
	}

	/**
	 * @brief 退款申请
	 * 部分商户订单取消
	 */
	public function refunds_edit()
	{
		$omid       = IFilter::act(IReq::get('omid'), 'int');
		$orderidstr = IFilter::act(IReq::get('orderidstr'));
		$order_id   = IFilter::act(IReq::get('order_id'), 'int');

		if ($order_id)
		{
			$orderDB  = new IModel('order');
			$orderRow = $orderDB->getObj('id = '.$order_id.' and user_id = '.$this->user['user_id']);
			if ($orderRow)
			{
				$this->orderidstr = $orderidstr;
				$this->omid       = $omid;
				$this->orderRow   = $orderRow;
				$this->redirect('refunds_edit');

				return;
			}
		}
		$this->redirect('refunds');
	}

	// 市场订单取消
	public function refunds_edit2()
	{
		$omid  = IFilter::act(IReq::get('omid'), 'int');
		$omDB  = new IModel('order_market');
		$omRow = $omDB->getObj('id = '.$omid.' and uid = '.$this->user['user_id']);
		// echo '<pre>';var_dump($omRow);exit;

		if ($omRow)
		{
			$this->omRow = $omRow;
			$this->redirect('refunds_edit2');

			return;
		}
		$this->redirect('refunds');
	}

	/**
	 * @brief 申请退货退款
	 */
	public function return_goods_edit()
	{
		$omid = IFilter::act(IReq::get('omid'), 'int');
		if ($omid)
		{
			$omDB = new IModel('order_market');
			// 只能在有效状态内退款
			$omRow = $omDB->getObj('id = '.$omid.' and uid = '.$this->user['user_id']
				.' and status in (9,11,5) and freight_status=3 and status!=5');
			if ($omRow)
			{
				$this->omRow = $omRow;
				$this->redirect('return_goods_edit');

				return;
			}
			else
			{
				$this->redirect('return_goods', FALSE);
				Util::showMessage("当前订单暂不符合退款退货状态");
			}
		}
		$this->redirect('return_goods');
	}

	//退货退款处理
	public function return_goods_update()
	{
		// echo '<pre>';var_dump($_POST);
		$omid        = IFilter::act(IReq::get('omid'), 'int');
		$user_id     = $this->user['user_id'];
		$content     = IFilter::act(IReq::get('content'), 'text');
		$return_type = IFilter::act(IReq::get('return_type'), 'int');
		$message     = '';
		if ( ! $content)
		{
			$message = "请填写退款理由";
			$this->redirect('return_goods', FALSE);
			Util::showMessage($message);
		}

		$omDB      = new IModel('order_market');
		$orderDB   = new IModel('order');
		$refundsDB = new IModel('returnment_doc');
		$ogDB      = new IModel('order_goods');

		$omRow       = $omDB->getObj("id = ".$omid." and uid = ".$user_id);
		$orderid_str = $omRow['orderid_str'];
		$orderidA    = explode(',', $orderid_str);
		foreach ($orderidA as $k => $v)
		{
			$order_id       = $v;
			$orderRow       = $orderDB->getObj("id = ".$order_id." and user_id = ".$user_id);
			$order_goods_id = IFilter::act(IReq::get('order_goods_'.$order_id.'_id'), 'int');
			// echo '<pre>';var_dump($order_goods_id);exit();
			if ($order_goods_id)
			{
				// 获取当前订单下的所有商品
				$refundResult = Order_Class::isRefundmentApply($orderRow, $order_goods_id);
				// var_dump($refundResult);exit();

				//判断退款申请是否有效
				if ($refundResult === TRUE)
				{
					//退款单数据
					$updateData = array(
						'order_no'       => $orderRow['order_no'],
						'order_id'       => $order_id,
						'user_id'        => $user_id,
						'time'           => ITime::getDateTime(),
						'content'        => $content,
						'return_type'    => $return_type,
						'seller_id'      => $orderRow['seller_id'],
						'order_goods_id' => join(",", $order_goods_id),
					);
					if ($k == 0)
					{
						$updateData['amount'] = $omRow['order_amount'] + $omRow['yunfei'];
					}

					//写入数据库
					$refundsDB->setData($updateData);
					$refundsDB->add();

					// 更新订单商品为退货

					$ogDB->setData(array('is_send' => 2));
					$ogDB->update('order_id='.$order_id.' and goods_id in ( '.join(",", $order_goods_id).' )');
					// echo $ogDB->getSql();

					//查询是否退货完毕
					$isSendData  = $ogDB->getObj('order_id = '.$order_id.' and is_send != 2');
					$orderStatus = 6;//全部退款
					if ($isSendData)
					{
						$orderStatus = 7;//部分退款
					}
					$orderDB->setData(array('status' => $orderStatus));
					$orderDB->update('id='.$order_id);
				}
				else
				{
					$message = $refundResult;
					$this->redirect('return_goods', FALSE);
					Util::showMessage($message);
				}
			}
		}

		//查询是否退货完毕
		$isSendData2 = $orderDB->getObj('id in ( '.$orderid_str.' )  and status =7');
		$omStatus    = 6;//全部退款
		if ($isSendData2)
		{
			$omStatus = 7;//部分退款
		}
		$omDB->setData(array('status' => $orderStatus));
		$omDB->update('id='.$omid);
		// echo $omDB->getSql();exit;
		$this->redirect('return_goods');
	}

	/**
	 * @brief 查看退货退款申请详情
	 */
	public function return_goods_detail()
	{
		$id        = IFilter::act(IReq::get('id'), 'int');
		$refundDB  = new IModel("returnment_doc");
		$refundRow = $refundDB->getObj("id = ".$id." and user_id = ".$this->user['user_id']);
		if ($refundRow)
		{
			//获取商品信息
			$orderGoodsDB   = new IModel('order_goods');
			$orderGoodsList = $orderGoodsDB->query("order_id=".$refundRow['order_id']." and goods_id in ("
				.$refundRow['order_goods_id'].")");
			if ($orderGoodsList)
			{
				$refundRow['goods'] = $orderGoodsList;
				$this->data         = $refundRow;
			}
			else
			{
				$this->redirect('return_goods', FALSE);
				Util::showMessage("没有找到要退货退款的商品");
			}
			$this->redirect('return_goods_detail');
		}
		else
		{
			$this->redirect('return_goods', FALSE);
			Util::showMessage("退货退款信息不存在");
		}
	}

	/**
	 * @brief 退货退款申请删除
	 */
	public function return_goods_del()
	{
		$id    = IFilter::act(IReq::get('id'), 'int');
		$model = new IModel("returnment_doc");
		$model->del("id = ".$id." and user_id = ".$this->user['user_id']);
		$this->redirect('return_goods');
	}

	/**
	 * @brief 建议中心
	 */
	public function complain_edit()
	{
		$id      = IFilter::act(IReq::get('id'), 'int');
		$title   = IFilter::act(IReq::get('title'), 'string');
		$content = IFilter::act(IReq::get('content'), 'string');
		$user_id = $this->user['user_id'];
		$model   = new IModel('suggestion');
		$model->setData(array('user_id' => $user_id, 'title' => $title, 'content' => $content, 'time' => ITime::getDateTime()));
		if ($id == '')
		{
			$model->add();
		}
		else
		{
			$model->update('id = '.$id.' and user_id = '.$this->user['user_id']);
		}
		$this->redirect('complain');
	}

	//站内消息
	public function message()
	{
		$msgObj = new Mess($this->user['user_id']);
		$msgIds = $msgObj->getAllMsgIds();
		$msgIds = $msgIds ? $msgIds : 0;
		$this->setRenderData(array('msgIds' => $msgIds, 'msgObj' => $msgObj));
		$this->redirect('message');
	}

	/**
	 * @brief 删除消息
	 *
	 * @param int $id 消息ID
	 */
	public function message_del()
	{
		$id  = IFilter::act(IReq::get('id'), 'int');
		$msg = new Mess($this->user['user_id']);
		$msg->delMessage($id);
		$this->redirect('message');
	}

	public function message_read()
	{
		$id  = IFilter::act(IReq::get('id'), 'int');
		$msg = new Mess($this->user['user_id']);
		echo $msg->writeMessage($id, 1);
	}

	//[修改密码]修改动作
	function password_edit()
	{
		$user_id = $this->user['user_id'];

		$fpassword  = IReq::get('fpassword');
		$password   = IReq::get('password');
		$repassword = IReq::get('repassword');

		$userObj = new IModel('user');
		$where   = 'id = '.$user_id;
		$userRow = $userObj->getObj($where);

		if ( ! preg_match('|\w{6,32}|', $password))
		{
			$message = '密码格式不正确，请重新输入';
		}
		else
		{
			if ($password != $repassword)
			{
				$message = '二次密码输入的不一致，请重新输入';
			}
			else
			{
				if (md5($fpassword) != $userRow['password'])
				{
					$message = '原始密码输入错误';
				}
				else
				{
					$passwordMd5 = md5($password);
					$dataArray   = array(
						'password' => $passwordMd5,
					);

					$userObj->setData($dataArray);
					$result = $userObj->update($where);
					if ($result)
					{
						ISafe::set('user_pwd', $passwordMd5);
						$message = '密码修改成功';
					}
					else
					{
						$message = '密码修改失败';
					}
				}
			}
		}

		$this->redirect('password', FALSE);
		Util::showMessage($message);
	}

	//[个人资料]展示 单页
	function info()
	{
		$user_id = $this->user['user_id'];

		$userObj       = new IModel('user');
		$where         = 'id = '.$user_id;
		$this->userRow = $userObj->getObj($where);

		$memberObj       = new IModel('member');
		$where           = 'user_id = '.$user_id;
		$this->memberRow = $memberObj->getObj($where);
		$this->redirect('info');
	}

	//[个人资料] 修改 [动作]
	function info_edit_act()
	{
		// echo '<pre>';var_dump($_POST);exit();
		$email = IFilter::act(IReq::get('email'), 'string');
		// $mobile    = IFilter::act( IReq::get('mobile'),'string');

		$user_id   = $this->user['user_id'];
		$memberObj = new IModel('member');
		$where     = 'user_id = '.$user_id;

		if ($email)
		{
			$memberRow = $memberObj->getObj('user_id != '.$user_id.' and email = "'.$email.'"');
			if ($memberRow)
			{
				IError::show('邮箱已经被注册');
			}
		}

		// if($mobile)
		// {
		//  $memberRow = $memberObj->getObj('user_id != '.$user_id.' and mobile = "'.$mobile.'"');
		//  if($memberRow)
		//  {
		//      IError::show('手机已经被注册');
		//  }
		// }

		//地区
		// $province = IFilter::act( IReq::get('province','post') ,'string');
		// $city     = IFilter::act( IReq::get('city','post') ,'string' );
		//    $area     = IFilter::act( IReq::get('area','post') ,'string' );
		// $address     = IFilter::act( IReq::get('address','post') ,'string' );

		// $dataArray       = array(
		//  'email'        => $email,
		//  'true_name'    => IFilter::act( IReq::get('true_name') ,'string'),
		//  'sex'          => IFilter::act( IReq::get('sex'),'int' ),
		//  'birthday'     => IFilter::act( IReq::get('birthday') ),
		//  'zip'          => IFilter::act( IReq::get('zip') ,'string' ),
		//  'qq'           => IFilter::act( IReq::get('qq') , 'string' ),
		//  'contact_addr' => IFilter::act( IReq::get('contact_addr'), 'string'),
		//  'mobile'       => $mobile,
		//        'telephone'    => IFilter::act( IReq::get('telephone'),'string'),
		//        'province'     => $province,
		//  'city'         => $city,
		//        'area'         => $area,
		//  'address'         => $address,
		// );

		$dataArray = array(
			'email' => $email,
		);

		$memberObj->setData($dataArray);
		$memberObj->update($where);
		$this->info();
		$this->redirect('index');
	}

	//[账户余额] 展示[单页]
	function withdraw()
	{
		$user_id = $this->user['user_id'];

		$memberObj       = new IModel('member', 'balance');
		$where           = 'user_id = '.$user_id;
		$this->memberRow = $memberObj->getObj($where);
		$this->redirect('withdraw');
	}

	//[账户余额] 提现动作
	function withdraw_act()
	{
		$user_id  = $this->user['user_id'];
		$amount   = IFilter::act(IReq::get('amount', 'post'), 'float');
		$redirect = IReq::get('redirect', 'post');
		$message  = '';

		$dataArray = array(
			'name'    => IFilter::act(IReq::get('name', 'post'), 'string'),
			'note'    => IFilter::act(IReq::get('note', 'post'), 'string'),
			'amount'  => $amount,
			'user_id' => $user_id,
			'time'    => ITime::getDateTime(),
		);

		$mixAmount = 0;
		$memberObj = new IModel('member');
		$where     = 'user_id = '.$user_id;
		$memberRow = $memberObj->getObj($where, 'balance');

		//提现金额范围
		if ($amount <= $mixAmount)
		{
			$message = '提现的金额必须大于'.$mixAmount.'元';
		}
		else
		{
			if ($amount > $memberRow['balance'])
			{
				$message = '提现的金额不能大于您的帐户余额';
			}
			else
			{
				$obj = new IModel('withdraw');
				$obj->setData($dataArray);
				$obj->add();
				if (isset($redirect) && $redirect != '')
				{
					$this->redirect($redirect);
				}
				else
				{
					$this->redirect('withdraw');
				}
			}
		}

		if ($message != '')
		{
			$this->memberRow   = array('balance' => $memberRow['balance']);
			$this->withdrawRow = $dataArray;
			$this->redirect('withdraw', FALSE);
			Util::showMessage($message);
		}
	}

	//[账户余额] 提现详情
	function withdraw_detail()
	{
		$user_id = $this->user['user_id'];

		$id                = IFilter::act(IReq::get('id'), 'int');
		$obj               = new IModel('withdraw');
		$where             = 'id = '.$id.' and user_id = '.$user_id;
		$this->withdrawRow = $obj->getObj($where);
		$this->redirect('withdraw_detail');
	}

	//[提现申请] 取消
	function withdraw_del()
	{
		$id = IFilter::act(IReq::get('id'), 'int');
		if ($id)
		{
			$dataArray   = array('is_del' => 1);
			$withdrawObj = new IModel('withdraw');
			$where       = 'id = '.$id.' and user_id = '.$this->user['user_id'];
			$withdrawObj->setData($dataArray);
			$withdrawObj->update($where);
		}
		$this->redirect('withdraw');
	}

	function account()
	{
		$user_id = $this->user['user_id'];

		$memberObj       = new IModel('member');
		$where           = 'user_id = '.$user_id;
		$this->memberRow = $memberObj->getObj($where);

		$this->redirect('account');
	}

	//[余额交易记录]
	function account_log()
	{
		$user_id = $this->user['user_id'];

		$memberObj       = new IModel('member');
		$where           = 'user_id = '.$user_id;
		$this->memberRow = $memberObj->getObj($where);
		$this->redirect('account_log');
	}

	//[收藏夹]备注信息
	function edit_summary()
	{
		$user_id = $this->user['user_id'];

		$id      = IFilter::act(IReq::get('id'), 'int');
		$summary = IFilter::act(IReq::get('summary'), 'string');

		//ajax返回结果
		$result = array(
			'isError' => TRUE,
		);

		if ( ! $id)
		{
			$result['message'] = '收藏夹ID值丢失';
		}
		else
		{
			if ( ! $summary)
			{
				$result['message'] = '请填写正确的备注信息';
			}
			else
			{
				$favoriteObj = new IModel('favorite');
				$where       = 'id = '.$id.' and user_id = '.$user_id;

				$dataArray = array(
					'summary' => $summary,
				);

				$favoriteObj->setData($dataArray);
				$is_success = $favoriteObj->update($where);

				if ($is_success === FALSE)
				{
					$result['message'] = '更新信息错误';
				}
				else
				{
					$result['isError'] = FALSE;
				}
			}
		}
		echo JSON::encode($result);
	}

	//[收藏夹]删除
	function favorite_del()
	{
		$user_id = $this->user['user_id'];
		$id      = IReq::get('id');

		if ( ! empty($id))
		{
			$id = IFilter::act($id, 'int');

			$favoriteObj = new IModel('favorite');

			if (is_array($id))
			{
				$idStr = join(',', $id);
				$where = 'user_id = '.$user_id.' and id in ('.$idStr.')';
			}
			else
			{
				$where = 'user_id = '.$user_id.' and id = '.$id;
			}

			$favoriteObj->del($where);
			$this->redirect('favorite');
		}
		else
		{
			$this->redirect('favorite', FALSE);
			Util::showMessage('请选择要删除的数据');
		}
	}

	//[我的积分] 单页展示
	function integral()
	{
		/*获取积分增减的记录日期时间段*/
		$this->historyTime = IFilter::string(IReq::get('history_time', 'post'));
		$defaultMonth      = 3;//默认查找最近3个月内的记录

		$lastStamp = ITime::getTime(ITime::getNow('Y-m-d')) - (3600 * 24 * 30 * $defaultMonth);
		$lastTime  = ITime::getDateTime('Y-m-d', $lastStamp);

		if ($this->historyTime != NULL && $this->historyTime != 'default')
		{
			$historyStamp     = ITime::getDateTime('Y-m-d', ($lastStamp - (3600 * 24 * 30 * $this->historyTime)));
			$this->c_datetime = 'datetime >= "'.$historyStamp.'" and datetime < "'.$lastTime.'"';
		}
		else
		{
			$this->c_datetime = 'datetime >= "'.$lastTime.'"';
		}

		$memberObj       = new IModel('member');
		$where           = 'user_id = '.$this->user['user_id'];
		$this->memberRow = $memberObj->getObj($where, 'point');
		$this->redirect('integral', FALSE);
	}

	//[我的积分]积分兑换代金券 动作
	function trade_ticket()
	{
		$ticketId = IFilter::act(IReq::get('ticket_id', 'post'), 'int');
		$message  = '';
		if (intval($ticketId) == 0)
		{
			$message = '请选择要兑换的代金券';
		}
		else
		{
			$nowTime   = ITime::getDateTime();
			$ticketObj = new IModel('ticket');
			$ticketRow = $ticketObj->getObj('id = '.$ticketId.' and point > 0 and start_time <= "'.$nowTime
				.'" and end_time > "'.$nowTime.'"');
			if (empty($ticketRow))
			{
				$message = '对不起，此代金券不能兑换';
			}
			else
			{
				$memberObj = new IModel('member');
				$where     = 'user_id = '.$this->user['user_id'];
				$memberRow = $memberObj->getObj($where, 'point');

				if ($ticketRow['point'] > $memberRow['point'])
				{
					$message = '对不起，您的积分不足，不能兑换此类代金券';
				}
				else
				{
					//生成红包
					$dataArray = array(
						'condition'  => $ticketRow['id'],
						'name'       => $ticketRow['name'],
						'card_name'  => 'T'.IHash::random(8),
						'card_pwd'   => IHash::random(8),
						'value'      => $ticketRow['value'],
						'start_time' => $ticketRow['start_time'],
						'end_time'   => $ticketRow['end_time'],
						'is_send'    => 1,
					);
					$propObj   = new IModel('prop');
					$propObj->setData($dataArray);
					$insert_id = $propObj->add();

					//更新用户prop字段
					$memberArray = array('prop' => "CONCAT(IFNULL(prop,''),'{$insert_id},')");
					$memberObj->setData($memberArray);
					$result = $memberObj->update('user_id = '.$this->user["user_id"], 'prop');

					//代金券成功
					if ($result)
					{
						$pointConfig = array(
							'user_id' => $this->user['user_id'],
							'point'   => '-'.$ticketRow['point'],
							'log'     => '积分兑换代金券，扣除了 -'.$ticketRow['point'].'积分',
						);
						$pointObj    = new Point;
						$pointObj->update($pointConfig);
					}
				}
			}
		}

		//展示
		if ($message != '')
		{
			$this->integral();
			Util::showMessage($message);
		}
		else
		{
			$this->redirect('redpacket');
		}
	}

	/**
	 * 余额付款
	 * T:支付失败;
	 * F:支付成功;
	 */
	function payment_balance()
	{
		$urlStr  = '';
		$user_id = intval($this->user['user_id']);

		$return['attach']     = IReq::get('attach');
		$return['total_fee']  = IReq::get('total_fee');
		$return['order_no']   = IReq::get('order_no');
		$return['return_url'] = IReq::get('return_url');
		$sign                 = IReq::get('sign');
		if (stripos($return['order_no'], 'recharge') !== FALSE)
		{
			IError::show(403, '余额支付方式不能用于在线充值');
		}

		if (floatval($return['total_fee']) < 0 || $return['order_no'] == '' || $return['return_url'] == ''
			|| $return['attach'] == '')
		{
			IError::show(403, '支付参数不正确');
		}

		$paymentDB  = new IModel('payment');
		$paymentRow = $paymentDB->getObj('class_name = "balance" ');
		$pkey       = Payment::getConfigParam($paymentRow['id'], 'M_PartnerKey');

		//md5校验
		ksort($return);
		foreach ($return as $key => $val)
		{
			$urlStr .= $key.'='.urlencode($val).'&';
		}

		$encryptKey = isset(IWeb::$app->config['encryptKey']) ? IWeb::$app->config['encryptKey'] : 'iwebshop';
		$urlStr     .= $pkey.$encryptKey;
		if ($sign != md5($urlStr))
		{
			IError::show(403, '数据校验不正确');
		}

		$memberObj = new IModel('member');
		$memberRow = $memberObj->getObj('user_id = '.$user_id);

		if (empty($memberRow))
		{
			IError::show(403, '用户信息不存在');
		}

		if ($memberRow['balance'] < $return['total_fee'])
		{
			IError::show(403, '账户余额不足');
		}

		//检查订单状态
		$orderObj = new IModel('order');
		$orderRow = $orderObj->getObj('order_no  = "'.$return['order_no']
			.'" and pay_status = 0 and status = 1 and user_id = '.$user_id);
		if ( ! $orderRow)
		{
			IError::show(403, '订单号【'.$return['order_no'].'】已经被处理过，请查看订单状态');
		}

		//扣除余额并且记录日志
		$logObj     = new AccountLog();
		$config     = array(
			'user_id'  => $user_id,
			'event'    => 'pay',
			'num'      => $return['total_fee'],
			'order_no' => str_replace("_", ",", $return['attach']),
		);
		$is_success = $logObj->write($config);
		if ( ! $is_success)
		{
			IError::show(403, $logObj->error ? $logObj->error : '用户余额更新失败');
		}

		$return['is_success'] = $is_success ? 'T' : 'F';
		ksort($return);

		//返还的URL地址
		$responseUrl = '';
		foreach ($return as $key => $val)
		{
			$responseUrl .= $key.'='.urlencode($val).'&';
		}

		$returnUrl = urldecode($return['return_url']);
		if (stripos($returnUrl, '?') === FALSE)
		{
			$returnJumpUrl = $returnUrl.'?'.$responseUrl;
		}
		else
		{
			$returnJumpUrl = $returnUrl.'&'.$responseUrl;
		}

		//计算要发送的md5校验
		$encryptKey = isset(IWeb::$app->config['encryptKey']) ? IWeb::$app->config['encryptKey'] : 'iwebshop';
		$urlStrMD5  = md5($responseUrl.$pkey.$encryptKey);

		//拼接进返还的URL中
		$returnJumpUrl .= 'sign='.$urlStrMD5;

		//同步通知
		header('location:'.$returnJumpUrl);
	}

	//我的代金券
	function redpacket()
	{
		$member_info = Api::run('getMemberInfo', $this->user['user_id']);
		$propIds     = trim($member_info['prop'], ',');
		$propIds     = $propIds ? $propIds : 0;
		$this->setRenderData(array('propId' => $propIds));
		$this->redirect('redpacket');
	}
}
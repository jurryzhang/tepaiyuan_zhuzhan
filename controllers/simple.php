<?php
/**
 * @copyright Copyright(c) 2011 aircheng.com
 * @file      Simple.php
 * @brief
 * @author    webning
 * @date      2011-03-22
 * @version   0.6
 * @note
 */

/**
 * @brief Simple
 * @class Simple
 * @note
 */
class Simple extends IController {

	public $layout = 'site_mini';

	function init()
	{
	}

	function login()
	{
		//如果已经登录，就跳到ucenter页面
		if ($this->user)
		{
			$this->redirect("/ucenter/index");
		}
		else
		{
			$this->redirect('login');
		}
	}

	//退出登录
	function logout()
	{
		plugin::trigger('clearUser');
		$this->redirect('login');
	}

	function regshow()
	{
		$this->redirect('regshow');
	}

	// 重新用户注册（ajax方式）
	function regAct()
	{
		$seller_name   = IFilter::act(IReq::get('seller_name', 'post'));
		$person_charge = IFilter::act(IReq::get('person_charge', 'post'));
		$phone         = IFilter::act(IReq::get('phone', 'post'));
		$mobile        = IFilter::act(IReq::get('mobile', 'post'));
		$true_name     = IFilter::act(IReq::get('true_name', 'post'));
		$tax_number    = IFilter::act(IReq::get('tax_number', 'post'));

		$province = IFilter::act(IReq::get('province', 'post'));
		$city     = IFilter::act(IReq::get('city', 'post'));
		$area     = IFilter::act(IReq::get('area', 'post'));
		$address  = IFilter::act(IReq::get('address', 'post'));

		$hours_start = IFilter::act(IReq::get('hours_start', 'post'));
		$hours_end   = IFilter::act(IReq::get('hours_end', 'post'));
		$agree       = IFilter::act(IReq::get('agree', 'post'));
		$agree       = ($agree == 'on') ? 1 : 0;
		if (empty($seller_name))
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('餐厅名称不能为空');
		}
		if (empty($person_charge))
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('负责人不能为空');
		}
		if (empty($mobile))
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('手机号不能为空');
		}

		//手机号验证
		if (IValidate::mobi($mobile) == FALSE)
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('手机号格式不正确');
		}
		if (empty($true_name))
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('注册名称不能为空');
		}
		if (empty($tax_number))
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('税号不能为空');
		}

		//税号
		if (IValidate::check('[A-Z0-9]{15}$|^[A-Z0-9]{18}', $tax_number) == FALSE)
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('税号格式不正确');
		}
		if ($_FILES['paper_img']['name'] == '' || $_FILES['certif_img']['name'] == ''
			|| $_FILES['carinfo_img']['name'] == '')
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('请传入至少三张照片');
		}
		if ($agree == 0)
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('请同意接受协议内容');
		}
		$memberObj = new IModel('member');
		$memberRow = $memberObj->getObj('mobile = "'.$mobile.'"');
		if ($memberRow)
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('手机号已经被注册');
		}

		//获取注册配置参数
		$siteConfig = new Config('site_config');
		$reg_option = $siteConfig->reg_option;

		/*注册信息校验*/
		if ($reg_option == 2)
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('当前网站禁止新用户注册');
		}
		//插入user表
		$userObj   = new IModel('user');
		$userArray = array(
			'username' => $mobile,
			'password' => md5('123456'),
		);
		$userObj->setData($userArray);
		$user_id = $userObj->add();
		if ( ! $user_id)
		{
			$userObj->rollback();
			$this->redirect('reg', FALSE);
			Util::showMessage('用户创建失败');
		}
		$memberArray = array(
			'user_id'       => $user_id,
			'time'          => ITime::getDateTime(),
			'status'        => $reg_option == 1 ? 3 : 1,
			'mobile'        => $mobile,
			'seller_name'   => $seller_name,
			'person_charge' => $person_charge,
			'telephone'     => $phone,
			'true_name'     => $true_name,
			'tax_number'    => $tax_number,
			'province'      => $province,
			'city'          => $city,
			'area'          => $area,
			'address'       => $address,
			'hours_start'   => $hours_start,
			'hours_end'     => $hours_end,
		);
		if ($_FILES)
		{
			$uploadObj = new PhotoUpload();
			$uploadObj->setIterance(FALSE);
			$photoInfo = $uploadObj->run();
			foreach ($photoInfo as $k => $v)
			{
				if (isset($v['img']) && file_exists($v['img']))
				{
					$memberArray[$k] = $photoInfo[$k]['img'];
				}
			}
		}
		// 获取经纬度
		$cityname           = IFilter::act(IReq::get('cityname', 'post'));
		$res                = area::getTraceByName($cityname, $address);
		$memberArray['lat'] = $res['lat'];
		$memberArray['lng'] = $res['lng'];

		$memberObj = new IModel('member');
		$memberObj->setData($memberArray);
		$memberObj->add();
		$userArray['id']       = $user_id;
		$userArray['head_ico'] = "";
		plugin::trigger("userLoginCallback", $userArray);
		if ($user_id > 0)
		{
			//自定义跳转页面
			$this->redirect('/site/success?message='.urlencode("注册成功！"));
		}
		else
		{
			$this->setError($userArray);
			$this->redirect('reg', FALSE);
			Util::showMessage($userArray);
		}
	}

	//重新用户注册（from 提交）
	function regAct2()
	{
		$seller_name   = IFilter::act(IReq::get('seller_name', 'post'));
		$person_charge = IFilter::act(IReq::get('person_charge', 'post'));
		$phone         = IFilter::act(IReq::get('phone', 'post'));
		$mobile        = IFilter::act(IReq::get('mobile', 'post'));
		$true_name     = IFilter::act(IReq::get('true_name', 'post'));
		$tax_number    = IFilter::act(IReq::get('tax_number', 'post'));

		$province = IFilter::act(IReq::get('province', 'post'));
		$city     = IFilter::act(IReq::get('city', 'post'));
		$area     = IFilter::act(IReq::get('area', 'post'));
		$address  = IFilter::act(IReq::get('address', 'post'));

		$hours_start = IFilter::act(IReq::get('hours_start', 'post'));
		$hours_end   = IFilter::act(IReq::get('hours_end', 'post'));
		$agree       = IFilter::act(IReq::get('agree', 'post'));
		$agree       = ($agree == 'on') ? 1 : 0;
		if (empty($seller_name))
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('餐厅名称不能为空');
		}
		if (empty($person_charge))
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('负责人不能为空');
		}
		if (empty($mobile))
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('手机号不能为空');
		}

		//手机号验证
		if (IValidate::mobi($mobile) == FALSE)
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('手机号格式不正确');
		}
		if (empty($true_name))
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('注册名称不能为空');
		}
		if (empty($tax_number))
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('税号不能为空');
		}

		//税号
		if (IValidate::check('[A-Z0-9]{15}$|^[A-Z0-9]{18}', $tax_number) == FALSE)
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('税号格式不正确');
		}
		if ($agree == 0)
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('请同意接受协议内容');
		}

		$memberObj = new IModel('member');
		$memberRow = $memberObj->getObj('mobile = "'.$mobile.'"');
		if ($memberRow)
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('手机号已经被注册');
		}
		if ($_FILES['paper_img']['name'] == '' || $_FILES['certif_img']['name'] == ''
			|| $_FILES['carinfo_img']['name'] == '')
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('请传入至少三张照片');
		}
		//获取注册配置参数
		$siteConfig = new Config('site_config');
		$reg_option = $siteConfig->reg_option;

		/*注册信息校验*/
		if ($reg_option == 2)
		{
			$this->redirect('reg', FALSE);
			Util::showMessage('当前网站禁止新用户注册');
		}
		//插入user表
		$userObj   = new IModel('user');
		$userArray = array(
			'username' => $mobile,
			'password' => md5('123456'),
		);
		$userObj->setData($userArray);
		$user_id = $userObj->add();
		if ( ! $user_id)
		{
			$userObj->rollback();
			$this->redirect('reg', FALSE);
			Util::showMessage('用户创建失败');
		}
		$memberArray = array(
			'user_id'       => $user_id,
			'time'          => ITime::getDateTime(),
			'status'        => $reg_option == 1 ? 3 : 1,
			'mobile'        => $mobile,
			'seller_name'   => $seller_name,
			'person_charge' => $person_charge,
			'telephone'     => $phone,
			'true_name'     => $true_name,
			'tax_number'    => $tax_number,
			'province'      => $province,
			'city'          => $city,
			'area'          => $area,
			'address'       => $address,
			'hours_start'   => $hours_start,
			'hours_end'     => $hours_end,
		);
		if ($_FILES)
		{
			$uploadObj = new PhotoUpload();
			$uploadObj->setIterance(FALSE);
			$photoInfo = $uploadObj->run();
			foreach ($photoInfo as $k => $v)
			{
				if (isset($v['img']) && file_exists($v['img']))
				{
					$memberArray[$k] = $photoInfo[$k]['img'];
				}
			}
		}
		$memberObj = new IModel('member');
		$memberObj->setData($memberArray);
		$memberObj->add();
		$userArray['id']       = $user_id;
		$userArray['head_ico'] = "";
		plugin::trigger("userLoginCallback", $userArray);
		if ($user_id > 0)
		{
			//自定义跳转页面
			$this->redirect('/site/success?message='.urlencode("注册成功！"));
		}
		else
		{
			$this->setError($userArray);
			$this->redirect('reg', FALSE);
			Util::showMessage($userArray);
		}
	}

	//用户注册
	function reg_act()
	{
		//调用_userInfo注册插件
		$result = plugin::trigger("userRegAct", $_POST);
		if (isset($_POST['goto']) && $_POST['goto'] == 1 && is_array($result))
		{
			echo '333';
			exit();
		}
		else
		{
			if (is_array($result))
			{
				//自定义跳转页面
				$this->redirect('/site/success?message='.urlencode("注册成功！"));
			}
			else
			{
				$this->setError($result);
				$this->redirect('reg', FALSE);
				Util::showMessage($result);
			}
		}
	}

	//用户登录
	function login_act()
	{
		//调用_userInfo登录插件
		$result = plugin::trigger('userLoginAct', $_POST);
		if (is_array($result))
		{
			//自定义跳转页面
			$callback = plugin::trigger('getCallback');
			if ($callback)
			{
				$this->redirect($callback);
			}
			else
			{
				$this->redirect('/ucenter/index');
			}
		}
		else
		{
			$this->setError($result);
			$this->redirect('login', FALSE);
			Util::showMessage($result);
		}
	}

	//商品加入购物车[ajax]
	function joinCart()
	{
		$link      = IReq::get('link');
		$goods_id  = IFilter::act(IReq::get('goods_id'), 'int');
		$goods_num = IReq::get('goods_num') === NULL ? 1 : intval(IReq::get('goods_num'));
		$type      = IFilter::act(IReq::get('type'));

		//加入购物车
		$cartObj   = new Cart();
		$addResult = $cartObj->add($goods_id, $goods_num, $type);

		if ($link != '')
		{
			if ($addResult === FALSE)
			{
				$this->cart(FALSE);
				Util::showMessage($cartObj->getError());
			}
			else
			{
				$this->redirect($link);
			}
		}
		else
		{
			if ($addResult === FALSE)
			{
				$result = array(
					'isError' => TRUE,
					'message' => $cartObj->getError(),
				);
			}
			else
			{
				$result = array(
					'isError' => FALSE,
					'message' => '添加成功',
				);
			}
			echo JSON::encode($result);
		}
	}

	//根据goods_id获取货品
	function getProducts()
	{
		$id           = IFilter::act(IReq::get('id'), 'int');
		$productObj   = new IModel('products');
		$productsList = $productObj->query('goods_id = '.$id, 'sell_price,id,spec_array,goods_id', 'store_nums desc',
			7);
		if ($productsList)
		{
			foreach ($productsList as $key => $val)
			{
				$productsList[$key]['specData'] = Block::show_spec($val['spec_array']);
			}
		}
		echo JSON::encode($productsList);
	}

	//删除购物车
	function removeCart()
	{
		$link     = IReq::get('link');
		$goods_id = IFilter::act(IReq::get('goods_id'), 'int');
		$type     = IFilter::act(IReq::get('type'));

		$cartObj   = new Cart();
		$cartInfo  = $cartObj->getMyCart();
		$delResult = $cartObj->del2($goods_id, $type);

		if ($link != '')
		{
			if ($delResult === FALSE)
			{
				$this->cart(FALSE);
				Util::showMessage($cartObj->getError());
			}
			else
			{
				$this->redirect($link);
			}
		}
		else
		{
			if ($delResult === FALSE)
			{
				$result = array(
					'isError' => TRUE,
					'message' => $cartObj->getError(),
				);
			}
			else
			{
				$goodsRow          = $cartInfo[$type]['data'][$goods_id];
				$cartInfo['sum']   -= $goodsRow['sell_price'] * $goodsRow['count'];
				$cartInfo['count'] -= $goodsRow['count'];

				$result = array(
					'isError' => FALSE,
					'data'    => $cartInfo,
				);
			}

			echo JSON::encode($result);
		}
	}

	//删除购物车
	function removeCart2()
	{
		$pdelStr = IReq::get('pdelStr');
		$gdelStr = IReq::get('gdelStr');

		$cartObj   = new Cart();
		$cartInfo  = $cartObj->getMyCart();
		$delResult = $cartObj->del2($gdelStr,$pdelStr);

		if ($delResult === FALSE)
		{
			$result = array(
				'isError' => TRUE,
				'message' => $cartObj->getError(),
			);
		}
		else
		{
			$result = array(
				'isError' => FALSE,
			);
		}

		echo JSON::encode($result);
	}

	//清空购物车
	function clearCart()
	{
		$cartObj = new Cart();
		$cartObj->clear();
		$this->redirect('cart');
	}

	//购物车div展示
	function showCart()
	{
		$cartObj       = new Cart();
		$cartList      = $cartObj->getMyCart();
		$data['data']  = array_merge($cartList['goods']['data'], $cartList['product']['data']);
		$data['count'] = $cartList['count'];
		$data['sum']   = $cartList['sum'];
		echo JSON::encode($data);
	}

	//购物车页面及商品价格计算[复杂]
	function cart($redirect = FALSE)
	{
		//防止页面刷新
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", FALSE);
		if ($this->user['user_id'] == NULL)
		{
			$this->redirect('/simple/login');
			exit();
		}
		else
		{
			$userid = $this->user['user_id'];
		}

		//开始计算购物车中的商品价格
		$countObj = new CountSum($userid);
		$result   = $countObj->cart_count();
		// echo '<pre>';var_dump($result);
		// exit();
		if (is_string($result))
		{
			IError::show($result, 403);
		}
		$goodsList =& $result['goodsList'];
		$marketL   = Api::run('getMarketList');

		$marketList    = array();
		$marketList[0] = '自营';
		foreach ($marketL as $k => $v)
		{
			$marketList[$v['id']] = $v['name'];
		}
		$marketArr = array();
		$i         = 0;
		foreach ($goodsList as $k => $v)
		{
			$goodsList[$k]['i'] = ++ $i;
			if (isset($marketArr[$v['market_id']]))
			{
				$marketArr[$v['market_id']]['id']   = $v['market_id'];
				$marketArr[$v['market_id']]['name'] = $marketList[$v['market_id']];
				$marketArr[$v['market_id']]['num']  += 1;
				$marketArr[$v['market_id']]['sum']  += $v['sum'];
			}
			else
			{
				$marketArr[$v['market_id']]['id']   = $v['market_id'];
				$marketArr[$v['market_id']]['name'] = $marketList[$v['market_id']];
				$marketArr[$v['market_id']]['num']  = 1;
				$marketArr[$v['market_id']]['sum']  += $v['sum'];
			}
		}

		$sellerL = Api::run('getSellerList2');
		$sellerA = array();
		foreach ($sellerL as $k => $v)
		{
			$sellerA[$v['id']] = $v['market_id'];
		}

		$promotion     = $result['promotion'];
		$promotionData = array();
		foreach ($promotion as $k => $v)
		{
			foreach ($v as $key => $val)
			{
				$market_id                   = $sellerA[$val['id']];
				$promotionData[$market_id][] = $val;
			}
		}

		//返回值
		$this->final_sum = $result['final_sum'];
		$this->promotion = $promotionData;
		$this->proReduce = $result['proReduce'];
		$this->sum       = $result['sum'];
		$this->goodsList = $result['goodsList'];
		$this->count     = $result['count'];
		$this->reduce    = $result['reduce'];
		$this->weight    = $result['weight'];
		$this->sellArr   = $sellArr;
		$this->marketArr = $marketArr;

		//渲染视图
		$this->redirect('cart', $redirect);
	}

	//计算促销规则[ajax]
	function promotionRuleAjax()
	{
		$goodsId   = IFilter::act(IReq::get("goodsId"), 'int');
		$productId = IFilter::act(IReq::get("productId"), 'int');
		$num       = IFilter::act(IReq::get("num"), 'int');

		if ( ! $goodsId || ! $num)
		{
			return;
		}

		$goodsArray   = array();
		$productArray = array();

		foreach ($goodsId as $key => $goods_id)
		{
			$pid  = $productId[$key];
			$nVal = $num[$key];

			if ($pid > 0)
			{
				$productArray[$pid] = $nVal;
			}
			else
			{
				$goodsArray[$goods_id] = $nVal;
			}
		}

		$countSumObj    = new CountSum();
		$cartObj        = new Cart();
		$countSumResult = $countSumObj->goodsCount($cartObj->cartFormat(array("goods" => $goodsArray, "product" => $productArray)));
		echo JSON::encode($countSumResult);
	}

	//填写订单信息cart2
	function cart2()
	{
		$id        = IFilter::act(IReq::get('id'), 'int');
		$type      = IFilter::act(IReq::get('type'));//goods,product
		$promo     = IFilter::act(IReq::get('promo'));
		$active_id = IFilter::act(IReq::get('active_id'), 'int');
		$buy_num   = IReq::get('num') ? IFilter::act(IReq::get('num'), 'int') : 1;
		$tourist   = IReq::get('tourist');//游客方式购物

		//必须为登录用户
		if ($tourist === NULL && $this->user['user_id'] == NULL)
		{
			if ($id == 0 || $type == '')
			{
				$this->redirect('/simple/login?tourist&callback=/simple/cart2');
			}
			else
			{
				$url = '/simple/login?tourist&callback=/simple/cart2/id/'.$id.'/type/'.$type.'/num/'.$buy_num;
				$url .= $promo ? '/promo/'.$promo : '';
				$url .= $active_id ? '/active_id/'.$active_id : '';
				$this->redirect($url);
			}
		}
		// 必须是登录会员
		//
		//游客的user_id默认为0
		// $user_id = ($this->user['user_id'] == null) ? 0 : $this->user['user_id'];
		if ($this->user['user_id'] == NULL)
		{
			$this->redirect('/simple/login');
			exit();
		}
		else
		{
			$user_id = $this->user['user_id'];
		}

		//计算商品
		$countSumObj = new CountSum($user_id);
		if ($id > 0)
		{
			$result = $countSumObj->cart_count($id, $type, $buy_num, $promo, $active_id);
		}
		else
		{
			$result = $countSumObj->cart_count();
		}

		if ($countSumObj->error)
		{
			IError::show(403, $countSumObj->error);
		}

		$goodsList = $result['goodsList'];

		// 根据选择地址获取经纬度

		$marketL       = Api::run('getMarketList');
		$marketList    = array();
		$marketList[0] = '自营';

		foreach ($marketL as $k => $v)
		{
			$marketList[$v['id']] = $v['name'];
		}
		$sellerL = Api::run('getSellerList2');
		$sellerA = array();
		foreach ($sellerL as $k => $v)
		{
			$sellerA[$v['id']] = $v['market_id'];
		}
		$promotion     = $result['promotion'];
		$promotionData = array();
		foreach ($promotion as $k => $v)
		{
			foreach ($v as $key => $val)
			{
				$market_id                   = $sellerA[$val['id']];
				$promotionData[$market_id][] = $val;
			}
		}

		//获取收货地址
		$addressObj  = new IModel('address');
		$addressList = $addressObj->query('user_id = '.$user_id, "*", "is_default desc");

		//更新$addressList数据
		foreach ($addressList as $key => $val)
		{
			$temp = area::name($val['province'], $val['city'], $val['area']);
			// echo '<pre>';var_dump($temp);

			if (isset($temp[$val['province']]) && isset($temp[$val['city']]) && isset($temp[$val['area']]))
			{
				$addressList[$key]['province_val'] = $temp[$val['province']];
				$addressList[$key]['city_val']     = $temp[$val['city']];
				$addressList[$key]['area_val']     = $temp[$val['area']];

				//通过地址获取经纬度
				// $lnglatData=area::getTraceByName($temp[$val['city']],$val['address']);
				// $addressList[$key]['area_val']['lng']=$lnglatData['lng'];
				// $addressList[$key]['area_val']['lat']=$lnglatData['lat'];
			}
		}
		// echo '<pre>';var_dump($addressList);exit();

		$marketArr    = array();
		$marketid_str = '';
		$yunfei_sum   = 0;
		foreach ($goodsList as $k => $v)
		{
			if (isset($marketArr[$v['market_id']]))
			{
				$marketArr[$v['market_id']]['id']   = $v['market_id'];
				$marketArr[$v['market_id']]['name'] = $marketList[$v['market_id']];
				$marketArr[$v['market_id']]['num']  += 1;
				$marketArr[$v['market_id']]['sum']  += $v['sum'];
			}
			else
			{
				$marketArr[$v['market_id']]['id']   = $v['market_id'];
				$marketArr[$v['market_id']]['name'] = $marketList[$v['market_id']];
				$marketArr[$v['market_id']]['num']  = 1;
				$marketArr[$v['market_id']]['sum']  += $v['sum'];
				$marketid_str                       .= $v['market_id'].',';
			}
		}
		// echo '<pre>';var_dump($goodsList);var_dump($marketid_str);exit();

		//获取习惯方式
		$memberObj = new IModel('member');
		$memberRow = $memberObj->getObj('user_id = '.$user_id, 'custom');
		if (isset($memberRow['custom']) && $memberRow['custom'])
		{
			$this->custom = unserialize($memberRow['custom']);
		}
		else
		{
			$this->custom = array(
				'payment'  => '',
				'delivery' => '',
			);
		}
		//返回值
		$this->gid          = $id;
		$this->type         = $type;
		$this->num          = $buy_num;
		$this->promo        = $promo;
		$this->active_id    = $active_id;
		$this->final_sum    = ($result['final_sum']);
		$this->promotion    = $promotionData;
		$this->proReduce    = $result['proReduce'];
		$this->sum          = $result['sum'];
		$this->goodsList    = $result['goodsList'];
		$this->count        = $result['count'];
		$this->reduce       = $result['reduce'];
		$this->weight       = round($result['weight'] / 1000, 5);
		$this->freeFreight  = $result['freeFreight'];
		$this->seller       = $result['seller'];
		$this->sellArr      = $sellArr;
		$this->marketArr    = $marketArr;
		$this->marketid_str = trim($marketid_str, ',');

		//收货地址列表
		$this->addressList = $addressList;

		//获取商品税金
		$this->goodsTax = $result['tax'];

		//渲染页面
		$this->redirect('cart2');
	}

	/**
	 * 生成订单
	 */
	function cart3()
	{
		$address_id  = IFilter::act(IReq::get('radio_address'), 'int');
		$delivery_id = IFilter::act(IReq::get('delivery_id'), 'int');
		$accept_time = IReq::get('accept_time');
		//状态类型 0 即时订单 1 3小时订单 2 6小时订单
		$status_type   = IFilter::act(IReq::get('status_type'), 'int');
		$songda_time   = IFilter::act(IReq::get('songda_time'));
		$payment       = IFilter::act(IReq::get('payment'), 'int');
		$order_message = IFilter::act(IReq::get('message'));
		$ticket_id     = IFilter::act(IReq::get('ticket_id'), 'int');
		$taxes         = IFilter::act(IReq::get('taxes'), 'float');
		$tax_title     = IFilter::act(IReq::get('tax_title'));
		$gid           = IFilter::act(IReq::get('direct_gid'), 'int');
		$num           = IFilter::act(IReq::get('direct_num'), 'int');
		$type          = IFilter::act(IReq::get('direct_type'));//商品或者货品
		$promo         = IFilter::act(IReq::get('direct_promo'));
		$active_id     = IFilter::act(IReq::get('direct_active_id'), 'int');
		$takeself      = IFilter::act(IReq::get('takeself'), 'int');
		$order_type    = 0;
		$dataArray     = array();
		$user_id       = ($this->user['user_id'] == NULL) ? 0 : $this->user['user_id'];

		//获取商品数据信息
		$countSumObj = new CountSum($user_id);
		$goodsResult = $countSumObj->cart_count($gid, $type, $num, $promo, $active_id);
		// echo '<pre>';var_dump($goodsResult);exit();

		if ($countSumObj->error)
		{
			IError::show(403, $countSumObj->error);
		}
		if ($user_id == 0)
		{
			IError::show(403, '尚未登录');
		}
		else
		{
			$addressDB  = new IModel('address');
			$addressRow = $addressDB->getObj('id = '.$address_id.' and user_id = '.$user_id);
		}

		if ( ! $addressRow)
		{
			IError::show(403, "收货地址信息不存在");
		}
		$accept_name = IFilter::act($addressRow['accept_name'], 'name');
		$province    = $addressRow['province'];
		$city        = $addressRow['city'];
		$area        = $addressRow['area'];
		$address     = IFilter::act($addressRow['address']);
		$mobile      = IFilter::act($addressRow['mobile'], 'mobile');
		$telphone    = IFilter::act($addressRow['telphone'], 'phone');
		$zip         = IFilter::act($addressRow['zip'], 'zip');

		//检查订单重复
		//   $checkData = array(
		//       "accept_name" => $accept_name,
		//       "address"     => $address,
		//       "mobile"      => $mobile,
		//       "distribution"=> $delivery_id,
		//   );
		//   $result = order_class::checkRepeat($checkData,$goodsResult['goodsList']);
		//   if( is_string($result) )
		//   {
		// IError::show(403,$result);
		//   }
		//检查订单重复 end

		//配送方式,判断是否为货到付款
		$deliveryObj = new IModel('delivery');
		$deliveryRow = $deliveryObj->getObj('id = '.$delivery_id);
		if ( ! $deliveryRow)
		{
			IError::show(403, '配送方式不存在');
		}

		if ($deliveryRow['type'] == 0)
		{
			if ($payment == 0)
			{
				IError::show(403, '请选择正确的支付方式');
			}
		}
		else
		{
			if ($deliveryRow['type'] == 1)
			{
				$payment = 0;
			}
			else
			{
				if ($deliveryRow['type'] == 2)
				{
					if ($takeself == 0)
					{
						IError::show(403, '请选择正确的自提点');
					}
				}
			}
		}
		//如果不是自提方式自动清空自提点
		if ($deliveryRow['type'] != 2)
		{
			$takeself = 0;
		}

		if ( ! $gid)
		{
			//清空购物车
			IInterceptor::reg("cart@onFinishAction");
		}

		//判断商品是否存在
		if (is_string($goodsResult) || empty($goodsResult['goodsList']))
		{
			IError::show(403, '商品数据错误');
		}

		//加入促销活动
		if ($promo && $active_id)
		{
			$activeObject = new Active($promo, $active_id, $user_id, $gid, $type, $num);
			$order_type   = $activeObject->getOrderType();
		}

		$paymentObj = new IModel('payment');
		$paymentRow = $paymentObj->getObj('id = '.$payment, 'type,name');
		if ( ! $paymentRow)
		{
			IError::show(403, '支付方式不存在');
		}
		$paymentName = $paymentRow['name'];
		$paymentType = $paymentRow['type'];

		// echo '<pre>';var_dump($goodsResult);exit();

		//最终订单金额计算
		$orderData = $countSumObj->countOrderFee($goodsResult, $province, $delivery_id, $payment, $taxes, 0, $promo,
			$active_id);
		// echo '<pre>';var_dump($goodsResult);exit();
		// echo '<pre>';var_dump($orderData);exit();
		if (is_string($orderData))
		{
			IError::show(403, $orderData);
			exit;
		}

		// 对应市场运费计算（重量暂不参与）
		$marketL                  = Api::run('getMarketList');
		$marketList               = array();
		$marketList[0]            = '自营';
		$mListLatlng              = array();
		$mListLatlng[0]['juli']   = 0;
		$mListLatlng[0]['yunfei'] = 0;
		// 通过地址数据获取经纬度
		$valgetname = area::name($province, $city, $area);
		$lnglatData = area::getTraceByName($valgetname[$city], $address);

		foreach ($marketL as $k => $v)
		{
			$marketList[$v['id']] = $v['name'];
			$lat                  = $v['lat'];
			$lng                  = $v['lng'];
			$juliData             = area::getDistance($lat, $lng, $lnglatData['lat'], $lnglatData['lng']);

			$mListLatlng[$v['id']]['juli']     = $juliData['text'];
			$mListLatlng[$v['id']]['juli_val'] = $juliData['value'];
			$yunfei                            = (($juliData['value'] / 1000) * $this->_siteConfig->freight_rate_value);
			$mListLatlng[$v['id']]['yunfei']   = round($yunfei, 2);
		}



		//根据商品所属商家不同批量生成订单
		$orderIdArray  = array();
		$orderNumArray = array();
		$final_sum     = 0;
		$marketData    = array();
		foreach ($orderData as $seller_id => $goodsResult)
		{
			// echo '<pre>';var_dump($goodsResult);
			$market_id = $goodsResult['market_id'];
			//生成的订单数据
			$dataArray = array(
				'order_no'        => Order_Class::createOrderNum(),
				'user_id'         => $user_id,
				'accept_name'     => $accept_name,
				'pay_type'        => $payment,
				'distribution'    => $delivery_id,
				'postcode'        => $zip,
				'telphone'        => $telphone,
				'province'        => $province,
				'city'            => $city,
				'area'            => $area,
				'address'         => $address,
				'mobile'          => $mobile,
				'create_time'     => ITime::getDateTime(),
				'postscript'      => $order_message,
				'accept_time'     => $accept_time,
				'status_type'     => $status_type,
				'songda_time'     => $songda_time,
				'exp'             => $goodsResult['exp'],
				'point'           => $goodsResult['point'],
				'type'            => $order_type,

				//商品价格
				'payable_amount'  => $goodsResult['sum'],
				'real_amount'     => $goodsResult['final_sum'],

				//运费价格
				'payable_freight' => $goodsResult['deliveryOrigPrice'],
				'real_freight'    => $goodsResult['deliveryPrice'],

				//手续费
				'pay_fee'         => $goodsResult['paymentPrice'],

				//税金
				'invoice'         => $tax_title ? 1 : 0,
				'invoice_title'   => $tax_title,
				'taxes'           => $goodsResult['taxPrice'],

				//优惠价格
				'promotions'      => $goodsResult['proReduce'] + $goodsResult['reduce'],

				//订单应付总额
				'order_amount'    => $goodsResult['orderAmountPrice'],

				//订单保价
				'insured'         => $goodsResult['insuredPrice'],

				//自提点ID
				'takeself'        => $takeself,

				//促销活动ID
				'active_id'       => $active_id,

				//商家ID
				'seller_id'       => $seller_id,
				'market_id'       => $market_id,
				'juli'            => $mListLatlng[$market_id]['juli_val']/1000,

				//备注信息
				'note'            => '',
			);

			//获取红包减免金额
			if ($ticket_id)
			{
				$memberObj = new IModel('member');
				$memberRow = $memberObj->getObj('user_id = '.$user_id, 'prop,custom');
				foreach ($ticket_id as $tk => $tid)
				{
					//游客手动添加或注册用户道具中已有的代金券
					if (ISafe::get('ticket_'.$tid) == $tid
						|| stripos(','.trim($memberRow['prop'], ',').',', ','.$tid.',') !== FALSE)
					{
						$propObj   = new IModel('prop');
						$ticketRow = $propObj->getObj('id = '.$tid
							.' and NOW() between start_time and end_time and type = 0 and is_close = 0 and is_userd = 0 and is_send = 1');
						if ( ! $ticketRow)
						{
							IError::show(403, '代金券不可用');
						}

						if ($ticketRow['seller_id'] == 0 || $ticketRow['seller_id'] == $seller_id)
						{
							$ticketRow['value']         = $ticketRow['value'] >= $goodsResult['final_sum']
								? $goodsResult['final_sum'] : $ticketRow['value'];
							$dataArray['prop']          = $tid;
							$dataArray['promotions']    += $ticketRow['value'];
							$dataArray['order_amount']  -= $ticketRow['value'];
							$goodsResult['promotion'][] = array(
								"plan" => "代金券", "info" => "使用了￥".$ticketRow['value']."代金券",
							);

							//锁定红包状态
							$propObj->setData(array('is_close' => 2));
							$propObj->update('id = '.$tid);

							unset($ticket_id[$tk]);
							break;
						}
					}
				}
			}

			//促销规则
			if (isset($goodsResult['promotion']) && $goodsResult['promotion'])
			{
				foreach ($goodsResult['promotion'] as $key => $val)
				{
					$dataArray['note'] .= join("，", $val)."。";
				}
			}

			$dataArray['order_amount'] = $dataArray['order_amount'] <= 0 ? 0 : $dataArray['order_amount'];
			// echo $dataArray['order_amount'].'<br>';
			// echo '<pre>';var_dump($dataArray);exit();

			//生成订单插入order表中
			$orderObj = new IModel('order');
			$orderObj->setData($dataArray);
			$order_id = $orderObj->add();

			if (isset($marketData[$market_id]))
			{
				$marketData[$market_id]['orderid_str']  .= ','.$order_id;
				$marketData[$market_id]['order_amount'] += $dataArray['order_amount'];
				$marketData[$market_id]['weight']       += $goodsResult['weight'];
			}
			else
			{
				$marketData[$market_id]['orderid_str']  = $order_id;
				$marketData[$market_id]['order_amount'] = $dataArray['order_amount'];
				$marketData[$market_id]['weight']       = $goodsResult['weight'];
			}

			if ($order_id == FALSE)
			{
				IError::show(403, '订单生成错误');
			}

			/*将订单中的商品插入到order_goods表*/
			$orderInstance = new Order_Class();
			$orderInstance->insertOrderGoods($order_id, $goodsResult['goodsResult']);

			foreach ($goodsResult['goodsList'] as $good)
			{

				$favObj = new IModel('favorite');
				$catObj = new IModel('category_extend');
				$cat    = $catObj->getObj('goods_id='.$good['goods_id']);
				$cat_id = $cat['category_id'];

				$fav = $favObj->getObj('user_id='.$user_id.' and rid='.$good['goods_id']);
				if ($fav)
				{
					continue;
				}
				$favObj->setData([
					'user_id' => $user_id,
					'rid'     => $good['goods_id'],
					'time'    => date('Y-m-d H:i:s'),
					'summary' => '购买商品加入常用列表',
					'cat_id'  => $cat_id,
				]);
				$fav_id = $favObj->add();
			}

			//订单金额小于等于0直接免单
			if ($dataArray['order_amount'] <= 0)
			{
				Order_Class::updateOrderStatus($dataArray['order_no']);
			}
			else
			{
				$orderIdArray[]  = $order_id;
				$orderNumArray[] = $dataArray['order_no'];
				$final_sum       += $dataArray['order_amount'];
			}
		}

		

		// 插入订单市场数据
		$marketidArray = array();
		$yunfei_m      = 0;
		if ($marketData)
		{
			foreach ($marketData as $key => $val)
			{
				// $i++;
				// echo $i.'-------['.$key.']'.'<br>';
				$myunfei = round($mListLatlng[$key]['yunfei']*($marketData[$key]['weight']/1000),2);
				// echo $myunfei.'-----['.$key.']<br>';
				$yunfei_m                += $myunfei;
				$marketR                 = array();
				$marketR['market_id']    = $key;
				$marketR['ordermarket_no']    = date('YmdHis').'_'.$key;
				$marketR['orderid_str']  = $val['orderid_str'];
				$marketR['order_amount'] = $val['order_amount'];
				$marketR['weight']       = $val['weight'];
				$marketR['uid']          = $user_id;
				$marketR['yunfei']       = $myunfei;
				$marketR['juli']         = $mListLatlng[$key]['juli_val'];
				$marketR['pay_type']     = $payment;
				$marketR['status_type']  = $status_type;
				$marketR['songda_time']  = $songda_time;
				$marketR['add_time']     = ITime::getDateTime();
				$marketR['status']       = 1;
				// echo '<pre>';var_dump($marketR);
				$omarketObj = new IModel('order_market');
				$omarketObj->setData($marketR);
				$orderm_id = $omarketObj->add();
				if ($orderm_id == FALSE)
				{
					IError::show(403, '市场订单生成错误');
				}
				$marketidArray[] = $orderm_id;


			}
		}
		// exit();

		//记录用户默认习惯的数据
		if ( ! isset($memberRow['custom']))
		{
			$memberObj = new IModel('member');
			$memberRow = $memberObj->getObj('user_id = '.$user_id, 'custom');
		}

		$memberData = array(
			'custom' => serialize(
				array(
					'payment'  => $payment,
					'delivery' => $delivery_id,
				)
			),
		);
		$memberObj->setData($memberData);
		$memberObj->update('user_id = '.$user_id);

		//收货地址的处理
		// if($user_id)
		// {
		//  $addressDefRow = $addressDB->getObj('user_id = '.$user_id.' and is_default = 1');
		//  if(!$addressDefRow)
		//  {
		//      $addressDB->setData(array('is_default' => 1));
		//      $addressDB->update('user_id = '.$user_id.' and id = '.$address_id);
		//  }
		// }

		//获取备货时间
		$this->stockup_time = $this->_siteConfig->stockup_time ? $this->_siteConfig->stockup_time : 2;

		// echo $final_sum.'---------'.$yunfei_m.'<br>';
		//数据渲染
		$this->order_id = join("_", $orderIdArray);
		//商品总额添加运费
		$this->final_sum    = ($final_sum + $yunfei_m);
		$this->order_num    = join(",", $orderNumArray);
		$this->payment      = $paymentName;
		$this->paymentType  = $paymentType;
		$this->delivery     = $deliveryRow['name'];
		$this->tax_title    = $tax_title;
		$this->deliveryType = $deliveryRow['type'];

		$this->market_id = join("_", $marketidArray);
		plugin::trigger('setCallback', '/ucenter/order');
		//订单金额为0时，订单自动完成
		if ($this->final_sum <= 0)
		{
			$this->redirect('/site/success/message/'.urlencode("订单确认成功，等待发货"));
		}
		else
		{
			$this->setRenderData($dataArray);
			$this->redirect('cart3');
		}
	}

	function gotopay()
	{
		$moid = IFilter::act(IReq::get('moid'));

		//获取当前按市场订单总金额
		$moDB   = new IModel('order_market');
		$moinfo = $moDB->getObj('id='.$moid);
		if ( ! $moinfo)
		{
			IError::show(403, '订单ID不存在');
		}
		if ($moinfo['pay_status'] == 1)
		{
			IError::show(403, '当前订单支付已完成');
		}
		$moinfo['zongjine'] = $moinfo['yunfei'] + $moinfo['order_amount'];
		$moinfo['oidstr']   = str_replace(',', '_', $moinfo['orderid_str']);
		$this->setRenderData($moinfo);

		$this->redirect('gotopay');
	}

	//到货通知处理动作
	function arrival_notice()
	{
		$user_id       = $this->user['user_id'];
		$email         = IFilter::act(IReq::get('email'));
		$mobile        = IFilter::act(IReq::get('mobile'));
		$goods_id      = IFilter::act(IReq::get('goods_id'), 'int');
		$register_time = ITime::getDateTime();

		if ( ! $goods_id)
		{
			IError::show(403, '商品ID不存在');
		}

		$model = new IModel('notify_registry');
		$obj   = $model->getObj("email = '{$email}' and user_id = '{$user_id}' and goods_id = '$goods_id'");
		if (empty($obj))
		{
			$model->setData(array('email' => $email, 'user_id' => $user_id, 'mobile' => $mobile, 'goods_id' => $goods_id, 'register_time' => $register_time));
			$model->add();
		}
		else
		{
			$model->setData(array('email' => $email, 'user_id' => $user_id, 'mobile' => $mobile, 'goods_id' => $goods_id, 'register_time' => $register_time, 'notify_status' => 0));
			$model->update('id = '.$obj['id']);
		}
		$this->redirect('/site/success', TRUE);
	}

	/**
	 * @brief 邮箱找回密码进行
	 */
	function find_password_email()
	{
		$username = IReq::get('username');
		if ($username === NULL || ! IValidate::name($username))
		{
			IError::show(403, "请输入正确的用户名");
		}

		$email = IReq::get("email");
		if ($email === NULL || ! IValidate::email($email))
		{
			IError::show(403, "请输入正确的邮箱地址");
		}

		$tb_user  = new IModel("user as u,member as m");
		$username = IFilter::act($username);
		$email    = IFilter::act($email);
		$user     = $tb_user->getObj(" u.id = m.user_id and u.username='{$username}' AND m.email='{$email}' ");
		if ( ! $user)
		{
			IError::show(403, "对不起，用户不存在");
		}
		$hash = IHash::md5(microtime(TRUE).mt_rand());

		//重新找回密码的数据
		$tb_find_password = new IModel("find_password");
		$tb_find_password->setData(array('hash' => $hash, 'user_id' => $user['id'], 'addtime' => time()));

		if ($tb_find_password->query("`hash` = '{$hash}'") || $tb_find_password->add())
		{
			$url     = IUrl::getHost().IUrl::creatUrl("/simple/restore_password/hash/{$hash}/user_id/".$user['id']);
			$content = mailTemplate::findPassword(array("{url}" => $url));

			$smtp   = new SendMail();
			$result = $smtp->send($user['email'], "您的密码找回", $content);

			if ($result === FALSE)
			{
				IError::show(403, "发信失败,请重试！或者联系管理员查看邮件服务是否开启");
			}
		}
		else
		{
			IError::show(403, "生成HASH重复，请重试");
		}
		$message = "恭喜您，密码重置邮件已经发送！请到您的邮箱中去激活";
		$this->redirect("/site/success/message/".urlencode($message));
	}

	//手机短信找回密码
	function find_password_mobile()
	{
		// $username = IReq::get('username');
		// if ($username === NULL || ! IValidate::name($username))
		// {
		// 	IError::show(403, "请输入正确的用户名");
		// }

		$mobile = IReq::get("mobile");
		if ($mobile === NULL || ! IValidate::mobi($mobile))
		{
			IError::show(403, "请输入正确的电话号码");
		}

		$mobile_code = IFilter::act(IReq::get('mobile_code'));
		if ($mobile_code === NULL)
		{
			IError::show(403, "请输入短信校验码");
		}

		$userDB  = new IModel('user as u , member as m');
		// $userRow = $userDB->getObj('u.username = "'.$username.'" and m.mobile = "'.$mobile.'" and u.id = m.user_id');
		$userRow = $userDB->getObj(' m.mobile = "'.$mobile.'" and u.id = m.user_id');
		if ($userRow)
		{
			$findPasswordDB = new IModel('find_password');
			$dataRow        = $findPasswordDB->getObj('user_id = '.$userRow['user_id'].' and hash = "'.$mobile_code
				.'"');
			if ($dataRow)
			{
				//短信验证码已经过期
				if (time() - $dataRow['addtime'] > 3600)
				{
					$findPasswordDB->del("user_id = ".$userRow['user_id']);
					IError::show(403, "您的短信校验码已经过期了，请重新找回密码");
				}
				else
				{
					$this->redirect('/simple/restore_password/hash/'.$mobile_code.'/user_id/'.$userRow['user_id']);
				}
			}
			else
			{
				IError::show(403, "您输入的短信校验码错误");
			}
		}
		else
		{
			IError::show(403, "用户名与手机号码不匹配");
		}
	}

	//找回密码发送手机验证码短信
	function send_message_mobile()
	{
		// $username = IFilter::act(IReq::get('username'));
		$mobile   = IFilter::act(IReq::get('mobile'));

		// if ($username === NULL || ! IValidate::name($username))
		// {
		// 	die("请输入正确的用户名");
		// }

		if ($mobile === NULL || ! IValidate::mobi($mobile))
		{
			die("请输入正确的手机号码");
		}

		$userDB  = new IModel('user as u , member as m');
		// $userRow = $userDB->getObj('u.username = "'.$username.'" and m.mobile = "'.$mobile.'" and u.id = m.user_id');
		$userRow = $userDB->getObj(' m.mobile = "'.$mobile.'" and u.id = m.user_id');

		if ($userRow)
		{
			$findPasswordDB = new IModel('find_password');
			$dataRow        = $findPasswordDB->query('user_id = '.$userRow['user_id'], '*', 'addtime desc');
			$dataRow        = current($dataRow);

			//120秒是短信发送的间隔
			if (isset($dataRow['addtime']) && (time() - $dataRow['addtime'] <= 120))
			{
				die("申请验证码的时间间隔过短，请稍候再试");
			}
			$mobile_code = rand(10000, 99999);
			$findPasswordDB->setData(array(
				'user_id' => $userRow['user_id'],
				'hash'    => $mobile_code,
				'addtime' => time(),
			));
			if ($findPasswordDB->add())
			{
				$content = smsTemplate::findPassword(array('{mobile_code}' => $mobile_code));
				$result  = Hsms::send($mobile, $content);
				if ($result == 'success')
				{
					die('success');
				}
				die($result);
			}
		}
		else
		{
			die('手机号码有误');
		}
	}

	/**
	 * @brief 重置密码验证
	 */
	function restore_password()
	{
		$hash    = IFilter::act(IReq::get("hash"));
		$user_id = IFilter::act(IReq::get("user_id"), 'int');

		if ( ! $hash)
		{
			IError::show(403, "找不到校验码");
		}
		$tb      = new IModel("find_password");
		$addtime = time() - 3600 * 72;
		$where   = " `hash`='$hash' AND addtime > $addtime ";
		$where   .= $this->user['user_id'] ? " and user_id = ".$this->user['user_id'] : "";

		$row = $tb->getObj($where);
		if ( ! $row)
		{
			IError::show(403, "校验码已经超时");
		}

		if ($row['user_id'] != $user_id)
		{
			IError::show(403, "验证码不属于此用户");
		}

		$this->formAction = IUrl::creatUrl("/simple/do_restore_password/hash/$hash/user_id/".$user_id);
		$this->redirect("restore_password");
	}

	/**
	 * @brief 执行密码修改重置操作
	 */
	function do_restore_password()
	{
		$hash    = IFilter::act(IReq::get("hash"));
		$user_id = IFilter::act(IReq::get("user_id"), 'int');

		if ( ! $hash)
		{
			IError::show(403, "找不到校验码");
		}
		$tb      = new IModel("find_password");
		$addtime = time() - 3600 * 72;
		$where   = " `hash`='$hash' AND addtime > $addtime ";
		$where   .= $this->user['user_id'] ? " and user_id = ".$this->user['user_id'] : "";

		$row = $tb->getObj($where);
		if ( ! $row)
		{
			IError::show(403, "校验码已经超时");
		}

		if ($row['user_id'] != $user_id)
		{
			IError::show(403, "验证码不属于此用户");
		}

		//开始修改密码
		$pwd   = IReq::get("password");
		$repwd = IReq::get("repassword");
		if ($pwd == NULL || strlen($pwd) < 6 || $repwd != $pwd)
		{
			IError::show(403, "新密码至少六位，且两次输入的密码应该一致。");
		}
		$pwd     = md5($pwd);
		$tb_user = new IModel("user");
		$tb_user->setData(array("password" => $pwd));
		$re = $tb_user->update("id='{$row['user_id']}'");
		if ($re !== FALSE)
		{
			$message = "修改密码成功";
			$tb->del("`hash`='{$hash}'");
			$this->redirect("/site/success/message/".urlencode($message));

			return;
		}
		IError::show(403, "密码修改失败，请重试");
	}

	//添加收藏夹
	function favorite_add()
	{
		$goods_id = IFilter::act(IReq::get('goods_id'), 'int');
		$message  = '';

		if ($goods_id == 0)
		{
			$message = '商品id值不能为空';
		}
		else
		{
			if ( ! isset($this->user['user_id']) || ! $this->user['user_id'])
			{
				$message = '请先登录';
			}
			else
			{
				$favoriteObj = new IModel('favorite');
				$goodsRow    = $favoriteObj->getObj('user_id = '.$this->user['user_id'].' and rid = '.$goods_id);
				if ($goodsRow)
				{
					$message = '您已经收藏过此件商品';
				}
				else
				{
					$catObj = new IModel('category_extend');
					$catRow = $catObj->getObj('goods_id = '.$goods_id);
					$cat_id = $catRow ? $catRow['category_id'] : 0;

					$dataArray = array(
						'user_id' => $this->user['user_id'],
						'rid'     => $goods_id,
						'time'    => ITime::getDateTime(),
						'cat_id'  => $cat_id,
					);
					$favoriteObj->setData($dataArray);
					$favoriteObj->add();
					$message = '收藏成功';

					//商品收藏信息更新
					$goodsDB = new IModel('goods');
					$goodsDB->setData(array("favorite" => "favorite + 1"));
					$goodsDB->update("id = ".$goods_id, 'favorite');
				}
			}
		}
		$result = array(
			'isError' => TRUE,
			'message' => $message,
		);

		echo JSON::encode($result);
	}

	//获取oauth登录地址
	public function oauth_login()
	{
		$id = IFilter::act(IReq::get('id'), 'int');
		if ($id)
		{
			$oauthObj = new Oauth($id);
			$result   = array(
				'isError' => FALSE,
				'url'     => $oauthObj->getLoginUrl(),
			);
		}
		else
		{
			$result = array(
				'isError' => TRUE,
				'message' => '请选择要登录的平台',
			);
		}
		echo JSON::encode($result);
	}

	//第三方登录回调
	public function oauth_callback()
	{
		$oauth_name = IFilter::act(IReq::get('oauth_name'));
		$oauthObj   = new IModel('oauth');
		$oauthRow   = $oauthObj->getObj('file = "'.$oauth_name.'"');

		if ( ! $oauth_name && ! $oauthRow)
		{
			IError::show(403, "{$oauth_name} 第三方平台信息不存在");
		}
		$id       = $oauthRow['id'];
		$oauthObj = new Oauth($id);
		$result   = $oauthObj->checkStatus($_GET);

		if ($result === TRUE)
		{
			$oauthObj->getAccessToken($_GET);
			$userInfo = $oauthObj->getUserInfo();

			if (isset($userInfo['id']) && isset($userInfo['name']) && $userInfo['id'] && $userInfo['name'])
			{
				$this->bindUser($userInfo, $id);

				return;
			}
		}
		else
		{
			IError::show("回调URL参数错误");
		}
	}

	//同步绑定用户数据
	public function bindUser($userInfo, $oauthId)
	{
		$userObj      = new IModel('user');
		$oauthUserObj = new IModel('oauth_user');
		$oauthUserRow = $oauthUserObj->getObj("oauth_user_id = '{$userInfo['id']}' and oauth_id = '{$oauthId}' ",
			'user_id');
		if ($oauthUserRow)
		{
			//清理oauth_user和user表不同步匹配的问题
			$tempRow = $userObj->getObj("id = '{$oauthUserRow['user_id']}'");
			if ( ! $tempRow)
			{
				$oauthUserObj->del("oauth_user_id = '{$userInfo['id']}' and oauth_id = '{$oauthId}' ");
			}
		}

		//存在绑定账号oauth_user与user表同步正常！
		if (isset($tempRow) && $tempRow)
		{
			$userRow = plugin::trigger("isValidUser", array($tempRow['username'], $tempRow['password']));
			plugin::trigger("userLoginCallback", $userRow);
			$callback = plugin::trigger('getCallback');
			$callback = $callback ? $callback : "/ucenter/index";
			$this->redirect($callback);
		}
		//没有绑定账号
		else
		{
			$userCount = $userObj->getObj("username = '{$userInfo['name']}'", 'count(*) as num');

			//没有重复的用户名
			if ($userCount['num'] == 0)
			{
				$username = $userInfo['name'];
			}
			else
			{
				//随即分配一个用户名
				$username = $userInfo['name'].$userCount['num'];
			}
			$userInfo['name'] = $username;
			ISession::set('oauth_id', $oauthId);
			ISession::set('oauth_userInfo', $userInfo);
			$this->setRenderData($userInfo);
			$this->redirect('bind_user', FALSE);
		}
	}

	//执行绑定已存在用户
	public function bind_exists_user()
	{
		$login_info     = IReq::get('login_info');
		$password       = IReq::get('password');
		$oauth_id       = IFilter::act(ISession::get('oauth_id'));
		$oauth_userInfo = IFilter::act(ISession::get('oauth_userInfo'));

		if ( ! $oauth_id || ! $oauth_userInfo || ! isset($oauth_userInfo['id']))
		{
			IError::show("缺少oauth信息");
		}

		if ($userRow = plugin::trigger("isValidUser", array($login_info, md5($password))))
		{
			$oauthUserObj = new IModel('oauth_user');

			//插入关系表
			$oauthUserData = array(
				'oauth_user_id' => $oauth_userInfo['id'],
				'oauth_id'      => $oauth_id,
				'user_id'       => $userRow['user_id'],
				'datetime'      => ITime::getDateTime(),
			);
			$oauthUserObj->setData($oauthUserData);
			$oauthUserObj->add();

			plugin::trigger("userLoginCallback", $userRow);

			//自定义跳转页面
			$this->redirect('/site/success?message='.urlencode("登录成功！"));
		}
		else
		{
			$this->setError("用户名和密码不匹配");
			$_GET['bind_type'] = 'exists';
			$this->redirect('bind_user', FALSE);
			Util::showMessage("用户名和密码不匹配");
		}
	}

	//执行绑定注册新用户用户
	public function bind_not_exists_user()
	{
		$oauth_id       = IFilter::act(ISession::get('oauth_id'));
		$oauth_userInfo = IFilter::act(ISession::get('oauth_userInfo'));

		if ( ! $oauth_id || ! $oauth_userInfo || ! isset($oauth_userInfo['id']))
		{
			IError::show("缺少oauth信息");
		}

		//调用_userInfo注册插件
		$result = plugin::trigger('userRegAct', $_POST);
		if (is_array($result))
		{
			//插入关系表
			$oauthUserObj  = new IModel('oauth_user');
			$oauthUserData = array(
				'oauth_user_id' => $oauth_userInfo['id'],
				'oauth_id'      => $oauth_id,
				'user_id'       => $result['id'],
				'datetime'      => ITime::getDateTime(),
			);
			$oauthUserObj->setData($oauthUserData);
			$oauthUserObj->add();
			$this->redirect('/site/success?message='.urlencode("注册成功！"));
		}
		else
		{
			$this->setError($result);
			$this->redirect('bind_user', FALSE);
			Util::showMessage($result);
		}
	}

	/**
	 * @brief 商户的增加动作
	 */
	public function seller_reg()
	{
		$seller_name = IValidate::name(IReq::get('seller_name')) ? IReq::get('seller_name') : "";
		$email       = IValidate::email(IReq::get('email')) ? IReq::get('email') : "";
		$truename    = IValidate::name(IReq::get('true_name')) ? IReq::get('true_name') : "";
		$phone       = IValidate::phone(IReq::get('phone')) ? IReq::get('phone') : "";
		$mobile      = IValidate::mobi(IReq::get('mobile')) ? IReq::get('mobile') : "";
		$home_url    = IValidate::url(IReq::get('home_url')) ? IReq::get('home_url') : "";

		$password   = IFilter::act(IReq::get('password'));
		$repassword = IFilter::act(IReq::get('repassword'));
		$province   = IFilter::act(IReq::get('province'), 'int');
		$city       = IFilter::act(IReq::get('city'), 'int');
		$area       = IFilter::act(IReq::get('area'), 'int');
		$address    = IFilter::act(IReq::get('address'));

		if ($password == '')
		{
			$errorMsg = '请输入密码！';
		}

		if ($password != $repassword)
		{
			$errorMsg = '两次输入的密码不一致！';
		}

		if ( ! $seller_name)
		{
			$errorMsg = '填写正确的登陆用户名';
		}

		if ( ! $truename)
		{
			$errorMsg = '填写正确的商户真实全称';
		}

		//创建商家操作类
		$sellerDB = new IModel("seller");
		if ($seller_name && $sellerDB->getObj("seller_name = '{$seller_name}'"))
		{
			$errorMsg = "登录用户名重复";
		}
		else
		{
			if ($truename && $sellerDB->getObj("true_name = '{$truename}'"))
			{
				$errorMsg = "商户真实全称重复";
			}
		}

		//操作失败表单回填
		if (isset($errorMsg))
		{
			$this->sellerRow = IFilter::act($_POST, 'text');
			$this->redirect('seller', FALSE);
			Util::showMessage($errorMsg);
		}

		//待更新的数据
		$sellerRow = array(
			'true_name' => $truename,
			'phone'     => $phone,
			'mobile'    => $mobile,
			'email'     => $email,
			'address'   => $address,
			'province'  => $province,
			'city'      => $city,
			'area'      => $area,
			'home_url'  => $home_url,
			'is_lock'   => 1,
		);

		//商户资质上传
		if (isset($_FILES['paper_img']['name']) && $_FILES['paper_img']['name'])
		{
			$uploadObj = new PhotoUpload();
			$uploadObj->setIterance(FALSE);
			$photoInfo = $uploadObj->run();
			if (isset($photoInfo['paper_img']['img']) && file_exists($photoInfo['paper_img']['img']))
			{
				$sellerRow['paper_img'] = $photoInfo['paper_img']['img'];
			}
		}

		$sellerRow['seller_name'] = $seller_name;
		$sellerRow['password']    = md5($password);
		$sellerRow['create_time'] = ITime::getDateTime();

		$sellerDB->setData($sellerRow);
		$sellerDB->add();

		//短信通知商城平台
		if ($this->_siteConfig->mobile)
		{
			$content = smsTemplate::sellerReg(array('{true_name}' => $truename));
			$result  = Hsms::send($this->_siteConfig->mobile, $content, 0);
		}

		$this->redirect('/site/success?message='.urlencode("申请成功！请耐心等待管理员的审核"));
	}

	//添加地址ajax
	function address_add()
	{
		$id          = IFilter::act(IReq::get('id'), 'int');
		$accept_name = IFilter::act(IReq::get('accept_name'));
		$province    = IFilter::act(IReq::get('province'), 'int');
		$city        = IFilter::act(IReq::get('city'), 'int');
		$area        = IFilter::act(IReq::get('area'), 'int');
		$address     = IFilter::act(IReq::get('address'));
		$zip         = IFilter::act(IReq::get('zip'));
		$telphone    = IFilter::act(IReq::get('telphone'));
		$mobile      = IFilter::act(IReq::get('mobile'));
		$user_id     = $this->user['user_id'];

		//整合的数据
		$sqlData = array(
			'user_id'     => $user_id,
			'accept_name' => $accept_name,
			'zip'         => $zip,
			'telphone'    => $telphone,
			'province'    => $province,
			'city'        => $city,
			'area'        => $area,
			'address'     => $address,
			'mobile'      => $mobile,
			'is_default'  => 1,
		);

		$checkArray = $sqlData;
		unset($checkArray['telphone'], $checkArray['zip'], $checkArray['user_id']);
		foreach ($checkArray as $key => $val)
		{
			if ( ! $val)
			{
				$result = array('result' => FALSE, 'msg' => '请仔细填写收货地址');
				die(JSON::encode($result));
			}
		}

		if ($user_id)
		{
			$model = new IModel('address');
			//先将其他所有的取消默认选项
			$model->setData(array('is_default' => 0));
			$model->update("user_id = ".$this->user['user_id']);
			$model->setData($sqlData);
			if ($id)
			{
				$model->update("id = ".$id." and user_id = ".$user_id);
			}
			else
			{
				$id = $model->add();
			}
			$sqlData['id'] = $id;
		}
		//访客地址保存
		else
		{
			ISafe::set("address", $sqlData);
		}

		$areaList                = area::name($province, $city, $area);
		$province_val            = $areaList[$province];
		$city_val                = $areaList[$city];
		$area_val                = $areaList[$area];
		$sqlData['province_val'] = $province_val;
		$sqlData['city_val']     = $city_val;
		$sqlData['area_val']     = $area_val;

		//生成HTML代码
/*            $htmlOutput =
<<< OEF
        <li class="curr" link="{url:/ucenter/address_default/id/$id/is_default/1}">
            <p>收货人：{$accept_name}&nbsp;&nbsp;{$mobile}</p>
            <p class="order-add1">收货地址：{$province_val} {$city_val} {$area_val} {$address}</p>
            <span class="line"></span>
            <div class="address-cz">
                <label class="am-radio am-warning">
                    <input type="radio" onclick="evalUrl('{url:/ucenter/address_default/id/$id/is_default/0}')" name="radio3" value="" data-am-ucheck checked>取消默认
                </label>
                <a href="javascript:void(0);" onclick='editAddress({$id})' class="editButton"><i class="icon iconfont icon-bianji"></i>&nbsp;编辑</a>
                <a href="javascript:void(0);" link="{url:/ucenter/address_del/id/$id}" class="deleteButton"><i class="icon iconfont icon-lajixiang-copy"></i>&nbsp;删除</a>
            </div>
        </li>
OEF;

            $sqlData['temp']=$htmlOutput;*/

		$result = array('data' => $sqlData);
		die(JSON::encode($result));
	}
}

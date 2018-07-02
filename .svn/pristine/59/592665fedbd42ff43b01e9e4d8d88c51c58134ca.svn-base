/**
 * 订单对象
 * address:收货地址; delivery:配送方式; payment:支付方式;
 */
function orderFormClass()
{
	_self = this;

	//商家信息
	this.seller = null;

	//默认数据
	this.deliveryId   = 0;

	//免运费的商家ID
	// this.freeFreight  = [];

	//订单各项数据
	this.orderAmount  = 0;//订单金额
	this.goodsSum     = 0;//商品金额
	this.deliveryPrice= 0;//运费金额
	// this.paymentPrice = 0;//支付金额
	this.taxPrice     = 0;//税金
	// this.protectPrice = 0;//保价
	// this.ticketPrice  = 0;//代金券
	this.weightVal    = 0;//重量值（kg）

	//算账
	this.doAccount = function()
	{
		//税金
		this.taxPrice = $('input:checkbox[name="taxes"]:checked').length > 0 ? $('input:checkbox[name="taxes"]:checked').val() : 0;

		this.deliveryPrice=parseFloat($('input:radio[name="radio_address"]').attr('yunfei'));
		// console.log(this.deliveryPrice);
		// console.log(parseFloat(this.goodsSum)+'-----'+parseFloat(this.deliveryPrice)+'-----'+parseFloat(this.taxPrice)+'-----'+parseFloat(this.paymentPrice));
		//最终金额
		this.orderAmount = parseFloat(this.goodsSum)  + parseFloat(this.deliveryPrice)  + parseFloat(this.taxPrice)+ parseFloat(this.paymentPrice) ;
		// console.log(this.orderAmount);
		this.orderAmount = this.orderAmount <=0 ? 0 : this.orderAmount.toFixed(2);
		// console.log(this.orderAmount);
		//刷新DOM数据
		$('#final_sum').html(this.orderAmount);
		$('[name="ticket_value"]').html(this.ticketPrice);
		$('#delivery_fee_show').html(this.deliveryPrice);
		$('#protect_price_value').html(this.protectPrice);
		$('#payment_value').html(this.paymentPrice);
		$('#tax_fee').html(this.taxPrice);
	}



	//地址修改
	this.addressEdit = function(addressId)
	{
		art.dialog.open(creatUrl("block/address/id/"+addressId),
		{
			"id":"addressWindow",
			"title":"修改收货地址",
			"ok":function(iframeWin, topWin){
				var formObject = iframeWin.document.forms[0];
				if(formObject.onsubmit() === false)
				{
					alert("请正确填写各项信息");
					return false;
				}
				$.getJSON(formObject.action,$(formObject).serialize(),function(content){
					if(content.result == false)
					{
						alert(content.msg);
						return;
					}
					addressId ? $('#addressItem'+addressId).remove() : $('#addressItem:first').remove();

					//修改后的节点增加
					var addressLiHtml = template.render('addressLiTemplate',{"item":content.data});
					$('.addr_list').prepend(addressLiHtml);
					$('input:radio[name="radio_address"]:first').trigger('click');

					art.dialog({"id":"addressWindow"}).close();
				});
				return false;
			},
			"okVal":"修改",
			"cancel":true,
			"cancelVal":"取消",
		});
	}

	//地址删除
	this.addressDel  = function(addressId)
	{
		$('#addressItem'+addressId).remove();
		$.get(creatUrl("ucenter/address_del"),{"id":addressId});
	}

	//地址增加
	this.addressAdd  = function()
	{
		art.dialog.open(creatUrl("block/address"),
		{
			"id":"addressWindow",
			"title":"添加收货地址",
			"ok":function(iframeWin, topWin){
				var formObject = iframeWin.document.forms[0];
				if(formObject.onsubmit() === false)
				{
					alert("请正确填写各项信息");
					return false;
				}
				$.getJSON(formObject.action,$(formObject).serialize(),function(content){
					if(content.result == false)
					{
						alert(content.msg);
						return;
					}
					var addressLiHtml = template.render('addressLiTemplate',{"item":content.data});
					$('.addr_list').prepend(addressLiHtml);
					$('input:radio[name="radio_address"]:first').trigger('click');

					art.dialog({"id":"addressWindow"}).close();
				});
				return false;
			},
			"okVal":"添加",
			"cancel":true,
			"cancelVal":"取消",
		});
	}
	//address初始化
	this.addressInit = function()
	{
		var addressList = $('input:radio[name="radio_address"]');
		var marketid_str=addressList.attr('marketidStr');
		var cityVal=addressList.attr('cityVal');
		var addVal=addressList.attr('addVal');
		$.getJSON(creatUrl("block/getyunfei"),{"marketid_str":marketid_str,"cityVal":cityVal,"addVal":addVal,"weightVal":this.weightVal},function(json){
			var sumyunfu=0;
			for(indexVal in json){
				sumyunfu+=json[indexVal].yunfei;
			}
			addressList.attr('yunfei',sumyunfu);
			_self.addSelected(sumyunfu);
		});
	}
	// 选择地址
	this.getDelivery = function(id)
	{
		console.log(id);
		var seladdress=$('input:radio[name="radio_address"]');
		var marketid_str=seladdress.attr('marketidStr');
		var cityVal=$('#cityval_'+id).html();
		var addVal=$('#addval_'+id).html();
		// console.log(marketid_str+'----'+cityVal+'----'+addVal);

		$.getJSON(creatUrl("block/getyunfei"),{"marketid_str":marketid_str,"cityVal":cityVal,"addVal":addVal},function(json){
			var sumyunfu=0;
			for(indexVal in json){
				sumyunfu+=json[indexVal].yunfei;
			}
			seladdress.attr('yunfei',sumyunfu);
			_self.addSelected(sumyunfu);
			$('[name="radio_address"]').val(id);
			$(".dizhiyes_box").css("display","none");
	        $(".orderps").css("display","block");
	        $(".orderCon").css("display","block");
	        $(".goodsTijiao").css("display","block");
	        $("#dizhiXuanze").html($('#address_'+id).html());
		});
	}
	this.addSelected=function(sumyunfu)
	{
		this.deliveryPrice=sumyunfu;
		_self.doAccount();
	}



	//delivery初始化
	this.deliveryInit = function(defaultDeliveryId)
	{
		this.deliveryId = defaultDeliveryId;
	}

	//payment初始化
	this.paymentInit = function(defaultPaymentId)
	{
		if(defaultPaymentId > 0)
		{
			$('input:radio[name="payment"][value="'+defaultPaymentId+'"]').trigger('click');
		}
	}

	//payment选择
	this.paymentSelected = function(paymentId)
	{
		var paymentObj = $('input[type="radio"][name="payment"][value="'+paymentId+'"]');
		this.paymentPrice = paymentObj.attr("alt");
		this.doAccount();
	}

	//检查表单是否可以提交
	this.isSubmit = function()
	{
		var paymentObj  = $('input[type="radio"][name="payment"]:checked');
		var accept_time  = $('select[name="accept_time"]').val();
		if(accept_time==0)
		{
			alert("请选择配送时限");
			return false;
		}

		if(paymentObj.length == 0 && deliveryObj.attr('paytype') != "1")
		{
			alert("请选择支付方式");
			return false;
		}
		return true;
	}


	//代金券显示
	this.ticketShow = function()
	{
		var sellerArray = [];
		for(var seller_id in this.seller)
		{
			sellerArray.push(seller_id);
		}

		art.dialog.open(creatUrl("block/ticket/sellerString/"+sellerArray.join("_")),{
			title:'选择代金券',
			okVal:'使用',
			ok:function(iframeWin, topWin)
			{
				//动态创建代金券节点
				_self.getForm().find("input[name='ticket_id[]']").remove();

				var formObject   = iframeWin.document.forms["ticketForm"];
				var resultTicket = 0;
				$(formObject).find("[name='ticket_id']:checked").each(function()
				{
					var sid    = $(this).attr('seller');
					var tprice = parseFloat($(this).attr('price'));

					//专用代金券
					if(_self.seller[sid] > 0)
					{
						resultTicket += (tprice >= _self.seller[sid]) ? _self.seller[sid] : tprice;
					}
					//通用代金券
					else if(sid == '0')
					{
						var maxPrice = 0;
						for(var sellerId in _self.seller)
						{
							if(_self.seller[sellerId] > maxPrice)
							{
								maxPrice = _self.seller[sellerId];
							}
						}
						resultTicket += (tprice >= maxPrice) ? maxPrice : tprice;
					}
					//动态插入节点
					_self.getForm().prepend("<input type='hidden' name='ticket_id[]' value='"+$(this).val()+"' />");
				});
				_self.ticketPrice = resultTicket;
				_self.doAccount();
			},
			"cancel":true,
			"cancelVal":"取消",
		});
	}

	//获取form表单
	this.getForm = function()
	{
		return $('form[name="order_form"]').length == 1 ? $('form[name="order_form"]') : $('form:first');
	}
}
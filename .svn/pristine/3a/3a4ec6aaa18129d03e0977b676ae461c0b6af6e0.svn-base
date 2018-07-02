$(function(){
	// 设置当前页面标题以及返回路径 开始
	var pageInfo = $("#pageInfo"),
		pageInfoTitle = pageInfo.data('title');
	if (pageInfoTitle) {
		$("#page_title").html(pageInfoTitle);
	};
});
window.loadding = function(message){var message = message ? message : '正在执行，请稍后...';art.dialog({"id":"loadding","lock":true,"fixed":true,"drag":false}).content(message);}
window.unloadding = function(){art.dialog({"id":"loadding"}).close();}
window.tips = function(mess){art.dialog.tips(mess);}
window.realAlert = window.alert;
// window.alert = function(mess){art.dialog.alert(mess);}
window.realConfirm = window.confirm;
window.confirm = function(mess,bnYes,bnNo)
{
	if(bnYes == undefined && bnNo == undefined)
	{
		return eval("window.realConfirm(mess)");
	}
	else
	{
		art.dialog.confirm(
			mess,
			function(){eval(bnYes)},
			function(){eval(bnNo)}
		);
	}
}

// 购物车结算
$('#jisuan').click(function(){
	var msg='是否确认结算全部商品？';
	var link=$(this).attr('gourl');
	var ok = 'window.location.href="'+link+'"';
	window.confirm(msg,ok,function(){});
});

//倒计时
var countdown=function()
{
	var _self=this;
	this.handle={};
	this.parent={'second':'minute','minute':'hour','hour':""};
	this.add=function(id)
	{
		_self.handle.id=setInterval(function(){_self.work(id,'second');},1000);
	};
	this.work=function(id,type)
	{
		if(type=="")
		{
			return false;
		}

		var e = document.getElementById("cd_"+type+"_"+id);
		var value=parseInt(e.innerHTML);
		if( value == 0 && _self.work( id,_self.parent[type] )==false )
		{
			clearInterval(_self.handle.id);
			return false;
		}
		else
		{
			e.innerHTML=(value==0?59:(value-1));
			return true;
		}
	};
};


//定位当前位置获取所有市场
$('.areaS').on('change',function(){
	alert($(this).val());
})


// 注册多图片上传
$('.imgup_show').on("change",function(){
	var index=$(this);
	if (!$(this).val().match(/.jpg|.jpeg|.gif|.png|.bmp/i)){
		return alert("图片"+$(this).val()+"上传格式不正确，请重新选择");
	}
	file =this.files[0];
	var objUrl = getObjectURL(file) ; //获取图片的路径，该路径不是图片在本地的路径
	if (objUrl) {
		index.parent().css({"background":"url('"+objUrl+"')",'background-size':'1rem'}) ; //将图片路径存入src中，显示出图片
	}
})

//图片上传（单图片）
$(".imgupload").on("change",function(){
	if (!$(this).val().match(/.jpg|.jpeg|.gif|.png|.bmp/i)){
		return alert("图片"+$(this).val()+"上传格式不正确，请重新选择");
	}
	var index=$(this);
	var lookid=index.attr('lookid');
	var url=index.attr('saveurl');
	// alert(lookid+'--'+shujubiao);
	file =this.files[0];
	var fd = new FormData();
	fd.append("uploadimg", file);
	fd.append("lookid", lookid);
	
	$(".submit").html('正在上传图片');
	$(".submit").attr('disabled',true);

	$.ajax({    
        url : url,    
        type : 'POST',    
        data : fd,
		cache: false,  
        processData: false,  
        contentType: false,		
        success : function(result){
        	
        	if(result['code']==1)
        	{
        		window.location.reload();
        	}else{
        		alert(result.msg);
        	}
        	$(".submit").html('确定');
        	$(".submit").removeAttr('disabled');
        },
		dataType:"json",
    })

});


// 首页搜索
$('.search-show').click(function(){
	$(this).hide();
	$('.search-from').show();
});
$('#searchFrom').click(function(){
	if($('.keywords').val!='')
	{
		 $("form[name='searchFrom']").submit(); 
	}else{
		alert('请输入关键词');
	}
});

//首页选择
$('#curMarket').on('change',function(){
	var index=$(this);
	var curMarketid=index.val();
	var url=index.attr('gurl');
	$.ajax({    
        url : url,    
        type : 'POST',    
        data : {'marketid':curMarketid},
        dataType:"json",    
        success : function(res){
        	if(res.code==1)
        	{
        		index.attr('curid',curMarketid);
        		location.reload();
        	}
            
        },
    })
});

// 列表显示
$("#listShow ul li").on("click",function  () {
	var index = $(this);
	var curId=$(this).attr('curId');
	var classShow=$(this).parent().attr('classShow');
	var curClass=index.parent().attr('curClass');
	showDiv(index,curId,curClass,classShow);
})
function showDiv(index,curId,curClass,classShow)
{

	var htmlText=$('.showid_'+curId).html();
	index.addClass(curClass).siblings('li').removeClass(curClass);
	$('.'+classShow).empty();
	$('.'+classShow).append(htmlText);
}

// 选择显示
$('.curSelect ul li').click(function(){
    var index = $('.curSelect ul li').index(this);
    var classShow=$(this).parent().parent().attr('classShow');
    $(this).addClass(classShow).siblings('li').removeClass(classShow);
    $('.curSelect .Box:eq('+index+')').show().siblings('.Box').hide();
})

// 详情页点击收藏
$(".nsc").click(function(){
	$(this).css("display","none");
	$(this).siblings(".ysc").css("display","block");
})


// 地址管理
$(".address-list > li > p").click(function(e) {
    $(this).parent().addClass("curr").siblings().removeClass("curr");
    var link=$(this).parent().attr('link');
    // 设置为默认选择
    $.get(link, function(result){
	    setTimeout(function() {
	            // history.back(-1);
	            window.location.href=document.referrer;
	    }, 500);
	});
   	// eval('window.location.href="'+link+'"');
    
});

// 地址删除
$(".deleteButton").click(function(e) {
	var link=$(this).attr('link');
	var msg="确定要删除么？";
	if(typeof($(this).attr("msg"))!="undefined")
	{
		msg=$(this).attr("msg");
	}
	var ok = 'window.location.href="'+link+'"';
    window.confirm(msg,ok,function(){$(this).parent().parent().remove();});
});


function editAddress(addressId)
{
	art.dialog.open(creatUrl("block/address/id/"+addressId),
	{
		"id":"addressWindow",
		"title":"收货地址",
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
				window.location.reload();
			});
			return false;
		},
		"okVal":"提交",
		"cancel":true,
		"cancelVal":"取消",
	});
}


// 执行url动作
function evalUrl(link)
{
	eval('window.location.href="'+link+'"');
}
// 跳转函数
function gourl(url){
	window.location.href = url;
}
function refurl()
{
	window.location.reload();
}


// 注册验证
function regActCheck()
{
	var seller_name=$('#seller_name').val();
	var person_charge=$('#person_charge').val();
	var mobile=$('#mobile').val();
	var true_name=$('#true_name').val();
	var tax_number=$('#tax_number').val();
	if(!seller_name || seller_name=='')
	{
		alert('餐厅名称不能为空');return false;
	}
	if(!person_charge || person_charge=='')
	{
		alert('负责人不能为空');return false;
	}
	if(!mobile || mobile=='')
	{
		alert('手机号码不能为空');return false;
	}
	var regularC = /^(0|86|17951)?(13[0-9]|15[0-9]|17[0-9]|18[0-9]|14[57])[0-9]{8}$/;
	if(!regularC.test(mobile))
	{
		alert('手机号格式有误');
		return false;
	}
	if(!true_name || true_name=='')
	{
		alert('注册名称不能为空');return false;
	}
	if(!tax_number || tax_number=='')
	{
		alert('税号不能为空');return false;
	}
}


// 我的收藏编辑
function mycheck(val)
{
	if($("#collect"+val).is(':checked'))
	{
		$(".label"+val).addClass("collectd");
		$(".collectbox").fadeIn(300)
		$(".kong").fadeIn()
	}
	else
	{
		$(".label"+val).removeClass("collectd");
		$(".collectbox").fadeOut(300)
		$(".kong").fadeOut()
	}
}

//建立一个可存取到该file的url
function getObjectURL(file) {
	var url = null ;
	if (window.createObjectURL!=undefined) { // basic
		url = window.createObjectURL(file) ;
	} else if (window.URL!=undefined) { // mozilla(firefox)
		url = window.URL.createObjectURL(file) ;
	} else if (window.webkitURL!=undefined) { // webkit or chrome
		url = window.webkitURL.createObjectURL(file) ;
	}
	return url ;
}











$("#jiedan p").on("click",function  () {
	$(this).hide().siblings().show();
})


$(".dingdannav li").on("click",function  () {
	var index = $(this).index();
	$(this).addClass("active").siblings().removeClass("active");
	$(".ddlist").eq(index).show().siblings().hide()
})

$(".back").click(function  () {
	window.location.go(-1);
})







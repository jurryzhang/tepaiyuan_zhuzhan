﻿/*计算总钱数*/
function total(){
	setTimeout(function(){
		var S=0;
	    $.each($('.total'), function() {
	    	var $ol_total=$(this).prev('ol').find("input[type='checkbox']");
	    	var s=0;
	        var n1=0;
			var s1=0;
	    	$.each($(this).prev('ol').find(".number"), function(i) {
				// if($ol_total.eq(i).attr("checked")=="checked"){
				// 	s=s+parseInt($(this).val())*parseFloat($(this).parent().prev().html().replace("￥",""));
				// 	n1=n1+parseInt($(this).val());
				// 	s1 = parseFloat($('.totyf').children("span").html().replace("￥",""));
				// }
				s=s+parseInt($(this).val())*parseFloat($(this).parent().prev().html().replace("￥",""));
				n1=n1+parseInt($(this).val());
				// s1 = parseFloat($('.totyf').children("span").html().replace("￥",""));
			});
			$(this).children("span").html("￥"+s.toFixed(2));
			$(this).children("number").html(n1);

			// S=S+s+s1;
			S=S+s;
	    });
	$(".bottom span").html(S.toFixed(2));
	},100)
}
/*计算总钱数*/
/*判断有无数据*/
function hide(){
	if ($(".content").length==0) {
		$(".bottom").hide();
		$(".no").css("display","block");
		return;
	}else{
		$(".bottom").eq(0).show();
		$(".no").css("display","none");
	}
}
/*判断有无数据*/
/*判断是否全选*/
$(".gwcxz label").hide();
$(".bottom .bottom-label").hide();
function sum(){
	if ($("ol input[checked='checked']").length==$("ol li").length) {
		$(".bottom input[type=checkbox]").attr("checked","checked");
		$(".bottom input[type=checkbox]").next('i').removeClass('icon-yuanquan-copy');
		$(".bottom input[type=checkbox]").next('i').addClass('icon-xuanzhong');
	}else{
		$(".bottom input[type=checkbox]").removeAttr("checked");
		$(".bottom input[type=checkbox]").next('i').removeClass('icon-xuanzhong');
		$(".bottom input[type=checkbox]").next('i').addClass('icon-yuanquan-copy');
	}
}
/*判断是否全选*/
/*给单选框或复选框添加样式*/
function checkbox($this){
	if($this.attr('type')=="checkbox"){
		   if ($this.attr('checked')=="checked") {
		   	$this.removeAttr("checked");
		   	$this.next('i').removeClass('icon-xuanzhong');
			$this.next('i').addClass('icon-yuanquan-copy');
		   }else{
		   	$this.attr("checked","checked");
			$this.next('i').removeClass('icon-yuanquan-copy');
			$this.next('i').addClass('icon-xuanzhong');
		   }
		}
		/*计算总钱数*/
		total();
		/*计算总钱数*/
}
/*给单选框或复选框添加样式*/







$(function(){
	hide();
	total();
	/*编辑*/
	$("#header span").click(function(){
		   if ($(this).html()=="编辑") {
			$(this).html("完成");
			$(".bottom").eq(1).show();
			$(".gwcxz label").show();
		   }else{
			$(this).html("编辑");
			$(".bottom").eq(1).hide();
			$(".gwcxz label").hide();
			$(".bottom .bottom-label").hide();
		   }
		   hide();
	});
	/*编辑*/
	/*底部全选*/
	$('.bottom-label input').change(function(){
			if ($(this).attr("checked")=="checked") {
				$(".con input[type='checkbox']").removeAttr("checked");
				$(".con input[type='checkbox']").next('i').removeClass('icon-xuanzhong');
				$(".con input[type='checkbox']").next('i').addClass('icon-yuanquan-copy');
			}else{
				$(".con input[type='checkbox']").attr("checked","checked");
				$(".con input[type='checkbox']").next('i').removeClass('icon-yuanquan-copy');
				$(".con input[type='checkbox']").next('i').addClass('icon-xuanzhong');
			}
			checkbox($(this));
	})
	/*底部全选*/
	/*子项全选*/
	$('.list input').change(function(){
		var $list_input=$(this).parents('.list').siblings('ol').find('input[type=checkbox]');
			if ($(this).attr("checked")==undefined) {
				$list_input.attr("checked","checked");
				$list_input.next('i').removeClass('icon-yuanquan-copy');
				$list_input.next('i').addClass('icon-xuanzhong');
			}else{
				$list_input.removeAttr("checked");
				$list_input.next('i').removeClass('icon-xuanzhong');
				$list_input.next('i').addClass('icon-yuanquan-copy');
			}
			checkbox($(this));
			sum();
	})
	/*子项全选*/
	$("ol input[type='checkbox']").change(function(){
		checkbox($(this));
		var $ol_input=$(this).parents('ol').prev('.list').find('input');
		if($(this).parents('ol').find("input[checked='checked']").length==$(this).parents("ol").children('li').length){
			$ol_input.attr("checked","checked");
			$ol_input.next('i').removeClass('icon-yuanquan-copy');
			$ol_input.next('i').addClass('icon-xuanzhong');
		}else{
			$ol_input.removeAttr("checked");
			$ol_input.next('i').removeClass('icon-xuanzhong');
			$ol_input.next('i').addClass('icon-yuanquan-copy');
		}
		sum();
	})
	/*点击加一*/
	$('.btn2').click(function(){
		$(this).prev('.number').val(parseInt($(this).prev('.number').val())+1);
		/*计算总钱数*/
	total();
	/*计算总钱数*/
	})
})
/*删除*/
$('.delete').click(function(){
	var url=$(this).attr('gurl');
	var gdelStr='';
	var pdelStr='';
	$.each($('li'), function() {
		if ($(this).find("input[type=checkbox]").attr("checked")=="checked") {
			var Gid=$(this).find("input[type=checkbox]").attr("Gid");
			var Gtype=$(this).find("input[type=checkbox]").attr("Gtype");
			var index=$(this);
			if(Gtype=='product'){pdelStr+=Gid+',';}else{gdelStr+=Gid+',';}
			
			index.remove();
		}
	});
	$.getJSON(url,{"gdelStr":gdelStr,"pdelStr":pdelStr,"random":Math.random()},function(){});
	$('input[type=checkbox]').attr("checked","checked");
	$('input[type=checkbox]').next('i').removeClass('icon-yuanquan-copy');
	$('input[type=checkbox]').next('i').addClass('icon-xuanzhong');
	$.each($(".content"), function() {
		if ($(this).find("ol li").length==0) {
			$(this).remove();
		}
	});
	hide();
	total();
});
/*删除*/




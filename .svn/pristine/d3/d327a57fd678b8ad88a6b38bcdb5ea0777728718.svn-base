$(function(){
	// 设置当前页面标题以及返回路径 开始
	var pageInfo = $("#pageInfo"),
		pageInfoTitle = pageInfo.data('title');
	if (pageInfoTitle) {
		$("#page_title").html(pageInfoTitle);
	};
});

// 分类页选择显示
$('#sidebar ul li').click(function(){
	$(this).addClass('curS').siblings('li').removeClass('curS');
	var index = $(this).index();
	$('.j-content').css("display","none");
	$('.j-content').eq(index).css("display","block");
})



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


$(".foodTitle ul li").on("click",function  () {
	var index = $(this).index();
	$(this).addClass("curLi").siblings().removeClass("curLi");
	$(".foodText").css("display","none")
	$(".foodText").eq(index).css("display","block")
})

// 地址管理
$(".address-list > li > p").click(function(e) {
    $(this).parent().addClass("curr").siblings().removeClass("curr");
    setTimeout(function() {
            history.back();
    }, 500);
});
$(".deleteButton").click(function(e) {
    $(this).parent().parent().remove();
});



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







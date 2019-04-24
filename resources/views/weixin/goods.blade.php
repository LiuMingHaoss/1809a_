<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

    <!-- Styles -->
    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 200;
            height: 100vh;
            margin: 0;
        }
        .full-height {
            height: 100vh;
        }
        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }
        .position-ref {
            position: relative;
        }
        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }
        .content {
            text-align: center;
        }
        .title {
            font-size: 84px;
        }
        .links > a {
            color: #636b6f;
            padding: 0 25px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }
        .m-b-md {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
<div class="flex-center position-ref full-height">
    @if (Route::has('login'))
        <div class="top-right links">
            @auth
                <a href="{{ url('/home') }}">Home</a>
            @else
                <a href="{{ route('login') }}">Login</a>

                @if (Route::has('register'))
                    <a href="{{ route('register') }}">Register</a>
                @endif
            @endauth
        </div>
    @endif

    <div class="content">
        <div class="title m-b-md">

        </div>

        <div id="qrcode"></div>

        <div class="links">
            <table>
                <tr>
                    <td>商品名称</td>
                    <td>商品价格</td>
                </tr>
                <tr>
                    <td>创维</td>
                    <td>4999</td>
                </tr>
            </table>
        </div>
    </div>
</div>

<button id="btn">选择图片</button>
<script src="/js/jquery/jquery-1.12.4.min.js"></script>
<script src="http://res2.wx.qq.com/open/js/jweixin-1.4.0.js"></script>
<script>
    wx.config({
        debug: true, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
        appId: "{{$jsconfig['appId']}}", // 必填，公众号的唯一标识
        timestamp:"{{$jsconfig['timestamp']}}" , // 必填，生成签名的时间戳
        nonceStr:"{{$jsconfig['nonceStr']}}", // 必填，生成签名的随机串
        signature:"{{$jsconfig['signature']}}",// 必填，签名
        jsApiList: ['updateAppMessageShareData','chooseImage','updateTimelineShareData'] // 必填，需要使用的JS接口列表
    });
    wx.ready(function(){
            $('#btn').click(function(){
                wx.chooseImage({
                    count: 1, // 默认9
                    sizeType: ['original', 'compressed'], // 可以指定是原图还是压缩图，默认二者都有
                    sourceType: ['album', 'camera'], // 可以指定来源是相册还是相机，默认二者都有
                    success: function (res) {
                        var localIds = res.localIds; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
                        alert(localIds);
                    }
                });
            })

            //分享至朋友 qq
            wx.updateAppMessageShareData({
                title: '商品详情了解一下', // 分享标题
                desc: '商品详情', // 分享描述
                link: 'http://1809liuminghao.comcto.com/weixin/goods', // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
                imgUrl: 'http://1809liuminghao.comcto.com/goodsImg/20190220/3a7b8dea4c6c14b2aa0990a2a2f0388e.jpg', // 分享图标
                success: function () {
                    // 设置成功
                }
            })

            wx.onMenuShareAppMessage({
                title: '商品详情了解一下', // 分享标题
                desc: '商品详情', // 分享描述
                link: 'http://1809liuminghao.comcto.com/weixin/goods', // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
                imgUrl: 'http://1809liuminghao.comcto.com/goodsImg/20190220/3a7b8dea4c6c14b2aa0990a2a2f0388e.jpg', // 分享图标
                type: '', // 分享类型,music、video或link，不填默认为link
                dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
                success: function () {
                // 用户点击了分享后执行的回调函数
                }
            });
            //分享至朋友圈 空间
            wx.ready(function () {      //需在用户可能点击分享按钮前就先调用
                wx.updateTimelineShareData({
                    title: '商品详情了解一下', // 分享标题
                    link: 'http://1809liuminghao.comcto.com/weixin/goods', // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
                    imgUrl: 'http://1809liuminghao.comcto.com/goodsImg/20190220/3a7b8dea4c6c14b2aa0990a2a2f0388e.jpg', // 分享图标
                    success: function () {
                        // 设置成功
                    }
                })
            });

    })
</script>
</body>
</html>
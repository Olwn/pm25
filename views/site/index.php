<?php
/* @var $this yii\web\View */
$this->title = 'PM 2.5 日志';
?>
<div class="site-index">

    <div class="jumbotron">
        <h1>PM 2.5 日志</h1>

        <p class="lead">记录你每一天吸入多少PM 2.5的首个个体水平暴露记录平台。</p>

        <p><a class="btn btn-lg btn-success" href="http://www.yiiframework.com">下载平台应用</a></p>
    </div>

    <div class="body-content">

        <div class="row">
            <div class="col-lg-4">
                <h3>分析用户环境和运动状态</h3>

                <p>应用通过GPS信号和通过推算定位得知的用户运动轨迹，综合判断用户所处环境类型，定位到1500多个监测站中最近的站点，基于科学依据换算环境PM2.5浓度，结合用户运动状态累积计算吸入量。</p>

                <p><a class="btn btn-default" href="/pm25/web/site/about">技术要点 &raquo;</a></p>
            </div>
            <div class="col-lg-4">
                <h3>用准确数据发展环境病研究</h3>

                <p>平台将用户吸入量通过安全协议传输到服务端，加密后在数据库中存储，在经过用户本人允许的情况下开放用户吸入量数据。支持环境流行病与环境污染物吸入量的关系研究，并对科研人士和开发者开放数据。欢迎加入我们，或申请数据。</p>

                <p><a class="btn btn-default" href="/pm25/web/site/contact">申请数据 &raquo;</a></p>
            </div>
            <div class="col-lg-4">
                <h3>技术合作</h3>

                <p>我们的PM 2.5数据来源自BestApp工作室的pm25.in数据平台。同时，在PM 2.5环境浓度检测方面，我们与济南诺方电子有限公司达成了合作，在应用中对诺方公司提供的检测设备进行了接入。我们十分欢迎有志之士与我们共同构建这个平台，将之发展完善。</p>

                <p><a class="btn btn-default" href="/pm25/web/site/contact">联系我们 &raquo;</a></p>
            </div>
        </div>

    </div>
</div>

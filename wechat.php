<?php
    require('wechatClass.php');
    define("TOKEN", "yinghaofei");
    $wechatObj = new wechatCallbackapiTest();
    if (isset($_GET['echostr'])) {
        $wechatObj->valid();
    }else{
        $wechatObj->responseMsg();
    }
?>
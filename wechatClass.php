<?php
//类的结构
// class wechat
// {
//     private $postStr;//传入的原始字符串
//     private $postObj;//解析后
//
//     function __construct();//构造函数，从服务器里收到数据并解析
//     public function distribute();//判断收到的信息是哪些，分发给处理函数
//     public function text();//文本消息
//     public function location();//地理位置消息
//     public function event();//事件信息
//     private function sendText($contentStr)//负责被动文本信息的发送，传入$contentStr即可返回给微信服务器，私有成员。
// }

require_once "weather.php";     // 查询天气模块

$wechatTest = new wechat();
$wechatTest->distribute(); // 入口函数distribute

class wechat
{
    private $postStr; // 传入的原始字符串
    private $postObj; // 解析后

    function __construct() // 构造函数，从服务器里收到数据并解析
    {
        $this->postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; // 这里要根据环境自行配置
        $this->postObj = simplexml_load_string($this->postStr, 'SimpleXMLElement', LIBXML_NOCDATA); 
    }

    public function distribute() // 判断收到的信息是哪些，分发给处理函数
    {
        if (!empty($this->postStr))
        {
            $msgType = $this->postObj->MsgType;

            switch ($msgType) // 分发
            {
                case "text":
                    $this->text();
                    break;
                case "location": 
                    $this->location();
                    break;

                case "event":
                    $this->event();
                    break;
                default:
                    echo "未知的消息类别";
                    break;
            }
        }
        else // 无法得到返回值
        {
            echo "无法得到返回值";
        }

    }

    public function text() // 文本消息
    {
        $content = trim($this->postObj->Content); // 去除用户发来信息的前后空格
        if($content == "帮助") $content = "help";
		if($content == "时间") $content = "time";
  		if($content == "关于") $content = "about";
        if($content == "合作") $content = "partner";
        if($content == "投稿") $content = "post";
        if($content == "天气") $content = "weather";
        if($content == "联系") $content = "contact";
        

        $inputCityName = $content;                              /*  WEATHER  */
        if(substr($inputCityName, 0, 6) == "天气") 
        {
            $inputCityName = trim(substr($inputCityName, 6)); 
            $contentStr = getWeather($inputCityName);
        }                                   /*  END WEATHER  */
        
        
        switch ($content)
        {
            case "time":
            {
                $contentStr = "哈哈,让飞哥告诉你,当前时间为: ".date("Y-m-d H:i:s",time())." 一寸光阴一寸金,请珍惜时间.";
                break;
            }
            case "help":
            {
                $contentStr = "您好,我是飞哥!请回复:\n  'about'或'关于';\n  'time'或'时间';\n  'weather'或'天气';\n  'partner'或'合作';\n  'post'或'投稿';\n  'contact'或'联系';";
                break;
            }

            case "about":
            {
                $contentStr = "    您好!我是飞哥,是一名大三学生.我兴趣广泛,热衷于web开发,熟悉Windows, Linux, Mac OS等各种系统的......安装,精通C/C++, python, javascript, php等语言的......拼写,掌握jQuery, Django, Nodejs等各种框架的......安装.\n    输入'contact'或'联系'获得我的联系方式.";
                break;
            }
            
            case "weather":
            {
                $contentStr = "天气使用教程:\n";
                $contentStr.= "回复 '天气 城市名' 获得天气.";
                $contentStr.= " 例:\n\n      天气 上海.\n\n";
                $contentStr.= "注:  天气和城市名之间空格数不限, 城市名后不用加 '市'字.";
                break;
            }
            
            case "post":
            {
                $contentStr = "Under Construction!正在建设中!";
                break;
            }
            
            case "partner":
            {
                $contentStr = "公众平台刚刚建立,目前服务器搭建在新浪的云上.如果关注增多,会考虑升级服务器.目前有几点想法:\n    1.想找几位有php经验的朋友共同管理开发.因为我本人对前端开发更熟悉一些,所以能有熟悉后端的朋友帮助就再好不过了.\n    2.初来乍到,对微信公众平台的运作模式还不熟悉,想找一些朋友负责公众号的功能管理以及推广.\n    3.为了服务器的升级扩容,也为了激励我更好的管理公众号,欢迎各位给我sponsor,也可以请我一杯啤酒. http://me.alipay.com/hfying 我的支付宝账户为yhf406716870@gmail.com";
                $contentStr.= "\n\n我们希望通过这个平台,大家能自由的分享知识和见解;我们希望每个人都在做自己喜欢做的事情,相信没有事物是为了满足别人的需求而存在;我们希望信息能平等地流动,双手能让世界更有意义.";
                break;
            }
            
            case "contact":
            {
                $contentStr = "如果您有任何建议或意见或好点子,请务必联系我!\n您可以直接回复消息给我,也可以发邮件给我:\n\nhfying@stu.xidian.edu.cn\n\nyhf406716870@gmail.com";
                break;
            }
            default:
                break;
        }
        $this->sendText($contentStr); // 发送信息
    }

    public function location() // 地理位置消息
    {

    }

    public function event() // 事件信息
    // 包含“关注”“取消关注”“报告地理位置信息”等..
    {
        if ($this->postObj->Event == "LOCATION") // 推送的地理位置信息
        {

            return ;
        }

        if ($this->postObj->Event == "subscribe") // 关注公众号之后会执行以下代码
        {
            //示例，欢迎信息
            $contentStr = "您好！欢迎关注 <飞哥日报> 微信公众平台,我是飞哥.";
            $contentStr.= " 在这里,我会推送一些小众,精品,有价值的内容,";
            $contentStr.= " 尽可能保证分享干货给大家.";
            $contentStr.= " 请回复:\n\n     'help' 或 '帮助' \n\n获得使用教程.更多功能正在开发中.";
            $contentStr.= " 另外,我不是机器人!!!!!!!!!!!! 您可以直接发送消息给我! 也可以回复 'contact' 或 '联系' 获得我的联系方式.";
            $contentStr.= " 最后,感谢您的关注！";
            
            $this->sendText($contentStr);
            return ;
        }
        else if ($this->postObj->Event == "unsubscribe") // 取消关注
        {
            
            return ;
        }
        else // 返回错误信息
        {
            echo "未知的事件类型";
            return ;
        }
    }

    private function sendText($contentStr) // 负责被动文本信息的发送，传入$contentStr即可返回给微信服务器，私有成员。
    {
        // 不检查用户是否输入为空，如需检查请在text()中自行实现
        $fromUsername = $this->postObj->FromUserName;
        $toUsername = $this->postObj->ToUserName;
        $time = time();
        $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                    </xml>";
        $msgType = "text"; // 返回的数据类型
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr); // 格式化写入XML
        echo $resultStr; // 发送
    }
}
?>
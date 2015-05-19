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
//     private function sendText($contentStr)
// }

require_once "weather.php";
require_once "mobile.php";


function get_mobile_area($mobile){
    $sms = array('province'=>'', 'supplier'=>'');    
    $url = "http://tcc.taobao.com/cc/json/mobile_tel_segment.htm?tel=".$mobile."&t=".time();  
    
    $content = file_get_contents($url);
    //$sms = "省份: ".substr($content, "56", "6")."    运营商: ".substr($content, "81", "6");
    $sms = utf8_encode($content);
    return $sms;
}

$wechatTest = new wechat();
$wechatTest->distribute();//入口函数distribute

class wechat
{
    private $postStr;//传入的原始字符串
    private $postObj;//解析后

    function __construct()//构造函数，从服务器里收到数据并解析
    {
        $this->postStr = $GLOBALS["HTTP_RAW_POST_DATA"];//这里要根据环境自行配置
        $this->postObj = simplexml_load_string($this->postStr, 'SimpleXMLElement', LIBXML_NOCDATA); 
    }

    public function distribute()//判断收到的信息是哪些，分发给处理函数
    {
        if (!empty($this->postStr))
        {
            $msgType = $this->postObj->MsgType;

            switch ($msgType) //分发
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
        else//无法得到返回值
        {
            echo "无法得到返回值";
        }

    }

    public function text()//文本消息
    {
        $content = trim($this->postObj->Content);//去除用户发来信息的前后空格
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
        }                                                       /*  END WEATHER  */
    
        
        
        
        switch ($content)
        {
            case "time":
            {
                $contentStr = "当前时间为: ".date("Y-m-d H:i:s",time())." 一寸光阴一寸金,请珍惜时间.";
                break;
            }
            case "help":
            {
                $contentStr = "您好! 请回复:\n  'about'或'关于';\n  'time'或'时间';\n  'weather'或'天气';\n  'partner'或'合作';\n  'post'或'投稿';\n  'contact'或'联系';";
                break;
            }

            case "about":
            {
                $contentStr = "    您好!我是一名大三学生.我兴趣广泛,热衷于web开发.\n    输入'contact'或'联系'获得我的联系方式.";
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
                $contentStr = "nothing";
                break;
            }
            
            case "contact":
            {
                $contentStr = "如果您有任何建议或意见或好点子,您可以联系我!\n您可以直接回复消息给我,也可以发邮件给我:\n\nhfying@stu.xidian.edu.cn\n\nyhf406716870@gmail.com";
                break;
            }
            case "news":
            {
                $this->sendNews();
                break;
            }
            case "mobile":
            {
                $contentStr = get_mobile_area("15605791188");
                break;
            }
            case "music":
            {
                $this->sendMusic();
                break;
            }
            default:
                break;
        }
        $this->sendText($contentStr);//发送信息
    }

    public function location()//地理位置消息
    {

    }

    public function event()//事件信息
    //包含“关注”“取消关注”“报告地理位置信息”等..
    {
        if ($this->postObj->Event == "LOCATION")//推送的地理位置信息
        {

            return ;
        }

        if ($this->postObj->Event == "subscribe")//关注公众号之后会执行以下代码
        {
            //示例，欢迎信息
            $contentStr = "您好！欢迎关注我的微信公众平台.";
            $contentStr.= " 请回复:\n\n     'help' 或 '帮助' \n\n获得使用教程.更多功能正在开发中.";
            $contentStr.= " 另外,我不是机器人!!!!!!!!!!!! 您可以直接发送消息给我! 也可以回复 'contact' 或 '联系' 获得我的联系方式.";
            $contentStr.= " 最后,感谢您的关注！";
            
            $this->sendText($contentStr);
            return ;
        }
        else if ($this->postObj->Event == "unsubscribe")//取消关注
        //经测试，此处无法反馈任何信息，用户也收不到
        {
            
            return ;
        }
        else//返回错误信息
        {
            echo "未知的事件类型";
            return ;
        }
    }

    private function sendText($contentStr)//负责被动文本信息的发送，传入$contentStr即可返回给微信服务器，私有成员。
    {
        //不检查用户是否输入为空，如需检查请在text()中自行实现
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
        
        $msgType = "text";//返回的数据类型
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);//格式化写入XML
        echo $resultStr;//发送
        exit;
    }
    private function sendMusic()//负责被动文本信息的发送，传入$contentStr即可返回给微信服务器，私有成员。
    {
        //不检查用户是否输入为空，如需检查请在text()中自行实现
        $fromUsername = $this->postObj->FromUserName;
        $toUsername = $this->postObj->ToUserName;
        $time = time();
        $msgType = "music";//返回的数据类型
        $musicTpl = "<xml>  
                            <ToUserName><![CDATA[%s]]></ToUserName> 
                            <FromUserName><![CDATA[%s]]></FromUserName>         
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Music>
                            <Title><![CDATA[%s]]></Title>
                            <Description><![CDATA[%s]]></Description>
                            <MusicUrl><!CDATA[%s]]><MusicUrl>
                            </Music>
                            <FuncFlag>0</FuncFlag>
                            </xml>";   
        $resultStr = sprintf($musicTpl,
                             $fromUsername,
                             $toUsername, 
                             $time, 
                             $msgType, 
                             "夕阳无限好", 
                             "陈奕迅", 
                             "http://sc.111ttt.com/up/mp3/103941/ADEB10A50E8EE4112279AA86D84AF3F9.mp3");//格式化写入XML
        echo $resultStr;//发送
        exit;
    }
    private function sendNews() {
        //不检查用户是否输入为空，如需检查请在text()中自行实现
        $fromUsername = $this->postObj->FromUserName;
        $toUsername = $this->postObj->ToUserName;
        $time = time();
        //$msgType = "news";//返回的数据类型
        $newsTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[news]]></MsgType>
                            <ArticleCount>1</ArticleCount>
                            <Articles>
                            <item>
                            <Title><![CDATA[title]]></Title> 
                            <Description><![CDATA[description1]]></Description>
                            <PicUrl><![CDATA[http://yinghaofei.tk/images/conca.png]]></PicUrl>
                            </item>
                            </Articles>
                            <FuncFlag>0</FuncFlag>
                            </xml>" ;
        $resultStr = sprintf($newsTpl,
                             $fromUsername,
                             $toUsername, 
                             $time);//格式化写入XML
        echo $resultStr;//发送
        exit;
    }
}
?>

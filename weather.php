<?php
function getWeather($inputCityName){
    require_once "city.php";
    $city = json_decode($city, true);
    $flag = 0; // flag =0, 未找到城市名
    foreach ($city as $cityName => $cityNumber) {
        if ($inputCityName == $cityName) {
            $url = "http://www.weather.com.cn/data/cityinfo/".$cityNumber.".html";
            $flag = 1; // 找到城市名
        }
    }
    if($flag == 0)
       exit("city not exists!");

    $html = file_get_contents($url);
    $weather = json_decode($html, true);
    $result  = "您查询的城市为: ".$weather['weatherinfo']['city']."\n";
    $result .= "天气: ".$weather['weatherinfo']['weather']."\n";
    $result .= "最低气温: ".$weather['weatherinfo']['temp2']."\n";
    $result .= "最高气温: ".$weather['weatherinfo']['temp1']."\n";
    return $result;
}
?>
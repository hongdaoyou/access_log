<?php


class AccessData {

public function getAccessData($indexName, $esObj) {
    // $awkCmd = "awk -F' ' -f a.awk /var/log/nginx/access.log";
    $awkCmd = "awk -F' ' -f a.awk /usr/local/nginx/logs/access.log";

    
    $ret = shell_exec($awkCmd);
    
    $delimter = "______________\n\n";
    
    // 切分成,每个item
    $arr = explode($delimter, $ret);
    
    foreach ($arr as $item) {
        // $dataArr = explode("\n", $item);
        // var_dump($item);
        
        // 将其,转换成,数组
        $dataArr =  json_decode($item, true);
    
        // 这条,记录是有效的
        if (is_array($dataArr) && array_key_exists('ip', $dataArr)) {
            // 将其,插入到 elastic中
            // echo json_encode($dataArr );
            
            $originalFormat = "d/M/Y:H:i:s";
            $newFormat = "Y-m-d\TH:i:s";
            // $newFormat = "Y-m-d'T'H:i:s.SSS'Z'";

            $dateTimeObj = DateTime::createFromFormat($originalFormat, $dataArr['access_time']);
            $dataArr['access_time'] = $dateTimeObj->format($newFormat);
            
            // echo $dataArr['access_time'] . "<br>";
            // return;
            $esObj->setOneData($indexName, $dataArr);
            // return;

        }
    }
}

}


<?php
require_once 'vendor/autoload.php';

require_once 'accessData.php';

$accessEsObj = new AccessEs();

// // 建立,连接
$ret = $accessEsObj->connect_es();
if (! $ret) {
    echo "连接elastic 失败";
    return;
}

// 索引名字
$indexName = 'access_log';


// 删除索引
// $ret = $accessEsObj->delete_index($indexName);
// if (! $ret) {
//     echo "删除,失败";
//     return;
// }


// $ret = $accessEsObj->check_index_exist($indexName);
// if (! $ret) {
//     // 创建,索引
//     $ret = $accessEsObj->create_index($indexName);
//     if (! $ret) {
//         echo "创建,失败";
//         return;
//     }
// }

// return;
// // 建立,映射
// $ret = $accessEsObj->putMap($indexName);
// if (! $ret) {
//     echo "建立映射,失败";
//     return;
// }

// return;

// 设置 fielddata
// $ret = $accessEsObj->putFieldDataMap($indexName);
// if (! $ret) {
//     echo "建立映射,失败";
//     return;
// }

// return;



// // // 获取数据,导入数据
// $accessDataObj = new AccessData();
// $ret= $accessDataObj->getAccessData($indexName, $accessEsObj );

// return;

// 获取
// $accessEsObj->getUsrAgentGroupData($indexName);

// 获取,每小时的数据统计
// $accessEsObj->getHourGroupData($indexName);

// 获取,请求的method
$accessEsObj->getRequstDataGroupData($indexName);



class AccessEs {
    public $client = null;

    // 检查,索引,是否存在

    public function check_index_exist($indexName) {

        $para = [
            'index'=> $indexName,
        ];
        $ret = $this->client->indices()->exists($para );
    
        return $ret;
    }


    // 设置,单条数据
    public function setOneData($indexName, $dataArr) {
        $para = [
            'index'=> $indexName,
            'body' =>  $dataArr
        ];

        try {
            $this->client->index($para );
        } catch (Exception $except) {
            $msg = $except->getMessage() ;
            echo $msg;
            
        }

    }

    // 连接,elastic
    public function connect_es() {
        
        $this->client = Elasticsearch\ClientBuilder::create()
            ->setHosts([
            'http://localhost:9200'
        ])
            ->build();

        return $this->client->ping();
    }


    public function create_index($indexName) {
        // 创建索引
        $params = [
            'index' =>  $indexName,
            'body' => [
                'settings' =>  [
                    'number_of_shards' => 2,
                    'number_of_replicas' => 1
                ]
            ]
        ];

        try {
            $response = $this->client->indices()->create($params);
            // echo json_encode($response);
            return true;

        } catch(Exception $except ) {
            // $msg = $except->getMessage() ;
            // $result = json_decode($msg, true );
            // echo $msg . "\n";
            return false;
        }

    }

    // 删除索引
    public function delete_index($indexName) {
        // 创建索引
        $params = [
            'index' =>  $indexName,
        ];

        try {
            $response = $this->client->indices()->delete($params);
            // echo json_encode($response);
            return true;

        } catch(Exception $except ) {
            // $msg = $except->getMessage() ;
            // $result = json_decode($msg, true );
            // echo $msg . "\n";
            return false;
        }

    }


    // 建立,映射
    public function putMap($indexName) {
        // 建立,映射
        $para = [
            'index'=> $indexName,
            'body' => [
                "properties" => [
                    "ip" => [
                        "type"=>"keyword",
                    ],
                    "access_time" => [
                        "type"=>"date",
                    ],
                    "method" => [
                        "type"=>"keyword",
                    ],
                    "uri" => [
                        "type"=>"keyword",
                    ],
                    "httpProtocol" => [
                        "type"=>"keyword",
                    ],
                    "responeCode" => [
                        "type"=>"keyword",
                    ],
                    "dataLen" => [
                        "type"=>"integer",
                    ],
                    "userAgent" => [
                        "type"=>"keyword",
                    ],
                ]

            ]
        ];

        try {
            $ret = $this->client->indices()->putMapping($para );
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();

            return false;
        }

    }

    // 将其, text 设置 fielddata
    public function putFieldDataMap($indexName) {
        // 建立,映射
        $para = [
            'index'=> $indexName,
            'body' => [
                "properties" => [
                    "refer" => [
                        'type'=>'text',
                        "fielddata"=>"true",
                    ]
                ]
            ]
        ];

        try {
            $ret = $this->client->indices()->putMapping($para );
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();

            return false;
        }

    }
    
    // 获取,用户代理的 最大的10个
    public function getUsrAgentGroupData($indexName) {
        // 设置聚合查询参数
        $params = [
            'index' => $indexName,
            'body' => [
                'size' => 0,
                'aggs' => [
                    'group_by_field' => [
                        'terms' => [
                            'field' => 'userAgent',
                            'size' => 10
                        ]
                    ]
                ]
            ]
        ];

        try {
            // 执行聚合查询
            $response = $this->client->search($params);

            // 解析聚合结果
            $aggregations = $response['aggregations'];
            echo json_encode($aggregations) . "<br><br>";

            $groupedData = $aggregations['group_by_field']['buckets'];

            // 打印每个分组的字段值和出现次数
            foreach ($groupedData as $bucket) {
                $fieldValue = $bucket['key'];
                $count = $bucket['doc_count'];
                
                echo "Field: $fieldValue, Count: $count" . "<br>\n";
            }
        } catch (Exception $e){
            echo $e->getMessage();
            echo "查询,失败\n";
        }
    }


    
    // 获取,每个时间段 最大的10个
    public function getHourGroupData($indexName) {
        // 设置聚合查询参数
        $params = [
            'index' => $indexName,

            'body' => [
                'size' => 0, // 不显示,检索的数据
                'aggs' => [
                    'group_by_field' => [
                        'date_histogram' => [
                            'field' => 'access_time',
                            'interval'=>"hour",
                        ],
                    "aggs" => [
                        "total_dataLen"=>[
                            "sum" =>[
                                'field' => "dataLen"
                            ]
                        ]
                    ],
                    ]
                ]
            ]
        ];

                    

        try {
            // 执行聚合查询
            $response = $this->client->search($params);

            // 解析聚合结果
            $aggregations = $response['aggregations'];
            
            // echo json_encode($response) . "<br><br>";

            // echo json_encode($aggregations) . "<br><br>";

            $groupedData = $aggregations['group_by_field']['buckets'];

            // 打印每个分组的字段值和出现次数
            foreach ($groupedData as $bucket) {
                $fieldValue = $bucket['key_as_string'];
                $doc_count = $bucket['doc_count'];
                $total_dataLen = $bucket['total_dataLen']['value'];
                
                echo "Field: $fieldValue, Count: $doc_count " .  "totalSize: $total_dataLen "  . "<br>\n";
            }

        } catch (Exception $e){
            echo $e->getMessage();
            echo "查询,失败\n";
        }
    }


    // 获取,请求的分组 的统计信息
    public function getRequstDataGroupData($indexName) {
        // 设置聚合查询参数
        $params = [
            'index' => $indexName,

            'body' => [
                'size' => 0, // 不显示,检索的数据
                'aggs' => [
                    'group_by_method' => [
                        'terms' => [
                            'field' => 'method',
                        ]
                    ],
                    'group_by_responeCode' => [
                        'terms' => [
                            'field' => 'responeCode',
                        ]
                    ],
                    'group_by_uri' => [
                        'terms' => [
                            'field' => 'uri',
                        ]
                    ],
                    'group_by_userAgent'=>[
                        'terms' => [
                            'field'=> 'userAgent'
                        ]
                    ],
                    'group_by_refer'=>[
                        'terms' => [
                            'field'=> 'refer.keyword'
                        ]
                    ]
                ]
            ]
        ];

        // responeCode
        try {
            // 执行聚合查询
            $response = $this->client->search($params);

            // 解析聚合结果
            $aggregations = $response['aggregations'];
            
            // echo json_encode($response) . "<br><br>";

            // echo json_encode($aggregations) . "<br><br>";

            echo "method的分组:<br>";

            $groupedData = $aggregations['group_by_method']['buckets'];

            // 打印每个分组的字段值和出现次数
            foreach ($groupedData as $bucket) {
                $fieldValue = $bucket['key'];
                $doc_count = $bucket['doc_count'];
                // $total_dataLen = $bucket['total_dataLen']['value'];
                
                echo "Field: $fieldValue, Count: $doc_count " .  "<br>\n";
            }

                
            echo "<br><br>响应码的分组:<br>";
            
            // 返回的响应码
            $groupedData = $aggregations['group_by_responeCode']['buckets'];

            // 打印每个分组的字段值和出现次数
            foreach ($groupedData as $bucket) {
                $fieldValue = $bucket['key'];
                $doc_count = $bucket['doc_count'];
                // $total_dataLen = $bucket['total_dataLen']['value'];
                
                echo "Field: $fieldValue, Count: $doc_count " .  "<br>\n";
            }
            

            echo "<br><br>userAgent的分组:<br>";

            // 请求的 userAgent
            $groupedData = $aggregations['group_by_userAgent']['buckets'];

            // 打印每个分组的字段值和出现次数
            foreach ($groupedData as $bucket) {
                $fieldValue = $bucket['key'];
                $doc_count = $bucket['doc_count'];
                // $total_dataLen = $bucket['total_dataLen']['value'];
                
                echo "Field: $fieldValue, Count: $doc_count " .  "<br>\n";
            }

            echo "<br><br>uri的分组:<br>";
            
            // 请求的 
            $groupedData = $aggregations['group_by_uri']['buckets'];

            // 打印每个分组的字段值和出现次数
            foreach ($groupedData as $bucket) {
                $fieldValue = $bucket['key'];
                $doc_count = $bucket['doc_count'];
                // $total_dataLen = $bucket['total_dataLen']['value'];
                
                echo "Field: $fieldValue, Count: $doc_count " .  "<br>\n";
            }

            echo "<br><br>refer的分组:<br>";
    
            // 请求的 
            $groupedData = $aggregations['group_by_refer']['buckets'];

            // 打印每个分组的字段值和出现次数
            foreach ($groupedData as $bucket) {
                $fieldValue = $bucket['key'];
                $doc_count = $bucket['doc_count'];
                // $total_dataLen = $bucket['total_dataLen']['value'];
                
                echo "Field: $fieldValue, Count: $doc_count " .  "<br>\n";
            }

        } catch (Exception $e){
            echo $e->getMessage();
            echo "查询,失败\n";
        }
    }


    
}



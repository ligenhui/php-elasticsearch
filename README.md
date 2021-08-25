# php-elasticsearch

php版本的elasticsearch常用封装

## 安装

```composer
composer require ligenhui/php-elasticsearch
```

## 使用示例

```php

require 'vendor/autoload.php';

/***************创建连接start***************/

//设置节点一
$config1 = new \Ligenhui\PhpElasticsearch\Config();
$config1->setHost('127.0.0.1')->setPort(9200)->setUser('user1')->setPassword('pass1');

//设置节点二
$config2 = new \Ligenhui\PhpElasticsearch\Config();
$config2->setHost('127.0.0.2')->setPort(9200)->setUser('user2')->setPassword('pass2');

//创建实例
$es = new \Ligenhui\PhpElasticsearch\Elasticsearch([$config1, $config2]);

/***************创建连接end***************/





/***************index操作start***************/

//检查index是否存在
$es->isIndexExists();

//设置index 类似mysql里面的表
$es->setIndex('test');

//获取indices实例
$es->indices();

$map = [
    'properties' => [
        'name' => [
            'type' => 'text',
        ],
        'age' => [
            'type' => 'integer',
        ],
    ]
];
//设置mappings
$es->setMappings($map);

//设置settings
$es->setSettings(['number_of_shards' => 3,'number_of_replicas' => 2]);

//获取mappings
$es->indices()->getMapping();

//获取settings
$es->indices()->getSettings();

//创建索引 创建表
$es->createIndex();
  
//删除索引 删除表
$es->deleteIndex();

/***************index操作end***************/





/***************数据操作start***************/
    
//新增数据并设置id值
$es->insert(['name' => 'aa', 'age' => 11],1);

//新增数据不设置id值 es自动生成
$es->insert(['name' => 'aa', 'age' => 11]);

//批量新增数据并设置id值
$es->insertAll([['name' => 'c', 'age' => 3, 'id' => 3],['name' => 'd', 'age' => 4, 'id' => 4]],'id');

//根据id删除一条数据
$es->deleteById(1);

//根据条件删除数据
$es->where('name', 'a')->delete();

//根据id更新一条数据
$es->updateById(['doc' =>['name' => 'ccc','age' => 33]],3);

//根据条件更新数据
$es->where('name','bb')->update(['script' => ['inline' => 'ctx._source.name=params.name;ctx._source.age=params.age','params' => ['name' => 'bbb', 'age' => 333],'lang' => 'painless']]);
   
//根据id查询数据
$es->get(6);

//设置分页
$es->setPageSize(1)->setScrollSize(10);

//设置正排序
$es->setSortAsc('name');

//设置倒排序
$es->setSortDesc('name');

//设置返回字段
$es->field(['name','age']);

//设置区间查询
$es->setFilterRange('create_time',['gte' => '2020-02-02 12:30:22','lte' => '2021-05-05 12:30:22']);

//设置模糊查询
$es->like(['name','age'],'*aaa*');

//根据条件查询数据
$es->where('name','bbb')->select();

/***************数据操作end***************/





/***************其他start***************/

//获取client实例
$client = $es->client();

//自定义搜索 例如求和
$client->search(['size' => 0, 'index' => 'test', 'body' => ['aggs' => ['age' => ['sum' => ['field' => 'age']]]]]);

//获取返回值 自定义操作的结果不支持 例上
$es->getResponse();

//获取组装数据
$es->getParams();

//获取检索以组装数据
$es->getSearchField();

//前缀查询 例如 查询值为张开头的数据
$es->setMustPrefix('name','张');

//multi_match Fuzzy
$es->setMultiMatchFuzzy(['name'],'张三','and',2);

//must term操作
$es->setMustTerm('name','aaa');

//filter Term
$es->setFilterTerm('name','aaa');

//Filter Match 
$es->setFilterMatch('name','aaa');

//Should Match
$es->setShouldMatch('name','aaa');

//Should Term
$es->setShouldTerm('name','aaa');
```
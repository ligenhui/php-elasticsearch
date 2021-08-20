# php-elasticsearch

php版本的elasticsearch常用封装

## 安装

```composer
composer require ligenhui/php-elasticsearch
```

## 使用示例

```php

require 'vendor/autoload.php';

//设置节点一
$config1 = new \Ligenhui\PhpElasticsearch\Config();
$config1->setHost('127.0.0.1')->setPort(9200)->setUser('user1')->setPassword('pass1');

//设置节点二
$config2 = new \Ligenhui\PhpElasticsearch\Config();
$config2->setHost('127.0.0.2')->setPort(9200)->setUser('user2')->setPassword('pass2');

//创建实例
$es = new \Ligenhui\PhpElasticsearch\Elasticsearch([$config1, $config2]);

//设置index
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

//获取mappings
$es->indices()->getMapping();

//设置settings
$es->setSettings(['number_of_shards' => 3,'number_of_replicas' => 2]);

//获取settings
$es->indices()->getSettings();

//创建索引
$es->createIndex();
  
//删除索引
$es->deleteIndex();
    
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

//设置区间查询
$es->setFilterRange('create_time',['gte' => '2020-02-02 12:30:22','lte' => '2021-05-05 12:30:22']);

//设置模糊查询
$es->like(['name','age'],'aaa');

//根据条件查询数据
$es->where('name','bbb')->select();
```
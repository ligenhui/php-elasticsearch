<?php

namespace Ligenhui\PhpElasticsearch;


use Elasticsearch\Client;
use Elasticsearch\Namespaces\IndicesNamespace;

interface EsInterface
{
    //设置映射
    public function setMappings(array $mappings): Elasticsearch;

    //创建索引
    public function createIndex(array $params, string $index = ''): bool;

    //删除索引
    public function deleteIndex(string $index = ''): bool;

    //检查索引是否存在
    public function isIndexExists(string $index = ''): bool;

    //新增数据
    public function insert(array $data, string $id = ''): int;

    //批量新增数据
    public function insertAll(array $data = [], string $idKey = '');

    //根据id删除一条数据
    public function deleteById(string $id): bool;

    //根据条件删除数据
    public function delete(): int;

    //根据id更新一条数据
    public function updateById(array $data, string $id): int;

    //根据条件更新数据
    public function update(array $data): int;

    //根据id查询单条数据
    public function get(string $id): ?array;

    //设置单个字段倒序
    public function setSortDesc(string $sortField): Elasticsearch;

    //设置单个字段正序
    public function setSortAsc(string $sortField): Elasticsearch;

    //设置页数
    public function setPageSize(int $pageSize): Elasticsearch;

    //获取设置的页数
    public function getPageSize(): int;

    //设置每页大小
    public function setScrollSize(int $scrollSize): Elasticsearch;

    //获取每页大小
    public function getScrollSize(): int;

    //设置返回字段
    public function field(array $field = []): Elasticsearch;

    //设置 must term 检索
    public function setMustTerm(string $field, string $value): Elasticsearch;

    //设置 must match 检索
    public function where(string $field, string $value): Elasticsearch;

    //设置 must prefix 检索
    public function setMustPrefix(string $field, string $value): Elasticsearch;

    //设置 must Fuzzy 模糊检索
    public function like(array $fields, string $value, int $fuzziness = 1): Elasticsearch;

    //设置 filter Term 检索
    public function setFilterTerm(string $field, string $value): Elasticsearch;

    //设置 filter Range 检索
    public function setFilterRange(string $field, array $value): Elasticsearch;

    //设置 filter Match 检索
    public function setFilterMatch(string $field, string $value): Elasticsearch;

    //设置 Should Match 检索
    public function setShouldMatch(string $field, string $value): Elasticsearch;

    //设置 Should Term 检索
    public function setShouldTerm(string $field, $value): Elasticsearch;

    //获取执行结果
    public function getResponse();

    //获取检索以组装数据
    public function getSearchField(): array;

    //全文搜索
    public function select(): ?array;

    //获取组装参数
    public function getParams(): array;

    //返回es提供的indices实例
    public function indices(): IndicesNamespace;

    //返回es提供的client实例
    public function client(): Client;


}
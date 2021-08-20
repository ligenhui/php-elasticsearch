<?php

namespace Ligenhui\PhpElasticsearch;


use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Namespaces\IndicesNamespace;
use Ligenhui\PhpElasticsearch\Exception\Exception;
use stdClass;

class Elasticsearch implements EsInterface
{

    const SEARCH_MODEL_MATCH = 'match';
    const SEARCH_MODEL_BOOL = 'bool';   //默认

    protected $client;
    protected $params = [];
    protected $pageSize = 1;
    protected $scrollSize = 10;
    protected $config = [];
    protected $hosts = [];
    protected $index = '';
    //重试次数 不包括4xx和5xx
    protected $retries = 0;
    protected $response;
    protected $maxInsertAll = 500;  //批量插入最大条数限制
    protected $searchSort = [];
    protected $searchField = [];
    protected $searchModel = self::SEARCH_MODEL_BOOL;
    protected $searchKeyword = '';

    /**
     *方法描述：设置搜索模式
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 4:12 下午
     * @param string $searchModel
     * @return $this
     */
    public function setSearchModel(string $searchModel): Elasticsearch
    {
        $this->searchModel = $searchModel;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxInsertAll(): int
    {
        return $this->maxInsertAll;
    }

    /**
     * @param int $maxInsertAll
     * @return Elasticsearch
     */
    public function setMaxInsertAll(int $maxInsertAll): Elasticsearch
    {
        $this->maxInsertAll = $maxInsertAll;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     * @return Elasticsearch
     */
    public function setResponse($response): Elasticsearch
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return int
     */
    public function getRetries(): int
    {
        return $this->retries;
    }

    /**
     * @param int $retries
     * @return Elasticsearch
     */
    public function setRetries(int $retries): Elasticsearch
    {
        $this->retries = $retries;
        return $this;
    }

    /**
     * @return string
     */
    public function getIndex(): string
    {
        return $this->index;
    }


    /**
     * @param string $index
     * @return Elasticsearch
     */
    public function setIndex(string $index): Elasticsearch
    {
        $this->index = $index;
        $this->params['index'] = $index;
        return $this;
    }

    /**
     *方法描述：获取初始化配置的config信息
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 1:26 下午
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Elasticsearch constructor.
     * @param array $hosts 连接配置 [节点1,节点2]
     * @param array $config 额外配置 支持[retries,logger,pool,serializer]
     */
    public function __construct(array $hosts, array $config = [])
    {
        foreach ($hosts as $c) {
            if ($c instanceof Config) {
                $this->hosts[] = $c->toArray();
            }
        }

        $build = ClientBuilder::create()->setHosts($this->hosts);

        if (isset($config['retries'])) {
            $this->setRetries((int)$config['retries']);
            $build->setRetries($this->getRetries());
        }

        if (isset($config['logger'])) {
            $build->setLogger($config['logger']);
        }

        if (isset($config['pool'])) {
            $build->setConnectionPool($config['pool']);
        }

        if (isset($config['serializer'])) {
            $build->setSerializer($config['serializer']);
        }

        $this->config = $config;

        $this->client = $build->build();
    }

    public function setMappings(array $mappings): Elasticsearch
    {
        $this->params['body']['mappings'] = $mappings;
        return $this;
    }

    public function setSettings(array $settings): Elasticsearch
    {
        $this->params['body']['settings'] = $settings;
        return $this;
    }

    /**
     *方法描述：创建索引
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 12:59 下午
     * @param array $params
     * @param string $index
     * @return bool
     * @throws Exception
     */
    public function createIndex(array $params = [], string $index = ''): bool
    {
        if ($this->isIndexExists($index)) {
            throw new Exception('需要创建的index:' . $index . '已存在');
        }

        if (isset($params['mappings']) && is_array($params['mappings'])) {
            $this->setMappings($params['mappings']);
        }

        if (isset($params['settings']) && is_array($params['settings'])) {
            $this->setSettings($params['settings']);
        }

        try {
            $response = $this->client->indices()->create($this->params);
        } catch (\Exception $e) {
            $response = $e->getMessage();
        }
        $this->setResponse($response);
        if (!is_array($response) || !$response['acknowledged']) {
            return false;
        }
        return true;
    }

    /**
     *方法描述：删除索引
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 1:13 下午
     * @param string $index
     * @return bool
     * @throws Exception
     */
    public function deleteIndex(string $index = ''): bool
    {
        if (empty($this->index) && empty($index)) {
            throw new Exception('需要删除的index:' . $index . '不能为空');
        }
        if (!empty($index)) {
            $this->setIndex($index);
        }

        try {
            $response = $this->client->indices()->delete(['index' => $this->index]);
        } catch (\Exception $e) {
            $response = $e->getMessage();
        }
        $this->setResponse($response);
        if (!is_array($response) || !$response['acknowledged']) {
            return false;
        }
        return true;
    }

    /**
     *方法描述：检查索引是否存在
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 1:13 下午
     * @param string $index
     * @return bool
     * @throws Exception
     */
    public function isIndexExists(string $index = ''): bool
    {
        if (empty($this->index) && empty($index)) {
            throw new Exception('index还未设置');
        }
        if (!empty($index)) {
            $this->setIndex($index);
        }

        return $this->client->indices()->exists(['index' => $this->index]);
    }

    /**
     *方法描述：返回Indices提供的实例
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 1:21 下午
     * @return IndicesNamespace
     */
    public function indices(): IndicesNamespace
    {
        return $this->client->indices();
    }

    /**
     *方法描述：返回client实例
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 4:53 下午
     * @return Client
     */
    public function client(): Client
    {
        return $this->client;
    }


    /**
     *方法描述：插入一条数据 如果id存在将覆盖
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 1:54 下午
     * @param array $data
     * @param string $id 不传es自动创建
     * @return int 成功返回插入的条数,如为0请查看response里面的数据在做处理
     */
    public function insert(array $data, string $id = ''): int
    {
        if (count($data) === 0) {
            return 0;
        }
        if (!empty($id)) {
            $this->params['id'] = $id;
        }
        $this->params['body'] = $data;
        try {
            $response = $this->client->index($this->params);
        } catch (\Exception $e) {
            $response = $e->getMessage();
        }
        $this->setResponse($response);
        if (is_array($response) && isset($response['_shards']['successful'])) {
            return $response['_shards']['successful'];
        }
        return 0;
    }

    /**
     *方法描述：批量插入数据 如果id存在将覆盖
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 2:21 下午
     * @param array $data [[key => val],[key=>val]]
     * @param string $idKey 所在数组的key 如需要自动生成id data里面需传 例如[[id=>1,'name'=>'a'],[id=>2,name=>'b']] 这里假设为id
     * @return int 成功返回插入的条数,如为0请查看response里面的数据在做处理
     * @throws Exception
     */
    public function insertAll(array $data = [], string $idKey = ''): int
    {
        if (count($data) === 0) {
            return 0;
        }

        if (count($data) > $this->getMaxInsertAll()) {
            throw new Exception('单次批量插入不能超过' . $this->getMaxInsertAll() . '条');
        }

        $this->params = [];
        foreach ($data as $value) {
            $this->params['body'][] = [
                'index' => [
                    '_index' => $this->index,
                    '_id' => $value[$idKey] ?? null
                ]
            ];

            unset($value[$idKey]);
            $this->params['body'][] = $value;
        }
        try {
            $responses = $this->client->bulk($this->params);
        } catch (\Exception $e) {
            $responses = $e->getMessage();
        }
        $this->setResponse($responses);
        if (is_array($responses) && isset($responses['errors']) && $responses['errors'] === false && isset($responses['items']) && is_array($responses['items'])) {
            return count($responses['items']);
        }
        return 0;
    }

    /**
     *方法描述：根据id删除一条数据
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 2:33 下午
     * @param string $id
     * @return bool
     */
    public function deleteById(string $id): bool
    {
        if (empty($id)) {
            return false;
        }
        $this->params['id'] = $id;
        try {
            $response = $this->client->delete($this->params);
        } catch (\Exception $e) {
            $response = $e->getMessage();
        }
        $this->setResponse($response);
        if (is_array($response) && isset($response['_shards']['successful']) && $response['_shards']['successful'] === 1) {
            return true;
        }
        return false;
    }

    /**
     *方法描述：根据条件删除数据
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 5:43 下午
     * @return int 返回删除条数
     */
    public function delete(): int
    {
        if (empty($this->searchField)) {
            return false;
        }
        $this->setBoolQuery();
        try {
            $response = $this->client->deleteByQuery($this->params);
        } catch (\Exception $e) {
            $response = $e->getMessage();
        }
        $this->setResponse($response);
        if (is_array($response) && isset($response['deleted'])) {
            return $response['deleted'];
        }
        return 0;
    }

    /**
     *方法描述：更新数据
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 2:47 下午
     * @param array $data
     * @param string $id
     * @return int
     */
    public function updateById(array $data, string $id): int
    {
        if (count($data) === 0 || empty($id)) {
            return 0;
        }
        $this->params['id'] = $id;
        $this->params['body'] = $data;
        try {
            $response = $this->client->update($this->params);
        } catch (\Exception $e) {
            $response = $e->getMessage();
        }
        $this->setResponse($response);
        if (is_array($response) && isset($response['_shards']['successful']) && $response['_shards']['successful'] === 1) {
            return $response['_shards']['successful'];
        }
        return 0;
    }

    /**
     *方法描述：根据条件更新数据
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 6:09 下午
     * @param array $data
     * @return int
     */
    public function update(array $data): int
    {
        if (empty($this->searchField)) {
            return false;
        }
        $this->setBoolQuery();
        $this->params['body'] = array_merge($this->params['body'], $data);
        try {
            $response = $this->client->updateByQuery($this->params);
        } catch (\Exception $e) {
            $response = $e->getMessage();
        }
        if (is_array($response) && isset($response['updated'])) {
            return $response['updated'];
        }
        return 0;
    }

    /**
     *方法描述：查询单条数据 不返回id 失败返回null
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 3:22 下午
     * @param string $id
     * @return array|null
     */
    public function get(string $id): ?array
    {
        $this->params['id'] = $id;
        try {
            $response = $this->client->get($this->params);
        } catch (\Exception $e) {
            $response = $e->getMessage();
        }
        $this->setResponse($response);
        if (is_array($response) && isset($response['_source']) && is_array($response['_source'])) {
            return $response['_source'];
        }
        return null;
    }

    /**
     *方法描述：设置字段倒序
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 3:26 下午
     * @param string $sortField
     * @return $this
     */
    public function setSortDesc(string $sortField): Elasticsearch
    {
        if ($sortField) {
            $this->searchSort[$sortField] = 'desc';
        }
        return $this;
    }

    /**
     *方法描述：设置字段正序
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 3:27 下午
     * @param string $sortField
     * @return $this
     */
    public function setSortAsc(string $sortField): Elasticsearch
    {
        if ($sortField) {
            $this->searchSort[$sortField] = 'asc';
        }
        return $this;
    }

    /**
     *方法描述：设置分页
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 3:29 下午
     * @param int $pageSize
     * @return $this
     */
    public function setPageSize(int $pageSize): Elasticsearch
    {
        if ($pageSize <= 1) $pageSize = 1;
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     *方法描述：设置每页大小
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 3:36 下午
     * @param int $scrollSize
     * @return $this
     */
    public function setScrollSize(int $scrollSize): Elasticsearch
    {
        if ($scrollSize) $this->scrollSize = $scrollSize;
        return $this;
    }

    /**
     * @return int
     */
    public function getScrollSize(): int
    {
        return $this->scrollSize;
    }

    /**
     *方法描述：设置返回字段
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 4:47 下午
     * @param array $field
     * @return $this
     */
    public function field(array $field = []): Elasticsearch
    {
        $this->params['_source'] = $field;
        return $this;
    }

    public function setMustTerm(string $field, string $value): Elasticsearch
    {
        $this->searchField['bool']['must'][]['term'][$field] = $value;
        $this->searchField['match'][$field] = $value;
        return $this;
    }

    /**
     *方法描述：设置key=>value格式的条件过滤 对应must match
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 3:49 下午
     * @param string $field
     * @param string $value
     * @return Elasticsearch
     */
    public function where(string $field, string $value): Elasticsearch
    {
        $this->searchField['bool']['must'][]['match'][$field] = $value;
        $this->searchField['match'][$field] = $value;
        return $this;
    }

    /**
     *方法描述：设置前缀查询
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 3:53 下午
     * @param string $field
     * @param string $value
     * @return $this
     */
    public function setMustPrefix(string $field, string $value): Elasticsearch
    {
        $this->searchField['bool']['must'][]['prefix'][$field] = $value;
        $this->searchField['match'][$field] = $value;
        return $this;
    }

    /**
     *方法描述：模糊查询 对应 must Fuzzy
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 3:54 下午
     * @param array $fields
     * @param string $value
     * @param int $fuzziness
     * @return $this
     */
    public function like(array $fields, string $value, int $fuzziness = 1): Elasticsearch
    {
        $this->searchField['bool']['must'][] = [
            'multi_match' => ['query' => $value, 'fuzziness' => $fuzziness, 'fields' => $fields]
        ];
        return $this;
    }

    /**
     *方法描述：filter 过滤器
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 3:56 下午
     * @param string $field
     * @param string $value
     * @return $this
     */
    public function setFilterTerm(string $field, string $value): Elasticsearch
    {
        $this->searchField['bool']['filter'][]['term'][$field] = $value;
        $this->searchField['match'][$field] = $value;
        return $this;
    }

    /**
     *方法描述：区间查询
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 3:59 下午
     * @param string $field
     * @param array $value
     * @return $this
     */
    public function setFilterRange(string $field, array $value): Elasticsearch
    {
        $this->searchField['bool']['filter'][]['range'][$field] = $value;
        $this->searchField['match'][$field] = $value;
        return $this;
    }

    /**
     *方法描述：设置 filter Match 检索
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 4:00 下午
     * @param string $field
     * @param string $value
     * @return $this
     */
    public function setFilterMatch(string $field, string $value): Elasticsearch
    {
        $this->searchField['bool']['filter'][]['match'][$field] = $value;
        $this->searchField['match'][$field] = $value;
        return $this;
    }

    /**
     *方法描述：设置 Should Match 检索
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 4:02 下午
     * @param string $field
     * @param string $value
     * @return $this
     */
    public function setShouldMatch(string $field, string $value): Elasticsearch
    {
        $this->searchField['bool']['should'][]['match'][$field] = $value;
        $this->searchField['match'][$field] = $value;

        return $this;
    }

    /**
     *方法描述：设置 Should Term 检索
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 4:03 下午
     * @param string $field
     * @param $value
     * @return Elasticsearch
     */
    public function setShouldTerm(string $field, $value): Elasticsearch
    {
        $this->searchField['bool']['should'][]['term'][$field] = $value;
        $this->searchField['match'][$field] = $value;
        return $this;
    }

    /**
     *方法描述：获取检索以组装数据
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 4:05 下午
     * @return array
     */
    public function getSearchField(): array
    {
        return $this->searchField;
    }

    public function select(): ?array
    {
        //组装搜索条件
        $this->params['from'] = ($this->pageSize - 1) * $this->scrollSize;
        $this->params['size'] = $this->scrollSize;
        switch ($this->searchModel) {
            case self::SEARCH_MODEL_BOOL:
                $this->setBoolQuery();
                break;
            default:
                $this->setMatchQuery();
                break;
        }

        //排序
        $this->setSort();

        $result = ['total' => 0, 'data' => []];
        try {
            $response = $this->client->search($this->params);
        } catch (\Exception $e) {
            $response = $e->getMessage();
        }
        $this->setResponse($response);
        if (!is_array($response) || !isset($response['hits']['hits'])) {
            return null;
        }
        $result['total'] = $response['hits']['total']['value'];
        foreach ($response['hits']['hits'] as $v) {
            $result['data'][] = array_merge($v['_source'], ['_id' => $v['_id']]);
        }
        return $result;
    }

    private function setBoolQuery()
    {
        if (!isset($this->searchField['bool'])) {
            $this->searchField['bool'] = new stdClass();
        }
        $this->params['body']['query']['bool'] = $this->searchField['bool'];
    }

    private function setMatchQuery()
    {
        foreach ($this->searchField['match'] as $field => $operator) {
            $this->params['body']['query']['match'][$field] = ['query' => $this->searchKeyword, 'operator' => $operator];
        }
    }

    private function setSort()
    {
        $this->params['body']['sort'] = $this->searchSort;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
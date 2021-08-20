<?php


namespace Ligenhui\PhpElasticsearch;


class Config
{
    protected $host = '127.0.0.1';
    protected $scheme = 'http';
    protected $port = 9200;
    protected $user = '';
    protected $pass = '';

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return Config
     */
    public function setHost(string $host): Config
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     * @return Config
     */
    public function setScheme(string $scheme): Config
    {
        $this->scheme = $scheme;
        return $this;
    }


    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return Config
     */
    public function setPort(int $port): Config
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     * @return Config
     */
    public function setUser(string $user): Config
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->pass;
    }

    /**
     * @param string $password
     * @return Config
     */
    public function setPassword(string $password): Config
    {
        $this->pass = $password;
        return $this;
    }

    /**
     *方法描述：将配置文件返回为数组格式
     * User：LiGenHui
     * Email：657801509@qq.com
     * DateTime:2021/8/20 12:06 下午
     * @return array
     */
    public function toArray(): array
    {
        return [
            'host' => $this->host,
            'scheme' => $this->scheme,
            'port' => $this->port,
            'user' => $this->user,
            'pass' => $this->pass,
        ];
    }
}
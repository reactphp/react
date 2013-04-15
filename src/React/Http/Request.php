<?php

namespace React\Http;

use React\Stream\ReadableStream;

class Request extends ReadableStream
{
    private $method;
    private $path;
    private $query;
    private $httpVersion;
    private $headers;

    public function __construct($method, $path, $query = array(), $httpVersion = '1.1', $headers = array())
    {
        $this->method = $method;
        $this->path = $path;
        $this->query = $query;
        $this->httpVersion = $httpVersion;
        $this->headers = $headers;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getHttpVersion()
    {
        return $this->httpVersion;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function expectsContinue()
    {
        return isset($this->headers['Expect']) && '100-continue' === $this->headers['Expect'];
    }
}

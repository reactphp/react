<?php

namespace React\Tests\Http;

use React\Http\RequestHeaderParser;
use React\Tests\Socket\TestCase;

class RequestHeaderParserTest extends TestCase
{
    public function testSplitShouldHappenOnDoubleCrlf()
    {
        $parser = new RequestHeaderParser();
        $parser->on('headers', $this->expectCallableNever());

        $parser->feed("GET / HTTP/1.1\r\n");
        $parser->feed("Host: example.com:80\r\n");
        $parser->feed("Connection: close\r\n");

        $parser->removeAllListeners();
        $parser->on('headers', $this->expectCallableOnce());

        $parser->feed("\r\n");
    }

    public function testFeedInOneGo()
    {
        $parser = new RequestHeaderParser();
        $parser->on('headers', $this->expectCallableOnce());

        $data = $this->createGetRequest();
        $parser->feed($data);
    }

    public function testHeadersEventShouldReturnRequestAndBodyBuffer()
    {
        $request = null;
        $bodyBuffer = null;

        $parser = new RequestHeaderParser();
        $parser->on('headers', function ($parsedRequest, $parsedBodyBuffer) use (&$request, &$bodyBuffer) {
            $request = $parsedRequest;
            $bodyBuffer = $parsedBodyBuffer;
        });

        $data = $this->createGetRequest();
        $data .= 'RANDOM DATA';
        $parser->feed($data);

        $this->assertInstanceOf('React\Http\Request', $request);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/', $request->getPath());
        $this->assertSame(array(), $request->getQuery());
        $this->assertSame('1.1', $request->getHttpVersion());
        $this->assertSame(array('Host' => 'example.com:80', 'Connection' => 'close'), $request->getHeaders());

        $this->assertSame('RANDOM DATA', $bodyBuffer);
    }

    public function testHeadersEventShouldReturnBinaryBodyBuffer()
    {
        $bodyBuffer = null;

        $parser = new RequestHeaderParser();
        $parser->on('headers', function ($parsedRequest, $parsedBodyBuffer) use (&$bodyBuffer) {
            $bodyBuffer = $parsedBodyBuffer;
        });

        $data = $this->createGetRequest();
        $data .= "\0x01\0x02\0x03\0x04\0x05";
        $parser->feed($data);

        $this->assertSame("\0x01\0x02\0x03\0x04\0x05", $bodyBuffer);
    }

    public function testHeadersEventShouldParsePathAndQueryString()
    {
        $request = null;

        $parser = new RequestHeaderParser();
        $parser->on('headers', function ($parsedRequest, $parsedBodyBuffer) use (&$request) {
            $request = $parsedRequest;
        });

        $data = $this->createAdvancedPostRequest();
        $parser->feed($data);

        $this->assertInstanceOf('React\Http\Request', $request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/foo', $request->getPath());
        $this->assertSame(array('bar' => 'baz'), $request->getQuery());
        $this->assertSame('1.1', $request->getHttpVersion());
        $headers = array(
            'Host' => 'example.com:80',
            'User-Agent' => 'Guzzle/2.0 (Language=PHP/5.3.8; curl=7.21.4; Host=universal-apple-darwin11.0)',
            'Connection' => 'close',
        );
        $this->assertSame($headers, $request->getHeaders());
    }

    public function testHeaderOverflowShouldEmitError()
    {
        $error = null;

        $parser = new RequestHeaderParser();
        $parser->on('headers', $this->expectCallableNever());
        $parser->on('error', function ($message) use (&$error) {
            $error = $message;
        });

        $data = str_repeat('A', 4097);
        $parser->feed($data);

        $this->assertInstanceOf('OverflowException', $error);
        $this->assertSame('Maximum header size of 4096 exceeded.', $error->getMessage());
    }

    public function testPostHandlesLargePayloads()
    {
        $request = null;
	$bodyBuffer = null;
        $parser = new RequestHeaderParser();
        $parser->on('headers', function ($parsedRequest, $parsedBodyBuffer) use (&$request, &$bodyBuffer) {
            $request = $parsedRequest;
            $bodyBuffer = $parsedBodyBuffer;
        });

        $data = $this->createLargePostRequest();
        $parser->feed($data);

        $this->assertInstanceOf('React\Http\Request', $request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/foo', $request->getPath());
        $this->assertSame(array('bar' => 'baz'), $request->getQuery());
        $this->assertSame('1.1', $request->getHttpVersion());
        $headers = array(
            'Host' => 'example.com:80',
            'User-Agent' => 'Guzzle/2.0 (Language=PHP/5.3.8; curl=7.21.4; Host=universal-apple-darwin11.0)',
            'Connection' => 'close',
        );
        $this->assertSame($headers, $request->getHeaders());
	$this->assertTrue(strlen($bodyBuffer) > 10);
    }





    private function createGetRequest()
    {
        $data = "GET / HTTP/1.1\r\n";
        $data .= "Host: example.com:80\r\n";
        $data .= "Connection: close\r\n";
        $data .= "\r\n";

        return $data;
    }

    private function createAdvancedPostRequest()
    {
        $data = "POST /foo?bar=baz HTTP/1.1\r\n";
        $data .= "Host: example.com:80\r\n";
        $data .= "User-Agent: Guzzle/2.0 (Language=PHP/5.3.8; curl=7.21.4; Host=universal-apple-darwin11.0)\r\n";
        $data .= "Connection: close\r\n";
        $data .= "\r\n";

        return $data;
    }

    private function createLargePostRequest()
    {


        $largePayload = str_repeat('A', 4097);
	$payloadSize = strlen($largePayload);

        $data = "POST /foo?bar=baz HTTP/1.1\r\n";
        $data .= "Host: example.com:80\r\n";
        $data .= "User-Agent: Guzzle/2.0 (Language=PHP/5.3.8; curl=7.21.4; Host=universal-apple-darwin11.0)\r\n";
        $data .= "Connection-type: text/plain\r\n";
        $data .= "Connection-length: $payloadSize\r\n";
        $data .= "Connection: close\r\n";
        $data .= $largePayload;
        $data .= "\r\n";

        return $data;
    }

}

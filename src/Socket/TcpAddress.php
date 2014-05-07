<?php

namespace React\Socket;

class TcpAddress implements TcpAddressInterface
{
    const EXPRESSION = '%^tcp://(?<host>.+?)(:(?<port>[0-9]+))?$%';

    /**
     * Address of the socket.
     */
    protected $address;

    /**
     * Host name of the socket.
     */
    protected $host;

    /**
     * Port of the socket.
     */
    protected $port;

    public function __construct($address)
    {
        preg_match(static::EXPRESSION, $address, $matches);

        $this->host = $matches['host'];
        $this->address = "tcp://{$this->host}";

        if (isset($matches['port'])) {
            $this->port = $matches['port'];
            $this->address .= ":{$this->port}";
        }
    }

    public function __toString()
    {
        return (string)$this->address;
    }

    public static function checkAddressType($address, &$error)
    {
        $result = (boolean)preg_match(static::EXPRESSION, $address, $matches);

        if (false === $result && 0 === strpos($address, 'tcp://')) {
            $error = new AddressException("Invalid address '{$address}', missing host name.");
        }

        return $result;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }
}
<?php

namespace React\Socket;

class UnixAddress implements UnixAddressInterface
{
    const EXPRESSION = '%^unix://(?<resource>.+)$%';

    /**
     * Address of the socket.
     */
    protected $address;

    /**
     * Filename of the socket.
     */
    protected $filename;

    public function __construct($address = null)
    {
        if ('WINNT' === PHP_OS) {
            throw new \RuntimeException("Unix sockets are unavailable on Windows.");
        }

        if ($address === null) return;

        preg_match(static::EXPRESSION, $address, $matches);

        $this->address = $matches[0];

        // Relative to root:
        if (0 === strpos($matches['resource'], '/')) {
            $this->filename = $matches['resource'];
        }

        // Relative to current path:
        else {
            $this->filename = getcwd() . '/' . $matches['resource'];
        }
    }

    public function __toString()
    {
        return (string)$this->address;
    }

    public static function checkAddressType($address, &$error)
    {
        $result = (boolean)preg_match(static::EXPRESSION, $address, $matches);

        if (false === $result && 0 === strpos($address, 'unix://')) {
            $error = new AddressException("Invalid address '{$address}', missing resource name.");
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

    public function isValid()
    {
        return isset($this->filename);
    }
}
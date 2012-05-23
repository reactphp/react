<?php

// downloading the two best technologies ever in parallel

require __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$files = array(
    'node-v0.6.18.tar.gz' => 'http://nodejs.org/dist/v0.6.18/node-v0.6.18.tar.gz',
    'php-5.4.3.tar.gz' => 'http://it.php.net/get/php-5.4.3.tar.gz/from/this/mirror',
);

$buffers = array();

foreach ($files as $file => $url) {
    $readStream = fopen($url, 'r');
    $writeStream = fopen($file, 'w');

    $buffers[$file] = new React\Socket\Buffer($writeStream, $loop);

    $loop->addReadStream($readStream, function ($readStream) use (&$buffers, $loop, $file, $writeStream, &$files) {
        if (feof($readStream)) {
            $loop->removeStream($readStream);
            $loop->removeStream($writeStream);
            unset($files[$file]);
            echo "Finished downloading $file\n";

            return;
        }
        $buffers[$file]->write(fread($readStream, 1024));
    });
}

$loop->addPeriodicTimer(5, function () use (&$files) {
    if (!count($files)) {
        return false;
    }

    foreach ($files as $file => $url) {
        echo "$file: ".filesize($file)." bytes\n";
    }
});

echo "This script will show the download status every 5 second.\n";

$loop->run();

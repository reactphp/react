<?php

namespace React\EventLoop\Timer;

class Timers
{
    const MIN_RESOLUTION = 0.001;

    private $time;
    private $active;
    private $timers;

    public function __construct()
    {
        $this->time = 0;
        $this->active = array();
        $this->timers = new \SplPriorityQueue();
    }

    public function updateTime()
    {
        return $this->time = microtime(true);
    }

    public function getTime()
    {
        return $this->time ?: $this->updateTime();
    }

    public function add($interval, $callback, $periodic = false)
    {
        if ($interval < self::MIN_RESOLUTION) {
            throw new \InvalidArgumentException('Timer events do not support sub-millisecond timeouts.');
        }

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('The callback must be a callable object.');
        }

        $interval = (float) $interval;

        $timer = (object) array(
            'interval' => $interval,
            'callback' => $callback,
            'periodic' => $periodic,
            'scheduled' => $interval + $this->getTime(),
        );

        $signature = $timer->signature = spl_object_hash($timer);
        $this->timers->insert($timer, -$timer->scheduled);
        $this->active[$signature] = $timer;

        return $signature;
    }

    public function cancel($signature)
    {
        unset($this->active[$signature]);
    }

    public function getFirst()
    {
        if ($this->timers->isEmpty()) {
            return -1;
        }

        return $this->timers->top()->scheduled;
    }

    public function isEmpty()
    {
        return !$this->active;
    }

    public function run()
    {
        $time = $this->updateTime();
        $timers = $this->timers;

        while (!$timers->isEmpty() && $timers->top()->scheduled < $time) {
            $timer = $timers->extract();

            if (isset($this->active[$timer->signature])) {
                $rearm = call_user_func($timer->callback);

                if ($timer->periodic === true && $rearm !== false) {
                    $timer->scheduled = $timer->interval + $time;
                    $this->timers->insert($timer, -$timer->scheduled);
                } else {
                    unset($this->active[$timer->signature]);
                }
            }
        }
    }
}

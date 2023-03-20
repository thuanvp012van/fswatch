<?php

namespace Penguin\Component\FsWatch;

use Symfony\Component\Process\Process;

class FsWatch
{
    // Events code
    const CREATED = 2;
    const CREATE_DIR = 200;
    const UPDATED = 4;
    const REMOVED = 8;
    const REMOVED_DIR = 800;
    const ERROR = 500;
    const ANY = 999;

    /**
     * Tracking paths
     */
    protected array $paths;

    /**
     * Registered events
     */
    protected array $events;

    /**
     * Using poll_monitor?
     */
    protected bool $polling = false;

    protected array $command = ['fswatch', '-xrn'];

    /**
     * Exclude paths matching regex
     */
    protected array $ignore = [];

    public function __construct(string ...$paths)
    {
        if (!$this->isAvailable()) {
            throw new \LogicException('fswatch util is required.');
        }

        $this->paths = $paths;
    }

    /**
     * Use poll_monitor
     *
     * @return $this
     */
    public function usePolling(): static
    {
        exec('fswatch --list-monitors', $output);
        if (is_array($output)) {
            $output = array_map('trim', $output);
            if (!in_array('poll_monitor', $output)) {
                throw new \Exception('The operating system does not support polling.');
            }
        } else {
            $output = trim($output);
            if ($output !== 'poll_monitor') {
                throw new \Exception('The operating system does not support polling.');
            }
        }
        $this->polling = true;
        return $this;
    }

    /**
     * Unwatch path
     *
     * @param array|string $paths
     * @return $this
     */
    public function unWatch(string ...$paths): static
    {
        $this->paths = array_diff($this->paths, $paths);
        return $this;
    }

    /**
     * Add watch path
     *
     * @param array|string $paths
     * @return $this
     */
    public function addWatch(string ...$paths): static
    {
        $this->paths = array_merge($this->paths, $paths);
        return $this;
    }

    /**
     * Register event change file
     *
     * @param Cloure $callback
     * @return $this
     */
    public function onChange(callable $callback): static
    {
        $this->events[self::UPDATED] = $callback;
        return $this;
    }

    /**
     * Register event add file
     *
     * @param Cloure $callback
     * @return $this
     */
    public function onAdd(callable $callback): static
    {
        $this->events[self::CREATED] = $callback;
        return $this;
    }

    /**
     * Register event add dir
     *
     * @param Cloure $callback
     * @return $this
     */
    public function onAddDir(callable $callback): static
    {
        $this->events[self::CREATE_DIR] = $callback;
        return $this;
    }

    /**
     * Register event delete file
     *
     * @param Cloure $callback
     * @return $this
     */
    public function onUnlink(callable $callback): static
    {
        $this->events[self::REMOVED] = $callback;
        return $this;
    }

    /**
     * Register event delete folder
     *
     * @param Cloure $callback
     * @return $this
     */
    public function onUnlinkDir(callable $callback): static
    {
        $this->events[self::REMOVED_DIR] = $callback;
        return $this;
    }

    /**
     * Register event on change
     *
     * @param Cloure $callback
     * @return $this
     */
    public function onAny(callable $callback): static
    {
        $this->events[self::ANY] = $callback;
        return $this;
    }

    /**
     * Register event error
     *
     * @param Cloure $callback
     * @return $this
     */
    public function onError(callable $callback): static
    {
        $this->events[self::ERROR] = $callback;
        return $this;
    }

    /**
     * Exclude paths matching regex
     *
     * @param string $regex
     * @return $this
     */
    public function ignore(string $regex): static
    {
        $this->ignore[] = '-e';
        $this->ignore[] = $regex;
        return $this;
    }

    /**
     * Exit fswatch after the first set of events is received
     *
     * @return $this
     */
    public function oneEvent(): static
    {
        $this->command[] = '-1';
        return $this;
    }

    /**
     * Don't exit fswatch after events is received
     *
     * @return $this
     */
    public function multiEvent(): static
    {
        if (($key = array_search('-1', $this->command)) !== false) {
            unset($this->command[$key]);
        }
        return $this;
    }

    /**
     * Handle file tracking
     */
    public function __destruct()
    {
        if (empty($this->events)) {
            throw new \LogicException('You have not registered for any events yet.');
        }

        if ($this->polling) {
            $this->command[] = '--monitor=poll_monitor';
        }

        $this->command = array_merge($this->command, $this->ignore, $this->paths);
        $process = new Process($this->command, null, null, null, null);
        $process->start();
        if ($process->isRunning()) {
            foreach ($process as $type => $message) {
                if ($process::OUT === $type) {
                    $data = array_filter(explode(PHP_EOL, $message), function($value) {
                        return !empty($value);
                    });

                    // Handle filter data and get event code
                    if (count($data) === 1) {
                        $data = explode(' ', $data[0]);
                    } else {
                        $data = explode(' ', $data[1]);
                    }
                    $event = $this->getEventCode($data);

                    if (!empty($this->events[$event])) {
                        $this->events[$event]($data[0]);
                    } else if (!empty($this->events[self::ANY])) {
                        $this->events[self::ANY]($event, $data[0]);
                    }
                } else {
                    $this->events[self::ERROR]($message);
                }
            }
        }
    }

    /**
     * Check fswatch is installed
     *
     * @return bool
     */
    protected function isAvailable(): bool
    {
        exec('fswatch 2>&1', $output);
        return strpos(implode(' ', $output), 'command not found') === false;
    }

    /**
     * Get event code
     *
     * @param array $data
     * @return int $eventCode
     */
    protected function getEventCode(array $data): int
    {
        $dir = $data[0];
        $event = (int)$data[1];
        $eventCode = 0;
        switch ($event) {
            case self::CREATED:
                $eventCode = is_file($dir) ? self::CREATED : self::CREATE_DIR;
                break;
            case self::REMOVED:
                $eventCode = is_file($dir) ? self::REMOVED : self::REMOVED_DIR;
                break;
            case self::UPDATED && is_file($dir):
                $eventCode = self::UPDATED;
                break;
        }

        return $eventCode;
    }
}

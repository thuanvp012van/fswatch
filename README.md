# Penguin FSWatch

This package provides a file watcher.

### Installation

The current version only supports [fswatch](https://github.com/emcrisostomo/fswatch), so you'll have to install fswatch first.

```sh
# MacOS
brew install fswatch

# Linux
wget https://github.com/emcrisostomo/fswatch/releases/download/{VERSION}/fswatch-{VERSION}.tar.gz
tar -xzvf fswatch-{VERSION}.tar.gz
cd fswatch-{VERSION} && ./configure && make && sudo make install && sudo ldconfig

# Composer
composer require penguin/fswatch
```

### Usage

```php
<?php

use Penguin\Component\FsWatch\FsWatch;

require __DIR__ . '/vendor/autoload.php';

(new FsWatch(__DIR__))
    ->usePolling() // use poll_monitor (default is inotify in Linux, FSEvents in MacOS)
    // ignore files json, txt with regex
    ->ignore('.*\.json')
    ->ignore('.*\.txt')
    ->onChange(function (string $path) {
        echo "onChange {$path}";
    });
```

### Methods
* `onChange(callable $callback(string $path, Process $process))`
* `onAdd(callable $callback(string $path, Process $process))`
* `onUnlink(callable $callback(string $path, Process $process))`
* `onAddDir(callable $callback(string $path, Process $process))`
* `onError(callable $callback, Process $process)`
* `onAny(callable $callback(int $eventCode, string $path, Process $process))`: Event will be executed when no other event is registered
* `usePolling()`: Use poll monitor. The poll monitor, available on any platform, only relies on available CPU and memory to perform its task.
* `oneEvent()`: Exit fswatch after the first set of events is received
* `multiEvent()`: Don't exit fswatch after events is received
* `ignore(string $regex)`: Exclude paths matching regex
* `unWatch(string ...$paths)`
* `addWatch(string ...$paths)`

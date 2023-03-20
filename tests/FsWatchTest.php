<?php

declare(strict_types=1);

namespace Penguin\Component\FsWatch;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class FsWatchTest extends TestCase
{
    public function triggerEvent(string $event): void
    {
        $process = new Process(['php', __DIR__ . "/trigger-events/{$event}.php"]);
        $process->start();
    }

    public function test_add_file(): void
    {
        $this->triggerEvent('add_file');
        (new FsWatch(__DIR__))
            ->usePolling()
            ->oneEvent()
            ->onAdd(function (string $path) {
                $this->assertIsString($path);
            });
    }

    public function test_change_file(): void
    {
        $this->triggerEvent('change_file');
        (new FsWatch(__DIR__))
            ->usePolling()
            ->oneEvent()
            ->onChange(function (string $path) {
                $this->assertIsString($path);
            });
    }

    public function test_delete_file(): void
    {
        $this->triggerEvent('delete_file');
        (new FsWatch(__DIR__))
            ->usePolling()
            ->oneEvent()
            ->onUnlink(function (string $path) {
                $this->assertIsString($path);
            });
    }

    public function test_add_folder_file(): void
    {
        $this->triggerEvent('add_folder');
        (new FsWatch(__DIR__))
            ->usePolling()
            ->oneEvent()
            ->onAddDir(function (string $path) {
                $this->assertIsString($path);
            });
    }

    public function test_delete_folder_file(): void
    {
        $this->triggerEvent('delete_folder');
        (new FsWatch(__DIR__))
            ->usePolling()
            ->oneEvent()
            ->onUnlinkDir(function (string $path) {
                $this->assertIsString($path);
            });
    }
}

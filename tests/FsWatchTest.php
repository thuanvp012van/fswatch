<?php

declare(strict_types=1);

namespace Penguin\Component\FsWatch\Tests;

use Penguin\Component\FsWatch\FsWatch;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class FsWatchTest extends TestCase
{
    public function triggerEvent(string $event): void
    {
        $process = new Process(['php', __DIR__ . "/trigger-events/{$event}.php"]);
        $process->start();
    }

    public function testNoEvent(): void
    {
        $this->expectException(\RuntimeException::class);
        (new FsWatch(__DIR__))->oneEvent();
    }

    public function testAddFile(): void
    {
        $this->triggerEvent('add_file');
        (new FsWatch(__DIR__))
            ->usePolling()
            ->oneEvent()
            ->onAdd(function (string $path) {
                $this->assertIsString($path);
            });
    }

    public function testChangeFile(): void
    {
        $this->triggerEvent('change_file');
        (new FsWatch(__DIR__))
            ->usePolling()
            ->oneEvent()
            ->onChange(function (string $path) {
                $this->assertIsString($path);
            });
    }

    public function testDeleteFile(): void
    {
        $this->triggerEvent('delete_file');
        (new FsWatch(__DIR__))
            ->usePolling()
            ->oneEvent()
            ->onUnlink(function (string $path) {
                $this->assertIsString($path);
            });
    }

    public function testAddFolderFile(): void
    {
        $this->triggerEvent('add_folder');
        (new FsWatch(__DIR__))
            ->usePolling()
            ->oneEvent()
            ->onAddDir(function (string $path) {
                $this->assertIsString($path);
            });
    }

    public function testDeleteFolderFile(): void
    {
        $this->triggerEvent('delete_folder');
        (new FsWatch(__DIR__))
            ->usePolling()
            ->oneEvent()
            ->onUnlink(function (string $path) {
                $this->assertIsString($path);
            });
    }

    public function testUnWatch(): void
    {
        $this->triggerEvent('add_file');
        sleep(2);
        $this->triggerEvent('change_file');
        (new FsWatch(__DIR__, '../'))
            ->unWatch('../')
            ->usePolling()
            ->oneEvent()
            ->onChange(function (string $path) {
                $this->assertIsString($path);
            });
    }

    public function testAddWatch(): void
    {
        $this->triggerEvent('change_file');
        (new FsWatch(__DIR__))
            ->unWatch(__DIR__)
            ->addWatch(__DIR__)
            ->usePolling()
            ->oneEvent()
            ->onChange(function (string $path) {
                $this->assertIsString($path);
            });
    }

    public function testOnAny(): void
    {
        $this->triggerEvent('delete_file');
        (new FsWatch(__DIR__))
            ->usePolling()
            ->oneEvent()
            ->onAny(function (int $event, string $path) {
                $this->assertIsString($path);
                $this->assertSame(FsWatch::REMOVED, $event);
            });
    }

    public function testIgnore(): void
    {
        $this->triggerEvent('add_ignore_file');
        $this->triggerEvent('add_file');
        (new FsWatch(__DIR__))
            ->usePolling()
            ->ignore(".+txt")
            ->oneEvent()
            ->onAdd(function (string $path) {
                $this->assertStringNotContainsString($path, '.txt');
            });
    }

    public function testMultiEvent(): void
    {
        $this->triggerEvent('delete_ignore_file');
        $this->triggerEvent('delete_file');
        (new FsWatch(__DIR__))
            ->usePolling()
            ->oneEvent()
            ->multiEvent()
            ->onUnlink(function (string $path, Process $process) {
                $this->assertIsString($path);
                if (!str_contains($path, '.txt')) {
                    $process->stop();
                }
            });
    }
}

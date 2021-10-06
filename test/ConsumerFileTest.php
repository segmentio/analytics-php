<?php

declare(strict_types=1);

namespace Segment\Test;

use PHPUnit\Framework\TestCase;
use Segment\Client;

class ConsumerFileTest extends TestCase
{
    private Client $client;
    private string $filename = '/tmp/analytics.log';

    public function setUp(): void
    {
        date_default_timezone_set('UTC');
        $this->clearLog();

        $this->client = new Client(
            'oq0vdlg7yi',
            [
                'consumer' => 'file',
                'filename' => $this->filename,
            ]
        );
    }

    private function clearLog(): void
    {
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function tearDown(): void
    {
        $this->clearLog();
    }

    public function testTrack(): void
    {
        self::assertTrue($this->client->track([
            'userId'    => 'some-user',
            'event'     => 'File PHP Event - Microtime',
            'timestamp' => microtime(true),
        ]));
        $this->checkWritten('track');
    }

    public function checkWritten($type): void
    {
        exec('wc -l ' . $this->filename, $output);
        $out = trim($output[0]);
        self::assertSame($out, '1 ' . $this->filename);
        $str = file_get_contents($this->filename);
        $json = json_decode(trim($str), false);
        self::assertSame($type, $json->type);
        unlink($this->filename);
    }

    public function testIdentify(): void
    {
        self::assertTrue($this->client->identify([
            'userId' => 'Calvin',
            'traits' => [
                'loves_php' => false,
                'type'      => 'analytics.log',
                'birthday'  => time(),
            ],
        ]));
        $this->checkWritten('identify');
    }

    public function testGroup(): void
    {
        self::assertTrue($this->client->group([
            'userId'  => 'user-id',
            'groupId' => 'group-id',
            'traits'  => [
                'type' => 'consumer analytics.log test',
            ],
        ]));
    }

    public function testPage(): void
    {
        self::assertTrue($this->client->page([
            'userId'     => 'user-id',
            'name'       => 'analytics-php',
            'category'   => 'analytics.log',
            'properties' => ['url' => 'https://a.url/'],
        ]));
    }

    public function testScreen(): void
    {
        self::assertTrue($this->client->screen([
            'userId'     => 'userId',
            'name'       => 'grand theft auto',
            'category'   => 'analytics.log',
            'properties' => [],
        ]));
    }

    public function testAlias(): void
    {
        self::assertTrue($this->client->alias([
            'previousId' => 'previous-id',
            'userId'     => 'user-id',
        ]));
        $this->checkWritten('alias');
    }

    public function testSend(): void
    {
        for ($i = 0; $i < 200; ++$i) {
            $this->client->track([
                'userId' => 'userId',
                'event'  => 'event',
            ]);
        }
        exec('php --define date.timezone=UTC send.php --secret oq0vdlg7yi --file /tmp/analytics.log', $output);
        self::assertSame('sent 200 from 200 requests successfully', trim($output[0]));
        self::assertFileDoesNotExist($this->filename);
    }

    public function testProductionProblems(): void
    {
        // Open to a place where we should not have write access.
        $client = new Client(
            'oq0vdlg7yi',
            [
                'consumer' => 'file',
                'filename' => '/dev/xxxxxxx',
            ]
        );

        $tracked = $client->track(['userId' => 'some-user', 'event' => 'my event']);
        self::assertFalse($tracked);
    }

    public function testFileSecurityCustom(): void
    {
        $client = new Client(
            'testsecret',
            [
                'consumer'        => 'file',
                'filename'        => $this->filename,
                'filepermissions' => 0600,
            ]
        );
        $client->track(['userId' => 'some_user', 'event' => 'File PHP Event']);
        self::assertEquals(0600, (fileperms($this->filename) & 0777));
    }

    public function testFileSecurityDefaults(): void
    {
        $client = new Client(
            'testsecret',
            [
                'consumer' => 'file',
                'filename' => $this->filename,
            ]
        );
        $client->track(['userId' => 'some_user', 'event' => 'File PHP Event']);
        self::assertEquals(0644, (fileperms($this->filename) & 0777));
    }
}

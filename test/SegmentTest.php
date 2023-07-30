<?php

declare(strict_types=1);

namespace Segment\Test;

use PHPUnit\Framework;
use Segment\Segment;
use Segment\SegmentException;

final class SegmentTest extends Framework\TestCase
{
    protected function setUp(): void
    {
        self::resetSegment();
    }

    public function testAliasThrowsSegmentExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(SegmentException::class);
        $this->expectExceptionMessage('Segment::init() must be called before any other tracking method.');

        Segment::alias([]);
    }
    public function testFlushThrowsSegmentExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(SegmentException::class);
        $this->expectExceptionMessage('Segment::init() must be called before any other tracking method.');

        Segment::flush();
    }
    public function testGroupThrowsSegmentExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(SegmentException::class);
        $this->expectExceptionMessage('Segment::init() must be called before any other tracking method.');

        Segment::group([]);
    }
    public function testIdentifyThrowsSegmentExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(SegmentException::class);
        $this->expectExceptionMessage('Segment::init() must be called before any other tracking method.');

        Segment::identify([]);
    }
    public function testPageThrowsSegmentExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(SegmentException::class);
        $this->expectExceptionMessage('Segment::init() must be called before any other tracking method.');

        Segment::page([]);
    }
    public function testScreenThrowsSegmentExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(SegmentException::class);
        $this->expectExceptionMessage('Segment::init() must be called before any other tracking method.');

        Segment::screen([]);
    }
    public function testTrackThrowsSegmentExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(SegmentException::class);
        $this->expectExceptionMessage('Segment::init() must be called before any other tracking method.');

        Segment::track([]);
    }

    private static function resetSegment(): void
    {
        $property = new \ReflectionProperty(
            Segment::class,
            'client'
        );

        $property->setAccessible(true);
        $property->setValue(null);
    }
}

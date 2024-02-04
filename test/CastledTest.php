<?php

declare(strict_types=1);

namespace Castled\Test;

use PHPUnit\Framework;
use Castled\Castled;
use Castled\CastledException;

final class CastledTest extends Framework\TestCase
{
    protected function setUp(): void
    {
        self::resetCastled();
    }

    public function testAliasThrowsCastledExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(CastledException::class);
        $this->expectExceptionMessage('Castled::init() must be called before any other tracking method.');

        Castled::alias([]);
    }
    public function testFlushThrowsCastledExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(CastledException::class);
        $this->expectExceptionMessage('Castled::init() must be called before any other tracking method.');

        Castled::flush();
    }
    public function testGroupThrowsCastledExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(CastledException::class);
        $this->expectExceptionMessage('Castled::init() must be called before any other tracking method.');

        Castled::group([]);
    }
    public function testIdentifyThrowsCastledExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(CastledException::class);
        $this->expectExceptionMessage('Castled::init() must be called before any other tracking method.');

        Castled::identify([]);
    }
    public function testPageThrowsCastledExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(CastledException::class);
        $this->expectExceptionMessage('Castled::init() must be called before any other tracking method.');

        Castled::page([]);
    }
    public function testScreenThrowsCastledExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(CastledException::class);
        $this->expectExceptionMessage('Castled::init() must be called before any other tracking method.');

        Castled::screen([]);
    }
    public function testTrackThrowsCastledExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(CastledException::class);
        $this->expectExceptionMessage('Castled::init() must be called before any other tracking method.');

        Castled::track([]);
    }

    private static function resetCastled(): void
    {
        $property = new \ReflectionProperty(
            Castled::class,
            'client'
        );

        $property->setAccessible(true);
        $property->setValue(null);
    }
}

<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (\Illuminate\Support\Facades\DB::connection() instanceof \Illuminate\Database\SQLiteConnection) {
            $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
            $pdo->sqliteCreateFunction('least', function (...$args) {
                return min($args);
            });
            $pdo->sqliteCreateFunction('greatest', function (...$args) {
                return max($args);
            });
            $pdo->sqliteCreateFunction('acos', function ($val) {
                return acos((float)$val);
            });
            $pdo->sqliteCreateFunction('cos', function ($val) {
                return cos((float)$val);
            });
            $pdo->sqliteCreateFunction('sin', function ($val) {
                return sin((float)$val);
            });
            $pdo->sqliteCreateFunction('radians', function ($val) {
                return deg2rad((float)$val);
            });
        }
    }
}

<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pengaman: tes tidak boleh berjalan di database asli,
     * karena RefreshDatabase menjalankan migrate:fresh.
     */
    protected function setUpTraits()
    {
        $default = config('database.default');
        $name = (string) config("database.connections.{$default}.database");

        if (! str_contains($name, 'test')) {
            throw new RuntimeException("Tes dihentikan: database '{$name}' bukan database uji (namanya harus mengandung 'test').");
        }

        return parent::setUpTraits();
    }
}
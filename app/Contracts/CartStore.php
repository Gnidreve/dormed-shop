<?php

namespace App\Contracts;

interface CartStore
{
    public function get(): array;

    public function put(array $payload): void;
}

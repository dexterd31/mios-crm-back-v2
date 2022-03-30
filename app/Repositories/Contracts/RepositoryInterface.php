<?php

namespace App\Repositories\Contracts;

interface RepositoryInterface
{
    public function all() : object;

    public function find(int $id) : object;

    public function create(array $data) : object;

    public function update(int $id, array $data) : object;

    public function delete(int $id);
}

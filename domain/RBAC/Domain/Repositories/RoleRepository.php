<?php

declare(strict_types=1);

namespace Domain\RBAC\Domain\Repositories;

interface RoleRepository
{
    public function existsWithName(string $name): bool;

    /** @return array<int, array{id:int, name:string, permissionNames:string[]}> */
    public function all(): array;

    /** @return array{id:int, name:string, permissionNames:string[]}|null */
    public function find(string $name): ?array;

    /** @return string[] */
    public function names(): array;

    /** @param  string[]  $permissionNames */
    public function create(string $name, array $permissionNames): int;

    /** @param  string[]  $permissionNames */
    public function updatePermissions(string $name, array $permissionNames): void;
}

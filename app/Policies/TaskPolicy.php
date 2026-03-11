<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Task;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any:task');
    }

    public function view(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('view:task');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create:task');
    }

    public function update(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('update:task');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('delete:task');
    }

    public function export(AuthUser $authUser): bool
    {
        return $authUser->can('export:task');
    }

    public function complete(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('complete:task');
    }

}
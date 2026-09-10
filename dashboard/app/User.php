<?php
namespace Portal;

final class User
{
    public int $id;
    public int $role;
    public string $name;
    public string $department;
    public string $tasks;

    public static function fromSession(array $session): ?self
    {
        $data = $session['u_data'] ?? null;
        if (!is_array($data)) return null;
        $id = filter_var($data[5] ?? null, FILTER_VALIDATE_INT);
        $role = filter_var($data[4] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1 || $role === false || $role < 0) return null;
        $user = new self();
        $user->id = $id;
        $user->role = $role;
        $user->name = (string) ($data[0] ?? 'Employee');
        $user->department = (string) ($data[2] ?? '');
        $user->tasks = (string) ($data[3] ?? '');
        return $user;
    }

    public function hasRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}

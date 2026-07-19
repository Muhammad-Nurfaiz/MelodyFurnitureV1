<?php

namespace App\Policies;

use App\Models\Series;
use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class SeriesPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return true;
    }

    public function view(Admin $admin, Series $series): bool
    {
        return true;
    }

    public function create(Admin $admin): bool
    {
        return true;
    }

    public function update(Admin $admin, Series $series): bool
    {
        return true;
    }

    public function delete(Admin $admin, Series $series): bool
    {
        return true;
    }
}
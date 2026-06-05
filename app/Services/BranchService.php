<?php

namespace App\Services;

use App\Models\Branch;

class BranchService
{
    public function create(array $data): Branch
    {
        $branch = new Branch($data);
        $branch->disableLogging()->save();

        activity()
            ->performedOn($branch)
            ->event('created')
            ->log("Филиал эҷод шуд: {$branch->name} ({$branch->code})");

        return $branch;
    }

    public function update(Branch $branch, array $data): Branch
    {
        $branch->disableLogging()->update($data);

        activity()
            ->performedOn($branch)
            ->event('updated')
            ->log("Филиал навсозӣ шуд: {$branch->name} ({$branch->code})");

        return $branch;
    }

    public function delete(Branch $branch): void
    {
        $name = $branch->name;
        $code = $branch->code;

        activity()
            ->performedOn($branch)
            ->event('deleted')
            ->log("Филиал нест карда шуд: {$name} ({$code})");

        $branch->disableLogging()->delete();
    }
}

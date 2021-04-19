<?php

namespace App\Drivers\Traits;

use App\Models\Contracts;

trait PlugDriverTrait
{
    protected function beforeSaveOrUpdate(): bool
    {
        return true;
    }

    protected function afterSaveOrUpdate(): void
    {
        return;
    }

    public function statusConfirmed(Contracts $contract): void
    {
        return;
    }
}

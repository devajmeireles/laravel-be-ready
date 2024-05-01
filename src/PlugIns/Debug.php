<?php

namespace DevAjMeireles\LaravelReady\PlugIns;

use DevAjMeireles\LaravelReady\Actions\ExecuteCommand;
use DevAjMeireles\LaravelReady\LaravelReady;

class Debug
{
    public function __invoke(LaravelReady $base): bool|string
    {
        if (($status = ExecuteCommand::run('barryvdh/laravel-debugbar --dev')) !== true) {
            return $status;
        }

        return true;
    }
}

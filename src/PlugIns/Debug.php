<?php

namespace DevAjMeireles\LaravelReady\PlugIns;

use DevAjMeireles\LaravelReady\Actions\ExecuteCommand;
use DevAjMeireles\LaravelReady\LaravelReady;
use function Laravel\Prompts\info;

class Debug
{
    public function __invoke(LaravelReady $base): bool|string
    {
        if (($status = ExecuteCommand::run('barryvdh/laravel-debugbar --dev')) !== true) {
            return $status;
        }

        info('DebugBar has been installed successfully.');

        return true;
    }
}

<?php

namespace DevAjMeireles\LaravelReady\PlugIns;

use DevAjMeireles\LaravelReady\Actions\ExecuteCommand;
use DevAjMeireles\LaravelReady\LaravelReady;

class Pint
{
    public function __invoke(LaravelReady $base): bool|string
    {
        if (($status = ExecuteCommand::run('laravel/pint --dev')) !== true) {
            return $status;
        }

        $composer                  = json_decode(file_get_contents('composer.json'));
        $composer->scripts->format = './vendor/bin/pint';
        file_put_contents('composer.json', json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return true;
    }
}

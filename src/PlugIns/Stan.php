<?php

namespace DevAjMeireles\LaravelReady\PlugIns;

use DevAjMeireles\LaravelReady\Actions\ExecuteCommand;
use DevAjMeireles\LaravelReady\LaravelReady;
use function Laravel\Prompts\info;

class Stan
{
    public function __invoke(LaravelReady $base): bool|string
    {
        if (($status = ExecuteCommand::run('larastan/larastan:^2.0 --dev')) !== true) {
            return $status;
        }

        $content = <<<'CONTENT'
includes:
    - vendor/larastan/larastan/extension.neon

parameters:

    paths:
        - app/

    # Level 9 is the highest level
    level: 5

#    ignoreErrors:
#        - '#PHPDoc tag @var#'
#
#    excludePaths:
#        - ./*/*/FileToBeExcluded.php
#
#    checkMissingIterableValueType: false
CONTENT;

        file_put_contents('phpstan.neon', $content);

        $composer                   = json_decode(file_get_contents('composer.json'));
        $composer->scripts->analyse = './vendor/bin/phpstan analyse --memory-limit=2G';
        file_put_contents('composer.json', json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        info('LaraStan has been installed successfully.');

        return true;
    }
}

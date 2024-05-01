<?php

namespace DevAjMeireles\LaravelReady\Actions;

use Exception;
use Symfony\Component\Process\Process;

class ExecuteCommand
{
    public static function run(string $command): bool|string
    {
        $command = "composer require $command";

        try {
            Process::fromShellCommandline("$command")
                ->setTty(false)
                ->setTimeout(null)
                ->run();

            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}

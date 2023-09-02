<?php

if (!file_exists($autoload = __DIR__ . '/vendor/autoload.php')) {
    echo 'Please, run composer install before run this script';

    exit;
}

require $autoload;

use GuzzleHttp\Client;
use Symfony\Component\Process\Process;
use function Laravel\Prompts\{confirm, info, multiselect, select, spin};

/** Default Zone */
const OPTIONS = [
    'readyEnvironment'  => 'Prepare .env with sqlite',
    'readyLivewire'     => '[Package] Install Livewire',
    'readySeeder'       => 'Prepare DatabaseSeeder',
    'readyProvider'     => 'Prepare AppServiceProvider',
    'readyAlpine'       => 'Remove AlpineJs',
    'readyPint'         => 'Install Laravel Pint',
    'readyLarastan'     => '[Package] Install LaraStan',
    'readyLaravelDebug' => '[Package] Install Laravel Debugbar',
    'readyIdeHelper'    => '[Package] Install Laravel IDE Helper',
    'readyMigration'    => 'Run migrations',
];

const STEPS = [
    'readyEnvironment'  => 'Preparing Environment...',
    'readyLivewire'     => 'Installing Livewire...',
    'readySeeder'       => 'Preparing DatabaseSeeder...',
    'readyProvider'     => 'Preparing AppServiceProvider...',
    'readyAlpine'       => 'Removing AlpineJs...',
    'readyPint'         => 'Installing Laravel Pint...',
    'readyLarastan'     => 'Installing LaraStan...',
    'readyLaravelDebug' => 'Installing Laravel Debugbar...',
    'readyIdeHelper'    => 'Installing Laravel IDE Helper...',
    'readyMigration'    => 'Running migrations...',
];

$steps            = [];
$selectedPackages = [];
$linkValet        = false;
$livewireVersion  = null;
$selfDestruction  = false;
$livewireSelector = function () {
    return select('Select Livewire version', [
        'livewire/livewire:^3.0' => 'Livewire [3.x]',
        'livewire/livewire:^2.0' => 'Livewire [2.x]',
    ]);
};
/** end */

$type = select('Start by selection what you want to do:', [
    'packages' => 'Install Packages',
    'project' => 'Prepare New Project'
]);

if ($type === 'packages') {
    $packages = collect(OPTIONS)
        ->filter(fn ($key) => str_contains($key, '[Package]'))
        ->mapWithKeys(fn ($value, $key) => [$key => str_replace('[Package] ', '', $value)])
        ->toArray();

    $selectedPackages = multiselect('Select the packages:', $packages, scroll: 20, required: true);

    if (in_array('readyLivewire', array_keys($packages))) {
        $livewireVersion = $livewireSelector();
    }

    $steps = $packages;

    dd($steps, $livewireVersion);
} else {
    $steps = multiselect('Select what you want to do:', OPTIONS, scroll: 20, required: true);

    if (in_array('readyLivewire', $steps)) {
        $livewireVersion = $livewireSelector();
    }

    $linkValet = confirm('Do you want to generate a Valet link?');
    $selfDestruction = confirm('Do you want to remove this file after run?');
}

function readyEnvironment(): void
{
    $content = file_get_contents('.env');
    $folder = str(__DIR__)->afterLast('/')->value();
    $content = str_replace('DB_CONNECTION=mysql', 'DB_CONNECTION=sqlite', $content);
    $content = str_replace("DB_DATABASE=$folder", 'DB_DATABASE=/Users/aj/database/database.sqlite', $content);

    file_put_contents('.env', $content);
}

function readyLivewire(): bool
{
    global $livewireVersion;

    try {
        return runCommand("composer require $livewireVersion");
    } catch (Exception $e) {
        //
    }

    return false;
}

function readySeeder(): void //OK
{
    $file = file_get_contents('database/seeders/DatabaseSeeder.php');
    $lines = explode("\n", $file);

    foreach ($lines as $key => $line) {
        if (empty($line)) continue;

        if (!str_contains($line, '//')) continue;

        if (str_contains($line, 'WithoutModelEvents')) continue;

        if (str_contains($line, 'factory(10)')) continue;

        $lines[$key] = str_replace('//', '', $line);
    }

    file_put_contents('database/seeders/DatabaseSeeder.php', implode("\n", $lines));
}

function readyProvider(): void // OK
{
    $file = file_get_contents('app/Providers/AppServiceProvider.php');
    $lines = explode("\n", $file);

    foreach ($lines as $key => $line) {
        if (empty($line)) continue;

        if ($key !== 21) continue;

        $lines[$key] = str_replace('//', 'auth()->loginUsingId(1);', $line);
    }

    file_put_contents('app/Providers/AppServiceProvider.php', implode("\n", $lines));
}

function readyAlpine(): bool
{
    global $livewireVersion;

    if ($livewireVersion === 'livewire/livewire:^2.0') return true;

    try {
        $status = runCommand("npm remove alpinejs");

        if (!$status) {
            return false;
        }

        $file = file_get_contents('resources/js/app.js');
        $lines = explode("\n", $file);

        foreach ($lines as $key => $line) {
            $line = strtolower($line);

            if (!str_contains($line, 'alpine')) continue;

            unset($lines[$key]);
        }

        $lines = array_filter($lines);

        file_put_contents('resources/js/app.js', implode("\n", $lines));

        return runCommand("npm run build");
    } catch (Exception) {
        //
    }

    return false;
}

function readyPint(): bool
{
    try {
        $status = runCommand("composer require laravel/pint --dev");

        if (!$status) {
            return false;
        }

        file_put_contents('pint.json', '');

        $content = (new Client())->get('https://gist.githubusercontent.com/devajmeireles/8c00117a89931c606ba4ebb2b5c58bd3/raw/e193a485029a46ad853aab526a92fd88359c149f/pint.json');

        file_put_contents('pint.json', $content->getBody()->getContents());

        $composer = json_decode(file_get_contents('composer.json'));
        $composer->scripts->format = './vendor/bin/pint';
        file_put_contents('composer.json', json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return true;
    } catch (Exception) {
        //
    }

    return false;
}

function readyLarastan(): bool
{
    try {
        $status = runCommand("composer require nunomaduro/larastan:^2.0 --dev");

        if (!$status) {
            return false;
        }

        $content = <<<FILE
includes:
    - ./vendor/nunomaduro/larastan/extension.neon

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
FILE;

        file_put_contents('phpstan.neon', $content);

        $composer = json_decode(file_get_contents('composer.json'));
        $composer->scripts->analyse = './vendor/bin/phpstan analyse --memory-limit=2G';
        file_put_contents('composer.json', json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } catch (Exception) {
        //
    }

    return false;
}

function readyLaravelDebug(): bool
{
    return runCommand("composer require barryvdh/laravel-debugbar --dev");
}

function readyIdeHelper(): bool
{
    return runCommand("composer require --dev barryvdh/laravel-ide-helper");
}

function readyMigration(): bool
{
    return runCommand("php artisan migrate:fresh --seed");
}

function runCommand(string $command): bool
{
    try {
        Process::fromShellCommandline("$command")
            ->setTty(false)
            ->setTimeout(null)
            ->run();

        return true;
    } catch (Exception $e) {
        return false;
    }
}

foreach ($steps as $step) {
    spin(function () use ($step) {
        try {
            if ($step() === false) {
                throw new Exception('Ops!');
            }
        } catch (Exception) {
            echo "$step failed!";
        }

        return true;
    }, STEPS[$step]);
}

info('Done!');

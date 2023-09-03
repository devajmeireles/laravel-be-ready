<?php

$envContent = getEnvironmentContent();

/** Verification Zone */
if (!file_exists($autoload = __DIR__ . '/vendor/autoload.php')) {
    exit("Please, run \"composer install\" before running this script." . PHP_EOL);
}

if ($envContent && str_contains($envContent, 'APP_ENV=production')) {
    exit("For safety, you cannot run this script in production." . PHP_EOL);
}
/** End Verification Zone */

require $autoload;

use GuzzleHttp\Client;

use function Laravel\Prompts\{confirm, info, multiselect, select, spin, text};

use Symfony\Component\Process\Process;

/** Default Zone */
$actions = [
    'executeEnvironmentPreparation'        => 'Prepare .env with SQLite',
    'executeLivewirePreparation'           => 'Install Livewire',
    'executeSeederPreparation'             => 'Prepare DatabaseSeeder',
    'executeAppServiceProviderPreparation' => 'Prepare AppServiceProvider',
    'executeAlpineJsPreparation'           => 'Remove AlpineJs',
    'executePintPreparation'               => '[Package] Install Laravel Pint',
    'executeLaraStanPreparation'           => '[Package] Install LaraStan',
    'executeLaravelDebugBarPreparation'    => '[Package] Install Laravel DebugBar',
    'executeIdeHelperPreparation'          => '[Package] Install Laravel IDE Helper',
    'executeMigrations'                    => 'Run Migrations',
    'executeCommentsRemoval'               => 'Remove Unnecessary Default Comments',
];

$messages = [
    'executeEnvironmentPreparation'        => 'Preparing Environment...',
    'executeLivewirePreparation'           => 'Installing Livewire...',
    'executeSeederPreparation'             => 'Preparing DatabaseSeeder...',
    'executeAppServiceProviderPreparation' => 'Preparing AppServiceProvider...',
    'executeAlpineJsPreparation'           => 'Removing AlpineJs...',
    'executePintPreparation'               => 'Installing Laravel Pint...',
    'executeLaraStanPreparation'           => 'Installing LaraStan...',
    'executeLaravelDebugBarPreparation'    => 'Installing Laravel Debugbar...',
    'executeIdeHelperPreparation'          => 'Installing Laravel IDE Helper...',
    'executeMigrations'                    => 'Running Migrations...',
    'executeValetPreparation'              => 'Preparing Valet...',
    'executeCommentsRemoval'               => 'Removing Unnecessary Comments...',
];

$executionSteps       = [];
$linkValet            = null;
$livewireVersion      = null;
$alpineRemoval        = null;

$livewireSelector = function () {
    return select('Select Livewire version:', [
        'livewire/livewire:^3.0' => 'Livewire [3.x]',
        'livewire/livewire:^2.0' => 'Livewire [2.x]',
    ]);
};
/** End Default Zone */

/** Execution Zone */
$type = select('What do you want to do?', [
    'packages' => 'Install Dev. Tools Packages',
    'project'  => 'Prepare New Project',
]);

if ($type === 'packages') {
    /** Packages Only */
    $packages = collect($actions)
        ->filter(fn ($key) => str_contains($key, '[Package]'))
        ->mapWithKeys(fn ($value, $key) => [$key => str_replace('[Package] ', '', $value)])
        ->toArray();

    $selecteds = multiselect('Select the packages:', $packages, scroll: 20, required: true);

    if (in_array('executeLivewirePreparation', array_keys($selecteds))) {
        $livewireVersion = $livewireSelector();
        $selecteds[]     = 'executeAlpineJsPreparation';
    }

    $executionSteps = $selecteds;
} else {
    /** Full New Project */
    $actions = collect($actions)
        ->map(fn ($value) => str_replace('[Package] ', '', $value))
        ->toArray();

    $executionSteps = multiselect('What do you want to do?', $actions, scroll: 20, required: true);

    if (in_array('executeLivewirePreparation', $executionSteps)) {
        $livewireVersion = $livewireSelector();
    }

    if (confirm('Do you want to generate a Valet link?')) {
        $linkValet = text(
            'Enter the link name',
            required: true,
            validate: function ($value) {
                if (str_contains($value, ' ')) {
                    return 'The link name cannot contain spaces';
                }

                if (str_contains($value, '.test')) {
                    return 'The link name cannot contain .test';
                }

                return null;
            },
            hint: "Use . to current folder. You don't need to add .test"
        );

        $executionSteps[] = 'executeValetPreparation';
    }
}
/** End Execution Zone */

/** Functions Zone */
function getEnvironmentContent(): bool|string
{
    return file_get_contents('.env');
}

function executeEnvironmentPreparation(): void
{
    global $envContent;

    $content = preg_replace('/^(DB_CONNECTION\s*=\s*).*$/m', 'DB_CONNECTION=sqlite', $envContent);
    $content = preg_replace('/^(DB_DATABASE\s*=\s*).*$/m', 'DB_DATABASE=/Users/aj/database/database.sqlite', $content);

    file_put_contents('.env', $content);
}

function executeLivewirePreparation(): bool|string
{
    global $livewireVersion;

    if (($result = executeCommand("composer require $livewireVersion")) !== true) {
        return $result;
    }

    if ($livewireVersion !== 'livewire/livewire:^3.0') {
        return true;
    }

    return executeAlpineJsPreparation();
}

function executeSeederPreparation(): void //OK
{
    $file  = file_get_contents('database/seeders/DatabaseSeeder.php');
    $lines = explode("\n", $file);

    foreach ($lines as $key => $line) {
        if (
            empty($line) ||
            !str_contains($line, '//') ||
            str_contains($line, 'WithoutModelEvents') ||
            str_contains($line, 'factory(10)')
        ) {
            continue;
        }

        $lines[$key] = str_replace('//', '', $line);
    }

    file_put_contents('database/seeders/DatabaseSeeder.php', implode("\n", $lines));
}

function executeAppServiceProviderPreparation(): void // OK
{
    $file  = file_get_contents('app/Providers/AppServiceProvider.php');
    $lines = explode("\n", $file);

    foreach ($lines as $key => $line) {
        if (empty($line) || $key !== 21) {
            continue;
        }

        $lines[$key] = str_replace('//', 'auth()->loginUsingId(1);', $line);
    }

    file_put_contents('app/Providers/AppServiceProvider.php', implode("\n", $lines));
}

function executeAlpineJsPreparation(): bool|string
{
    global $livewireVersion;

    if ($livewireVersion === 'livewire/livewire:^2.0') {
        return true;
    }

    try {
        if (($status = executeCommand("npm remove alpinejs")) !== true) {
            return $status;
        }

        $pattern = "/import\s+'\.\/bootstrap';/";
        $content = preg_replace($pattern, '', file_get_contents('resources/js/app.js'));
        file_put_contents('resources/js/app.js', implode("\n", $content));

        return executeCommand("npm run build");
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function executePintPreparation(): bool|string
{
    try {
        if (($status = executeCommand("composer require laravel/pint --dev")) !== true) {
            return $status;
        }

        $content = (new Client())->get('https://gist.githubusercontent.com/devajmeireles/8c00117a89931c606ba4ebb2b5c58bd3/raw/e193a485029a46ad853aab526a92fd88359c149f/pint.json');

        file_put_contents('pint.json', $content->getBody()->getContents());

        $composer                  = json_decode(file_get_contents('composer.json'));
        $composer->scripts->format = './vendor/bin/pint';
        file_put_contents('composer.json', json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function executeLaraStanPreparation(): bool|string
{
    try {
        if (($status = executeCommand("composer require nunomaduro/larastan:^2.0 --dev")) !== true) {
            return $status;
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

        $composer                   = json_decode(file_get_contents('composer.json'));
        $composer->scripts->analyse = './vendor/bin/phpstan analyse --memory-limit=2G';
        file_put_contents('composer.json', json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function executeLaravelDebugBarPreparation(): bool|string
{
    return executeCommand("composer require barryvdh/laravel-debugbar --dev");
}

function executeIdeHelperPreparation(): bool|string
{
    return executeCommand("composer require barryvdh/laravel-ide-helper --dev");
}

function executeMigrations(): bool|string
{
    return executeCommand("php artisan migrate:fresh --seed");
}

function executeValetPreparation(): bool|string
{
    global $envContent, $linkValet;

    if (!$linkValet) {
        return true;
    }

    if (($status = executeCommand("valet link $linkValet")) !== true) {
        return $status;
    }

    preg_match('/APP_URL=(["\'])(.*?)\\1/', $envContent, $matches);

    $env = str_replace($matches[0], "APP_URL=http://$linkValet.test", $envContent);

    file_put_contents('.env', $env);

    return true;
}

function executeCommentsRemoval(): bool|string
{
    try {
        //Note: Since Laravel is not bootstrapped we can't use Laravel File Facade here.
        function filesRecursively($directory): array
        {
            $fileList = [];

            $files = glob($directory . '/*');

            foreach ($files as $file) {
                if (is_dir($file)) {
                    $fileList = array_merge($fileList, filesRecursively($file));
                } else {
                    $fileList[] = $file;
                }
            }

            return $fileList;
        }

        $files = array_merge(filesRecursively(__DIR__ . '/app'), filesRecursively(__DIR__ . '/database'));

        foreach ($files as $file) {
            $content = preg_replace('/\/\*(.*?)\*\/|\/\/(.*?)(?=\r|\n)/s', '', file_get_contents($file));

            file_put_contents($file, $content);
        }

        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function executeCommand(string $command): bool|string
{
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
/** End Functions Zone */

/** Steps Execution */
foreach ($executionSteps as $step) {
    spin(function () use ($step) {
        try {
            if (($result = $step()) !== true) {
                throw new Exception($result);
            }
        } catch (Exception $e) {
            echo "$step failed: {$e->getMessage()}";
        }

        return true;
    }, $messages[$step]);
}
/** End Steps Execution */

info('Your project is ready to be used! 🚀 The file will be deleted in 3 seconds.');

sleep(3);

// unlink(__FILE__);

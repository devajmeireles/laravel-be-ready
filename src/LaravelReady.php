<?php

namespace DevAjMeireles\LaravelReady;

use function Laravel\Prompts\{confirm, info};

class LaravelReady
{
    public function __construct(
        private bool $pint = false,
        private bool $stan = false,
        private bool $debug = false,
        private bool $comments = false,
    ) {
        //
    }

    public function ask(): self
    {
        $this->pint     = confirm('Do you want to install Laravel Pint', required: true);
        $this->stan     = confirm('Do you want to install Laravel Stan', required: true);
        $this->debug    = confirm('Do you want to install Laravel Debug Bar', required: true);
        $this->comments = confirm('Do you want to remove all unnecessary comments?', required: true);

        return $this;
    }

    public function execute(): void
    {
        foreach ([
            'pint'     => $this->pint,
            'stan'     => $this->stan,
            'debug'    => $this->debug,
            'comments' => $this->comments,
        ] as $key => $value) {
            if (!$value) {
                continue;
            }

            $class = 'DevAjMeireles\\LaravelReady\\PlugIns\\' . ucfirst($key);

            (new $class())($this);
        }

        info('Your project is ready! 🚀');

        if (confirm('Do you want to delete this file?', required: true)) {
            unlink(__FILE__);

            info('File deleted successfully');
        }
    }
}

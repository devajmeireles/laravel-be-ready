<?php

namespace DevAjMeireles\LaravelReady\PlugIns;

use DevAjMeireles\LaravelReady\LaravelReady;

class Comments
{
    public function __invoke(LaravelReady $base): bool|string
    {
        function recursively($directory): array
        {
            $list = [];

            $files = glob($directory . '/*');

            foreach ($files as $file) {
                if (is_dir($file)) {
                    $list = array_merge($list, recursively($file));
                } else {
                    $list[] = $file;
                }
            }

            return $list;
        }

        $files = array_merge(recursively(__DIR__ . '/app'), recursively(__DIR__ . '/database'));

        foreach ($files as $file) {
            $content = preg_replace('/\/\*(.*?)\*\//s', '', file_get_contents($file));

            file_put_contents($file, $content);
        }

        return true;
    }
}

<?php

spl_autoload_register(function ($c) {
    if (strpos($c, 'Xiaochi') === 0) {
        $f = __DIR__.'/src/'.substr($c, strlen('Xiaochi')+1).'.php';
        require $f;
    }
});

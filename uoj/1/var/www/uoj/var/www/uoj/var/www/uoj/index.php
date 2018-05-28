<?php

require $_SERVER['DOCUMENT_ROOT'] . '/app/uoj-lib.php';
require $_SERVER['DOCUMENT_ROOT'] . '/app/route.php';
require $_SERVER['DOCUMENT_ROOT'] . '/app/controllers/subdomain/blog/route.php';
echo "test";
include($_SERVER['DOCUMENT_ROOT'] . '/app/controllers' . call_user_func(function() {
        $route = Route::dispatch();
        $q_pos = strpos($route['action'], '?');

        if ($q_pos === false) {
            $path = $route['action'];
        } else {
            parse_str(substr($route['action'], $q_pos + 1), $vars);
            $_GET += $vars;
            $path = substr($route['action'], 0, $q_pos);
        }

        if (isset($route['onload'])) {
            call_user_func($route['onload']);
        }

        return $path;
    }));

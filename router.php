<?php
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
if (preg_match('#^/series/([^/]+)/[^/]+/([0-9]+)\.(.+)$#', $path, $m)) {
    $_GET['type'] = 'series';
    $_GET['movieId'] = $m[2];
    $_GET['data'] = $m[3];
    $_GET['username'] = $m[1];
    require 'play.php';
    return true;
}
return false;

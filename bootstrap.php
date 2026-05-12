<?php

namespace nostriphant\Blossom;

require_once __DIR__ . '/vendor/autoload.php';

define('ROOT_DIR', __DIR__);
function data_directory() {
    return __DIR__ . '/data';
}
function make_data_directory() {
    return is_dir(data_directory()) || mkdir(data_directory());
}
function destroy_data_directory() {
    return true;
}


$dotenv = Dotenv\Dotenv::createMutable(__DIR__);
$dotenv->safeLoad();
<?php

defined('BASEPATH') OR exit('O acesso direto a esse script não é permitido!');

$active_group = 'mateus';
$query_builder = TRUE;

// Minha máquina
$db['mateus'] = array(
    'dsn' => '',
    'hostname' => 'localhost',
    'username' => 'dbuser',
    'password' => 'password',
    'database' => 'senschema_db',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);
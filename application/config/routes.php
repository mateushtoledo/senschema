<?php
defined('BASEPATH') OR exit('O acesso direto a esse script não é permitido');
//-----------------------------ROUTES-------------------------------------
$route['default_controller'] = 'senschema';
$route['404_override'] = 'senschema/notFound';
$route["select-endpoint"]["get"] = "senschema/showEndpoints";
$route["select-endpoint"]["post"] = "senschema/extractEndpoints";
$route["endpoint-schema"] = "senschema/createJsonSchema";
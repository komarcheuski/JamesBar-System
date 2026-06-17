<?php

/*
|--------------------------------------------------------------------------
| ARQUIVO: bootstrap.php
|--------------------------------------------------------------------------
| FUNÇÃO:
| Inicializa o ambiente de testes PHPUnit e carrega dependências necessárias.
*/
error_reporting(E_ALL);

require_once __DIR__ . '/../security/SecurityHelper.php';
require_once __DIR__ . '/../security/TotpService.php';

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Promoter.php';
require_once __DIR__ . '/../models/Lista.php';

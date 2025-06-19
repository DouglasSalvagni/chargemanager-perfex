<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Lista de URLs a serem excluídas da verificação CSRF
 * Retorna um array de padrões (pode incluir expressões regulares)
 */
return [
    'chargemanager/webhook/.+'
];
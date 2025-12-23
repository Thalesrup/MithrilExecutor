<?php

use MithrilExecutor\Resolvers\ReflectionResolver;

// Este arquivo simula o autoloader da sua aplicação
require_once __DIR__ . '/ReportGenerator.php';

// O bootstrap deve retornar um ResolverInterface
// Para casos simples, usamos o ReflectionResolver padrão
return new ReflectionResolver();

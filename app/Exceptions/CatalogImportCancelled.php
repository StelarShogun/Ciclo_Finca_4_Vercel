<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza desde el callback de avance cuando el administrador cancela una
 * importación en curso. Al propagarse rompe la transacción del importador, de
 * modo que no quedan filas a medio aplicar.
 */
final class CatalogImportCancelled extends RuntimeException {}

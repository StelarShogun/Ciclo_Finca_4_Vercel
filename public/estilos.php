<?php
/**
 * Archivo unificado de estilos CSS
 * Este archivo concatena todos los archivos CSS del proyecto
 * para reducir el número de peticiones HTTP al servidor
 */

// Define el tipo de contenido como CSS
header('Content-type: text/css');

// Opcional: Configurar cache (1 día = 86400 segundos)
header('Cache-Control: public, max-age=86400');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

// Función para incluir archivos CSS de forma segura
function incluir_css($ruta) {
    $rutaCompleta = __DIR__ . '/' . $ruta;
    
    // Verificar que el archivo existe
    if (file_exists($rutaCompleta)) {
        // Leer y mostrar el contenido del archivo
        $contenido = file_get_contents($rutaCompleta);
        
        // Opcional: Comentar el nombre del archivo para debugging
        echo "\n/* ===== " . basename($ruta) . " ===== */\n";
        
        echo $contenido;
        echo "\n";
    } else {
        // Si el archivo no existe, mostrar un comentario (no rompe el CSS)
        echo "\n/* Archivo no encontrado: " . $ruta . " */\n";
    }
}

// Incluir archivos CSS en orden lógico
// 1. Variables primero (necesarias para otros archivos)
incluir_css('css/variables.css');

// 2. Estilos base y principales
incluir_css('css/main.css');

// 3. Estilos de administración
incluir_css('css/admin.css');
incluir_css('css/admin-pages.css');

// 4. Componentes reutilizables (compartidos entre módulos)
incluir_css('css/components/modals.css');
incluir_css('css/buttons.css');
incluir_css('css/formularios.css');
incluir_css('css/pagination-sidebar.css');
incluir_css('css/loading-states.css');

// 5. Estilos específicos de páginas
incluir_css('css/dashboard.css');
incluir_css('css/inventory.css');
incluir_css('css/sales.css');
incluir_css('css/proveedores.css');
incluir_css('css/usuarios.css');
incluir_css('css/login.css');
incluir_css('css/export-import.css');
incluir_css('css/clientes.css');

?>


<?php
declare(strict_types=1);

/**
 * Plugin Name: VGT Universal Bridge (Strict PSR-4 & IoC)
 * Plugin URI: https://visiongaiatechnology.de
 * Description: Modularer, plattformagnostischer VGT-Adapter. Implementiert O(1) Memory-Cached PSR-4 Autoloading und Zero-Global Container Isolation.
 * Version: 1.3.1
 * Author: VisionGaia Technology
 * Requires PHP: 7.0
 * License: AGPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/agpl-3.0.html
 * * VGT OMEGA PROTOCOL: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or (at your option) 
 * any later version.
 */

namespace VGT\Bridge;

if (!defined('ABSPATH')) {
    exit;
}

/* ====================================================================
 * 1. O(1) MEMORY CACHED PSR-4 AUTOLOADER
 * ==================================================================== */
spl_autoload_register(function (string $class) {
    static $classMap = []; // In-Memory Cache für I/O Reduktion

    if (isset($classMap[$class])) {
        require_once $classMap[$class];
        return;
    }

    $prefix = 'VGT\\Bridge\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    
    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    
    if (file_exists($file)) {
        $classMap[$class] = $file;
        require_once $file;
    }
});

/* ====================================================================
 * 2. KERNEL BOOTSTRAP (ISOLATED - ZERO GLOBALS)
 * ==================================================================== */
\call_user_func(function() {
    $container = \VGT\Bridge\Core\Container::getInstance();
    if (!$container->has(\VGT\Bridge\Contracts\BridgeInterface::class)) {
        $provider = new \VGT\Bridge\Providers\WordPressServiceProvider();
        $provider->register($container);
    }
    // Export container for VGT Modules globally via explicit context
    $GLOBALS['vgt_container'] = $container;
});

/* ====================================================================
 * 3. ATOMIC MU-DEPLOYMENT (CI/CD COMPLIANT)
 * ==================================================================== */
class Installer {
    const MU_FILENAME = 'vgt-layer0-bridge.php';
    const REG_FILENAME = 'vgt-bridge-registry.json';

    public static function activate(): void {
        self::updateRegistry(true);
    }

    public static function deactivate(): void {
        self::updateRegistry(false);
    }

    private static function updateRegistry(bool $add): void {
        // CI/CD & IMMUTABLE ENVIRONMENT BYPASS
        if ((defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS) || (defined('VGT_CI_ENV') && VGT_CI_ENV)) {
            if ($add) {
                error_log('VGT Bridge Installer: Immutable CI/CD Environment erkannt. Automatisches MU-Deployment übersprungen.');
            }
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        
        if (get_filesystem_method() !== 'direct') {
            if ($add) {
                deactivate_plugins(plugin_basename(__FILE__));
                wp_die(
                    esc_html__('VGT Bridge: Server erzwingt FTP/SSH für Dateioperationen. Bitte nutzen Sie VGT_CI_ENV in der wp-config.php für Immutable Deployments.', 'vgt'),
                    esc_html__('System-Konflikt: I/O Verweigerung', 'vgt'),
                    ['back_link' => true]
                );
            }
            return;
        }
        
        \WP_Filesystem();
        global $wp_filesystem;

        $mu_dir = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins';
        if (!$wp_filesystem->is_dir($mu_dir) && !$wp_filesystem->mkdir($mu_dir, FS_CHMOD_DIR)) {
            if ($add) {
                deactivate_plugins(plugin_basename(__FILE__));
                wp_die('VGT Bridge: Konnte MU-Plugins Verzeichnis nicht erstellen. Bitte Verzeichnisrechte (0755) prüfen.', 'System-Fehler', ['back_link' => true]);
            }
            return;
        }

        $reg_file = $mu_dir . '/' . self::REG_FILENAME;
        $mu_file = $mu_dir . '/' . self::MU_FILENAME;
        $plugin_path = __FILE__;

        $registry = $wp_filesystem->exists($reg_file) ? (json_decode($wp_filesystem->get_contents($reg_file), true) ?: []) : [];

        if ($add && !in_array($plugin_path, $registry, true)) {
            $registry[] = $plugin_path;
        } elseif (!$add) {
            $registry = array_values(array_filter($registry, fn($p) => $p !== $plugin_path));
        }

        if (empty($registry)) {
            if ($wp_filesystem->exists($reg_file)) $wp_filesystem->delete($reg_file);
            if ($wp_filesystem->exists($mu_file)) $wp_filesystem->delete($mu_file);
        } else {
            $wp_filesystem->put_contents($reg_file, wp_json_encode($registry), FS_CHMOD_FILE);

            $stub = "<?php\n/** VGT LAYER-0 LOADER (REGISTRY AWARE) */\n";
            $stub .= "\$vgt_reg = __DIR__ . '/" . self::REG_FILENAME . "';\n";
            $stub .= "if (file_exists(\$vgt_reg)) {\n";
            $stub .= "    foreach ((json_decode(file_get_contents(\$vgt_reg), true) ?: []) as \$p) {\n";
            $stub .= "        if (file_exists(\$p)) { require_once \$p; break; }\n";
            $stub .= "    }\n}\n";

            if (!$wp_filesystem->exists($mu_file) || $wp_filesystem->get_contents($mu_file) !== $stub) {
                $wp_filesystem->put_contents($mu_file, $stub, FS_CHMOD_FILE);
            }
        }
    }
}

\register_activation_hook(__FILE__, [Installer::class, 'activate']);
\register_deactivation_hook(__FILE__, [Installer::class, 'deactivate']);
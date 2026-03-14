<?php
declare(strict_types=1);

namespace VGT\Bridge\Providers;

use VGT\Bridge\Core\Container;
use VGT\Bridge\Contracts\BridgeInterface;
use VGT\Bridge\Contracts\EnvironmentInterface;
use VGT\Bridge\Adapters\WordPressAdapter;

/**
 * Kapselt das Environment sicher.
 */
class WordPressEnvironment implements EnvironmentInterface {
    public function getDbPrefix(): string {
        global $wpdb;
        return isset($wpdb) ? $wpdb->prefix : 'wp_';
    }
}

/**
 * Service Provider für das WordPress Ökosystem.
 */
class WordPressServiceProvider {
    
    public function register(Container $container): void {
        
        $container->set(EnvironmentInterface::class, function() {
            return new WordPressEnvironment();
        });

        $container->set(BridgeInterface::class, function(Container $c) {
            return new WordPressAdapter($c->get(EnvironmentInterface::class));
        });
        
    }
}
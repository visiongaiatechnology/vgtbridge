<?php
declare(strict_types=1);

namespace VGT\Bridge\Core;

/**
 * VGT Dependency Injection Container
 * STATUS: DIAMANT VGT SUPREME (Zero-Globals, Memory Optimized, Type-Safe, POI-Secured)
 */
final class Container {
    
    /** @var array<string, object> */
    private array $services = [];
    
    /** @var array<string, callable> */
    private array $factories = [];
    
    private static ?Container $instance = null;

    // Singleton-Enforcement: Konstruktion, Klonen blockiert
    private function __construct() {}
    private function __clone() {}
    
    /**
     * Verhindert PHP Object Injection (POI) Vektoren via unserialize()
     * @throws \BadMethodCallException
     */
    public function __wakeup(): void {
        throw new \BadMethodCallException('VGT Security: Deserialization of DI Container is strictly forbidden.');
    }

    public static function getInstance(): self {
        return self::$instance ??= new self();
    }

    public function set(string $id, callable|object $concrete): void {
        if (is_callable($concrete)) {
            $this->factories[$id] = $concrete;
        } else {
            $this->services[$id] = $concrete;
        }
    }

    /**
     * @template T
     * @param class-string<T>|string $id
     * @return T|object
     * @throws \OutOfBoundsException
     * @throws \UnexpectedValueException
     */
    public function get(string $id): object {
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        if (isset($this->factories[$id])) {
            $instance = ($this->factories[$id])($this);
            
            if (!is_object($instance)) {
                throw new \UnexpectedValueException(sprintf('VGT Logic Error: Factory for "%s" must return an object.', $id));
            }
            
            $this->services[$id] = $instance;
            unset($this->factories[$id]); // Memory-Freigabe nach Instanziierung
            return $this->services[$id];
        }

        throw new \OutOfBoundsException(sprintf('VGT Security: Service "%s" not found in Container.', $id));
    }

    public function has(string $id): bool {
        return isset($this->services[$id]) || isset($this->factories[$id]);
    }
}
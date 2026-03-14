<?php
declare(strict_types=1);

namespace VGT\Bridge\Contracts;

/**
 * STATUS: DIAMANT VGT SUPREME
 * Strikte Typisierung und deterministische Verträge (Zero-Mixed-Policy).
 */
interface BridgeInterface {
    
    public function addAction(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool;
    public function doAction(string $tag, mixed ...$args): void;
    public function addFilter(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool;
    
    /**
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    public function applyFilters(string $tag, mixed $value, mixed ...$args): mixed;
    
    public function getState(string $key, array|string|int|float|bool|null $default = false): array|string|int|float|bool|null;
    public function setState(string $key, array|string|int|float|bool $value, bool $autoload = true): bool;
    public function deleteState(string $key): bool;
    
    public function setCache(string $key, array|string|int|float|bool $value, int $exp = 0): bool;
    public function getCache(string $key): array|string|int|float|bool|null;

    public function addMenuMain(string $p_title, string $m_title, string $cap, string $slug, callable $cb, string $icon = '', ?int $pos = null): string|false;
    public function addMenuSub(string $p_slug, string $p_title, string $m_title, string $cap, string $slug, callable $cb): string|false;
    
    public function enqueueJs(string $handle, string $src, array $deps = [], bool|string $version = false, bool $inFooter = false): void;
    public function enqueueCss(string $handle, string $src, array $deps = [], bool|string $version = false, string $media = 'all'): void;

    public function sendSuccess(array|string|int|float|bool|null $data = null): never;
    public function sendError(array|string|int|float|bool|null $data = null): never;
    
    /** @return array{is_error: bool, error_message: string, code: int, body: string, headers: array<string, string>} */
    public function httpGet(string $url, array $args = []): array;
    /** @return array{is_error: bool, error_message: string, code: int, body: string, headers: array<string, string>} */
    public function httpPost(string $url, array $args = []): array;
    
    public function isAdmin(): bool;
    public function getDbPrefix(): string;
    public function createNonce(string $action): string;
    public function verifyNonce(string $nonce, string $action): int|false;

    public function queryString(string $key, array|string $default = ''): array|string;
    public function queryInt(string $key, int $default = 0): int;
    public function bodyString(string $key, array|string $default = ''): array|string;
    public function bodyInt(string $key, int $default = 0): int;
}
<?php
declare(strict_types=1);

namespace VGT\Bridge\Adapters;

use VGT\Bridge\Contracts\BridgeInterface;
use VGT\Bridge\Contracts\EnvironmentInterface;

final class WordPressAdapter implements BridgeInterface {
    
    /** Constructor Property Promotion (PHP 8.0+) */
    public function __construct(
        private EnvironmentInterface $env
    ) {}

    public function addAction(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool { 
        return \add_action($tag, $callback, $priority, $acceptedArgs); 
    }
    public function doAction(string $tag, mixed ...$args): void { \do_action($tag, ...$args); }
    
    public function addFilter(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool { 
        return \add_filter($tag, $callback, $priority, $acceptedArgs); 
    }
    public function applyFilters(string $tag, mixed $value, mixed ...$args): mixed { 
        return \apply_filters($tag, $value, ...$args); 
    }

    public function getState(string $key, array|string|int|float|bool|null $default = false): array|string|int|float|bool|null { 
        return \get_option($key, $default); 
    }
    public function setState(string $key, array|string|int|float|bool $value, bool $autoload = true): bool { 
        return \update_option($key, $value, $autoload ? 'yes' : 'no'); 
    }
    public function deleteState(string $key): bool { return \delete_option($key); }
    
    public function setCache(string $key, array|string|int|float|bool $value, int $exp = 0): bool { 
        return \set_transient($key, $value, $exp); 
    }
    public function getCache(string $key): array|string|int|float|bool|null { 
        return \get_transient($key); 
    }

    public function addMenuMain(string $p_title, string $m_title, string $cap, string $slug, callable $cb, string $icon = '', ?int $pos = null): string|false { 
        return \add_menu_page($p_title, $m_title, $cap, $slug, $cb, $icon, $pos); 
    }
    public function addMenuSub(string $p_slug, string $p_title, string $m_title, string $cap, string $slug, callable $cb): string|false { 
        return \add_submenu_page($p_slug, $p_title, $m_title, $cap, $slug, $cb); 
    }
    
    public function enqueueJs(string $hnd, string $src, array $deps = [], bool|string $version = false, bool $inFooter = false): void { \wp_enqueue_script($hnd, $src, $deps, $version, $inFooter); }
    public function enqueueCss(string $hnd, string $src, array $deps = [], bool|string $version = false, string $media = 'all'): void { \wp_enqueue_style($hnd, $src, $deps, $version, $media); }

    public function sendSuccess(array|string|int|float|bool|null $data = null): never { 
        \wp_send_json_success($data); 
        exit; 
    }
    public function sendError(array|string|int|float|bool|null $data = null): never { 
        \wp_send_json_error($data); 
        exit; 
    }

    private function mapHttpResponse(array|\WP_Error $res): array {
        if (\is_wp_error($res)) {
            return ['is_error' => true, 'error_message' => $res->get_error_message(), 'code' => 500, 'body' => '', 'headers' => []];
        }
        $raw_headers = \wp_remote_retrieve_headers($res);
        $headers = [];
        foreach ($raw_headers as $name => $value) {
            $headers[$name] = is_array($value) ? implode(', ', $value) : $value;
        }
        return [
            'is_error' => false, 'error_message' => '',
            'code' => (int) \wp_remote_retrieve_response_code($res),
            'body' => \wp_remote_retrieve_body($res),
            'headers' => $headers
        ];
    }

    public function httpGet(string $url, array $args = []): array { return $this->mapHttpResponse(\wp_remote_get($url, $args)); }
    public function httpPost(string $url, array $args = []): array { return $this->mapHttpResponse(\wp_remote_post($url, $args)); }

    public function isAdmin(): bool { return \is_admin(); }
    public function getDbPrefix(): string { return $this->env->getDbPrefix(); }
    
    public function createNonce(string $action): string { return \wp_create_nonce($action); }
    public function verifyNonce(string $nonce, string $action): int|false { return \wp_verify_nonce($nonce, $action); }

    private function fetchUnslashed(string $key, string $method): mixed {
        $pool = ($method === 'POST') ? $_POST : $_GET;
        if (!isset($pool[$key])) return null;
        return function_exists('wp_unslash') ? \wp_unslash($pool[$key]) : \stripslashes_deep($pool[$key]);
    }

    private function stripControlChars(string $string): string {
        static $dict = null;
        if ($dict === null) {
            $dict = [];
            for ($i = 0; $i <= 31; $i++) if (!in_array($i, [9, 10, 13], true)) $dict[chr($i)] = '';
            $dict[chr(127)] = '';
        }
        return strtr($string, $dict);
    }

    private function sanitizeScalar(mixed $data): string {
        if ($data === null || is_resource($data)) return '';
        
        if (is_object($data)) {
            if (!method_exists($data, '__toString')) return ''; 
        }
        
        $v = $this->stripControlChars((string)$data);
        if (function_exists('sanitize_text_field')) return \sanitize_text_field($v);
        
        $v = \strip_tags($v);
        return trim(\preg_replace('/[\r\n\t ]+/', ' ', $v));
    }

    private function sanitizeRecursive(mixed $data, int $depth = 0): array|string {
        if ($depth > 50) throw new \RuntimeException('VGT Security Constraint: Maximum array nesting depth exceeded.');

        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $k => $v) {
                $safe_key = $this->sanitizeScalar($k);
                if ($safe_key === '') continue; 
                $sanitized[$safe_key] = $this->sanitizeRecursive($v, $depth + 1);
            }
            return $sanitized;
        }
        
        return $this->sanitizeScalar($data);
    }

    public function queryString(string $key, array|string $default = ''): array|string {
        $val = $this->fetchUnslashed($key, 'GET');
        return $val !== null ? $this->sanitizeRecursive($val) : $default;
    }
    public function bodyString(string $key, array|string $default = ''): array|string {
        $val = $this->fetchUnslashed($key, 'POST');
        return $val !== null ? $this->sanitizeRecursive($val) : $default;
    }
    public function queryInt(string $key, int $default = 0): int {
        $val = $this->fetchUnslashed($key, 'GET');
        return $val !== null ? absint($val) : $default;
    }
    public function bodyInt(string $key, int $default = 0): int {
        $val = $this->fetchUnslashed($key, 'POST');
        return $val !== null ? absint($val) : $default;
    }
}
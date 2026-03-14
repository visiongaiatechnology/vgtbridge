# 🌉 VGT Universal Bridge — Framework-Agnostic Adapter Layer

[![License](https://img.shields.io/badge/License-AGPLv3-green?style=for-the-badge)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.0+-blue?style=for-the-badge&logo=php)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.0+-21759B?style=for-the-badge&logo=wordpress)](https://wordpress.org)
[![PSR-4](https://img.shields.io/badge/PSR--4-Autoloading-orange?style=for-the-badge)](https://www.php-fig.org/psr/psr-4/)
[![Status](https://img.shields.io/badge/Status-DIAMANT-purple?style=for-the-badge)](#)
[![VGT](https://img.shields.io/badge/VGT-VisionGaia_Technology-red?style=for-the-badge)](https://visiongaiatechnology.de)
[![Donate](https://img.shields.io/badge/Donate-PayPal-00457C?style=for-the-badge&logo=paypal)](https://www.paypal.com/paypalme/dergoldenelotus)

> *"Your plugin should not know what framework it runs on."*

**VGT Universal Bridge** is the open-source adapter layer that decouples the entire VGT ecosystem from WordPress — and any other PHP framework. It provides a clean, typed, PSR-4 contract layer between your application logic and the underlying platform, making any VGT plugin truly framework-agnostic.

Built to the **DIAMANT VGT SUPREME** standard. The same architectural foundation that powers the VGT Sentinel Security Suite.

---

## 🚨 The Problem with Standard WordPress Plugins

Every standard WordPress plugin is hardwired to WordPress. `add_action()`, `get_option()`, `wp_remote_get()` — scattered throughout the codebase. The result: untestable, unportable, and permanently GPL-locked business logic.

| Standard WordPress Plugin | VGT Universal Bridge |
|---|---|
| ❌ Hardwired to WordPress functions | ✅ Contract-based adapter pattern |
| ❌ GPL infects all business logic | ✅ Core logic is fully isolated |
| ❌ Zero-testability (globals everywhere) | ✅ 100% mockable via interfaces |
| ❌ Framework migration = full rewrite | ✅ Swap adapter, keep logic |
| ❌ `global $wpdb` pollution | ✅ Zero-globals IoC Container |

---

## ⚙️ Architecture — The Triad

The Bridge is built on three isolated layers that communicate exclusively through contracts.

```
┌─────────────────────────────────────────┐
│           YOUR PLUGIN / SENTINEL        │  ← Knows only BridgeInterface
├─────────────────────────────────────────┤
│         VGT UNIVERSAL BRIDGE            │
│  ┌─────────────┐   ┌─────────────────┐  │
│  │  Contracts  │   │  IoC Container  │  │  ← Zero-Globals, Singleton
│  │  (PSR-4)    │   │  (Lazy-Loading) │  │
│  └──────┬──────┘   └────────┬────────┘  │
│         │                   │           │
│  ┌──────▼───────────────────▼────────┐  │
│  │           Adapters                │  │
│  │  WordPressAdapter  LaravelAdapter │  │  ← Platform-specific impl.
│  └───────────────────────────────────┘  │
├─────────────────────────────────────────┤
│         WORDPRESS / LARAVEL / PHP       │  ← Bridge knows this. You don't.
└─────────────────────────────────────────┘
```

### Layer 0 — MU-Plugin Auto-Deployment
On activation, the Bridge deploys itself as a **Must-Use plugin** via an atomic registry system. It loads before any regular plugin — guaranteed.

### Contracts — The Single Source of Truth
`BridgeInterface` and `EnvironmentInterface` define every platform interaction. Your code calls the interface. Never the framework.

### IoC Container — Zero-Globals Isolation
A lightweight Dependency Injection Container with factory-to-singleton lazy-loading and automatic garbage collection after instantiation.

### Adapters — Platform Intelligence
Each adapter implements `BridgeInterface` and translates VGT calls to native framework functions. WordPress today. Laravel tomorrow.

---

## 🛡️ Security Layer — Built-In

The Bridge is not just an adapter. It is a security boundary.

### Typesafe Desanitization Engine
Every inbound request value passes through a recursive sanitization pipeline before your code ever sees it.

```php
// Defense in Depth against payload attacks:
$title = $bridge->queryString('title'); // sanitized, unslashed, control-char stripped
$id    = $bridge->bodyInt('post_id');   // cast to int, XSS impossible
```

- **Control Character Stripping** — Removes all non-printable ASCII (0–31, 127) except tab/newline
- **Recursive Depth Limit** — Array nesting capped at 50 levels. Stack overflow attacks blocked at the gate.
- **Object Drop Protection** — Non-stringable objects are silently dropped, preventing Fatal Errors from crafted payloads
- **Type-Enforced Integers** — `queryInt()` / `bodyInt()` use `absint()`. No negative injection surface.

### Nonce System
```php
$nonce = $bridge->createNonce('vgt-action');
$valid = $bridge->verifyNonce($_POST['_nonce'], 'vgt-action');
```

---

## 📦 System Specs

```
ARCHITECTURE      PSR-4 STRICT / IoC CONTAINER
AUTOLOADER        O(1) MEMORY CACHED (Zero I/O on repeat loads)
MU_DEPLOYMENT     ATOMIC REGISTRY (Multi-instance collision-safe)
SECURITY_LAYER    RECURSIVE TYPESAFE DESANITIZATION
CONTAINER         LAZY-LOAD SINGLETON / GC OPTIMIZED
GLOBALS           ZERO (Complete isolation)
LICENSE           GNU AGPLv3
```

---

## 🚀 Installation

### Requirements
- WordPress 6.0+
- PHP 8.0+
- Direct filesystem write access (for MU-deployment)

### Setup

1. Download and extract to `/wp-content/plugins/vgt-universal-bridge/`
2. Activate via **Plugins → Installed Plugins**
3. The Bridge auto-deploys to `/mu-plugins/` — it is now active on Layer 0

### Usage in Your Plugin

```php
use VGT\Bridge\Core\Container;
use VGT\Bridge\Contracts\BridgeInterface;

// Resolve the bridge from the container
$bridge = Container::getInstance()->get(BridgeInterface::class);

// Hook into WordPress (or any future framework) without knowing it
$bridge->addAction('init', function() {
    // your logic here
});

// Read request data — always sanitized
$search = $bridge->queryString('s');
$page   = $bridge->queryInt('paged', 1);

// Store state — framework-agnostic
$bridge->setState('vgt_sentinel_active', true);

// HTTP requests — normalized response, no WP_Error handling needed
$response = $bridge->httpGet('https://api.example.com/data');
if (!$response['is_error']) {
    $data = json_decode($response['body'], true);
}
```

---

## 📁 File Structure

```
vgt-universal-bridge/
├── src/
│   ├── Adapters/
│   │   └── WordPressAdapter.php     ← WordPress implementation
│   ├── Contracts/
│   │   ├── BridgeInterface.php      ← Core contract (11 method groups)
│   │   └── EnvironmentInterface.php ← Environment isolation contract
│   ├── Core/
│   │   └── Container.php            ← IoC DI Container
│   └── Providers/
│       └── WordPressServiceProvider.php ← Service wiring
└── vgt-universal-bridge.php         ← Bootstrap + MU Installer
```

---

## 🗺️ Roadmap

| Phase | Adapter | Status |
|---|---|---|
| ✅ Phase 1 | WordPress Adapter | **Stable** |
| 🔄 Phase 2 | Laravel Adapter | In Development |
| 📋 Phase 3 | Symfony Adapter | Planned |
| 📋 Phase 4 | Bare PHP Adapter | Planned |

Once all adapters are complete, any VGT plugin will run on any PHP framework without a single line change in business logic.

---

## 🤝 Contributing

Pull requests are welcome. For major changes, please open an issue first.

This project is licensed under **GNU AGPLv3** — open source, transparent, sovereign. The Bridge is open because trust must be verifiable. What it loads is our business.

---

## ☕ Support the Project

VGT Universal Bridge is free. If it saves you hours of architecture work:

[![Donate via PayPal](https://img.shields.io/badge/Donate-PayPal-00457C?style=for-the-badge&logo=paypal)](https://www.paypal.com/paypalme/dergoldenelotus)

---

## 🏢 Built by VisionGaia Technology

[![VGT](https://img.shields.io/badge/VGT-VisionGaia_Technology-red?style=for-the-badge)](https://visiongaiatechnology.de)

VisionGaia Technology builds enterprise-grade security and AI tooling — engineered to the DIAMANT VGT SUPREME standard.

> *"The Bridge is open because trust must be verifiable. What it loads is our business."*

---

*Version 1.3.1 (DIAMANT SUPREME) — VGT Universal Bridge // IoC Architecture*

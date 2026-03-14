<?php
declare(strict_types=1);

namespace VGT\Bridge\Contracts;

/**
 * Isoliert alle globalen Status-Abfragen und System-Variablen 
 * von der Kernlogik. Ermöglicht 100% Mocking in Unit Tests.
 */
interface EnvironmentInterface {
    
    /**
     * Ruft das Datenbank-Präfix des aktuellen Systems ab.
     * @return string Das Tabellen-Präfix (z.B. 'wp_').
     */
    public function getDbPrefix(): string;
}
<?php
/**
 * Smart Clinical Decision System - Configuration
 *
 * Central configuration file containing all constants for database connections,
 * Fuseki SPARQL endpoints, ontology file paths, and namespace definitions.
 *
 * @package SmartCDS
 * @version 1.0.0
 */

// ─── Apache Jena Fuseki Endpoints ────────────────────────────────────────────

/** @var string SPARQL query endpoint (HTTP GET) */
define('FUSEKI_ENDPOINT', 'http://localhost:3030/cds/sparql');

/** @var string SPARQL update endpoint (HTTP POST) */
define('FUSEKI_UPDATE', 'http://localhost:3030/cds/update');

/** @var string Graph Store Protocol endpoint for uploading data */
define('FUSEKI_DATA', 'http://localhost:3030/cds/data');

/** @var string Base URL of the Fuseki dataset */
define('FUSEKI_BASE', 'http://localhost:3030/cds');

// ─── MySQL Database Connection (optional, for session/audit logs) ────────────

/** @var string Database host */
define('DB_HOST', 'localhost');

/** @var string Database name */
define('DB_NAME', 'smart_cds');

/** @var string Database username */
define('DB_USER', 'root');

/** @var string Database password */
define('DB_PASS', '');

// ─── Ontology File Paths ─────────────────────────────────────────────────────

/** @var string Absolute path to the OWL ontology file */
define('OWL_FILE_PATH', __DIR__ . '/../ontology/clinical-decision.owl');

/** @var string Absolute path to the Turtle patient data file */
define('TTL_FILE_PATH', __DIR__ . '/../ontology/patient-data.ttl');

// ─── CDS Ontology Namespace ─────────────────────────────────────────────────

/** @var string The primary namespace URI for the Clinical Decision System ontology */
define('CDS_NAMESPACE', 'http://www.semanticweb.org/clinical-decision-system#');

// ─── Application Settings ────────────────────────────────────────────────────

/** @var string Application title */
define('APP_TITLE', 'Smart Clinical Decision System');

/** @var string Application version */
define('APP_VERSION', '1.0.0');

/** @var bool Enable debug mode (set to false in production) */
define('DEBUG_MODE', true);

// ─── Error Reporting ─────────────────────────────────────────────────────────

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ─── Timezone ────────────────────────────────────────────────────────────────

date_default_timezone_set('Asia/Manila');

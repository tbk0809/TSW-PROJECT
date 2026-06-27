<?php
/**
 * Smart Clinical Decision System - REST API
 *
 * Central API endpoint that routes incoming requests to the appropriate
 * controller methods and returns JSON responses. Supports both GET and
 * POST methods with CORS headers for cross-origin access.
 *
 * API Actions:
 * - GET  ?action=patients          → List all patients
 * - GET  ?action=patient&id={id}   → Single patient detail
 * - GET  ?action=search&name={n}   → Search patients by name
 * - GET  ?action=diagnose&symptoms=a,b → Diagnosis suggestions
 * - GET  ?action=risk&level={lvl}  → List patients by risk level
 * - GET  ?action=inference&patientId={id} → Inferred facts
 * - GET  ?action=contraindications&patientId={id} → Drug interactions
 * - POST ?action=sparql (body: query=...) → Execute custom SPARQL
 * - GET  ?action=dashboard         → Dashboard statistics
 * - GET  ?action=classify          → Run OWL classification
 * - GET  ?action=preloaded_queries → Get predefined SPARQL queries
 * - POST ?action=load_ontology     → Reload ontology into Fuseki
 *
 * @package SmartCDS\API
 * @version 1.0.0
 */

// ─── Bootstrap ───────────────────────────────────────────────────────────────

require_once __DIR__ . '/../config/config.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

require_once __DIR__ . '/../services/SparqlService.php';
require_once __DIR__ . '/../services/OntologyLoader.php';
require_once __DIR__ . '/../services/InferenceService.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Disease.php';
require_once __DIR__ . '/../models/Ontology.php';
require_once __DIR__ . '/../controllers/PatientController.php';
require_once __DIR__ . '/../controllers/DiagnosisController.php';
require_once __DIR__ . '/../controllers/InferenceController.php';
require_once __DIR__ . '/../controllers/SparqlController.php';

// ─── CORS Headers ────────────────────────────────────────────────────────────

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ─── Route the request ──────────────────────────────────────────────────────

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $response = match ($action) {
        'patients'          => handlePatients(),
        'patient'           => handlePatientDetail(),
        'search'            => handleSearch(),
        'diagnose'          => handleDiagnose(),
        'risk'              => handleRiskPatients(),
        'inference'         => handleInference(),
        'contraindications' => handleContraindications(),
        'sparql'            => handleSparql(),
        'dashboard'         => handleDashboard(),
        'classify'          => handleClassify(),
        'preloaded_queries' => handlePreloadedQueries(),
        'load_ontology'     => handleLoadOntology(),
        default             => [
            'success' => false,
            'error'   => "Unknown API action: '{$action}'.",
            'availableActions' => [
                'patients', 'patient', 'search', 'diagnose', 'risk',
                'inference', 'contraindications', 'sparql', 'dashboard',
                'classify', 'preloaded_queries', 'load_ontology',
            ],
        ],
    };

    if (isset($response['success']) && $response['success'] === false) {
        http_response_code(400); // Send an HTTP error so Javascript's fetch() detects it
    }
    
    echo json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success'   => false,
        'error'     => 'Internal server error: ' . $e->getMessage(),
        'trace'     => DEBUG_MODE ? $e->getTraceAsString() : null,
        'timestamp' => date('c'),
    ], JSON_PRETTY_PRINT);
}

exit;

// ─── Handler Functions ───────────────────────────────────────────────────────

/**
 * Handle GET ?action=patients
 *
 * Lists all patients with basic information.
 *
 * @return array JSON-serializable response
 */
function handlePatients(): array
{
    $controller = new \Controllers\PatientController();
    $patients = $controller->index();

    return [
        'success'      => true,
        'data'         => $patients,
        'totalResults' => count($patients),
        'timestamp'    => date('c'),
    ];
}

/**
 * Handle GET ?action=patient&id={id}
 *
 * Retrieves full details for a specific patient.
 *
 * @return array JSON-serializable response
 */
function handlePatientDetail(): array
{
    $id = $_GET['id'] ?? '';

    if (empty($id)) {
        return [
            'success' => false,
            'error'   => "Missing required parameter: 'id'.",
        ];
    }

    $controller = new \Controllers\PatientController();
    $patient = $controller->show($id);

    if ($patient === null) {
        return [
            'success' => false,
            'error'   => "Patient not found with ID: {$id}",
        ];
    }

    return [
        'success'   => true,
        'data'      => $patient,
        'timestamp' => date('c'),
    ];
}

/**
 * Handle GET ?action=search&name={name}
 *
 * Searches patients by name (case-insensitive regex).
 *
 * @return array JSON-serializable response
 */
function handleSearch(): array
{
    $name = $_GET['name'] ?? '';

    if (empty($name)) {
        return [
            'success' => false,
            'error'   => "Missing required parameter: 'name'.",
        ];
    }

    $controller = new \Controllers\PatientController();
    $patients = $controller->search($name);

    return [
        'success'      => true,
        'data'         => $patients,
        'searchTerm'   => $name,
        'totalResults' => count($patients),
        'timestamp'    => date('c'),
    ];
}

/**
 * Handle GET ?action=diagnose&symptoms=fever,cough,...
 *
 * Performs symptom-based diagnosis using the ontology.
 *
 * @return array JSON-serializable response
 */
function handleDiagnose(): array
{
    $symptomsParam = $_GET['symptoms'] ?? '';

    if (empty($symptomsParam)) {
        return [
            'success' => false,
            'error'   => "Missing required parameter: 'symptoms'. Provide comma-separated symptom names.",
        ];
    }

    $symptoms = array_map('trim', explode(',', $symptomsParam));
    $symptoms = array_filter($symptoms, fn($s) => !empty($s));

    if (empty($symptoms)) {
        return [
            'success' => false,
            'error'   => 'No valid symptoms provided after parsing.',
        ];
    }

    $controller = new \Controllers\DiagnosisController();
    $result = $controller->diagnose($symptoms);

    // If the diagnose method already returns a structured response, use it
    if (isset($result['success'])) {
        $result['timestamp'] = date('c');
        return $result;
    }

    return [
        'success'   => true,
        'data'      => $result,
        'timestamp' => date('c'),
    ];
}

/**
 * Handle GET ?action=risk&level={level}
 *
 * Lists patients filtered by risk level.
 *
 * @return array JSON-serializable response
 */
function handleRiskPatients(): array
{
    $level = $_GET['level'] ?? 'HighRisk';

    $controller = new \Controllers\PatientController();
    $patients = $controller->getRiskPatients($level);

    return [
        'success'      => true,
        'data'         => $patients,
        'riskLevel'    => $level,
        'totalResults' => count($patients),
        'timestamp'    => date('c'),
    ];
}

/**
 * Handle GET ?action=inference&patientId={id}
 *
 * Retrieves inferred facts for a specific patient.
 *
 * @return array JSON-serializable response
 */
function handleInference(): array
{
    $patientId = $_GET['patientId'] ?? '';

    if (empty($patientId)) {
        return [
            'success' => false,
            'error'   => "Missing required parameter: 'patientId'.",
        ];
    }

    $controller = new \Controllers\InferenceController();
    $result = $controller->getInferredFacts($patientId);

    $result['timestamp'] = date('c');
    return $result;
}

/**
 * Handle GET ?action=contraindications&patientId={id}
 *
 * Checks drug interactions and contraindications for a patient.
 *
 * @return array JSON-serializable response
 */
function handleContraindications(): array
{
    $patientId = $_GET['patientId'] ?? '';

    if (empty($patientId)) {
        return [
            'success' => false,
            'error'   => "Missing required parameter: 'patientId'.",
        ];
    }

    $inferenceService = new \Services\InferenceService();
    $result = $inferenceService->checkDrugInteractions($patientId);

    $result['timestamp'] = date('c');
    return $result;
}

/**
 * Handle POST ?action=sparql (body: query=...)
 *
 * Executes a custom SPARQL query submitted via POST body.
 *
 * @return array JSON-serializable response
 */
function handleSparql(): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return [
            'success' => false,
            'error'   => 'SPARQL execution requires POST method. Send the query in the request body as "query" parameter.',
        ];
    }

    // Try to get query from POST body
    $query = $_POST['query'] ?? '';

    // If not in POST, try raw body (for JSON content type or plain text)
    if (empty($query)) {
        $rawBody = file_get_contents('php://input');
        $jsonBody = json_decode($rawBody, true);
        if (isset($jsonBody['query'])) {
            $query = $jsonBody['query'];
        } elseif (!empty(trim($rawBody))) {
            // Frontend sends the query as plain text body
            $query = trim($rawBody);
        }
    }

    if (empty($query)) {
        return [
            'success' => false,
            'error'   => "Missing required parameter: 'query'. Send SPARQL query as POST body parameter.",
        ];
    }

    $controller = new \Controllers\SparqlController();
    $result = $controller->executeQuery($query);

    $result['timestamp'] = date('c');
    return $result;
}

/**
 * Handle GET ?action=dashboard
 *
 * Returns dashboard statistics (patient count, risk count, etc.).
 *
 * @return array JSON-serializable response
 */
function handleDashboard(): array
{
    $controller = new \Controllers\PatientController();
    $stats = $controller->getDashboardStats();

    return [
        'success'   => true,
        'data'      => $stats,
        'timestamp' => date('c'),
    ];
}

/**
 * Handle GET ?action=classify[&patientId={id}]
 *
 * Runs OWL classification. When patientId is supplied only that patient's
 * inferred facts are returned; otherwise all patients are classified.
 *
 * @return array JSON-serializable response
 */
function handleClassify(): array
{
    $patientId = $_GET['patientId'] ?? '';

    $controller = new \Controllers\InferenceController();
    $result = $controller->runClassification($patientId ?: null);

    $result['timestamp'] = date('c');
    return $result;
}

/**
 * Handle GET ?action=preloaded_queries
 *
 * Returns the list of predefined SPARQL queries.
 *
 * @return array JSON-serializable response
 */
function handlePreloadedQueries(): array
{
    $controller = new \Controllers\SparqlController();
    $queries = $controller->getPreloadedQueries();

    return [
        'success'    => true,
        'data'       => $queries,
        'totalCount' => count($queries),
        'timestamp'  => date('c'),
    ];
}

/**
 * Handle POST ?action=load_ontology
 *
 * Reloads the OWL ontology and TTL data files into Fuseki.
 *
 * @return array JSON-serializable response
 */
function handleLoadOntology(): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        return [
            'success' => false,
            'error'   => 'This action supports POST (or GET for convenience).',
        ];
    }

    $loader = new \Services\OntologyLoader();

    // Check if we should do a full reload or just load
    $fullReload = ($_GET['reload'] ?? $_POST['reload'] ?? 'true') === 'true';

    if ($fullReload) {
        $result = $loader->reloadAll();
    } else {
        // Just load without clearing
        $owlResult = $loader->loadOwlFile();
        $ttlResult = $loader->loadTtlFile();

        $result = [
            'success' => $owlResult['success'] && $ttlResult['success'],
            'owl'     => $owlResult,
            'ttl'     => $ttlResult,
        ];
    }

    $result['timestamp'] = date('c');
    return $result;
}

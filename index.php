<?php
/**
 * Smart Clinical Decision System - Front Controller
 *
 * Main entry point that handles routing based on the 'page' GET parameter.
 * Routes requests to appropriate views or the API endpoint.
 *
 * @package SmartCDS
 * @version 1.0.0
 */

// Load configuration
require_once __DIR__ . '/config/config.php';

// Load Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Load service classes
require_once __DIR__ . '/services/SparqlService.php';
require_once __DIR__ . '/services/OntologyLoader.php';
require_once __DIR__ . '/services/InferenceService.php';

// Load model classes
require_once __DIR__ . '/models/Patient.php';
require_once __DIR__ . '/models/Disease.php';
require_once __DIR__ . '/models/Ontology.php';

// Load controller classes
require_once __DIR__ . '/controllers/PatientController.php';
require_once __DIR__ . '/controllers/DiagnosisController.php';
require_once __DIR__ . '/controllers/InferenceController.php';
require_once __DIR__ . '/controllers/SparqlController.php';

// ─── Routing ─────────────────────────────────────────────────────────────────

$page = $_GET['page'] ?? 'dashboard';

// Sanitize the page parameter
$page = preg_replace('/[^a-zA-Z0-9_\-]/', '', $page);

// Check if this is an API request
if ($page === 'api' || str_starts_with($page, 'api')) {
    require_once __DIR__ . '/api/api.php';
    exit;
}

// ─── Define valid pages and their view files ─────────────────────────────────

$validPages = [
    'dashboard'      => 'views/dashboard.php',
    'patients'       => 'views/patients.php',
    'patient_detail' => 'views/patient_detail.php',
    'diagnosis'      => 'views/diagnosis.php',
    'inference'      => 'views/inference.php',
    'sparql'         => 'views/sparql.php',
];

// ─── Prepare page data based on the route ────────────────────────────────────

$pageData = [];
$pageTitle = APP_TITLE;
$currentPage = $page;

try {
    switch ($page) {
        case 'dashboard':
            $patientController = new \Controllers\PatientController();
            $pageData = $patientController->getDashboardStats();
            $pageTitle = 'Dashboard - ' . APP_TITLE;
            break;

        case 'patients':
            $patientController = new \Controllers\PatientController();
            $searchName = $_GET['search'] ?? '';
            if (!empty($searchName)) {
                $pageData['patients'] = $patientController->search($searchName);
                $pageData['searchTerm'] = $searchName;
            } else {
                $pageData['patients'] = $patientController->index();
            }
            $pageTitle = 'Patients - ' . APP_TITLE;
            break;

        case 'patient_detail':
            $patientController = new \Controllers\PatientController();
            $patientID = $_GET['id'] ?? '';
            if (!empty($patientID)) {
                $pageData['patient'] = $patientController->show($patientID);

                // Also fetch inferred facts
                $inferenceController = new \Controllers\InferenceController();
                $pageData['inferredFacts'] = $inferenceController->getInferredFacts($patientID);
            }
            $pageTitle = 'Patient Detail - ' . APP_TITLE;
            break;

        case 'diagnosis':
            $diagnosisController = new \Controllers\DiagnosisController();
            $symptoms = $_GET['symptoms'] ?? '';
            if (!empty($symptoms)) {
                $symptomArray = array_map('trim', explode(',', $symptoms));
                $pageData['diagnosisResults'] = $diagnosisController->diagnose($symptomArray);
            }
            $pageTitle = 'Diagnosis - ' . APP_TITLE;
            break;

        case 'inference':
            $inferenceController = new \Controllers\InferenceController();
            $patientID = $_GET['patientId'] ?? '';
            if (!empty($patientID)) {
                $pageData['classification'] = $inferenceController->getInferredFacts($patientID);
                $pageData['explanation'] = $inferenceController->explainInference($patientID);
            }
            $pageTitle = 'Inference Engine - ' . APP_TITLE;
            break;

        case 'sparql':
            $sparqlController = new \Controllers\SparqlController();
            $pageData['preloadedQueries'] = $sparqlController->getPreloadedQueries();
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['query'])) {
                $pageData['queryResults'] = $sparqlController->executeQuery($_POST['query']);
                $pageData['executedQuery'] = $_POST['query'];
            }
            $pageTitle = 'SPARQL Console - ' . APP_TITLE;
            break;

        default:
            $page = 'dashboard';
            $patientController = new \Controllers\PatientController();
            $pageData = $patientController->getDashboardStats();
            $pageTitle = 'Dashboard - ' . APP_TITLE;
            break;
    }
} catch (\Exception $e) {
    $pageData['error'] = $e->getMessage();
}

// ─── Determine the view file ─────────────────────────────────────────────────

$viewFile = $validPages[$page] ?? $validPages['dashboard'];

// ─── Render the page ─────────────────────────────────────────────────────────

// Check if layout exists, otherwise render view directly
$layoutFile = __DIR__ . '/views/layout.php';
if (file_exists($layoutFile)) {
    // Layout will include the view file
    $contentView = __DIR__ . '/' . $viewFile;
    require_once $layoutFile;
} else {
    // Render view directly if no layout file
    $viewPath = __DIR__ . '/' . $viewFile;
    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        // Fallback: output JSON data if no view files exist yet
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'page'    => $page,
            'title'   => $pageTitle,
            'data'    => $pageData,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

<?php
/**
 * Smart Clinical Decision System - Ontology Loader
 *
 * Service for managing the loading and reloading of OWL ontology
 * and Turtle data files into the Apache Jena Fuseki triple store.
 *
 * @package SmartCDS\Services
 * @version 1.0.0
 */

namespace Services;

class OntologyLoader
{
    /** @var SparqlService Instance of the SPARQL service for communicating with Fuseki */
    private SparqlService $sparqlService;

    /** @var string Path to the OWL ontology file */
    private string $owlFilePath;

    /** @var string Path to the Turtle data file */
    private string $ttlFilePath;

    /**
     * Construct the OntologyLoader.
     *
     * @param SparqlService|null $sparqlService Optional SparqlService instance (creates new one if null)
     * @param string|null        $owlFilePath   Override path to the OWL file
     * @param string|null        $ttlFilePath   Override path to the TTL file
     */
    public function __construct(
        ?SparqlService $sparqlService = null,
        ?string $owlFilePath = null,
        ?string $ttlFilePath = null
    ) {
        $this->sparqlService = $sparqlService ?? new SparqlService();
        $this->owlFilePath   = $owlFilePath   ?? OWL_FILE_PATH;
        $this->ttlFilePath   = $ttlFilePath   ?? TTL_FILE_PATH;
    }

    /**
     * Load the OWL ontology file into Fuseki.
     *
     * Reads the configured OWL file and uploads it to the Fuseki
     * data endpoint as RDF/XML content.
     *
     * @return array Structured response:
     *               - On success: ['success' => true, 'message' => '...']
     *               - On failure: ['success' => false, 'error' => '...']
     */
    public function loadOwlFile(): array
    {
        if (!file_exists($this->owlFilePath)) {
            return [
                'success' => false,
                'error'   => 'OWL ontology file not found at: ' . $this->owlFilePath,
            ];
        }

        $result = $this->sparqlService->loadOntology($this->owlFilePath);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'OWL ontology file loaded successfully.',
                'file'    => basename($this->owlFilePath),
            ];
        }

        return $result;
    }

    /**
     * Load the Turtle data file into Fuseki.
     *
     * Reads the configured TTL file and uploads it to the Fuseki
     * data endpoint as Turtle content.
     *
     * @return array Structured response:
     *               - On success: ['success' => true, 'message' => '...']
     *               - On failure: ['success' => false, 'error' => '...']
     */
    public function loadTtlFile(): array
    {
        if (!file_exists($this->ttlFilePath)) {
            return [
                'success' => false,
                'error'   => 'TTL data file not found at: ' . $this->ttlFilePath,
            ];
        }

        $result = $this->sparqlService->loadTurtleData($this->ttlFilePath);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Turtle data file loaded successfully.',
                'file'    => basename($this->ttlFilePath),
            ];
        }

        return $result;
    }

    /**
     * Clear the Fuseki dataset and reload both OWL and TTL files.
     *
     * Performs the following steps:
     * 1. Drops all data from the Fuseki dataset
     * 2. Uploads the OWL ontology file
     * 3. Uploads the Turtle patient data file
     *
     * @return array Structured response with results for each step:
     *               ['success' => true/false, 'steps' => [...], 'message' => '...']
     */
    public function reloadAll(): array
    {
        $steps = [];

        // Step 1: Clear existing data
        $clearResult = $this->sparqlService->clearDataset();
        $steps[] = [
            'step'    => 'Clear Dataset',
            'success' => $clearResult['success'],
            'detail'  => $clearResult['success']
                ? 'All existing triples removed.'
                : ($clearResult['error'] ?? 'Unknown error during clear.'),
        ];

        if (!$clearResult['success']) {
            return [
                'success' => false,
                'error'   => 'Failed to clear dataset: ' . ($clearResult['error'] ?? 'Unknown error'),
                'steps'   => $steps,
            ];
        }

        // Step 2: Load OWL ontology
        $owlResult = $this->loadOwlFile();
        $steps[] = [
            'step'    => 'Load OWL Ontology',
            'success' => $owlResult['success'],
            'detail'  => $owlResult['success']
                ? ($owlResult['message'] ?? 'OWL loaded.')
                : ($owlResult['error'] ?? 'Unknown error during OWL load.'),
        ];

        // Step 3: Load TTL data
        $ttlResult = $this->loadTtlFile();
        $steps[] = [
            'step'    => 'Load Turtle Data',
            'success' => $ttlResult['success'],
            'detail'  => $ttlResult['success']
                ? ($ttlResult['message'] ?? 'TTL loaded.')
                : ($ttlResult['error'] ?? 'Unknown error during TTL load.'),
        ];

        // Get triple count after loading
        $countResult = $this->sparqlService->getTripleCount();
        $tripleCount = $countResult['success']
            ? $countResult['data']['tripleCount']
            : 'unknown';

        $allSuccess = $owlResult['success'] && $ttlResult['success'];

        return [
            'success'     => $allSuccess,
            'message'     => $allSuccess
                ? "All data reloaded successfully. Total triples: {$tripleCount}"
                : 'Some steps failed during reload. Check the steps array for details.',
            'steps'       => $steps,
            'tripleCount' => $tripleCount,
        ];
    }

    /**
     * Check if data is already loaded in Fuseki.
     *
     * Runs a simple ASK query to determine whether any triples exist
     * in the default graph of the Fuseki dataset.
     *
     * @return array Structured response:
     *               - ['success' => true, 'data' => ['loaded' => true/false, 'tripleCount' => int]]
     *               - ['success' => false, 'error' => '...']
     */
    public function isDataLoaded(): array
    {
        // Run ASK query to check for any triples
        $askResult = $this->sparqlService->query('ASK WHERE { ?s ?p ?o }');

        if (!$askResult['success']) {
            return [
                'success' => false,
                'error'   => 'Failed to check data status: ' . ($askResult['error'] ?? 'Unknown error'),
            ];
        }

        $isLoaded = false;

        // Handle ASK query response (could be boolean or in results)
        if (isset($askResult['data']['boolean'])) {
            $isLoaded = (bool) $askResult['data']['boolean'];
        }

        // Also get the triple count for more detail
        $countResult = $this->sparqlService->getTripleCount();
        $tripleCount = $countResult['success']
            ? $countResult['data']['tripleCount']
            : 0;

        return [
            'success' => true,
            'data'    => [
                'loaded'      => $isLoaded,
                'tripleCount' => $tripleCount,
            ],
        ];
    }
}

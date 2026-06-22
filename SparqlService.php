<?php
/**
 * Smart Clinical Decision System - SPARQL Service
 *
 * Core service for communicating with Apache Jena Fuseki via cURL.
 * Handles SPARQL queries, updates, and data uploads (OWL/TTL files).
 *
 * @package SmartCDS\Services
 * @version 1.0.0
 */

namespace Services;

class SparqlService
{
    /** @var string SPARQL query endpoint URL */
    private string $queryEndpoint;

    /** @var string SPARQL update endpoint URL */
    private string $updateEndpoint;

    /** @var string Graph Store Protocol data endpoint URL */
    private string $dataEndpoint;

    /** @var int cURL timeout in seconds */
    private int $timeout;

    /**
     * Construct the SparqlService.
     *
     * @param string|null $queryEndpoint  Override for the SPARQL query endpoint
     * @param string|null $updateEndpoint Override for the SPARQL update endpoint
     * @param string|null $dataEndpoint   Override for the Graph Store Protocol endpoint
     * @param int         $timeout        cURL request timeout in seconds
     */
    public function __construct(
        ?string $queryEndpoint = null,
        ?string $updateEndpoint = null,
        ?string $dataEndpoint = null,
        int $timeout = 30
    ) {
        $this->queryEndpoint  = $queryEndpoint  ?? FUSEKI_ENDPOINT;
        $this->updateEndpoint = $updateEndpoint ?? FUSEKI_UPDATE;
        $this->dataEndpoint   = $dataEndpoint   ?? FUSEKI_DATA;
        $this->timeout        = $timeout;
    }

    /**
     * Execute a SPARQL SELECT or ASK query against the Fuseki endpoint.
     *
     * Sends an HTTP GET request with the query as a URL parameter.
     * Returns the parsed JSON results as an associative array.
     *
     * @param string $sparql The SPARQL query string to execute
     *
     * @return array Structured response:
     *               - On success: ['success' => true, 'data' => ['head' => [...], 'results' => ['bindings' => [...]]]]
     *               - On failure: ['success' => false, 'error' => 'Error message']
     *
     * @throws \RuntimeException If cURL initialization fails
     */
    public function query(string $sparql): array
    {
        $sparql = trim($sparql);

        if (empty($sparql)) {
            return [
                'success' => false,
                'error'   => 'SPARQL query string cannot be empty.',
            ];
        }

        $url = $this->queryEndpoint . '?' . http_build_query(['query' => $sparql]);

        $ch = curl_init();

        if ($ch === false) {
            return [
                'success' => false,
                'error'   => 'Failed to initialize cURL session.',
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/sparql-results+json',
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Handle cURL-level errors
        if ($response === false) {
            return [
                'success' => false,
                'error'   => 'cURL request failed: ' . $curlError,
            ];
        }

        // Handle HTTP error responses
        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'success' => false,
                'error'   => "Fuseki returned HTTP {$httpCode}: " . substr($response, 0, 500),
            ];
        }

        // Parse JSON response
        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Could be an ASK query returning plain boolean or CONSTRUCT returning RDF
            // Try to handle ASK results
            $trimmed = strtolower(trim($response));
            if ($trimmed === 'true' || $trimmed === 'false') {
                return [
                    'success' => true,
                    'data'    => ['boolean' => $trimmed === 'true'],
                ];
            }

            return [
                'success' => false,
                'error'   => 'Failed to parse JSON response: ' . json_last_error_msg(),
                'raw'     => substr($response, 0, 1000),
            ];
        }

        return [
            'success' => true,
            'data'    => $decoded,
        ];
    }

    /**
     * Execute a SPARQL UPDATE operation (INSERT, DELETE, etc.) against Fuseki.
     *
     * Sends an HTTP POST request with the update query in the request body.
     *
     * @param string $sparql The SPARQL UPDATE query string
     *
     * @return array Structured response:
     *               - On success: ['success' => true, 'message' => 'Update executed successfully.']
     *               - On failure: ['success' => false, 'error' => 'Error message']
     */
    public function update(string $sparql): array
    {
        $sparql = trim($sparql);

        if (empty($sparql)) {
            return [
                'success' => false,
                'error'   => 'SPARQL update string cannot be empty.',
            ];
        }

        $ch = curl_init();

        if ($ch === false) {
            return [
                'success' => false,
                'error'   => 'Failed to initialize cURL session.',
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->updateEndpoint,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['update' => $sparql]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'success' => false,
                'error'   => 'cURL request failed: ' . $curlError,
            ];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'success' => false,
                'error'   => "Fuseki update returned HTTP {$httpCode}: " . substr($response, 0, 500),
            ];
        }

        return [
            'success' => true,
            'message' => 'SPARQL update executed successfully.',
        ];
    }

    /**
     * Upload an OWL ontology file to Fuseki via the Graph Store Protocol.
     *
     * Sends the file content via HTTP POST to the data endpoint with the
     * appropriate Content-Type header for OWL/RDF-XML.
     *
     * @param string $filePath Absolute path to the .owl file
     *
     * @return array Structured response:
     *               - On success: ['success' => true, 'message' => 'Ontology loaded successfully.']
     *               - On failure: ['success' => false, 'error' => 'Error message']
     */
    public function loadOntology(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error'   => "OWL file not found at: {$filePath}",
            ];
        }

        if (!is_readable($filePath)) {
            return [
                'success' => false,
                'error'   => "OWL file is not readable: {$filePath}",
            ];
        }

        $fileContent = file_get_contents($filePath);

        if ($fileContent === false) {
            return [
                'success' => false,
                'error'   => "Failed to read OWL file: {$filePath}",
            ];
        }

        $ch = curl_init();

        if ($ch === false) {
            return [
                'success' => false,
                'error'   => 'Failed to initialize cURL session.',
            ];
        }

        // Use POST to add data (PUT would replace the entire dataset)
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->dataEndpoint . '?default',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $fileContent,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/rdf+xml',
                'Content-Length: ' . strlen($fileContent),
            ],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'success' => false,
                'error'   => 'cURL request failed during OWL upload: ' . $curlError,
            ];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'success' => false,
                'error'   => "Fuseki returned HTTP {$httpCode} during OWL upload: " . substr($response, 0, 500),
            ];
        }

        return [
            'success' => true,
            'message' => 'OWL ontology loaded successfully into Fuseki.',
        ];
    }

    /**
     * Upload a Turtle (.ttl) data file to Fuseki via the Graph Store Protocol.
     *
     * Sends the file content via HTTP POST to the data endpoint with the
     * text/turtle Content-Type header.
     *
     * @param string $filePath Absolute path to the .ttl file
     *
     * @return array Structured response:
     *               - On success: ['success' => true, 'message' => 'Turtle data loaded successfully.']
     *               - On failure: ['success' => false, 'error' => 'Error message']
     */
    public function loadTurtleData(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error'   => "TTL file not found at: {$filePath}",
            ];
        }

        if (!is_readable($filePath)) {
            return [
                'success' => false,
                'error'   => "TTL file is not readable: {$filePath}",
            ];
        }

        $fileContent = file_get_contents($filePath);

        if ($fileContent === false) {
            return [
                'success' => false,
                'error'   => "Failed to read TTL file: {$filePath}",
            ];
        }

        $ch = curl_init();

        if ($ch === false) {
            return [
                'success' => false,
                'error'   => 'Failed to initialize cURL session.',
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->dataEndpoint . '?default',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $fileContent,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/turtle',
                'Content-Length: ' . strlen($fileContent),
            ],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'success' => false,
                'error'   => 'cURL request failed during TTL upload: ' . $curlError,
            ];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'success' => false,
                'error'   => "Fuseki returned HTTP {$httpCode} during TTL upload: " . substr($response, 0, 500),
            ];
        }

        return [
            'success' => true,
            'message' => 'Turtle data loaded successfully into Fuseki.',
        ];
    }

    /**
     * Clear all data from the default graph in Fuseki.
     *
     * Executes a SPARQL UPDATE DROP ALL to remove all triples.
     *
     * @return array Structured response with success or error
     */
    public function clearDataset(): array
    {
        return $this->update('DROP ALL');
    }

    /**
     * Get the count of triples in the default graph.
     *
     * @return array Structured response with triple count in data key
     */
    public function getTripleCount(): array
    {
        $sparql = 'SELECT (COUNT(*) AS ?count) WHERE { ?s ?p ?o }';
        $result = $this->query($sparql);

        if (!$result['success']) {
            return $result;
        }

        $count = 0;
        if (!empty($result['data']['results']['bindings'])) {
            $count = (int) $result['data']['results']['bindings'][0]['count']['value'];
        }

        return [
            'success' => true,
            'data'    => ['tripleCount' => $count],
        ];
    }
}

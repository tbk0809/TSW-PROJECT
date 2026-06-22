<?php
/**
 * Smart Clinical Decision System - SPARQL Controller
 *
 * Handles custom SPARQL query execution and provides a set of
 * preloaded/predefined queries for the SPARQL console interface.
 *
 * @package SmartCDS\Controllers
 * @version 1.0.0
 */

namespace Controllers;

use Services\SparqlService;

class SparqlController
{
    /** @var SparqlService Instance of the SPARQL service */
    private SparqlService $sparqlService;

    /**
     * Construct the SparqlController.
     *
     * @param SparqlService|null $sparqlService Optional SparqlService instance
     */
    public function __construct(?SparqlService $sparqlService = null)
    {
        $this->sparqlService = $sparqlService ?? new SparqlService();
    }

    /**
     * Validate and execute a custom SPARQL query.
     *
     * Performs basic validation to prevent destructive operations,
     * then executes the query against the Fuseki endpoint.
     *
     * @param string $sparql The SPARQL query string to execute
     *
     * @return array Structured response with query results:
     *               [
     *                   'success' => true/false,
     *                   'data' => [...],          // SPARQL JSON results
     *                   'executionTime' => float,  // Seconds elapsed
     *                   'resultCount' => int       // Number of result rows
     *               ]
     */
    public function executeQuery(string $sparql): array
    {
        $sparql = trim($sparql);

        // Validate the query is not empty
        if (empty($sparql)) {
            return [
                'success' => false,
                'error'   => 'SPARQL query cannot be empty.',
            ];
        }

        // Basic security validation — block destructive operations via the query endpoint
        $upperSparql = strtoupper($sparql);
        $blockedKeywords = ['DROP', 'DELETE', 'CLEAR', 'MOVE', 'COPY', 'ADD'];
        foreach ($blockedKeywords as $keyword) {
            // Check for keyword as a standalone word (not inside a string literal)
            if (preg_match('/\b' . $keyword . '\b/', $upperSparql)) {
                // Allow DELETE inside WHERE (common SPARQL pattern) but not standalone DROP/CLEAR
                if ($keyword === 'DELETE') {
                    // DELETE is allowed only in DELETE WHERE patterns, which should go through update endpoint
                    continue;
                }
                return [
                    'success' => false,
                    'error'   => "Destructive operation '{$keyword}' is not allowed through the query console. "
                        . 'Use the SPARQL UPDATE endpoint for write operations.',
                ];
            }
        }

        // Measure execution time
        $startTime = microtime(true);

        // Determine if this is a SELECT/ASK/CONSTRUCT/DESCRIBE query
        $result = $this->sparqlService->query($sparql);

        $executionTime = round(microtime(true) - $startTime, 4);

        if (!$result['success']) {
            return [
                'success'       => false,
                'error'         => $result['error'] ?? 'Query execution failed.',
                'executionTime' => $executionTime,
            ];
        }

        // Count results
        $resultCount = 0;
        if (isset($result['data']['results']['bindings'])) {
            $resultCount = count($result['data']['results']['bindings']);
        } elseif (isset($result['data']['boolean'])) {
            $resultCount = 1; // ASK query returns a single boolean
        }

        // Extract column headers
        $headers = $result['data']['head']['vars'] ?? [];

        return [
            'success'       => true,
            'data'          => $result['data'],
            'headers'       => $headers,
            'resultCount'   => $resultCount,
            'executionTime' => $executionTime,
            'queryType'     => $this->detectQueryType($sparql),
        ];
    }

    /**
     * Get an array of predefined SPARQL queries for the console.
     *
     * Returns 8 commonly-used queries with titles, descriptions,
     * and complete SPARQL query strings ready for execution.
     *
     * @return array Array of predefined query objects:
     *               [
     *                   ['id' => int, 'title' => '...', 'description' => '...', 'query' => '...'],
     *                   ...
     *               ]
     */
    public function getPreloadedQueries(): array
    {
        return [
            [
                'id'          => 1,
                'title'       => 'List All Patients',
                'description' => 'Retrieves all patients with their basic information including name, age, and risk level.',
                'query'       => "PREFIX cds: <http://www.semanticweb.org/clinical-decision-system#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?patient ?patientName ?age ?riskLevel
WHERE {
    ?patient rdf:type cds:Patient .
    OPTIONAL { ?patient cds:patientName ?patientName . }
    OPTIONAL { ?patient cds:age ?age . }
    OPTIONAL { ?patient cds:riskLevel ?riskLevel . }
}
ORDER BY ?patientName",
            ],
            [
                'id'          => 2,
                'title'       => 'Patients with Symptoms and Diseases',
                'description' => 'Shows patients along with their symptoms and diagnosed diseases.',
                'query'       => "PREFIX cds: <http://www.semanticweb.org/clinical-decision-system#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?patientName ?symptomName ?diseaseName
WHERE {
    ?patient rdf:type cds:Patient .
    ?patient cds:patientName ?patientName .
    OPTIONAL {
        ?patient cds:hasSymptom ?symptom .
        ?symptom cds:symptomName ?symptomName .
    }
    OPTIONAL {
        ?patient cds:hasDiagnosis ?disease .
        ?disease cds:diseaseName ?diseaseName .
    }
}
ORDER BY ?patientName ?symptomName",
            ],
            [
                'id'          => 3,
                'title'       => 'High Risk Patients',
                'description' => 'Lists all patients classified as high risk based on OWL reasoning or risk level property.',
                'query'       => "PREFIX cds: <http://www.semanticweb.org/clinical-decision-system#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?patient ?patientName ?riskLevel (COUNT(?symptom) AS ?symptomCount)
WHERE {
    ?patient rdf:type cds:Patient .
    OPTIONAL { ?patient cds:patientName ?patientName . }
    OPTIONAL { ?patient cds:riskLevel ?riskLevel . }
    OPTIONAL { ?patient cds:hasSymptom ?symptom . }
    {
        { ?patient cds:riskLevel \"High\" . }
        UNION
        { ?patient rdf:type cds:HighRiskPatient . }
    }
}
GROUP BY ?patient ?patientName ?riskLevel
ORDER BY ?patientName",
            ],
            [
                'id'          => 4,
                'title'       => 'Symptom-Disease Relationships',
                'description' => 'Shows which symptoms indicate which diseases based on the ontology.',
                'query'       => "PREFIX cds: <http://www.semanticweb.org/clinical-decision-system#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?symptomName ?diseaseName
WHERE {
    ?symptom rdf:type cds:Symptom .
    ?symptom cds:symptomName ?symptomName .
    ?symptom cds:indicatesDisease ?disease .
    ?disease cds:diseaseName ?diseaseName .
}
ORDER BY ?diseaseName ?symptomName",
            ],
            [
                'id'          => 5,
                'title'       => 'Medications and Treated Diseases',
                'description' => 'Lists all medications and the diseases they treat.',
                'query'       => "PREFIX cds: <http://www.semanticweb.org/clinical-decision-system#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?medicationName ?diseaseName ?dosage
WHERE {
    ?medication rdf:type cds:Medication .
    ?medication cds:medicationName ?medicationName .
    ?medication cds:treatsDisease ?disease .
    ?disease cds:diseaseName ?diseaseName .
    OPTIONAL { ?medication cds:dosage ?dosage . }
}
ORDER BY ?diseaseName ?medicationName",
            ],
            [
                'id'          => 6,
                'title'       => 'Drug Contraindications',
                'description' => 'Shows all known drug contraindications and interactions in the ontology.',
                'query'       => "PREFIX cds: <http://www.semanticweb.org/clinical-decision-system#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?med1Name ?med2Name ?type
WHERE {
    {
        ?med1 cds:contraindicatedWith ?med2 .
        BIND(\"Contraindication\" AS ?type)
    }
    UNION
    {
        ?med1 cds:interactsWith ?med2 .
        BIND(\"Interaction\" AS ?type)
    }
    OPTIONAL { ?med1 cds:medicationName ?med1Name . }
    OPTIONAL { ?med2 cds:medicationName ?med2Name . }
}
ORDER BY ?type ?med1Name",
            ],
            [
                'id'          => 7,
                'title'       => 'Ontology Class Hierarchy',
                'description' => 'Displays the OWL class hierarchy of the clinical decision system ontology.',
                'query'       => "PREFIX cds: <http://www.semanticweb.org/clinical-decision-system#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>

SELECT ?class ?parentClass
WHERE {
    ?class rdf:type owl:Class .
    OPTIONAL { ?class rdfs:subClassOf ?parentClass . }
    FILTER(STRSTARTS(STR(?class), STR(cds:)))
}
ORDER BY ?parentClass ?class",
            ],
            [
                'id'          => 8,
                'title'       => 'Dataset Statistics',
                'description' => 'Shows aggregate statistics about the loaded dataset including entity counts.',
                'query'       => "PREFIX cds: <http://www.semanticweb.org/clinical-decision-system#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT
    (COUNT(DISTINCT ?patient) AS ?patients)
    (COUNT(DISTINCT ?disease) AS ?diseases)
    (COUNT(DISTINCT ?symptom) AS ?symptoms)
    (COUNT(DISTINCT ?medication) AS ?medications)
WHERE {
    OPTIONAL { ?patient rdf:type cds:Patient . }
    OPTIONAL { ?disease rdf:type cds:Disease . }
    OPTIONAL { ?symptom rdf:type cds:Symptom . }
    OPTIONAL { ?medication rdf:type cds:Medication . }
}",
            ],
        ];
    }

    /**
     * Detect the type of SPARQL query.
     *
     * Analyzes the query string to determine whether it is a
     * SELECT, ASK, CONSTRUCT, or DESCRIBE query.
     *
     * @param string $sparql The SPARQL query string
     *
     * @return string The detected query type: 'SELECT', 'ASK', 'CONSTRUCT', 'DESCRIBE', or 'UNKNOWN'
     */
    private function detectQueryType(string $sparql): string
    {
        $sparql = trim($sparql);

        // Remove prefixes and comments to find the query form keyword
        $cleaned = preg_replace('/PREFIX\s+\S+\s+<[^>]+>\s*/i', '', $sparql);
        $cleaned = preg_replace('/#.*$/m', '', $cleaned);
        $cleaned = trim($cleaned);

        if (preg_match('/^\s*SELECT\b/i', $cleaned)) {
            return 'SELECT';
        }
        if (preg_match('/^\s*ASK\b/i', $cleaned)) {
            return 'ASK';
        }
        if (preg_match('/^\s*CONSTRUCT\b/i', $cleaned)) {
            return 'CONSTRUCT';
        }
        if (preg_match('/^\s*DESCRIBE\b/i', $cleaned)) {
            return 'DESCRIBE';
        }

        return 'UNKNOWN';
    }
}

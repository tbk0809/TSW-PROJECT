<?php
/**
 * Smart Clinical Decision System - Diagnosis Controller
 *
 * Handles symptom-based diagnosis, medication suggestions,
 * and contraindication checks using SPARQL queries against
 * the clinical ontology.
 *
 * @package SmartCDS\Controllers
 * @version 1.0.0
 */

namespace Controllers;

use Services\SparqlService;

class DiagnosisController
{
    /** @var SparqlService Instance of the SPARQL service */
    private SparqlService $sparqlService;

    /** @var string SPARQL PREFIX declarations */
    private string $prefixes;

    /**
     * Construct the DiagnosisController.
     *
     * @param SparqlService|null $sparqlService Optional SparqlService instance
     */
    public function __construct(?SparqlService $sparqlService = null)
    {
        $this->sparqlService = $sparqlService ?? new SparqlService();
        $this->prefixes = "PREFIX cds: <http://www.semanticweb.org/clinical-decision-system#>\n"
            . "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n"
            . "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\n"
            . "PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>\n";
    }

    /**
     * Diagnose probable diseases based on an array of symptom names.
     *
     * Queries the ontology to find diseases that are linked to the given
     * symptoms via the cds:indicatesDisease relationship. Returns diseases
     * ranked by the number of matching symptoms as a confidence indicator.
     *
     * @param array $symptoms Array of symptom name strings (e.g., ['Fever', 'Cough', 'Headache'])
     *
     * @return array Diagnosis results sorted by confidence:
     *               [
     *                   [
     *                       'diseaseURI' => '...',
     *                       'diseaseName' => '...',
     *                       'matchingSymptoms' => [...],
     *                       'matchCount' => int,
     *                       'totalSymptoms' => int,
     *                       'confidence' => float (0-100)
     *                   ],
     *                   ...
     *               ]
     */
    public function diagnose(array $symptoms): array
    {
        if (empty($symptoms)) {
            return [
                'success'  => false,
                'error'    => 'No symptoms provided for diagnosis.',
                'diseases' => [],
            ];
        }

        // Clean and normalize symptom names
        $symptoms = array_map('trim', $symptoms);
        $symptoms = array_filter($symptoms, fn($s) => !empty($s));

        if (empty($symptoms)) {
            return [
                'success'  => false,
                'error'    => 'No valid symptoms after sanitization.',
                'diseases' => [],
            ];
        }

        // Build FILTER for matching symptom names (case-insensitive)
        $filterParts = [];
        foreach ($symptoms as $symptom) {
            $escaped = addslashes($symptom);
            $filterParts[] = "REGEX(?symptomName, \"{$escaped}\", \"i\")";
        }
        $filterClause = implode(' || ', $filterParts);

        // Query: Find diseases linked to matching symptoms via indicatesDisease
        $sparql = $this->prefixes . "
            SELECT ?disease ?diseaseName ?symptom ?symptomName
            WHERE {
                ?symptom rdf:type cds:Symptom .
                ?symptom cds:symptomName ?symptomName .
                FILTER ({$filterClause})
                ?symptom cds:indicatesDisease ?disease .
                OPTIONAL { ?disease cds:diseaseName ?diseaseName . }
            }
            ORDER BY ?diseaseName ?symptomName
        ";

        $result = $this->sparqlService->query($sparql);

        if (!$result['success']) {
            return [
                'success'  => false,
                'error'    => $result['error'] ?? 'Failed to execute diagnosis query.',
                'diseases' => [],
            ];
        }

        $bindings = $result['data']['results']['bindings'] ?? [];

        // Aggregate results by disease
        $diseaseMap = [];
        foreach ($bindings as $row) {
            $diseaseURI  = $row['disease']['value'] ?? '';
            $diseaseName = $row['diseaseName']['value'] ?? $this->extractLocalName($diseaseURI);
            $symptomName = $row['symptomName']['value'] ?? '';

            if (!isset($diseaseMap[$diseaseURI])) {
                $diseaseMap[$diseaseURI] = [
                    'diseaseURI'       => $diseaseURI,
                    'diseaseName'      => $diseaseName,
                    'matchingSymptoms' => [],
                    'matchCount'       => 0,
                ];
            }

            if (!in_array($symptomName, $diseaseMap[$diseaseURI]['matchingSymptoms'])) {
                $diseaseMap[$diseaseURI]['matchingSymptoms'][] = $symptomName;
                $diseaseMap[$diseaseURI]['matchCount']++;
            }
        }

        // For each disease, get total symptom count and calculate confidence
        foreach ($diseaseMap as $uri => &$disease) {
            $totalSymptomsResult = $this->getTotalSymptomsForDisease($uri);
            $disease['totalSymptoms'] = $totalSymptomsResult;
            $disease['confidence'] = $disease['totalSymptoms'] > 0
                ? round(($disease['matchCount'] / $disease['totalSymptoms']) * 100, 1)
                : 0;
        }
        unset($disease);

        // Sort by confidence (match count) descending
        usort($diseaseMap, fn($a, $b) => $b['matchCount'] <=> $a['matchCount']);

        return [
            'success'      => true,
            'inputSymptoms' => $symptoms,
            'diseases'     => array_values($diseaseMap),
            'totalMatches' => count($diseaseMap),
        ];
    }

    /**
     * Get suggested medications for a specific disease.
     *
     * Queries the ontology to find medications that are linked to the
     * disease via the cds:treatsDisease relationship (inverse lookup).
     *
     * @param string $diseaseURI The full URI or local name of the disease
     *
     * @return array List of medication suggestions:
     *               [
     *                   ['uri' => '...', 'name' => '...', 'dosage' => '...', 'sideEffects' => '...'],
     *                   ...
     *               ]
     */
    public function getSuggestedMedications(string $diseaseURI): array
    {
        // Normalize URI
        if (!str_starts_with($diseaseURI, 'http')) {
            $diseaseURI = CDS_NAMESPACE . $diseaseURI;
        }

        $sparql = $this->prefixes . "
            SELECT DISTINCT ?medication ?medicationName ?dosage ?sideEffects ?frequency
                            ?drugClass ?manufacturer
            WHERE {
                ?medication rdf:type cds:Medication .
                ?medication cds:treatsDisease <{$diseaseURI}> .
                OPTIONAL { ?medication cds:medicationName ?medicationName . }
                OPTIONAL { ?medication cds:dosage ?dosage . }
                OPTIONAL { ?medication cds:sideEffects ?sideEffects . }
                OPTIONAL { ?medication cds:frequency ?frequency . }
                OPTIONAL { ?medication cds:drugClass ?drugClass . }
                OPTIONAL { ?medication cds:manufacturer ?manufacturer . }
            }
            ORDER BY ?medicationName
        ";

        $result = $this->sparqlService->query($sparql);

        if (!$result['success']) {
            return [
                'success'     => false,
                'error'       => $result['error'] ?? 'Failed to query medications.',
                'medications' => [],
            ];
        }

        $medications = [];
        foreach ($result['data']['results']['bindings'] ?? [] as $row) {
            $medications[] = [
                'uri'          => $row['medication']['value'] ?? '',
                'name'         => $row['medicationName']['value'] ?? $this->extractLocalName($row['medication']['value'] ?? ''),
                'dosage'       => $row['dosage']['value'] ?? 'Consult physician',
                'sideEffects'  => $row['sideEffects']['value'] ?? 'Not documented',
                'frequency'    => $row['frequency']['value'] ?? 'As prescribed',
                'drugClass'    => $row['drugClass']['value'] ?? 'N/A',
                'manufacturer' => $row['manufacturer']['value'] ?? 'N/A',
            ];
        }

        return [
            'success'     => true,
            'diseaseURI'  => $diseaseURI,
            'medications' => $medications,
            'totalFound'  => count($medications),
        ];
    }

    /**
     * Check for contraindications and interactions among a list of medications.
     *
     * Given a list of medication URIs or names, queries the ontology for
     * contraindicatedWith and interactsWith relationships between all pairs.
     *
     * @param array $medicationList Array of medication URIs or local names
     *
     * @return array Warning information:
     *               [
     *                   'success' => true,
     *                   'warnings' => [
     *                       ['type' => 'contraindication', 'med1' => '...', 'med2' => '...', 'message' => '...'],
     *                       ...
     *                   ],
     *                   'hasWarnings' => bool
     *               ]
     */
    public function checkContraindications(array $medicationList): array
    {
        if (count($medicationList) < 2) {
            return [
                'success'     => true,
                'warnings'    => [],
                'hasWarnings' => false,
                'message'     => 'At least 2 medications are needed to check for interactions.',
            ];
        }

        // Build VALUES clause for medication URIs
        $valuesParts = [];
        foreach ($medicationList as $med) {
            if (str_starts_with($med, 'http')) {
                $valuesParts[] = "<{$med}>";
            } else {
                $valuesParts[] = "cds:" . trim($med);
            }
        }
        $valuesClause = implode(' ', $valuesParts);

        // Query contraindications
        $sparqlContra = $this->prefixes . "
            SELECT DISTINCT ?med1 ?med1Name ?med2 ?med2Name ?severity
            WHERE {
                VALUES ?med1 { {$valuesClause} }
                VALUES ?med2 { {$valuesClause} }
                ?med1 cds:contraindicatedWith ?med2 .
                FILTER (?med1 != ?med2)
                OPTIONAL { ?med1 cds:medicationName ?med1Name . }
                OPTIONAL { ?med2 cds:medicationName ?med2Name . }
                OPTIONAL { ?med1 cds:contraindicationSeverity ?severity . }
            }
        ";

        // Query interactions
        $sparqlInteract = $this->prefixes . "
            SELECT DISTINCT ?med1 ?med1Name ?med2 ?med2Name ?interactionType ?description
            WHERE {
                VALUES ?med1 { {$valuesClause} }
                VALUES ?med2 { {$valuesClause} }
                ?med1 cds:interactsWith ?med2 .
                FILTER (?med1 != ?med2)
                OPTIONAL { ?med1 cds:medicationName ?med1Name . }
                OPTIONAL { ?med2 cds:medicationName ?med2Name . }
                OPTIONAL { ?med1 cds:interactionType ?interactionType . }
                OPTIONAL { ?med1 cds:interactionDescription ?description . }
            }
        ";

        $contraResult  = $this->sparqlService->query($sparqlContra);
        $interactResult = $this->sparqlService->query($sparqlInteract);

        $warnings = [];
        $processedPairs = [];

        // Process contraindications
        if ($contraResult['success'] && !empty($contraResult['data']['results']['bindings'])) {
            foreach ($contraResult['data']['results']['bindings'] as $row) {
                $med1 = $row['med1Name']['value'] ?? $this->extractLocalName($row['med1']['value'] ?? '');
                $med2 = $row['med2Name']['value'] ?? $this->extractLocalName($row['med2']['value'] ?? '');
                $severity = $row['severity']['value'] ?? 'Unknown';

                // Avoid duplicate pairs
                $pairKey = min($med1, $med2) . '|' . max($med1, $med2);
                if (in_array($pairKey, $processedPairs)) {
                    continue;
                }
                $processedPairs[] = $pairKey;

                $warnings[] = [
                    'type'     => 'contraindication',
                    'severity' => $severity,
                    'med1'     => $med1,
                    'med2'     => $med2,
                    'message'  => "⚠️ CONTRAINDICATION: {$med1} is contraindicated with {$med2} (Severity: {$severity}).",
                ];
            }
        }

        // Process interactions
        if ($interactResult['success'] && !empty($interactResult['data']['results']['bindings'])) {
            foreach ($interactResult['data']['results']['bindings'] as $row) {
                $med1 = $row['med1Name']['value'] ?? $this->extractLocalName($row['med1']['value'] ?? '');
                $med2 = $row['med2Name']['value'] ?? $this->extractLocalName($row['med2']['value'] ?? '');
                $type = $row['interactionType']['value'] ?? 'Unknown';
                $desc = $row['description']['value'] ?? '';

                $pairKey = min($med1, $med2) . '|' . max($med1, $med2) . '|interaction';
                if (in_array($pairKey, $processedPairs)) {
                    continue;
                }
                $processedPairs[] = $pairKey;

                $warnings[] = [
                    'type'            => 'interaction',
                    'interactionType' => $type,
                    'med1'            => $med1,
                    'med2'            => $med2,
                    'description'     => $desc,
                    'message'         => "⚡ INTERACTION: {$med1} interacts with {$med2} (Type: {$type}). {$desc}",
                ];
            }
        }

        return [
            'success'        => true,
            'medications'    => $medicationList,
            'warnings'       => $warnings,
            'hasWarnings'    => !empty($warnings),
            'warningCount'   => count($warnings),
        ];
    }

    /**
     * Get the total number of symptoms associated with a disease.
     *
     * @param string $diseaseURI The full URI of the disease
     *
     * @return int Total symptom count
     */
    private function getTotalSymptomsForDisease(string $diseaseURI): int
    {
        $sparql = $this->prefixes . "
            SELECT (COUNT(DISTINCT ?symptom) AS ?total)
            WHERE {
                ?symptom cds:indicatesDisease <{$diseaseURI}> .
            }
        ";

        $result = $this->sparqlService->query($sparql);

        if (!$result['success'] || empty($result['data']['results']['bindings'])) {
            return 0;
        }

        return (int) ($result['data']['results']['bindings'][0]['total']['value'] ?? 0);
    }

    /**
     * Extract the local name from a full URI.
     *
     * @param string $uri Full URI string
     *
     * @return string Local name portion
     */
    private function extractLocalName(string $uri): string
    {
        if (str_contains($uri, '#')) {
            return substr($uri, strrpos($uri, '#') + 1);
        }
        if (str_contains($uri, '/')) {
            return substr($uri, strrpos($uri, '/') + 1);
        }
        return $uri;
    }
}

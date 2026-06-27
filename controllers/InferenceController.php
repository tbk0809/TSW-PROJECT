<?php
/**
 * Smart Clinical Decision System - Inference Controller
 *
 * Controller for managing OWL-based inference operations including
 * patient classification, inferred fact retrieval, and explanation generation.
 *
 * @package SmartCDS\Controllers
 * @version 1.0.0
 */

namespace Controllers;

use Services\InferenceService;
use Services\SparqlService;

class InferenceController
{
    /** @var InferenceService Instance of the inference service */
    private InferenceService $inferenceService;

    /** @var SparqlService Instance of the SPARQL service */
    private SparqlService $sparqlService;

    /**
     * Construct the InferenceController.
     *
     * @param InferenceService|null $inferenceService Optional InferenceService instance
     * @param SparqlService|null    $sparqlService    Optional SparqlService instance
     */
    public function __construct(
        ?InferenceService $inferenceService = null,
        ?SparqlService $sparqlService = null
    ) {
        $this->sparqlService    = $sparqlService ?? new SparqlService();
        $this->inferenceService = $inferenceService ?? new InferenceService($this->sparqlService);
    }

    /**
     * Run OWL classification, optionally scoped to one patient.
     *
     * When $patientId is supplied the engine still materialises all inference
     * rules (so the graph is up-to-date), then returns triples[] and
     * explanations[] scoped to that patient — the shape the JS frontend
     * renders in the Inference Results table.
     *
     * When $patientId is null every patient is classified and the same
     * triples[] / explanations[] arrays are built by aggregating across all
     * patients, so the table is never empty after classification.
     *
     * @param string|null $patientId Patient local-name (e.g. "Patient_007") or null for all
     *
     * @return array Structured response including triples[], explanations[], patients[], summary
     */
    public function runClassification(?string $patientId = null): array
    {
        try {
            // Always materialise inferred triples first so the graph is current
            $reasoningResult = $this->inferenceService->runReasoning();

            $prefixes = "PREFIX cds: <http://www.semanticweb.org/clinical-decision-system#>\n"
                . "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n";

            // ── Fetch the list of patients to classify ──────────────────────
            if ($patientId !== null) {
                // Single patient: build URI and resolve name
                $patientURI  = str_starts_with($patientId, 'http') ? $patientId : (CDS_NAMESPACE . $patientId);
                $nameSparql  = $prefixes . "
                    SELECT ?patientID ?patientName WHERE {
                        BIND(<{$patientURI}> AS ?patient)
                        OPTIONAL { ?patient cds:patientID ?patientID . }
                        OPTIONAL { ?patient cds:patientName ?patientName . }
                    }
                ";
                $nameResult  = $this->sparqlService->query($nameSparql);
                $nameRow     = $nameResult['data']['results']['bindings'][0] ?? [];
                $patientsToProcess = [[
                    'uri'  => $patientURI,
                    'id'   => $nameRow['patientID']['value'] ?? $this->extractLocalName($patientURI),
                    'name' => $nameRow['patientName']['value'] ?? $this->extractLocalName($patientURI),
                ]];
            } else {
                // All patients
                $allSparql = $prefixes . "
                    SELECT DISTINCT ?patient ?patientID ?patientName
                    WHERE {
                        ?patient rdf:type cds:Patient .
                        OPTIONAL { ?patient cds:patientID ?patientID . }
                        OPTIONAL { ?patient cds:patientName ?patientName . }
                    }
                    ORDER BY ?patientName
                ";
                $allResult = $this->sparqlService->query($allSparql);
                $patientsToProcess = [];
                foreach ($allResult['data']['results']['bindings'] ?? [] as $row) {
                    $uri = $row['patient']['value'] ?? '';
                    $patientsToProcess[] = [
                        'uri'  => $uri,
                        'id'   => $row['patientID']['value'] ?? $this->extractLocalName($uri),
                        'name' => $row['patientName']['value'] ?? 'Unknown',
                    ];
                }
            }

            // ── Classify each patient and build flat triples[] array ─────────
            $patientClassifications = [];
            $triples      = [];   // flat [{subject, predicate, object}] for the JS table
            $explanations = [];   // [{title, text}] for the Explanations section

            foreach ($patientsToProcess as $p) {
                $classification = $this->inferenceService->classifyPatientRisk($p['uri']);

                if (!$classification['success']) {
                    continue;
                }

                $data = $classification['data'];
                $label = $data['patientName'] ?? $p['name'];

                $patientClassifications[] = [
                    'patientID'       => $p['id'],
                    'patientName'     => $label,
                    'riskLevel'       => $data['riskLevel'],
                    'classifications' => $data['classifications'],
                    'isHighRisk'      => $data['isHighRisk'],
                    'isDiagnosed'     => $data['isDiagnosed'],
                    'isComplexCase'   => $data['isComplexCase'],
                ];

                // Build inferred triples for every OWL class membership
                foreach ($data['classifications'] as $cls) {
                    $triples[] = [
                        'subject'   => $label,
                        'predicate' => 'rdf:type',
                        'object'    => $cls,
                    ];
                }

                // Risk level triple
                $triples[] = [
                    'subject'   => $label,
                    'predicate' => 'cds:riskLevel',
                    'object'    => $data['riskLevel'],
                ];

                // Explanation entry
                $classStr = implode(', ', $data['classifications']) ?: 'LowRisk';
                $explanations[] = [
                    'title' => "{$label} — {$data['riskLevel']} Risk",
                    'text'  => "{$label} has {$data['symptomCount']} symptom(s) and "
                        . "{$data['diseaseCount']} diagnosis/diagnoses. "
                        . "Inferred class(es): {$classStr}.",
                ];

                // If scoped to one patient also pull inferred triples from the graph
                if ($patientId !== null) {
                    $inferredResult = $this->inferenceService->getInferredTriples($p['uri']);
                    if ($inferredResult['success']) {
                        $inf = $inferredResult['data'];

                        // Related conditions from transitive property
                        foreach ($inf['relatedConditions'] as $cond) {
                            $triples[] = [
                                'subject'   => $label,
                                'predicate' => 'cds:isRelatedConditionOf (transitive)',
                                'object'    => $cond['name'],
                            ];
                        }

                        // Recommended medications from forward-chaining
                        foreach ($inf['recommendedMedications'] as $med) {
                            $triples[] = [
                                'subject'   => $label,
                                'predicate' => 'cds:recommendedMedication',
                                'object'    => $med['name'],
                            ];
                        }

                        if ($inf['requiresSpecialist']) {
                            $triples[] = [
                                'subject'   => $label,
                                'predicate' => 'cds:requiresSpecialistReview',
                                'object'    => 'true',
                            ];
                            $explanations[] = [
                                'title' => 'Specialist Review Required',
                                'text'  => "{$label} meets the ComplexCase criteria and has been flagged for specialist review.",
                            ];
                        }
                    }

                    // Drug interactions
                    $drugResult = $this->inferenceService->checkDrugInteractions($p['uri']);
                    if ($drugResult['success']) {
                        $interactions     = $drugResult['data']['interactions'] ?? [];
                        $contraindications = $drugResult['data']['contraindications'] ?? [];

                        foreach ($contraindications as $ci) {
                            $m1 = $ci['med1Name'] ?? $ci['med1'] ?? '?';
                            $m2 = $ci['med2Name'] ?? $ci['med2'] ?? '?';
                            $triples[] = [
                                'subject'   => $m1,
                                'predicate' => 'cds:contraindicatedWith',
                                'object'    => $m2,
                            ];
                            $explanations[] = [
                                'title' => "⚠️ Contraindication: {$m1} ↔ {$m2}",
                                'text'  => "Prescribed medications {$m1} and {$m2} are contraindicated for {$label}.",
                            ];
                        }

                        foreach ($interactions as $ia) {
                            $m1 = $ia['med1Name'] ?? $ia['med1'] ?? '?';
                            $m2 = $ia['med2Name'] ?? $ia['med2'] ?? '?';
                            $triples[] = [
                                'subject'   => $m1,
                                'predicate' => 'cds:interactsWith',
                                'object'    => $m2,
                            ];
                        }

                        if (empty($interactions) && empty($contraindications)) {
                            $explanations[] = [
                                'title' => 'Inference Rule',
                                'text'  => 'No drug interactions or contraindications found for this patient.',
                            ];
                        }
                    }
                }
            }

            $totalPatients  = count($patientClassifications);
            $highRiskCount  = count(array_filter($patientClassifications, fn($p) => $p['isHighRisk']));
            $diagnosedCount = count(array_filter($patientClassifications, fn($p) => $p['isDiagnosed']));
            $complexCount   = count(array_filter($patientClassifications, fn($p) => $p['isComplexCase']));

            return [
                'success'      => true,
                'triples'      => $triples,       // ← what the JS table reads
                'explanations' => $explanations,  // ← what the JS explanations panel reads
                'reasoning'    => $reasoningResult,
                'patients'     => $patientClassifications,
                'summary'      => [
                    'totalPatients'     => $totalPatients,
                    'highRiskPatients'  => $highRiskCount,
                    'diagnosedPatients' => $diagnosedCount,
                    'complexCases'      => $complexCount,
                ],
                'message' => "Classification complete. {$totalPatients} patient(s) processed: "
                    . "{$highRiskCount} high risk, {$diagnosedCount} diagnosed, {$complexCount} complex cases.",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => 'Classification failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get all inferred facts about a specific patient.
     *
     * Retrieves the results of OWL reasoning for the given patient,
     * including class memberships, risk levels, recommended medications,
     * and related conditions.
     *
     * @param string $patientID The patient identifier (local name or full URI)
     *
     * @return array Structured response with inferred facts:
     *               [
     *                   'success' => true/false,
     *                   'data' => [
     *                       'classification' => [...],
     *                       'inferredTriples' => [...],
     *                       'drugInteractions' => [...]
     *                   ]
     *               ]
     */
    public function getInferredFacts(string $patientID): array
    {
        try {
            // Get classification
            $classification = $this->inferenceService->classifyPatientRisk($patientID);

            // Normalize patient URI for inferred triples query
            $patientURI = $patientID;
            if (!str_starts_with($patientID, 'http')) {
                $patientURI = CDS_NAMESPACE . $patientID;
            }

            // Get inferred triples
            $inferredTriples = $this->inferenceService->getInferredTriples($patientURI);

            // Get drug interactions
            $drugInteractions = $this->inferenceService->checkDrugInteractions($patientID);

            return [
                'success' => true,
                'data'    => [
                    'patientID'        => $patientID,
                    'classification'   => $classification['success'] ? $classification['data'] : null,
                    'inferredTriples'  => $inferredTriples['success'] ? $inferredTriples['data'] : null,
                    'drugInteractions' => $drugInteractions['success'] ? $drugInteractions['data'] : null,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => 'Failed to retrieve inferred facts: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Provide a human-readable explanation of inference results for a patient.
     *
     * Generates detailed explanations of what was inferred and why,
     * suitable for display in the clinical decision support UI.
     *
     * @param string $patientID The patient identifier
     *
     * @return array Structured response with explanations:
     *               [
     *                   'success' => true/false,
     *                   'data' => [
     *                       'explanations' => [...],
     *                       'summary' => '...',
     *                       'warningCount' => int
     *                   ]
     *               ]
     */
    public function explainInference(string $patientID): array
    {
        try {
            return $this->inferenceService->explainInference($patientID);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => 'Failed to generate inference explanation: ' . $e->getMessage(),
            ];
        }
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

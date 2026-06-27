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
     * Run OWL classification on all patients in the dataset.
     *
     * Triggers the inference engine to apply all classification rules
     * (HighRiskPatient, DiagnosedPatient, ComplexCase) and materialize
     * the resulting inferred triples into the Fuseki triple store.
     *
     * @return array Structured response with classification results for all patients:
     *               [
     *                   'success' => true/false,
     *                   'reasoning' => [...],     // Results from inference rule application
     *                   'patients' => [...]        // Per-patient classification summaries
     *               ]
     */
    public function runClassification(): array
    {
        try {
            // Step 1: Run the reasoning engine to materialize inferred triples
            $reasoningResult = $this->inferenceService->runReasoning();

            // Step 2: Get all patients and classify each one
            $prefixes = "PREFIX cds: <http://www.semanticweb.org/clinical-decision-system#>\n"
                . "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n";

            $sparql = $prefixes . "
                SELECT DISTINCT ?patient ?patientID ?patientName
                WHERE {
                    ?patient rdf:type cds:Patient .
                    OPTIONAL { ?patient cds:patientID ?patientID . }
                    OPTIONAL { ?patient cds:patientName ?patientName . }
                }
                ORDER BY ?patientName
            ";

            $patientsResult = $this->sparqlService->query($sparql);
            $patientClassifications = [];

            if ($patientsResult['success'] && !empty($patientsResult['data']['results']['bindings'])) {
                foreach ($patientsResult['data']['results']['bindings'] as $row) {
                    $patientURI = $row['patient']['value'] ?? '';
                    $patientID  = $row['patientID']['value'] ?? $this->extractLocalName($patientURI);
                    $patientName = $row['patientName']['value'] ?? 'Unknown';

                    // Classify this patient
                    $classification = $this->inferenceService->classifyPatientRisk($patientURI);

                    if ($classification['success']) {
                        $patientClassifications[] = [
                            'patientID'       => $patientID,
                            'patientName'     => $patientName,
                            'riskLevel'       => $classification['data']['riskLevel'],
                            'classifications' => $classification['data']['classifications'],
                            'isHighRisk'      => $classification['data']['isHighRisk'],
                            'isDiagnosed'     => $classification['data']['isDiagnosed'],
                            'isComplexCase'   => $classification['data']['isComplexCase'],
                        ];
                    }
                }
            }

            // Summary stats
            $totalPatients  = count($patientClassifications);
            $highRiskCount  = count(array_filter($patientClassifications, fn($p) => $p['isHighRisk']));
            $diagnosedCount = count(array_filter($patientClassifications, fn($p) => $p['isDiagnosed']));
            $complexCount   = count(array_filter($patientClassifications, fn($p) => $p['isComplexCase']));

            return [
                'success'   => true,
                'reasoning' => $reasoningResult,
                'patients'  => $patientClassifications,
                'summary'   => [
                    'totalPatients'     => $totalPatients,
                    'highRiskPatients'  => $highRiskCount,
                    'diagnosedPatients' => $diagnosedCount,
                    'complexCases'      => $complexCount,
                ],
                'message'   => "Classification complete. {$totalPatients} patients processed: "
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
            // Run reasoning first to ensure Fuseki has inferred data
            $this->inferenceService->runReasoning();

            // Get classification
            $classification = $this->inferenceService->classifyPatientRisk($patientID);

            // Normalize patient URI for inferred triples query
            $patientURI = $patientID;
            if (!str_starts_with($patientID, 'http')) {
                $patientURI = CDS_NAMESPACE . $patientID;
            }

            // Get inferred triples structured data
            $inferredTriples = $this->inferenceService->getInferredTriples($patientURI);

            // Get drug interactions
            $drugInteractions = $this->inferenceService->checkDrugInteractions($patientID);

            // Generate flat triples array for the UI
            $triples = [];
            if ($inferredTriples['success']) {
                $data = $inferredTriples['data'];
                foreach ($data['classTypes'] as $type) {
                    $triples[] = [
                        'subject'   => $patientURI,
                        'predicate' => 'rdf:type',
                        'object'    => CDS_NAMESPACE . $type
                    ];
                }
                if (!empty($data['riskLevel'])) {
                    $triples[] = [
                        'subject'   => $patientURI,
                        'predicate' => CDS_NAMESPACE . 'hasRiskLevel',
                        'object'    => CDS_NAMESPACE . $data['riskLevel'] . 'Risk_Instance'
                    ];
                }
                foreach ($data['recommendedMedications'] as $med) {
                    $triples[] = [
                        'subject'   => $patientURI,
                        'predicate' => CDS_NAMESPACE . 'recommendedMedication',
                        'object'    => $med
                    ];
                }
                if (!empty($data['requiresSpecialist'])) {
                    $triples[] = [
                        'subject'   => $patientURI,
                        'predicate' => CDS_NAMESPACE . 'requiresSpecialistReview',
                        'object'    => 'true'
                    ];
                }
                foreach ($data['relatedConditions'] as $cond) {
                    $triples[] = [
                        'subject'   => $patientURI,
                        'predicate' => CDS_NAMESPACE . 'hasRelatedCondition',
                        'object'    => $cond
                    ];
                }
            }

            if ($drugInteractions['success'] && !empty($drugInteractions['data']['interactions'])) {
                foreach ($drugInteractions['data']['interactions'] as $interaction) {
                    $triples[] = [
                        'subject'   => CDS_NAMESPACE . $interaction['medication1'],
                        'predicate' => CDS_NAMESPACE . 'interactsWith',
                        'object'    => CDS_NAMESPACE . $interaction['medication2']
                    ];
                }
            }

            // Get explanations
            $explanations = $this->inferenceService->explainInference($patientID);

            return [
                'success' => true,
                'triples' => $triples,
                'explanations' => $explanations['success'] ? $explanations['data']['explanations'] : [],
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

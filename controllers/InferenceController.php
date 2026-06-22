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

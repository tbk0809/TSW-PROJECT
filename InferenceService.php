<?php
/**
 * Smart Clinical Decision System - Inference Service
 *
 * Service for executing OWL-based reasoning and custom inference rules
 * against the Fuseki triple store. Handles patient risk classification,
 * drug interaction checks, and inference explanation generation.
 *
 * @package SmartCDS\Services
 * @version 1.0.0
 */

namespace Services;

class InferenceService
{
    /** @var SparqlService Instance of the SPARQL service */
    private SparqlService $sparqlService;

    /** @var string CDS ontology namespace prefix declaration for SPARQL queries */
    private string $prefixes;

    /**
     * Construct the InferenceService.
     *
     * @param SparqlService|null $sparqlService Optional SparqlService instance
     */
    public function __construct(?SparqlService $sparqlService = null)
    {
        $this->sparqlService = $sparqlService ?? new SparqlService();
        $this->prefixes = "PREFIX cds: <http://www.semanticweb.org/clinical-decision-system#>\n"
            . "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n"
            . "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\n"
            . "PREFIX owl: <http://www.w3.org/2002/07/owl#>\n"
            . "PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>\n";
    }

    /**
     * Run reasoning and materialize inferred triples.
     *
     * Uses SPARQL CONSTRUCT queries to generate new triples based on
     * custom inference rules. These supplement the built-in OWL reasoning
     * provided by Fuseki's reasoner.
     *
     * Inference rules applied:
     * 1. Patients with 3+ symptoms → HighRiskPatient
     * 2. Patients with diagnosed diseases → DiagnosedPatient
     * 3. Patients who are both HighRisk and Diagnosed → ComplexCase
     * 4. Transitivity of relatedCondition relationships
     *
     * @return array Structured response with counts of inferred triples per rule
     */
    public function runReasoning(): array
    {
        $results = [];

        // ── Rule 1: Classify patients with 3+ symptoms as HighRiskPatient ────
        $rule1 = $this->prefixes . "
            INSERT {
                ?patient rdf:type cds:HighRiskPatient .
                ?patient cds:riskLevel \"High\" .
            }
            WHERE {
                ?patient rdf:type cds:Patient .
                {
                    SELECT ?patient (COUNT(?symptom) AS ?symptomCount)
                    WHERE {
                        ?patient cds:hasSymptom ?symptom .
                    }
                    GROUP BY ?patient
                    HAVING (COUNT(?symptom) >= 3)
                }
                FILTER NOT EXISTS { ?patient rdf:type cds:HighRiskPatient }
            }
        ";

        $r1 = $this->sparqlService->update($rule1);
        $results[] = [
            'rule'    => 'HighRiskPatient classification (3+ symptoms)',
            'success' => $r1['success'],
            'detail'  => $r1['success'] ? 'Applied successfully.' : ($r1['error'] ?? 'Failed'),
        ];

        // ── Rule 2: Classify patients with diagnosed diseases ────────────────
        $rule2 = $this->prefixes . "
            INSERT {
                ?patient rdf:type cds:DiagnosedPatient .
            }
            WHERE {
                ?patient rdf:type cds:Patient .
                ?patient cds:hasDiagnosis ?disease .
                ?disease rdf:type cds:Disease .
                FILTER NOT EXISTS { ?patient rdf:type cds:DiagnosedPatient }
            }
        ";

        $r2 = $this->sparqlService->update($rule2);
        $results[] = [
            'rule'    => 'DiagnosedPatient classification',
            'success' => $r2['success'],
            'detail'  => $r2['success'] ? 'Applied successfully.' : ($r2['error'] ?? 'Failed'),
        ];

        // ── Rule 3: Classify complex cases (HighRisk + Diagnosed) ────────────
        $rule3 = $this->prefixes . "
            INSERT {
                ?patient rdf:type cds:ComplexCase .
                ?patient cds:requiresSpecialistReview \"true\"^^xsd:boolean .
            }
            WHERE {
                ?patient rdf:type cds:HighRiskPatient .
                ?patient rdf:type cds:DiagnosedPatient .
                FILTER NOT EXISTS { ?patient rdf:type cds:ComplexCase }
            }
        ";

        $r3 = $this->sparqlService->update($rule3);
        $results[] = [
            'rule'    => 'ComplexCase classification (HighRisk + Diagnosed)',
            'success' => $r3['success'],
            'detail'  => $r3['success'] ? 'Applied successfully.' : ($r3['error'] ?? 'Failed'),
        ];

        // ── Rule 4: Infer related conditions transitively ────────────────────
        $rule4 = $this->prefixes . "
            INSERT {
                ?disease1 cds:relatedCondition ?disease3 .
            }
            WHERE {
                ?disease1 cds:relatedCondition ?disease2 .
                ?disease2 cds:relatedCondition ?disease3 .
                FILTER (?disease1 != ?disease3)
                FILTER NOT EXISTS { ?disease1 cds:relatedCondition ?disease3 }
            }
        ";

        $r4 = $this->sparqlService->update($rule4);
        $results[] = [
            'rule'    => 'Transitive relatedCondition inference',
            'success' => $r4['success'],
            'detail'  => $r4['success'] ? 'Applied successfully.' : ($r4['error'] ?? 'Failed'),
        ];

        // ── Rule 5: Infer medication recommendations based on diagnosis ──────
        $rule5 = $this->prefixes . "
            INSERT {
                ?patient cds:recommendedMedication ?medication .
            }
            WHERE {
                ?patient rdf:type cds:Patient .
                ?patient cds:hasDiagnosis ?disease .
                ?medication cds:treatsDisease ?disease .
                FILTER NOT EXISTS { ?patient cds:recommendedMedication ?medication }
                FILTER NOT EXISTS {
                    ?patient cds:takesMedication ?currentMed .
                    ?currentMed cds:contraindicatedWith ?medication .
                }
            }
        ";

        $r5 = $this->sparqlService->update($rule5);
        $results[] = [
            'rule'    => 'Medication recommendation inference',
            'success' => $r5['success'],
            'detail'  => $r5['success'] ? 'Applied successfully.' : ($r5['error'] ?? 'Failed'),
        ];

        $allSuccess = array_reduce($results, fn($carry, $item) => $carry && $item['success'], true);

        return [
            'success' => $allSuccess,
            'message' => $allSuccess
                ? 'All inference rules applied successfully.'
                : 'Some inference rules failed. Check individual results.',
            'rules'   => $results,
        ];
    }

    /**
     * Retrieve all inferred triples for a specific patient.
     *
     * Queries for inferred class memberships, risk levels, recommended
     * medications, and specialist review flags.
     *
     * @param string $patientURI Full URI or local name of the patient
     *
     * @return array Structured response with inferred triple data
     */
    public function getInferredTriples(string $patientURI): array
    {
        // Normalize the patient URI
        if (!str_starts_with($patientURI, 'http')) {
            $patientURI = CDS_NAMESPACE . $patientURI;
        }

        $sparql = $this->prefixes . "
            SELECT ?type ?riskLevel ?recommendedMed ?recommendedMedName
                   ?requiresSpecialist ?relatedCondition ?relatedConditionName
            WHERE {
                BIND(<{$patientURI}> AS ?patient)

                OPTIONAL { ?patient rdf:type ?type . }
                OPTIONAL { ?patient cds:riskLevel ?riskLevel . }
                OPTIONAL {
                    ?patient cds:recommendedMedication ?recommendedMed .
                    OPTIONAL { ?recommendedMed cds:medicationName ?recommendedMedName . }
                }
                OPTIONAL { ?patient cds:requiresSpecialistReview ?requiresSpecialist . }
                OPTIONAL {
                    ?patient cds:hasDiagnosis ?disease .
                    ?disease cds:relatedCondition ?relatedCondition .
                    OPTIONAL { ?relatedCondition cds:diseaseName ?relatedConditionName . }
                }
            }
        ";

        $result = $this->sparqlService->query($sparql);

        if (!$result['success']) {
            return $result;
        }

        $bindings = $result['data']['results']['bindings'] ?? [];

        // Parse the results into structured data
        $inferred = [
            'patientURI'           => $patientURI,
            'classTypes'           => [],
            'riskLevel'            => null,
            'recommendedMedications' => [],
            'requiresSpecialist'   => false,
            'relatedConditions'    => [],
        ];

        foreach ($bindings as $row) {
            // Collect class types
            if (!empty($row['type']['value'])) {
                $type = $row['type']['value'];
                if (str_contains($type, 'clinical-decision-system#')) {
                    $localType = str_replace(CDS_NAMESPACE, '', $type);
                    if (!in_array($localType, $inferred['classTypes'])) {
                        $inferred['classTypes'][] = $localType;
                    }
                }
            }

            // Risk level
            if (!empty($row['riskLevel']['value'])) {
                $inferred['riskLevel'] = $row['riskLevel']['value'];
            }

            // Recommended medications
            if (!empty($row['recommendedMed']['value'])) {
                $medURI = $row['recommendedMed']['value'];
                $medName = $row['recommendedMedName']['value'] ?? str_replace(CDS_NAMESPACE, '', $medURI);
                if (!isset($inferred['recommendedMedications'][$medURI])) {
                    $inferred['recommendedMedications'][$medURI] = $medName;
                }
            }

            // Specialist review
            if (!empty($row['requiresSpecialist']['value'])) {
                $inferred['requiresSpecialist'] = $row['requiresSpecialist']['value'] === 'true';
            }

            // Related conditions
            if (!empty($row['relatedCondition']['value'])) {
                $condURI = $row['relatedCondition']['value'];
                $condName = $row['relatedConditionName']['value'] ?? str_replace(CDS_NAMESPACE, '', $condURI);
                if (!isset($inferred['relatedConditions'][$condURI])) {
                    $inferred['relatedConditions'][$condURI] = $condName;
                }
            }
        }

        // Convert associative arrays to indexed arrays for cleaner JSON
        $inferred['recommendedMedications'] = array_values(
            array_map(
                fn($uri, $name) => ['uri' => $uri, 'name' => $name],
                array_keys($inferred['recommendedMedications']),
                array_values($inferred['recommendedMedications'])
            )
        );

        $inferred['relatedConditions'] = array_values(
            array_map(
                fn($uri, $name) => ['uri' => $uri, 'name' => $name],
                array_keys($inferred['relatedConditions']),
                array_values($inferred['relatedConditions'])
            )
        );

        return [
            'success' => true,
            'data'    => $inferred,
        ];
    }

    /**
     * Classify a patient's risk level based on OWL class definitions.
     *
     * Determines if the patient is classified as:
     * - HighRiskPatient: Has 3+ symptoms or critical lab results
     * - DiagnosedPatient: Has at least one diagnosed disease
     * - ComplexCase: Both high risk and diagnosed
     *
     * @param string $patientID The patient identifier (local name or full URI)
     *
     * @return array Structured response with classification results
     */
    public function classifyPatientRisk(string $patientID): array
    {
        // Normalize patient reference
        if (!str_starts_with($patientID, 'http')) {
            $patientURI = CDS_NAMESPACE . $patientID;
        } else {
            $patientURI = $patientID;
        }

        // Query for classification data
        $sparql = $this->prefixes . "
            SELECT
                ?patientName
                (COUNT(DISTINCT ?symptom) AS ?symptomCount)
                (COUNT(DISTINCT ?disease) AS ?diseaseCount)
                (COUNT(DISTINCT ?medication) AS ?medicationCount)
                ?riskLevel
            WHERE {
                BIND(<{$patientURI}> AS ?patient)
                ?patient rdf:type cds:Patient .
                OPTIONAL { ?patient cds:patientName ?patientName . }
                OPTIONAL { ?patient cds:hasSymptom ?symptom . }
                OPTIONAL { ?patient cds:hasDiagnosis ?disease . }
                OPTIONAL { ?patient cds:takesMedication ?medication . }
                OPTIONAL { ?patient cds:riskLevel ?riskLevel . }
            }
            GROUP BY ?patientName ?riskLevel
        ";

        $result = $this->sparqlService->query($sparql);

        if (!$result['success']) {
            return $result;
        }

        $bindings = $result['data']['results']['bindings'] ?? [];

        if (empty($bindings)) {
            return [
                'success' => false,
                'error'   => "Patient not found: {$patientID}",
            ];
        }

        $row = $bindings[0];
        $patientName   = $row['patientName']['value'] ?? $patientID;
        $symptomCount  = (int) ($row['symptomCount']['value'] ?? 0);
        $diseaseCount  = (int) ($row['diseaseCount']['value'] ?? 0);
        $medCount      = (int) ($row['medicationCount']['value'] ?? 0);
        $existingRisk  = $row['riskLevel']['value'] ?? null;

        // Determine classification
        $classifications = [];
        $riskLevel = 'Low';

        // HighRiskPatient: 3+ symptoms
        if ($symptomCount >= 3) {
            $classifications[] = 'HighRiskPatient';
            $riskLevel = 'High';
        } elseif ($symptomCount >= 2) {
            $riskLevel = 'Medium';
        }

        // DiagnosedPatient: has diagnosed diseases
        if ($diseaseCount > 0) {
            $classifications[] = 'DiagnosedPatient';
        }

        // ComplexCase: both high risk and diagnosed
        if (in_array('HighRiskPatient', $classifications) && in_array('DiagnosedPatient', $classifications)) {
            $classifications[] = 'ComplexCase';
        }

        // Use existing risk level if already set in the ontology
        if ($existingRisk !== null) {
            $riskLevel = $existingRisk;
        }

        return [
            'success' => true,
            'data'    => [
                'patientURI'      => $patientURI,
                'patientName'     => $patientName,
                'symptomCount'    => $symptomCount,
                'diseaseCount'    => $diseaseCount,
                'medicationCount' => $medCount,
                'riskLevel'       => $riskLevel,
                'classifications' => $classifications,
                'isHighRisk'      => in_array('HighRiskPatient', $classifications),
                'isDiagnosed'     => in_array('DiagnosedPatient', $classifications),
                'isComplexCase'   => in_array('ComplexCase', $classifications),
            ],
        ];
    }

    /**
     * Check for drug interactions and contraindications for a patient.
     *
     * Queries the ontology for medications prescribed to the patient and
     * checks contraindicatedWith and interactsWith relationships between them.
     *
     * @param string $patientID The patient identifier
     *
     * @return array Structured response with drug interaction warnings:
     *               ['success' => true, 'data' => ['interactions' => [...], 'contraindications' => [...]]]
     */
    public function checkDrugInteractions(string $patientID): array
    {
        if (!str_starts_with($patientID, 'http')) {
            $patientURI = CDS_NAMESPACE . $patientID;
        } else {
            $patientURI = $patientID;
        }

        // Query for contraindications between patient's medications
        $sparqlContra = $this->prefixes . "
            SELECT DISTINCT ?med1 ?med1Name ?med2 ?med2Name ?severity
            WHERE {
                BIND(<{$patientURI}> AS ?patient)
                ?patient cds:takesMedication ?med1 .
                ?patient cds:takesMedication ?med2 .
                ?med1 cds:contraindicatedWith ?med2 .
                FILTER (?med1 != ?med2)
                OPTIONAL { ?med1 cds:medicationName ?med1Name . }
                OPTIONAL { ?med2 cds:medicationName ?med2Name . }
                OPTIONAL { ?med1 cds:contraindicationSeverity ?severity . }
            }
        ";

        $contraResult = $this->sparqlService->query($sparqlContra);

        // Query for drug interactions
        $sparqlInteract = $this->prefixes . "
            SELECT DISTINCT ?med1 ?med1Name ?med2 ?med2Name ?interactionType ?description
            WHERE {
                BIND(<{$patientURI}> AS ?patient)
                ?patient cds:takesMedication ?med1 .
                ?patient cds:takesMedication ?med2 .
                ?med1 cds:interactsWith ?med2 .
                FILTER (?med1 != ?med2)
                OPTIONAL { ?med1 cds:medicationName ?med1Name . }
                OPTIONAL { ?med2 cds:medicationName ?med2Name . }
                OPTIONAL { ?med1 cds:interactionType ?interactionType . }
                OPTIONAL { ?med1 cds:interactionDescription ?description . }
            }
        ";

        $interactResult = $this->sparqlService->query($sparqlInteract);

        // Also check recommended medications against current medications
        $sparqlRecommendContra = $this->prefixes . "
            SELECT DISTINCT ?currentMed ?currentMedName ?recommendedMed ?recommendedMedName
            WHERE {
                BIND(<{$patientURI}> AS ?patient)
                ?patient cds:takesMedication ?currentMed .
                ?patient cds:recommendedMedication ?recommendedMed .
                ?currentMed cds:contraindicatedWith ?recommendedMed .
                OPTIONAL { ?currentMed cds:medicationName ?currentMedName . }
                OPTIONAL { ?recommendedMed cds:medicationName ?recommendedMedName . }
            }
        ";

        $recommendContraResult = $this->sparqlService->query($sparqlRecommendContra);

        // Parse contraindications
        $contraindications = [];
        if ($contraResult['success'] && !empty($contraResult['data']['results']['bindings'])) {
            foreach ($contraResult['data']['results']['bindings'] as $row) {
                $med1Name = $row['med1Name']['value'] ?? str_replace(CDS_NAMESPACE, '', $row['med1']['value']);
                $med2Name = $row['med2Name']['value'] ?? str_replace(CDS_NAMESPACE, '', $row['med2']['value']);
                $severity = $row['severity']['value'] ?? 'Unknown';

                // Avoid duplicate pairs (A↔B and B↔A)
                $pairKey = min($med1Name, $med2Name) . '|' . max($med1Name, $med2Name);
                if (!isset($contraindications[$pairKey])) {
                    $contraindications[$pairKey] = [
                        'medication1' => $med1Name,
                        'medication2' => $med2Name,
                        'severity'    => $severity,
                        'warning'     => "CONTRAINDICATION: {$med1Name} is contraindicated with {$med2Name}.",
                    ];
                }
            }
        }

        // Parse interactions
        $interactions = [];
        if ($interactResult['success'] && !empty($interactResult['data']['results']['bindings'])) {
            foreach ($interactResult['data']['results']['bindings'] as $row) {
                $med1Name = $row['med1Name']['value'] ?? str_replace(CDS_NAMESPACE, '', $row['med1']['value']);
                $med2Name = $row['med2Name']['value'] ?? str_replace(CDS_NAMESPACE, '', $row['med2']['value']);
                $type     = $row['interactionType']['value'] ?? 'Unknown';
                $desc     = $row['description']['value'] ?? '';

                $pairKey = min($med1Name, $med2Name) . '|' . max($med1Name, $med2Name);
                if (!isset($interactions[$pairKey])) {
                    $interactions[$pairKey] = [
                        'medication1'     => $med1Name,
                        'medication2'     => $med2Name,
                        'interactionType' => $type,
                        'description'     => $desc,
                        'warning'         => "INTERACTION: {$med1Name} interacts with {$med2Name} ({$type}).",
                    ];
                }
            }
        }

        // Parse recommendation contraindications
        $recommendationWarnings = [];
        if ($recommendContraResult['success'] && !empty($recommendContraResult['data']['results']['bindings'])) {
            foreach ($recommendContraResult['data']['results']['bindings'] as $row) {
                $currentName   = $row['currentMedName']['value'] ?? str_replace(CDS_NAMESPACE, '', $row['currentMed']['value']);
                $recommendedName = $row['recommendedMedName']['value'] ?? str_replace(CDS_NAMESPACE, '', $row['recommendedMed']['value']);

                $recommendationWarnings[] = [
                    'currentMedication'     => $currentName,
                    'recommendedMedication' => $recommendedName,
                    'warning'               => "WARNING: Recommended medication {$recommendedName} is contraindicated with current medication {$currentName}.",
                ];
            }
        }

        $hasWarnings = !empty($contraindications) || !empty($interactions) || !empty($recommendationWarnings);

        return [
            'success' => true,
            'data'    => [
                'patientID'              => $patientID,
                'hasWarnings'            => $hasWarnings,
                'contraindications'      => array_values($contraindications),
                'interactions'           => array_values($interactions),
                'recommendationWarnings' => $recommendationWarnings,
                'totalWarnings'          => count($contraindications) + count($interactions) + count($recommendationWarnings),
            ],
        ];
    }

    /**
     * Generate a human-readable explanation of all inferences made for a patient.
     *
     * Combines classification, drug interaction, and recommendation data
     * into an array of explanation strings that can be displayed in the UI.
     *
     * @param string $patientID The patient identifier
     *
     * @return array Structured response with human-readable explanations:
     *               ['success' => true, 'data' => ['explanations' => [...], 'summary' => '...']]
     */
    public function explainInference(string $patientID): array
    {
        $explanations = [];

        // Step 1: Get classification data
        $classResult = $this->classifyPatientRisk($patientID);
        if ($classResult['success']) {
            $data = $classResult['data'];
            $patientName = $data['patientName'];

            $explanations[] = [
                'category' => 'Patient Overview',
                'text'     => "Patient {$patientName} has {$data['symptomCount']} symptom(s), "
                    . "{$data['diseaseCount']} diagnosed disease(s), and {$data['medicationCount']} medication(s).",
            ];

            // Risk classification explanation
            if ($data['isHighRisk']) {
                $explanations[] = [
                    'category' => 'Risk Classification',
                    'text'     => "⚠️ {$patientName} is classified as HIGH RISK because they present "
                        . "{$data['symptomCount']} symptoms (threshold: 3 or more).",
                ];
            } else {
                $explanations[] = [
                    'category' => 'Risk Classification',
                    'text'     => "✅ {$patientName} has a risk level of {$data['riskLevel']}.",
                ];
            }

            // Diagnosis classification
            if ($data['isDiagnosed']) {
                $explanations[] = [
                    'category' => 'Diagnosis Status',
                    'text'     => "📋 {$patientName} is a Diagnosed Patient with {$data['diseaseCount']} disease(s) on record.",
                ];
            } else {
                $explanations[] = [
                    'category' => 'Diagnosis Status',
                    'text'     => "ℹ️ {$patientName} does not have any diagnosed diseases on record.",
                ];
            }

            // Complex case
            if ($data['isComplexCase']) {
                $explanations[] = [
                    'category' => 'Complex Case',
                    'text'     => "🔴 {$patientName} is classified as a COMPLEX CASE (High Risk + Diagnosed). "
                        . "A specialist review is recommended.",
                ];
            }

            // OWL classifications
            if (!empty($data['classifications'])) {
                $explanations[] = [
                    'category' => 'OWL Classifications',
                    'text'     => "Based on OWL reasoning, {$patientName} belongs to: "
                        . implode(', ', $data['classifications']) . '.',
                ];
            }
        }

        // Step 2: Get drug interactions
        $drugResult = $this->checkDrugInteractions($patientID);
        if ($drugResult['success']) {
            $drugData = $drugResult['data'];

            if ($drugData['hasWarnings']) {
                $explanations[] = [
                    'category' => 'Drug Safety',
                    'text'     => "⚠️ Found {$drugData['totalWarnings']} drug safety warning(s) for this patient.",
                ];

                foreach ($drugData['contraindications'] as $contra) {
                    $explanations[] = [
                        'category' => 'Contraindication',
                        'text'     => "🚫 {$contra['warning']}",
                    ];
                }

                foreach ($drugData['interactions'] as $interact) {
                    $explanations[] = [
                        'category' => 'Drug Interaction',
                        'text'     => "⚡ {$interact['warning']}",
                    ];
                }

                foreach ($drugData['recommendationWarnings'] as $recWarn) {
                    $explanations[] = [
                        'category' => 'Recommendation Warning',
                        'text'     => "📝 {$recWarn['warning']}",
                    ];
                }
            } else {
                $explanations[] = [
                    'category' => 'Drug Safety',
                    'text'     => '✅ No drug interactions or contraindications found for this patient.',
                ];
            }
        }

        // Step 3: Get inferred triples
        if (!str_starts_with($patientID, 'http')) {
            $patientURI = CDS_NAMESPACE . $patientID;
        } else {
            $patientURI = $patientID;
        }

        $inferredResult = $this->getInferredTriples($patientURI);
        if ($inferredResult['success']) {
            $inferredData = $inferredResult['data'];

            if (!empty($inferredData['recommendedMedications'])) {
                $medNames = array_map(fn($m) => $m['name'], $inferredData['recommendedMedications']);
                $explanations[] = [
                    'category' => 'Recommended Medications',
                    'text'     => '💊 Based on diagnoses and ontology reasoning, recommended medications: '
                        . implode(', ', $medNames) . '.',
                ];
            }

            if (!empty($inferredData['relatedConditions'])) {
                $condNames = array_map(fn($c) => $c['name'], $inferredData['relatedConditions']);
                $explanations[] = [
                    'category' => 'Related Conditions',
                    'text'     => '🔗 Related conditions identified through ontology reasoning: '
                        . implode(', ', $condNames) . '.',
                ];
            }
        }

        // Generate summary
        $warningCount = 0;
        foreach ($explanations as $exp) {
            if (str_contains($exp['text'], '⚠️') || str_contains($exp['text'], '🚫') || str_contains($exp['text'], '🔴')) {
                $warningCount++;
            }
        }

        $summary = count($explanations) . " inference result(s) generated";
        if ($warningCount > 0) {
            $summary .= " with {$warningCount} warning(s) requiring attention";
        }
        $summary .= '.';

        return [
            'success' => true,
            'data'    => [
                'patientID'    => $patientID,
                'explanations' => $explanations,
                'summary'      => $summary,
                'warningCount' => $warningCount,
                'totalFacts'   => count($explanations),
            ],
        ];
    }
}

<?php
/**
 * Smart Clinical Decision System - Patient Controller
 *
 * Handles all patient-related operations including listing, searching,
 * detail retrieval, risk filtering, and dashboard statistics.
 * All data is retrieved from the Fuseki SPARQL endpoint.
 *
 * @package SmartCDS\Controllers
 * @version 1.0.0
 */

namespace Controllers;

use Services\SparqlService;
use Models\Patient;

class PatientController
{
    /** @var SparqlService Instance of the SPARQL service */
    private SparqlService $sparqlService;

    /** @var string SPARQL PREFIX declarations used across all queries */
    private string $prefixes;

    /**
     * Construct the PatientController.
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
     * List all patients with basic information.
     *
     * Retrieves a summary of all patients including name, age, risk level,
     * and primary disease. Results are grouped by patient to avoid duplicate rows.
     *
     * @return array List of patient arrays with keys: uri, patientID, name, age, riskLevel, disease
     */
    public function index(): array
    {
        $sparql = $this->prefixes . "
            SELECT ?patient ?patientID ?patientName ?age ?riskLevel
                   (GROUP_CONCAT(DISTINCT ?diseaseName; SEPARATOR=', ') AS ?diseases)
            WHERE {
                ?patient rdf:type cds:Patient .
                OPTIONAL { ?patient cds:patientID ?patientID . }
                OPTIONAL { ?patient cds:patientName ?patientName . }
                OPTIONAL { ?patient cds:patientAge ?age . }
                OPTIONAL { 
                    ?patient cds:hasRiskLevel ?riskLevelInst .
                    ?riskLevelInst rdfs:label ?riskLevel .
                }
                OPTIONAL {
                    ?patient cds:hasDiagnosis ?disease .
                    ?disease cds:diseaseName ?diseaseName .
                }
            }
            GROUP BY ?patient ?patientID ?patientName ?age ?riskLevel
            ORDER BY ?patientName
        ";

        $result = $this->sparqlService->query($sparql);

        if (!$result['success']) {
            return [];
        }

        $patients = [];
        $bindings = $result['data']['results']['bindings'] ?? [];

        foreach ($bindings as $row) {
            $patients[] = [
                'uri'       => $row['patient']['value'] ?? '',
                'patientID' => $row['patientID']['value'] ?? $this->extractLocalName($row['patient']['value'] ?? ''),
                'name'      => $row['patientName']['value'] ?? 'Unknown',
                'age'       => $row['age']['value'] ?? 'N/A',
                'riskLevel' => $row['riskLevel']['value'] ?? 'Not Assessed',
                'diseases'  => $row['diseases']['value'] ?? 'None diagnosed',
            ];
        }

        return $patients;
    }

    /**
     * Get full details for a specific patient.
     *
     * Retrieves comprehensive patient information including demographics,
     * symptoms, diseases, medications, lab results, risk level, and clinical records.
     * Uses multiple OPTIONAL blocks to handle patients with incomplete data.
     *
     * @param string $patientID The patient identifier (local name or full URI)
     *
     * @return array|null Detailed patient data array, or null if not found
     */
    public function show(string $patientID): ?array
    {
        // Build the patient URI filter
        $patientFilter = $this->buildPatientFilter($patientID);

        // Query 1: Basic patient info
        $sparqlBasic = $this->prefixes . "
            SELECT ?patient ?patientID ?patientName ?age ?gender ?bloodType
                   ?riskLevel ?admissionDate ?contactNumber ?address
            WHERE {
                {$patientFilter}
                ?patient rdf:type cds:Patient .
                OPTIONAL { ?patient cds:patientID ?patientID . }
                OPTIONAL { ?patient cds:patientName ?patientName . }
                OPTIONAL { ?patient cds:patientAge ?age . }
                OPTIONAL { ?patient cds:gender ?gender . }
                OPTIONAL { ?patient cds:bloodType ?bloodType . }
                OPTIONAL { 
                    ?patient cds:hasRiskLevel ?riskLevelInst .
                    ?riskLevelInst rdfs:label ?riskLevel . 
                }
                OPTIONAL { ?patient cds:admissionDate ?admissionDate . }
                OPTIONAL { ?patient cds:contactNumber ?contactNumber . }
                OPTIONAL { ?patient cds:address ?address . }
            }
            LIMIT 1
        ";

        $basicResult = $this->sparqlService->query($sparqlBasic);

        if (!$basicResult['success'] || empty($basicResult['data']['results']['bindings'])) {
            return null;
        }

        $basicRow = $basicResult['data']['results']['bindings'][0];

        $patient = [
            'uri'           => $basicRow['patient']['value'] ?? '',
            'patientID'     => $basicRow['patientID']['value'] ?? $patientID,
            'name'          => $basicRow['patientName']['value'] ?? 'Unknown',
            'age'           => $basicRow['age']['value'] ?? 'N/A',
            'gender'        => $basicRow['gender']['value'] ?? 'N/A',
            'bloodType'     => $basicRow['bloodType']['value'] ?? 'N/A',
            'riskLevel'     => $basicRow['riskLevel']['value'] ?? 'Not Assessed',
            'admissionDate' => $basicRow['admissionDate']['value'] ?? 'N/A',
            'contactNumber' => $basicRow['contactNumber']['value'] ?? 'N/A',
            'address'       => $basicRow['address']['value'] ?? 'N/A',
            'symptoms'      => [],
            'diseases'      => [],
            'medications'   => [],
            'labResults'    => [],
            'clinicalRecords' => [],
        ];

        $patientURI = $patient['uri'];

        // Query 2: Symptoms
        $sparqlSymptoms = $this->prefixes . "
            SELECT DISTINCT ?symptom ?symptomName ?severity ?bodyPart
            WHERE {
                <{$patientURI}> cds:hasSymptom ?symptom .
                OPTIONAL { ?symptom cds:symptomName ?symptomName . }
                OPTIONAL { ?symptom cds:severity ?severity . }
                OPTIONAL { ?symptom cds:affectsBodyPart ?bodyPart . }
            }
            ORDER BY ?symptomName
        ";

        $symptomResult = $this->sparqlService->query($sparqlSymptoms);
        if ($symptomResult['success'] && !empty($symptomResult['data']['results']['bindings'])) {
            foreach ($symptomResult['data']['results']['bindings'] as $row) {
                $patient['symptoms'][] = [
                    'uri'      => $row['symptom']['value'] ?? '',
                    'name'     => $row['symptomName']['value'] ?? $this->extractLocalName($row['symptom']['value'] ?? ''),
                    'severity' => $row['severity']['value'] ?? 'N/A',
                    'bodyPart' => $row['bodyPart']['value'] ?? 'N/A',
                ];
            }
        }

        // Query 3: Diseases / Diagnoses
        $sparqlDiseases = $this->prefixes . "
            SELECT DISTINCT ?disease ?diseaseName ?diagnosisDate ?stage ?description
            WHERE {
                <{$patientURI}> cds:hasDiagnosis ?disease .
                OPTIONAL { ?disease cds:diseaseName ?diseaseName . }
                OPTIONAL { ?disease cds:diagnosisDate ?diagnosisDate . }
                OPTIONAL { ?disease cds:stage ?stage . }
                OPTIONAL { ?disease cds:description ?description . }
            }
            ORDER BY ?diseaseName
        ";

        $diseaseResult = $this->sparqlService->query($sparqlDiseases);
        if ($diseaseResult['success'] && !empty($diseaseResult['data']['results']['bindings'])) {
            foreach ($diseaseResult['data']['results']['bindings'] as $row) {
                $patient['diseases'][] = [
                    'uri'           => $row['disease']['value'] ?? '',
                    'name'          => $row['diseaseName']['value'] ?? $this->extractLocalName($row['disease']['value'] ?? ''),
                    'diagnosisDate' => $row['diagnosisDate']['value'] ?? 'N/A',
                    'stage'         => $row['stage']['value'] ?? 'N/A',
                    'description'   => $row['description']['value'] ?? 'N/A',
                ];
            }
        }

        // Query 4: Medications
        $sparqlMedications = $this->prefixes . "
            SELECT DISTINCT ?medication ?medicationName ?dosage ?frequency ?prescribedDate
            WHERE {
                <{$patientURI}> cds:takesMedication ?medication .
                OPTIONAL { ?medication cds:medicationName ?medicationName . }
                OPTIONAL { ?medication cds:dosage ?dosage . }
                OPTIONAL { ?medication cds:frequency ?frequency . }
                OPTIONAL { ?medication cds:prescribedDate ?prescribedDate . }
            }
            ORDER BY ?medicationName
        ";

        $medResult = $this->sparqlService->query($sparqlMedications);
        if ($medResult['success'] && !empty($medResult['data']['results']['bindings'])) {
            foreach ($medResult['data']['results']['bindings'] as $row) {
                $patient['medications'][] = [
                    'uri'           => $row['medication']['value'] ?? '',
                    'name'          => $row['medicationName']['value'] ?? $this->extractLocalName($row['medication']['value'] ?? ''),
                    'dosage'        => $row['dosage']['value'] ?? 'N/A',
                    'frequency'     => $row['frequency']['value'] ?? 'N/A',
                    'prescribedDate' => $row['prescribedDate']['value'] ?? 'N/A',
                ];
            }
        }

        // Query 5: Lab Results
        $sparqlLabResults = $this->prefixes . "
            SELECT DISTINCT ?labResult ?testName ?testValue ?unit ?referenceRange
                            ?testDate ?status
            WHERE {
                <{$patientURI}> cds:hasLabResult ?labResult .
                OPTIONAL { ?labResult cds:testName ?testName . }
                OPTIONAL { ?labResult cds:testValue ?testValue . }
                OPTIONAL { ?labResult cds:unit ?unit . }
                OPTIONAL { ?labResult cds:referenceRange ?referenceRange . }
                OPTIONAL { ?labResult cds:testDate ?testDate . }
                OPTIONAL { ?labResult cds:status ?status . }
            }
            ORDER BY ?testDate
        ";

        $labResult = $this->sparqlService->query($sparqlLabResults);
        if ($labResult['success'] && !empty($labResult['data']['results']['bindings'])) {
            foreach ($labResult['data']['results']['bindings'] as $row) {
                $patient['labResults'][] = [
                    'uri'            => $row['labResult']['value'] ?? '',
                    'testName'       => $row['testName']['value'] ?? 'N/A',
                    'testValue'      => $row['testValue']['value'] ?? 'N/A',
                    'unit'           => $row['unit']['value'] ?? '',
                    'referenceRange' => $row['referenceRange']['value'] ?? 'N/A',
                    'testDate'       => $row['testDate']['value'] ?? 'N/A',
                    'status'         => $row['status']['value'] ?? 'N/A',
                ];
            }
        }

        // Query 6: Clinical Records
        $sparqlRecords = $this->prefixes . "
            SELECT DISTINCT ?record ?recordType ?recordDate ?notes ?attendingDoctor
            WHERE {
                <{$patientURI}> cds:hasClinicalRecord ?record .
                OPTIONAL { ?record cds:recordType ?recordType . }
                OPTIONAL { ?record cds:recordDate ?recordDate . }
                OPTIONAL { ?record cds:notes ?notes . }
                OPTIONAL { ?record cds:attendingDoctor ?attendingDoctor . }
            }
            ORDER BY DESC(?recordDate)
        ";

        $recordResult = $this->sparqlService->query($sparqlRecords);
        if ($recordResult['success'] && !empty($recordResult['data']['results']['bindings'])) {
            foreach ($recordResult['data']['results']['bindings'] as $row) {
                $patient['clinicalRecords'][] = [
                    'uri'             => $row['record']['value'] ?? '',
                    'recordType'      => $row['recordType']['value'] ?? 'N/A',
                    'recordDate'      => $row['recordDate']['value'] ?? 'N/A',
                    'notes'           => $row['notes']['value'] ?? 'N/A',
                    'attendingDoctor' => $row['attendingDoctor']['value'] ?? 'N/A',
                ];
            }
        }

        return $patient;
    }

    /**
     * Search patients by name using SPARQL FILTER regex.
     *
     * Performs a case-insensitive search against patientName using
     * SPARQL's FILTER regex function.
     *
     * @param string $name The name or partial name to search for
     *
     * @return array List of matching patient arrays
     */
    public function search(string $name): array
    {
        $name = addslashes(trim($name));

        if (empty($name)) {
            return $this->index();
        }

        $sparql = $this->prefixes . "
            SELECT ?patient ?patientID ?patientName ?age ?riskLevel
                   (GROUP_CONCAT(DISTINCT ?diseaseName; SEPARATOR=', ') AS ?diseases)
            WHERE {
                ?patient rdf:type cds:Patient .
                ?patient cds:patientName ?patientName .
                FILTER (REGEX(?patientName, \"{$name}\", \"i\"))
                OPTIONAL { ?patient cds:patientID ?patientID . }
                OPTIONAL { ?patient cds:patientAge ?age . }
                OPTIONAL { 
                    ?patient cds:hasRiskLevel ?riskLevelInst .
                    ?riskLevelInst rdfs:label ?riskLevel .
                }
                OPTIONAL {
                    ?patient cds:hasDiagnosis ?disease .
                    ?disease cds:diseaseName ?diseaseName .
                }
            }
            GROUP BY ?patient ?patientID ?patientName ?age ?riskLevel
            ORDER BY ?patientName
        ";

        $result = $this->sparqlService->query($sparql);

        if (!$result['success']) {
            return [];
        }

        $patients = [];
        foreach ($result['data']['results']['bindings'] ?? [] as $row) {
            $patients[] = [
                'uri'       => $row['patient']['value'] ?? '',
                'patientID' => $row['patientID']['value'] ?? $this->extractLocalName($row['patient']['value'] ?? ''),
                'name'      => $row['patientName']['value'] ?? 'Unknown',
                'age'       => $row['age']['value'] ?? 'N/A',
                'riskLevel' => $row['riskLevel']['value'] ?? 'Not Assessed',
                'diseases'  => $row['diseases']['value'] ?? 'None diagnosed',
            ];
        }

        return $patients;
    }

    /**
     * Get all patients with a specified risk level.
     *
     * Filters patients by their cds:riskLevel property value.
     *
     * @param string $level Risk level to filter by (e.g., 'High', 'Medium', 'Low', 'HighRisk')
     *
     * @return array List of patients matching the specified risk level
     */
    public function getRiskPatients(string $level = 'HighRisk'): array
    {
        // Normalize common risk level names
        $normalizedLevel = match (strtolower($level)) {
            'highrisk', 'high_risk' => 'High',
            'mediumrisk', 'medium_risk' => 'Medium',
            'lowrisk', 'low_risk' => 'Low',
            default => $level,
        };

        $sparql = $this->prefixes . "
            SELECT ?patient ?patientID ?patientName ?age ?riskLevel
                   (GROUP_CONCAT(DISTINCT ?diseaseName; SEPARATOR=', ') AS ?diseases)
                   (COUNT(DISTINCT ?symptom) AS ?symptomCount)
            WHERE {
                ?patient rdf:type cds:Patient .
                OPTIONAL { 
                    ?patient cds:hasRiskLevel ?riskLevelInst .
                    ?riskLevelInst rdfs:label ?riskLevel .
                }
                FILTER (REGEX(?riskLevel, \"{$normalizedLevel}\", \"i\"))
                OPTIONAL { ?patient cds:patientID ?patientID . }
                OPTIONAL { ?patient cds:patientName ?patientName . }
                OPTIONAL { ?patient cds:patientAge ?age . }
                OPTIONAL { ?patient cds:hasSymptom ?symptom . }
                OPTIONAL {
                    ?patient cds:hasDiagnosis ?disease .
                    ?disease cds:diseaseName ?diseaseName .
                }
            }
            GROUP BY ?patient ?patientID ?patientName ?age ?riskLevel
            ORDER BY ?patientName
        ";

        $result = $this->sparqlService->query($sparql);

        if (!$result['success']) {
            return [];
        }

        $patients = [];
        foreach ($result['data']['results']['bindings'] ?? [] as $row) {
            $patients[] = [
                'uri'          => $row['patient']['value'] ?? '',
                'patientID'    => $row['patientID']['value'] ?? $this->extractLocalName($row['patient']['value'] ?? ''),
                'name'         => $row['patientName']['value'] ?? 'Unknown',
                'age'          => $row['age']['value'] ?? 'N/A',
                'riskLevel'    => $row['riskLevel']['value'] ?? $level,
                'diseases'     => $row['diseases']['value'] ?? 'None diagnosed',
                'symptomCount' => (int) ($row['symptomCount']['value'] ?? 0),
            ];
        }

        return $patients;
    }

    /**
     * Get dashboard statistics.
     *
     * Returns aggregate counts for the dashboard including total patients,
     * high-risk patients, unique diseases, and total medications.
     *
     * @return array Dashboard statistics with keys: totalPatients, highRiskCount, diseaseCount, medicationCount
     */
    public function getDashboardStats(): array
    {
        // Total patients
        $sparqlTotal = $this->prefixes . "
            SELECT (COUNT(DISTINCT ?patient) AS ?total)
            WHERE {
                ?patient rdf:type cds:Patient .
            }
        ";

        // High risk patients
        $sparqlHighRisk = $this->prefixes . "
            SELECT (COUNT(DISTINCT ?patient) AS ?total)
            WHERE {
                ?patient rdf:type cds:Patient .
                {
                    { ?patient cds:riskLevel ?rl . FILTER(REGEX(?rl, \"High\", \"i\")) }
                    UNION
                    { ?patient rdf:type cds:HighRiskPatient . }
                }
            }
        ";

        // Unique diseases
        $sparqlDiseases = $this->prefixes . "
            SELECT (COUNT(DISTINCT ?disease) AS ?total)
            WHERE {
                ?disease rdf:type cds:Disease .
            }
        ";

        // Total medications
        $sparqlMedications = $this->prefixes . "
            SELECT (COUNT(DISTINCT ?medication) AS ?total)
            WHERE {
                ?medication rdf:type cds:Medication .
            }
        ";

        $totalResult   = $this->sparqlService->query($sparqlTotal);
        $riskResult    = $this->sparqlService->query($sparqlHighRisk);
        $diseaseResult = $this->sparqlService->query($sparqlDiseases);
        $medResult     = $this->sparqlService->query($sparqlMedications);

        return [
            'totalPatients'   => $this->extractCount($totalResult),
            'highRiskCount'   => $this->extractCount($riskResult),
            'diseaseCount'    => $this->extractCount($diseaseResult),
            'medicationCount' => $this->extractCount($medResult),
        ];
    }

    /**
     * Extract a count value from a SPARQL query result.
     *
     * @param array $result The SPARQL query result array
     *
     * @return int The extracted count, or 0 if not found
     */
    private function extractCount(array $result): int
    {
        if (!$result['success']) {
            return 0;
        }

        $bindings = $result['data']['results']['bindings'] ?? [];
        if (empty($bindings)) {
            return 0;
        }

        return (int) ($bindings[0]['total']['value'] ?? 0);
    }

    /**
     * Build a SPARQL pattern to match a patient by ID or URI.
     *
     * Generates the appropriate SPARQL filter clause depending on whether
     * the input is a full URI or a local patient ID.
     *
     * @param string $patientID The patient identifier or full URI
     *
     * @return string SPARQL pattern fragment for the WHERE clause
     */
    private function buildPatientFilter(string $patientID): string
    {
        if (str_starts_with($patientID, 'http')) {
            return "BIND(<{$patientID}> AS ?patient) .";
        }

        // Try matching by patientID property first, or by URI local name
        return "
            {
                { ?patient cds:patientID \"{$patientID}\" . }
                UNION
                { BIND(cds:{$patientID} AS ?patient) . }
            }
        ";
    }

    /**
     * Extract the local name from a full URI.
     *
     * Removes the namespace prefix to get just the identifier portion.
     *
     * @param string $uri The full URI
     *
     * @return string The local name portion after the last # or /
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

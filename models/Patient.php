<?php
/**
 * Smart Clinical Decision System - Patient Model
 *
 * Data model representing a patient entity with properties mapped
 * from SPARQL query results. Includes demographics, clinical data,
 * and helper methods for UI rendering.
 *
 * @package SmartCDS\Models
 * @version 1.0.0
 */

namespace Models;

class Patient
{
    /** @var string Full URI of the patient resource in the ontology */
    public string $uri = '';

    /** @var string Unique patient identifier */
    public string $patientID = '';

    /** @var string Patient's full name */
    public string $name = '';

    /** @var string|int Patient's age */
    public string|int $age = '';

    /** @var string Patient's gender */
    public string $gender = '';

    /** @var string Patient's blood type */
    public string $bloodType = '';

    /** @var array List of symptoms as associative arrays [uri, name, severity, bodyPart] */
    public array $symptoms = [];

    /** @var array List of diagnosed diseases as associative arrays [uri, name, diagnosisDate, stage] */
    public array $diseases = [];

    /** @var array List of medications as associative arrays [uri, name, dosage, frequency] */
    public array $medications = [];

    /** @var array List of lab results as associative arrays [uri, testName, testValue, unit, referenceRange] */
    public array $labResults = [];

    /** @var string Risk level classification (High, Medium, Low, Not Assessed) */
    public string $riskLevel = 'Not Assessed';

    /** @var array List of clinical records as associative arrays [uri, recordType, recordDate, notes] */
    public array $clinicalRecords = [];

    /** @var string Admission date */
    public string $admissionDate = '';

    /** @var string Contact number */
    public string $contactNumber = '';

    /** @var string Address */
    public string $address = '';

    /**
     * Create a Patient instance from a SPARQL result row.
     *
     * Maps the variable bindings from a SPARQL SELECT query result
     * into the Patient model properties.
     *
     * @param array $row A single binding row from a SPARQL JSON result
     *                   Expected keys: patient, patientID, patientName, age, gender,
     *                   bloodType, riskLevel, admissionDate, contactNumber, address
     *
     * @return self New Patient instance populated from the SPARQL row
     */
    public static function fromSparqlResult(array $row): self
    {
        $patient = new self();

        $patient->uri           = $row['patient']['value'] ?? '';
        $patient->patientID     = $row['patientID']['value'] ?? self::extractLocalName($row['patient']['value'] ?? '');
        $patient->name          = $row['patientName']['value'] ?? 'Unknown';
        $patient->age           = $row['age']['value'] ?? 'N/A';
        $patient->gender        = $row['gender']['value'] ?? 'N/A';
        $patient->bloodType     = $row['bloodType']['value'] ?? 'N/A';
        $patient->riskLevel     = $row['riskLevel']['value'] ?? 'Not Assessed';
        $patient->admissionDate = $row['admissionDate']['value'] ?? '';
        $patient->contactNumber = $row['contactNumber']['value'] ?? '';
        $patient->address       = $row['address']['value'] ?? '';

        return $patient;
    }

    /**
     * Convert the Patient model to an associative array.
     *
     * Useful for JSON serialization and passing data to views.
     *
     * @return array All patient properties as key-value pairs
     */
    public function toArray(): array
    {
        return [
            'uri'             => $this->uri,
            'patientID'       => $this->patientID,
            'name'            => $this->name,
            'age'             => $this->age,
            'gender'          => $this->gender,
            'bloodType'       => $this->bloodType,
            'symptoms'        => $this->symptoms,
            'diseases'        => $this->diseases,
            'medications'     => $this->medications,
            'labResults'      => $this->labResults,
            'riskLevel'       => $this->riskLevel,
            'clinicalRecords' => $this->clinicalRecords,
            'admissionDate'   => $this->admissionDate,
            'contactNumber'   => $this->contactNumber,
            'address'         => $this->address,
            'symptomCount'    => count($this->symptoms),
            'diseaseCount'    => count($this->diseases),
            'medicationCount' => count($this->medications),
        ];
    }

    /**
     * Get the CSS badge class for the patient's risk level.
     *
     * Returns a Bootstrap-compatible badge class name based
     * on the current risk level for use in the UI.
     *
     * @return string CSS class name for the risk badge:
     *                - 'badge-danger' for High risk
     *                - 'badge-warning' for Medium risk
     *                - 'badge-success' for Low risk
     *                - 'badge-secondary' for Not Assessed or unknown
     */
    public function getRiskBadgeClass(): string
    {
        return match (strtolower($this->riskLevel)) {
            'high', 'highrisk', 'critical' => 'badge-danger',
            'medium', 'moderate'           => 'badge-warning',
            'low', 'minimal'               => 'badge-success',
            default                        => 'badge-secondary',
        };
    }

    /**
     * Check if the patient is classified as high risk.
     *
     * @return bool True if the patient's risk level is High or Critical
     */
    public function isHighRisk(): bool
    {
        return in_array(strtolower($this->riskLevel), ['high', 'highrisk', 'critical']);
    }

    /**
     * Get the number of symptoms.
     *
     * @return int Count of symptoms
     */
    public function getSymptomCount(): int
    {
        return count($this->symptoms);
    }

    /**
     * Get the number of diseases.
     *
     * @return int Count of diseases
     */
    public function getDiseaseCount(): int
    {
        return count($this->diseases);
    }

    /**
     * Extract the local name from a full URI.
     *
     * @param string $uri Full URI string
     *
     * @return string Local name portion after the last # or /
     */
    private static function extractLocalName(string $uri): string
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

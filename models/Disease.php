<?php
/**
 * Smart Clinical Decision System - Disease Model
 *
 * Data model representing a disease entity in the clinical ontology.
 * Maps properties from SPARQL query results and provides serialization helpers.
 *
 * @package SmartCDS\Models
 * @version 1.0.0
 */

namespace Models;

class Disease
{
    /** @var string Full URI of the disease resource in the ontology */
    public string $uri = '';

    /** @var string Human-readable name of the disease */
    public string $name = '';

    /** @var string Description of the disease */
    public string $description = '';

    /** @var string Stage or severity of the disease (e.g., 'Stage I', 'Acute') */
    public string $stage = '';

    /** @var array List of related condition URIs and names */
    public array $relatedConditions = [];

    /** @var array List of medications that treat this disease */
    public array $medications = [];

    /** @var array List of symptoms that indicate this disease */
    public array $symptoms = [];

    /** @var string ICD code if available */
    public string $icdCode = '';

    /** @var string Body system affected */
    public string $bodySystem = '';

    /**
     * Create a Disease instance from a SPARQL result row.
     *
     * Maps the variable bindings from a SPARQL SELECT query result
     * into the Disease model properties.
     *
     * @param array $row A single binding row from a SPARQL JSON result
     *                   Expected keys: disease, diseaseName, description, stage,
     *                   icdCode, bodySystem
     *
     * @return self New Disease instance populated from the SPARQL row
     */
    public static function fromSparqlResult(array $row): self
    {
        $disease = new self();

        $disease->uri         = $row['disease']['value'] ?? '';
        $disease->name        = $row['diseaseName']['value'] ?? self::extractLocalName($row['disease']['value'] ?? '');
        $disease->description = $row['description']['value'] ?? '';
        $disease->stage       = $row['stage']['value'] ?? '';
        $disease->icdCode     = $row['icdCode']['value'] ?? '';
        $disease->bodySystem  = $row['bodySystem']['value'] ?? '';

        return $disease;
    }

    /**
     * Convert the Disease model to an associative array.
     *
     * Useful for JSON serialization and passing data to views.
     *
     * @return array All disease properties as key-value pairs
     */
    public function toArray(): array
    {
        return [
            'uri'               => $this->uri,
            'name'              => $this->name,
            'description'       => $this->description,
            'stage'             => $this->stage,
            'relatedConditions' => $this->relatedConditions,
            'medications'       => $this->medications,
            'symptoms'          => $this->symptoms,
            'icdCode'           => $this->icdCode,
            'bodySystem'        => $this->bodySystem,
        ];
    }

    /**
     * Add a related condition to this disease.
     *
     * @param string $uri  The full URI of the related condition
     * @param string $name The human-readable name of the related condition
     *
     * @return void
     */
    public function addRelatedCondition(string $uri, string $name): void
    {
        // Avoid duplicates
        foreach ($this->relatedConditions as $condition) {
            if ($condition['uri'] === $uri) {
                return;
            }
        }

        $this->relatedConditions[] = [
            'uri'  => $uri,
            'name' => $name,
        ];
    }

    /**
     * Add a medication that treats this disease.
     *
     * @param string $uri  The full URI of the medication
     * @param string $name The human-readable name of the medication
     *
     * @return void
     */
    public function addMedication(string $uri, string $name): void
    {
        // Avoid duplicates
        foreach ($this->medications as $med) {
            if ($med['uri'] === $uri) {
                return;
            }
        }

        $this->medications[] = [
            'uri'  => $uri,
            'name' => $name,
        ];
    }

    /**
     * Add a symptom that indicates this disease.
     *
     * @param string $uri  The full URI of the symptom
     * @param string $name The human-readable name of the symptom
     *
     * @return void
     */
    public function addSymptom(string $uri, string $name): void
    {
        foreach ($this->symptoms as $symptom) {
            if ($symptom['uri'] === $uri) {
                return;
            }
        }

        $this->symptoms[] = [
            'uri'  => $uri,
            'name' => $name,
        ];
    }

    /**
     * Check if this disease has any related conditions.
     *
     * @return bool True if there are related conditions
     */
    public function hasRelatedConditions(): bool
    {
        return !empty($this->relatedConditions);
    }

    /**
     * Check if there are medications available for this disease.
     *
     * @return bool True if there are known medications
     */
    public function hasMedications(): bool
    {
        return !empty($this->medications);
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

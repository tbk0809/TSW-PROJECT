<?php
/**
 * Smart Clinical Decision System - Ontology Model
 *
 * Utility model for managing CDS ontology namespace operations,
 * URI construction, and local name extraction. Provides static
 * helper methods used across controllers and services.
 *
 * @package SmartCDS\Models
 * @version 1.0.0
 */

namespace Models;

class Ontology
{
    /**
     * The primary namespace URI for the Clinical Decision System ontology.
     *
     * All classes, properties, and individuals in the CDS ontology
     * are defined under this namespace.
     *
     * @var string
     */
    public const NAMESPACE = 'http://www.semanticweb.org/clinical-decision-system#';

    /**
     * Common namespace prefixes used in SPARQL queries.
     *
     * @var array<string, string>
     */
    public const PREFIXES = [
        'cds'  => 'http://www.semanticweb.org/clinical-decision-system#',
        'rdf'  => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
        'owl'  => 'http://www.w3.org/2002/07/owl#',
        'xsd'  => 'http://www.w3.org/2001/XMLSchema#',
    ];

    /**
     * Known OWL classes in the CDS ontology.
     *
     * @var array<string>
     */
    public const CLASSES = [
        'Patient',
        'HighRiskPatient',
        'DiagnosedPatient',
        'ComplexCase',
        'Disease',
        'Symptom',
        'Medication',
        'LabResult',
        'ClinicalRecord',
        'Treatment',
        'Doctor',
    ];

    /**
     * Known object properties in the CDS ontology.
     *
     * @var array<string>
     */
    public const OBJECT_PROPERTIES = [
        'hasSymptom',
        'hasDiagnosis',
        'takesMedication',
        'hasLabResult',
        'hasClinicalRecord',
        'indicatesDisease',
        'treatsDisease',
        'contraindicatedWith',
        'interactsWith',
        'relatedCondition',
        'recommendedMedication',
        'prescribedBy',
        'attendedBy',
    ];

    /**
     * Known data properties in the CDS ontology.
     *
     * @var array<string>
     */
    public const DATA_PROPERTIES = [
        'patientID',
        'patientName',
        'age',
        'gender',
        'bloodType',
        'riskLevel',
        'symptomName',
        'severity',
        'diseaseName',
        'medicationName',
        'dosage',
        'frequency',
        'testName',
        'testValue',
        'unit',
        'referenceRange',
        'diagnosisDate',
        'prescribedDate',
        'testDate',
        'recordDate',
        'notes',
    ];

    /**
     * Construct a full URI from a local name.
     *
     * Prepends the CDS namespace to the given local name to form
     * a complete URI reference.
     *
     * @param string $localName The local identifier (e.g., 'Patient001', 'Fever')
     *
     * @return string The full URI (e.g., 'http://www.semanticweb.org/clinical-decision-system#Patient001')
     */
    public static function getFullURI(string $localName): string
    {
        // If already a full URI, return as-is
        if (str_starts_with($localName, 'http://') || str_starts_with($localName, 'https://')) {
            return $localName;
        }

        // Remove any leading # if present
        $localName = ltrim($localName, '#');

        return self::NAMESPACE . $localName;
    }

    /**
     * Extract the local name from a full URI.
     *
     * Removes the namespace prefix to return just the identifier portion
     * of the URI. Handles both hash (#) and slash (/) delimited URIs.
     *
     * @param string $fullURI The complete URI (e.g., 'http://www.semanticweb.org/clinical-decision-system#Patient001')
     *
     * @return string The local name (e.g., 'Patient001')
     */
    public static function getLocalName(string $fullURI): string
    {
        // Check for hash delimiter first (most common in OWL ontologies)
        if (str_contains($fullURI, '#')) {
            return substr($fullURI, strrpos($fullURI, '#') + 1);
        }

        // Fall back to slash delimiter
        if (str_contains($fullURI, '/')) {
            return substr($fullURI, strrpos($fullURI, '/') + 1);
        }

        // If no delimiter found, return the input as-is
        return $fullURI;
    }

    /**
     * Get the CDS ontology namespace URI.
     *
     * @return string The namespace URI string
     */
    public static function getNamespace(): string
    {
        return self::NAMESPACE;
    }

    /**
     * Build a SPARQL PREFIX declaration string for the CDS namespace.
     *
     * @return string SPARQL PREFIX line: "PREFIX cds: <http://...#>"
     */
    public static function getSparqlPrefix(): string
    {
        return 'PREFIX cds: <' . self::NAMESPACE . '>';
    }

    /**
     * Build all standard SPARQL PREFIX declarations.
     *
     * Returns a multi-line string with PREFIX declarations for
     * cds, rdf, rdfs, owl, and xsd namespaces.
     *
     * @return string Multi-line SPARQL PREFIX block
     */
    public static function getAllPrefixes(): string
    {
        $lines = [];
        foreach (self::PREFIXES as $prefix => $uri) {
            $lines[] = "PREFIX {$prefix}: <{$uri}>";
        }
        return implode("\n", $lines) . "\n";
    }

    /**
     * Check if a URI belongs to the CDS namespace.
     *
     * @param string $uri The URI to check
     *
     * @return bool True if the URI starts with the CDS namespace
     */
    public static function isCdsUri(string $uri): bool
    {
        return str_starts_with($uri, self::NAMESPACE);
    }

    /**
     * Check if a local name corresponds to a known OWL class.
     *
     * @param string $localName The local name to check
     *
     * @return bool True if it matches a known class name
     */
    public static function isKnownClass(string $localName): bool
    {
        return in_array($localName, self::CLASSES, true);
    }

    /**
     * Check if a local name corresponds to a known object property.
     *
     * @param string $localName The local name to check
     *
     * @return bool True if it matches a known object property name
     */
    public static function isKnownObjectProperty(string $localName): bool
    {
        return in_array($localName, self::OBJECT_PROPERTIES, true);
    }

    /**
     * Check if a local name corresponds to a known data property.
     *
     * @param string $localName The local name to check
     *
     * @return bool True if it matches a known data property name
     */
    public static function isKnownDataProperty(string $localName): bool
    {
        return in_array($localName, self::DATA_PROPERTIES, true);
    }

    /**
     * Build a URI for a CDS class instance.
     *
     * @param string $className    The class name (e.g., 'Patient')
     * @param string $instanceName The instance identifier (e.g., '001')
     *
     * @return string Full URI like 'http://.../clinical-decision-system#Patient001'
     */
    public static function buildInstanceURI(string $className, string $instanceName): string
    {
        return self::NAMESPACE . $className . $instanceName;
    }
}

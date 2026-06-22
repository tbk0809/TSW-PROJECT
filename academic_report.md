# Smart Clinical Decision Support System
## Academic Project Report

**Group:** [GroupXX]
**Date:** June 2026

**Team Members:**
- Name: _______________________ | ID: _______________________
- Name: _______________________ | ID: _______________________
- Name: _______________________ | ID: _______________________
- Name: _______________________ | ID: _______________________

---

## 1. Introduction
The Smart Clinical Decision Support (CDS) System aims to demonstrate the practical application of Semantic Web technologies in healthcare. This system utilizes a formal OWL ontology to model clinical knowledge (diseases, symptoms, medications, risks) and relies on Apache Jena Fuseki to perform automated reasoning. This enables healthcare professionals to receive intelligent, inference-driven diagnostic and treatment recommendations.

## 2. System Architecture
The application follows a modern, decoupled architecture:
1.  **Knowledge Base (OWL + RDF):** The foundational layer consisting of an OWL 2.0 ontology (`clinical-decision.owl`) defining the schema and constraints, combined with RDF Turtle data (`patient-data.ttl`) storing realistic patient health records.
2.  **Triplestore & Reasoner (Apache Jena Fuseki):** Serves as the graph database executing SPARQL queries. Crucially, it provides a built-in OWL reasoning engine that materializes inferred knowledge (e.g., classifying a patient as `HighRiskPatient` based on symptom counts).
3.  **Backend (PHP 8.x + EasyRdf):** Acts as the middleware. It receives HTTP requests, constructs dynamic SPARQL queries, communicates with the Fuseki endpoint via cURL, parses the JSON responses, and exposes a RESTful API (`api/api.php`).
4.  **Frontend (HTML/CSS/Vanilla JS):** A responsive, dashboard-style UI utilizing Fetch API to asynchronously retrieve data from the PHP backend.

## 3. Ontology Design and Inference Rules
The ontology defines a robust hierarchy of classes including `Patient`, `Symptom`, `Disease`, `Medication`, and `RiskLevel`.

**Key Inference Axioms Implemented:**
-   **Equivalent Class:** `HighRiskPatient ≡ Patient ⊓ ≥3 hasSymptom ⊓ ∃diagnosedWith.Disease`
-   **Property Chain:** `treatedBy⁻¹ ∘ diagnosedWith → isSpecialistFor`
-   **Transitive Property:** `isRelatedConditionOf` ensures that if A is related to B, and B to C, then A is related to C.
-   **Disjoint Classes:** `Disease ⊥ Medication` enforces strict logical separation between conditions and treatments.

## 4. Implementation Details
The backend strictly adheres to an MVC-like structure without relying on bloated frameworks. Controllers (e.g., `PatientController.php`, `InferenceController.php`) encapsulate specific clinical logic. The `SparqlService.php` isolates the HTTP communication with the Jena Fuseki endpoint, ensuring that data retrieval remains robust and distinct from application logic.

The REST API serves as the primary data exchange conduit, utilizing standard JSON formatting and handling cross-origin (CORS) configurations dynamically. The frontend leverages this to populate a rich graphical dashboard using `Chart.js` for risk stratification visualization.

## 5. Conclusion
This project successfully demonstrates how Semantic Web technologies can overcome the limitations of traditional relational databases in complex, interconnected domains like healthcare. By utilizing explicit formal logic (OWL) and automated reasoning, the system not only stores clinical data but actively derives new clinical insights, improving the efficacy of clinical decision-making.

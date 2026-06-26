# TSW6223 SEMANTIC WEB TECHNOLOGY PROJECT REPORT TERM 2610

**Title:** Smart Clinical Decision Support System: A Semantic Web Approach
**Group ID:** [Group leader to fill]
**Selected Topics:** 
(1) Category 2: RDF, RDFS, and SPARQL
(2) Category 3: Web Ontology Language (OWL) and Inference

**Members:**

| Name/ID | List contribution(s) in the project | State which part of the write-up in the report |
| :--- | :--- | :--- |
| 1 (Project Leader) [Name/ID] | [Contribution] | [Part] |
| 2 [Name/ID] | [Contribution] | [Part] |
| 3 [Name/ID] | [Contribution] | [Part] |
| 4 [Name/ID] | [Contribution] | [Part] |

---

## 1. Introduction

Semantic web technology (SWT) enhances internet data by making it more structured, machine-readable, and interconnected, thereby supporting improved automation, search, and information integration. Its core objective is to enable computers to interpret the meaning of data rather than merely presenting it as static content. This project explores the practical application of SWT within the highly complex domain of healthcare and clinical diagnostics. 

Specifically, we developed a Smart Clinical Decision Support System (CDS) that leverages SWT to facilitate seamless medical data sharing, automate complex diagnostic processes, and execute patient risk stratifications based on well-defined ontological rules. By promoting standardized clinical vocabularies, the system ensures consistency, traceability, and clarity in medical record management. Through the use of formal logic, the application assists healthcare providers in identifying drug contraindications and assessing patient risk profiles, showcasing how semantic interconnectivity solves critical real-world problems.

## 2. Problem Statement and Objectives

**Problem Statement:**
Traditional healthcare information systems typically rely on relational databases (RDBMS) which operate under a rigid schema and the Closed World Assumption (CWA). These systems struggle to natively represent the highly interconnected and hierarchical nature of medical knowledge, such as transitive disease progressions or symmetric drug contraindications. Furthermore, relational databases treat missing data as false; in a clinical setting, an unrecorded allergy does not guarantee the absence of an allergy. There is a critical need for a system that can handle incomplete data (Open World Assumption) and automatically infer implicit medical knowledge from explicit patient records to prevent diagnostic errors and adverse drug events.

**Objectives:**
1. To understand the core concepts and underlying principles of Semantic Web Technology, specifically RDF, SPARQL, and OWL.
2. To design and implement a practical, SWT-based Clinical Decision Support solution that successfully integrates an OWL ontology, RDF patient data, and SPARQL reasoning to solve real-world healthcare triage and diagnostic challenges.

## 3. Solution Development

### 3.1 System Overview and Architecture

The Smart Clinical Decision Support System (CDS) represents a robust implementation of semantic web technologies designed to enhance healthcare diagnostics. The architecture follows a multi-tier, decoupled paradigm where the presentation and application logic are handled by a PHP 8.x front-end, while the data persistence and knowledge reasoning are delegated to an Apache Jena Fuseki (version 4.x) triplestore.

The data flow within this system operates in a cyclic, request-response manner. When a user interacts with the PHP front-end, the PHP `SparqlService` formulates a precise SPARQL query. This query is transmitted via HTTP to the Apache Jena Fuseki endpoint. The Fuseki server hosts the TDB2 persistent triplestore, which contains both the clinical ontology (OWL schema) and the instance data (RDF patient records). Crucially, the dataset in Fuseki is configured as an Inference Model (`ja:InfModel`), utilizing the `OWLFBRuleReasoner` (OWL Full with Forward and Backward chaining rules). 

Before or during the execution of the query, the reasoner evaluates the asserted triples against the OWL axioms (e.g., transitivity, property chains, class equivalence), dynamically materializing inferred triples. The SPARQL engine then evaluates the query against this enriched graph. In addition to Fuseki's built-in reasoning, the PHP application executes custom SPARQL `INSERT` operations to apply complex domain rules—such as classifying a patient as a `ComplexCase` based on multiple aggregated conditions. The resulting dataset is returned to PHP, parsed, and rendered as dynamic HTML.

### 3.2 OWL (Web Ontology Language)

The Web Ontology Language (OWL) is a computational logic-based knowledge representation language designed to author ontologies that formally describe the taxonomy and classification networks of a specific domain. In this project, formal ontological representation is paramount because medical knowledge is highly relational, hierarchical, and rule-dependent. 

The `clinical-decision.owl` file is a meticulously constructed schema defining the formal backbone of the CDS:
- **Class Hierarchy:** Defines classes like `Patient`, `Symptom`, `Disease`, `Medication`, and `RiskLevel`. `CriticalTreatment` is a subclass of `Treatment`.
- **Object Properties:** Properties map instances to instances. `hasSymptom` (Patient → Symptom), `diagnosedWith` (Patient → Disease). It uses complex properties such as `isRelatedConditionOf` (Transitive Property), `interactsWith` (Symmetric Property), and `isSpecialistFor` (Property Chain: `treatsPatient` $\circ$ `diagnosedWith`).
- **Property Restrictions and Axioms:** `DiagnosedPatient` uses `owl:someValuesFrom`. `ComplexCase` requires `hasSymptom min 2` (`owl:minCardinality`). `owl:AllDisjointClasses` ensures that a `Disease` and a `Medication` are mutually exclusive concepts.

OWL acts as the immutable, intelligent schema. When the PHP application queries for a `HighRiskPatient`, it relies entirely on the Apache Jena Inference Engine to have already parsed the OWL equivalent class axioms and materialized the classification.

### 3.3 RDF (Resource Description Framework)

RDF abstracts data into a directed, labeled graph represented by Subject-Predicate-Object triples. In the CDS project, RDF was selected over relational rows because medical patient data is highly varied, sparse, and deeply interconnected. RDF's Open World Assumption allows the system to gracefully handle incomplete clinical records.

The `patient-data.ttl` file serves as the ABox (Assertional Box), containing the instance data serialized in Turtle (`.ttl`) format. 
Example from the dataset:
```turtle
cds:Patient_001
    a                       cds:Patient ;
    cds:patientName         "Ahmed"^^xsd:string ;
    cds:patientAge          55 ;
    cds:hasSymptom          cds:Headache , cds:Dizziness ;
    cds:prescribedMedication cds:Amlodipine .
```
This elegantly establishes class membership, attaches typed literals (`xsd:string`, `xsd:integer`) via Data Properties, and links to multiple object URIs simultaneously via Object Properties. RDF forms the raw graph substrate ingested by the Apache Jena Fuseki triplestore.

### 3.4 SPARQL (SPARQL Protocol and RDF Query Language)

Given that the project's data is housed in an RDF triplestore, SPARQL is the mandatory access language. The project leverages SPARQL extensively across multiple files, utilizing `SELECT` queries for the analytics console and `INSERT` queries for advanced updates.

For example, a query to aggregate risk levels uses standard SQL-like aggregates in a graph context:
```sparql
SELECT ?riskLevelClass (COUNT(?patient) AS ?patientCount)
WHERE {
    ?patient    rdf:type            cds:Patient .
    ?patient    cds:hasRiskLevel    ?riskLevel .
    ?riskLevel  rdf:type            ?riskLevelClass .
}
GROUP BY ?riskLevelClass
```
Crucially, SPARQL queries interface seamlessly with the inference engine. SPARQL enables the PHP layer to query abstract, high-level clinical concepts without needing to manually write complex graph traversals to derive those concepts.

### 3.5 PHP Application Layer

PHP 8.x forms the orchestration layer of the CDS. It bridges the sophisticated but austere Fuseki backend with an intuitive HTML clinical dashboard. Using a lightweight MVC architecture, the `index.php` router directs traffic to controllers. The core integration lies in `services/SparqlService.php`, which dynamically constructs SPARQL strings, initiates HTTP GET/POST requests via `cURL` to the Fuseki REST endpoints, and retrieves the JSON payloads. PHP then deserializes this JSON and maps it to HTML UI elements.

### 3.6 Inference Mechanism

Inference is the automated computational process of deriving new, implicit knowledge from existing facts. The system employs the `OWLFBRuleReasoner` inside Apache Jena Fuseki.
- **Symmetry Example:** If the dataset asserts `Metformin interactsWith Diazepam`, the reasoner automatically infers `Diazepam interactsWith Metformin`, ensuring drug interaction warnings trigger regardless of data entry order.
- **Transitivity Example:** If Hypertension relates to Coronary Artery Disease (CAD), and CAD relates to Heart Failure, the system infers a direct pathological link from Hypertension to Heart Failure.

Furthermore, the PHP `InferenceService.php` uses procedural SPARQL `INSERT` rules to compute aggregations (like threshold counting) to explicitly insert new triples classifying patients as a `ComplexCase` or `HighRiskPatient`, supplementing the native reasoner.

### 3.7 Integration

The complete use-case relies on all layers functioning harmoniously. A physician requests a patient's profile (PHP). The application formulates a query and dispatches it over HTTP (SPARQL). Inside Fuseki, the query engine interacts with the TDB2 persistent store (RDF), guided by the schema (OWL). The reasoner dynamically generates inferences (like symmetric drug interactions), and the results are returned as JSON to be rendered as visual alerts by PHP.

## 4. Evaluation

The implementation of the Smart CDS demonstrates profound advantages over traditional relational database applications. By utilizing OWL and RDF, the system achieves a level of schema fluidity impossible in SQL; adding a new modality of medical data requires zero schema migrations, merely the assertion of new triples. The expressiveness of the ontology successfully models complex medical reality, preventing logical contradictions through disjointness axioms.

However, this sophisticated expressiveness introduces performance considerations. Semantic reasoning is computationally expensive. As the patient dataset scales to thousands of individuals, the `OWLFBRuleReasoner` evaluating full forward and backward chaining will face significant latency, particularly upon server startup or during bulk data updates. Despite these limitations, the semantic graph approach proves highly superior for accurately modeling clinical diagnostic logic.

## 5. Future Improvements

While the current implementation successfully demonstrates the power of SWT, several future improvements are recommended:
1. **Integration with Global Standard Vocabularies:** Future versions should align the custom `cds:` ontology with established global medical ontologies such as SNOMED CT for clinical terms, ICD-10 for disease classification, and RxNorm for medications. This would enable true interoperability with external hospital systems.
2. **Advanced Reasoning Engines:** To address scalability bottlenecks as the RDF dataset grows, the project should migrate from the integrated Jena rule engine to a high-performance, dedicated Description Logic reasoner (such as Pellet, HermiT, or ELK) or an enterprise graph database (like GraphDB) to optimize reasoning caching and query response times.
3. **Enhanced User Interface & Graph Visualisation:** The current PHP-based front-end could be upgraded to a modern Single Page Application (SPA) using React or Vue.js, integrating libraries like D3.js or Cytoscape.js to provide doctors with real-time, interactive graph visualizations of patient health data and disease pathways.

## 6. Conclusion

The Smart Clinical Decision Support System successfully demonstrates the transformative potential of Semantic Web technologies in the healthcare sector. By migrating from rigid relational databases to a flexible RDF graph model governed by a rigorous OWL ontology, the system accurately captures the complex realities of medical data. The integration of Apache Jena Fuseki's inference engine with dynamic PHP and SPARQL queries allows the application to automatically deduce critical information—such as drug interactions and patient risk levels—that was never explicitly entered into the database. Ultimately, the project fulfills its objectives by providing a highly intelligent, interoperable, and scalable solution to a complex real-world problem, showcasing the tangible benefits of machine-readable, interconnected semantic data.

## 7. References

- Apache Software Foundation. (n.d.). *Apache Jena Fuseki*. Retrieved from https://jena.apache.org/documentation/fuseki2/
- Hitzler, P., Krötzsch, M., Parsia, B., Patel-Schneider, P. F., & Rudolph, S. (2012). *OWL 2 Web Ontology Language Primer (2nd ed.)*. W3C Recommendation. Retrieved from https://www.w3.org/TR/owl2-primer/
- Harris, S., & Seaborne, A. (2013). *SPARQL 1.1 Query Language*. W3C Recommendation. Retrieved from https://www.w3.org/TR/sparql11-query/
- Schreiber, G., & Raimond, Y. (2014). *RDF 1.1 Primer*. W3C Note. Retrieved from https://www.w3.org/TR/rdf11-primer/

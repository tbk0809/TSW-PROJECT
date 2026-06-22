<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.x">
  <img src="https://img.shields.io/badge/Apache_Jena_Fuseki-4.x-1E88E5?style=for-the-badge&logo=apache&logoColor=white" alt="Fuseki 4.x">
  <img src="https://img.shields.io/badge/OWL-2.0-FF6F00?style=for-the-badge&logo=semanticweb&logoColor=white" alt="OWL 2.0">
  <img src="https://img.shields.io/badge/SPARQL-1.1-43A047?style=for-the-badge&logo=graphql&logoColor=white" alt="SPARQL 1.1">
  <img src="https://img.shields.io/badge/EasyRdf-1.x-9C27B0?style=for-the-badge" alt="EasyRdf">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License">
</p>

# 🏥 Smart Clinical Decision Support System

> A Semantic Web-powered Clinical Decision Support (CDS) system that leverages OWL ontologies, SPARQL queries, and automated OWL reasoning to provide intelligent diagnostic recommendations, drug interaction alerts, and treatment suggestions for healthcare professionals.

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Architecture](#-architecture)
- [Prerequisites](#-prerequisites)
- [Step-by-Step Local Deployment](#-step-by-step-local-deployment)
  - [Step 1 — Install & Start Apache Jena Fuseki](#step-1--install--start-apache-jena-fuseki)
  - [Step 2 — Create the CDS Dataset](#step-2--create-the-cds-dataset-in-fuseki)
  - [Step 3 — Upload the OWL Ontology](#step-3--upload-the-owl-ontology-to-fuseki)
  - [Step 4 — Upload the RDF Patient Data](#step-4--upload-the-rdf-patient-data-to-fuseki)
  - [Step 5 — Enable OWL Reasoning in Fuseki](#step-5--enable-owl-reasoning-in-fuseki)
  - [Step 6 — Set Up the PHP Project in XAMPP](#step-6--set-up-the-php-project-in-xampp)
  - [Step 7 — Install PHP Dependencies via Composer](#step-7--install-php-dependencies-via-composer)
  - [Step 8 — Configure the Application](#step-8--configure-the-application)
  - [Step 9 — Run the Application](#step-9--run-the-application)
  - [Step 10 — Verify Inference is Working](#step-10--verify-inference-is-working)
- [Fuseki Reasoning Configuration](#-fuseki-reasoning-configuration-reference)
- [Project Structure](#-project-structure)
- [Composer Configuration](#-composerjson-reference)
- [Troubleshooting](#-troubleshooting)
- [API Endpoints](#-api-endpoints)
- [License](#-license)

---

## 🔍 Overview

The **Smart Clinical Decision Support System** integrates:

| Component | Purpose |
|---|---|
| **OWL 2.0 Ontology** | Formally models diseases, symptoms, medications, lab tests, and clinical guidelines |
| **RDF Patient Data** | Encodes patient records (vitals, diagnoses, medications) as linked data |
| **Apache Jena Fuseki** | SPARQL triplestore with built-in OWL reasoning engine |
| **PHP + EasyRdf Backend** | Server-side SPARQL query execution and result processing |
| **Responsive Web Frontend** | Professional clinical dashboard for healthcare providers |

**Key capabilities:**
- 🔬 **Automated Diagnosis Inference** — OWL reasoner infers probable diagnoses from symptoms
- 💊 **Drug Interaction Detection** — identifies contraindicated medication combinations
- 📊 **Risk Stratification** — classifies patients into risk categories based on clinical data
- 🧪 **Lab Result Interpretation** — flags abnormal results with clinical significance
- 📋 **Treatment Recommendations** — suggests evidence-based treatment plans

---

## 🏗 Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                        CLIENT BROWSER                            │
│                   (HTML / CSS / JavaScript)                       │
└────────────────────────────┬─────────────────────────────────────┘
                             │  HTTP Requests
                             ▼
┌──────────────────────────────────────────────────────────────────┐
│                     APACHE + PHP 8.x (XAMPP)                     │
│  ┌────────────┐  ┌──────────────┐  ┌─────────────────────────┐  │
│  │ index.php  │  │  api/*.php   │  │  src/SparqlClient.php   │  │
│  │ (Router)   │  │ (Endpoints)  │  │  (EasyRdf + cURL)       │  │
│  └────────────┘  └──────────────┘  └───────────┬─────────────┘  │
└────────────────────────────────────────────────┼────────────────┘
                                                 │  SPARQL over HTTP
                                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                   APACHE JENA FUSEKI 4.x                         │
│  ┌─────────────────────┐  ┌──────────────────────────────────┐  │
│  │  TDB2 Triplestore   │  │  OWL Reasoner (RDFS+OWL Micro)  │  │
│  │  ┌───────────────┐  │  │  - Subclass inference            │  │
│  │  │ cds_ontology  │  │  │  - Property chain reasoning      │  │
│  │  │   .owl        │  │  │  - Restriction-based inference   │  │
│  │  ├───────────────┤  │  │  - Disjointness checking         │  │
│  │  │ patient_data  │  │  └──────────────────────────────────┘  │
│  │  │   .ttl        │  │                                        │
│  │  └───────────────┘  │                                        │
│  └─────────────────────┘                                        │
└──────────────────────────────────────────────────────────────────┘
```

---

## ✅ Prerequisites

Before starting, ensure you have the following installed on your system:

### 1. XAMPP (PHP 8.x + Apache + MySQL)

| Requirement | Details |
|---|---|
| **Download** | [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html) |
| **Version** | XAMPP with **PHP 8.0+** (PHP 8.1 or 8.2 recommended) |
| **Components** | Apache, PHP (MySQL optional for this project) |

> [!IMPORTANT]
> During XAMPP installation, ensure **Apache** is selected. MySQL is not required for this project as all data is stored in Fuseki's triplestore.

### 2. Java Runtime Environment (JRE 11+)

Apache Jena Fuseki requires Java 11 or later.

```powershell
# Verify Java installation
java -version
```

If not installed, download from [https://adoptium.net/](https://adoptium.net/) (Eclipse Temurin recommended).

### 3. Apache Jena Fuseki 4.x

| Requirement | Details |
|---|---|
| **Download** | [https://jena.apache.org/download/](https://jena.apache.org/download/) |
| **Package** | Download **`apache-jena-fuseki-4.x.x.zip`** (the standalone server) |
| **Requirement** | Java 11+ must be installed first |

### 4. Composer (PHP Dependency Manager)

| Requirement | Details |
|---|---|
| **Download** | [https://getcomposer.org/download/](https://getcomposer.org/download/) |
| **Windows** | Download and run `Composer-Setup.exe` |
| **Verify** | Run `composer --version` in your terminal |

### 5. EasyRdf (Installed via Composer)

EasyRdf will be installed automatically in [Step 7](#step-7--install-php-dependencies-via-composer) via `composer install`. No manual download is required.

---

## 🚀 Step-by-Step Local Deployment

### Step 1 — Install & Start Apache Jena Fuseki

**1.1 Download Fuseki**

Go to [https://jena.apache.org/download/](https://jena.apache.org/download/) and download the latest **Apache Jena Fuseki** binary distribution:

```
apache-jena-fuseki-4.x.x.zip
```

**1.2 Extract the Archive**

Extract the ZIP file to a location of your choice, for example:

```
C:\tools\apache-jena-fuseki-4.x.x\
```

**1.3 Start the Fuseki Server**

Open a terminal (PowerShell or Command Prompt) and navigate to the Fuseki directory:

```powershell
# Windows — Command Prompt or PowerShell
cd C:\tools\apache-jena-fuseki-4.x.x
.\fuseki-server.bat
```

```bash
# Linux / macOS
cd /opt/apache-jena-fuseki-4.x.x
./fuseki-server
```

You should see output similar to:

```
[2026-06-23 03:00:00] INFO  Apache Jena Fuseki 4.x.x
[2026-06-23 03:00:00] INFO  Started on port 3030
```

**1.4 Verify Fuseki is Running**

Open your browser and navigate to:

```
http://localhost:3030
```

You should see the **Apache Jena Fuseki** web management interface.

> [!TIP]
> To run Fuseki as a background service on Windows, you can create a batch file or use `nssm` (Non-Sucking Service Manager) to register it as a Windows service.

---

### Step 2 — Create the CDS Dataset in Fuseki

**2.1 Open the Fuseki Admin UI**

Navigate to:

```
http://localhost:3030
```

**2.2 Create a New Dataset**

1. Click **"Manage"** in the top navigation bar
2. Click **"New Dataset"** (or the **"add new dataset"** tab)
3. Fill in the form:

| Field | Value |
|---|---|
| **Dataset name** | `cds` |
| **Dataset type** | `TDB2 – Persistent` |

4. Click **"Create Dataset"**

> [!NOTE]
> Choose **TDB2** (not in-memory) so your data persists across Fuseki restarts. The dataset will be available at the SPARQL endpoint: `http://localhost:3030/cds/sparql`

**2.3 Verify the Dataset**

After creation, the dataset should appear in the dataset list. You can verify the endpoints:

| Endpoint | URL |
|---|---|
| **SPARQL Query** | `http://localhost:3030/cds/sparql` |
| **SPARQL Update** | `http://localhost:3030/cds/update` |
| **Graph Store (Read)** | `http://localhost:3030/cds/data` |
| **Graph Store (Write)** | `http://localhost:3030/cds/data` |

---

### Step 3 — Upload the OWL Ontology to Fuseki

The OWL ontology file (`cds_ontology.owl`) defines the clinical domain model including classes, properties, and inference rules.

**Option A — Via Fuseki Web UI (Recommended)**

1. Navigate to `http://localhost:3030`
2. Click on the **`cds`** dataset
3. Click **"Upload Data"** (or **"upload files"** tab)
4. Click **"Select Files..."** and choose `ontology/cds_ontology.owl`
5. Set the **destination graph** to `default` (or leave empty for the default graph)
6. Click **"Upload"**

**Option B — Via cURL Command**

```bash
curl -X POST "http://localhost:3030/cds/data" \
  -H "Content-Type: application/rdf+xml" \
  --data-binary @ontology/cds_ontology.owl
```

For Turtle format (`.ttl`):

```bash
curl -X POST "http://localhost:3030/cds/data" \
  -H "Content-Type: text/turtle" \
  --data-binary @ontology/cds_ontology.ttl
```

**Option C — Via PowerShell (Windows)**

```powershell
Invoke-RestMethod -Uri "http://localhost:3030/cds/data" `
  -Method POST `
  -ContentType "application/rdf+xml" `
  -InFile "ontology\cds_ontology.owl"
```

> [!IMPORTANT]
> Upload the ontology **before** the patient data to ensure class and property definitions are available for data validation.

---

### Step 4 — Upload the RDF Patient Data to Fuseki

The RDF patient data file (`data/patient_data.ttl`) contains sample patient records encoded as linked data.

**Option A — Via Fuseki Web UI**

1. Navigate to `http://localhost:3030` → select the **`cds`** dataset
2. Click **"Upload Data"**
3. Select `data/patient_data.ttl`
4. Ensure the graph destination is `default`
5. Click **"Upload"**

**Option B — Via cURL Command**

```bash
curl -X POST "http://localhost:3030/cds/data" \
  -H "Content-Type: text/turtle" \
  --data-binary @data/patient_data.ttl
```

**Option C — Via PowerShell (Windows)**

```powershell
Invoke-RestMethod -Uri "http://localhost:3030/cds/data" `
  -Method POST `
  -ContentType "text/turtle" `
  -InFile "data\patient_data.ttl"
```

**Verify Upload — Run a Test Query**

Go to `http://localhost:3030` → select `cds` → **"Query"** tab, and run:

```sparql
SELECT (COUNT(*) AS ?count) WHERE { ?s ?p ?o }
```

You should see a non-zero count confirming data was loaded.

---

### Step 5 — Enable OWL Reasoning in Fuseki

By default, Fuseki does **not** perform OWL reasoning. You must configure a reasoner to enable inference (e.g., subclass reasoning, property chain inference, restriction-based classification).

**5.1 Stop the Fuseki Server**

Press `Ctrl + C` in the terminal where Fuseki is running.

**5.2 Create the Configuration File**

Create a file named `config.ttl` in the Fuseki root directory (e.g., `C:\tools\apache-jena-fuseki-4.x.x\config.ttl`):

```turtle
@prefix :        <#> .
@prefix fuseki:  <http://jena.apache.org/fuseki#> .
@prefix rdf:     <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix rdfs:    <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ja:      <http://jena.hpl.hp.com/2005/11/Assembler#> .
@prefix tdb2:    <http://jena.apache.org/2016/tdb#> .

## ─── Fuseki Server ──────────────────────────────────────────────
:service a fuseki:Service ;
    fuseki:name                "cds" ;
    fuseki:serviceQuery        "sparql" ;
    fuseki:serviceQuery        "query" ;
    fuseki:serviceUpdate       "update" ;
    fuseki:serviceUpload       "upload" ;
    fuseki:serviceReadWriteGraphStore "data" ;
    fuseki:dataset             :inf_dataset ;
    .

## ─── Inference Dataset (OWL Reasoning Layer) ────────────────────
:inf_dataset a ja:InfModel ;
    ja:baseModel :tdb_dataset ;
    ja:reasoner [
        ja:reasonerURL <http://jena.hpl.hp.com/2003/OWLFBRuleReasoner>
    ] ;
    .

## ─── TDB2 Base Dataset ─────────────────────────────────────────
:tdb_dataset a tdb2:DatasetTDB2 ;
    tdb2:location "databases/cds" ;
    .
```

> [!NOTE]
> The `OWLFBRuleReasoner` provides OWL reasoning with forward and backward chaining. For lighter reasoning, you can use:
> - `http://jena.hpl.hp.com/2003/OWLMicroFBRuleReasoner` — OWL Micro (faster, fewer inferences)
> - `http://jena.hpl.hp.com/2003/OWLMiniFBRuleReasoner` — OWL Mini (balanced)
> - `http://jena.hpl.hp.com/2003/RDFSRuleReasoner` — RDFS only (fastest)

**5.3 Restart Fuseki with the Configuration File**

```powershell
# Windows
.\fuseki-server.bat --config=config.ttl
```

```bash
# Linux / macOS
./fuseki-server --config=config.ttl
```

**5.4 Re-upload Data (if needed)**

If you previously created the dataset via the web UI, you may need to re-upload the ontology and patient data after switching to the configuration-based approach, because the `config.ttl` creates a new TDB2 store at `databases/cds`.

Repeat [Step 3](#step-3--upload-the-owl-ontology-to-fuseki) and [Step 4](#step-4--upload-the-rdf-patient-data-to-fuseki) to re-upload the data.

---

### Step 6 — Set Up the PHP Project in XAMPP

**6.1 Locate the XAMPP htdocs Directory**

The default location is:

```
C:\xampp\htdocs\
```

**6.2 Copy the Project**

Copy the entire `smart-cds` project folder into the htdocs directory:

```powershell
# Option 1 — Copy via command line
xcopy /E /I "C:\path\to\smart-cds" "C:\xampp\htdocs\smart-cds"
```

```powershell
# Option 2 — Or simply move/clone the project directly into htdocs
cd C:\xampp\htdocs
git clone <repository-url> smart-cds
```

After copying, the path should be:

```
C:\xampp\htdocs\smart-cds\
```

**6.3 Verify the Structure**

Ensure the following key files exist:

```
C:\xampp\htdocs\smart-cds\index.php
C:\xampp\htdocs\smart-cds\composer.json
C:\xampp\htdocs\smart-cds\config.php
```

---

### Step 7 — Install PHP Dependencies via Composer

**7.1 Open a Terminal in the Project Directory**

```powershell
cd C:\xampp\htdocs\smart-cds
```

**7.2 Install Dependencies**

```bash
composer install
```

This will read `composer.json` and install:

- **EasyRdf** (`easyrdf/easyrdf`) — PHP library for RDF parsing and SPARQL queries
- Any other project dependencies

You should see output like:

```
Installing dependencies from lock file (including require-dev)
  - Installing easyrdf/easyrdf (1.1.1): Extracting archive
Generating autoload files
```

> [!TIP]
> If you encounter memory issues during installation, run:
> ```bash
> php -d memory_limit=-1 composer.phar install
> ```

---

### Step 8 — Configure the Application

**8.1 Open `config.php`**

Edit the file `config.php` in the project root:

```php
<?php
/**
 * Smart CDS Configuration
 * 
 * Adjust these settings to match your local environment.
 */

return [
    // ─── Fuseki SPARQL Endpoint ─────────────────────────────────
    'fuseki' => [
        'base_url'       => 'http://localhost:3030',
        'dataset'        => 'cds',
        'sparql_endpoint'=> 'http://localhost:3030/cds/sparql',
        'update_endpoint'=> 'http://localhost:3030/cds/update',
        'data_endpoint'  => 'http://localhost:3030/cds/data',
    ],

    // ─── Ontology Namespace ─────────────────────────────────────
    'ontology' => [
        'namespace' => 'http://example.org/cds#',
        'prefix'    => 'cds',
    ],

    // ─── Application Settings ───────────────────────────────────
    'app' => [
        'name'    => 'Smart Clinical Decision Support System',
        'version' => '1.0.0',
        'debug'   => true,   // Set to false in production
    ],
];
```

**8.2 Verify the Fuseki URL**

Ensure the `sparql_endpoint` URL matches your Fuseki setup:

- **Default**: `http://localhost:3030/cds/sparql`
- If Fuseki runs on a different port, update accordingly
- If the dataset has a different name, change `cds` to your dataset name

---

### Step 9 — Run the Application

**9.1 Start Apache in XAMPP**

1. Open **XAMPP Control Panel**
2. Click **"Start"** next to **Apache**
3. Ensure the status shows **green** (running)

**9.2 Ensure Fuseki is Running**

Make sure Fuseki is still running (from [Step 1](#step-1--install--start-apache-jena-fuseki) or [Step 5](#step-5--enable-owl-reasoning-in-fuseki)):

```powershell
# If not running, start it with the reasoning config
cd C:\tools\apache-jena-fuseki-4.x.x
.\fuseki-server.bat --config=config.ttl
```

**9.3 Open the Application**

Navigate to:

```
http://localhost/smart-cds/
```

You should see the **Smart CDS Dashboard** with:

- 🏠 Dashboard overview with patient statistics
- 👤 Patient lookup and detail views
- 🔍 SPARQL query interface
- 💊 Drug interaction checker
- 📊 Risk assessment results
- 🧪 Lab result interpretation

---

### Step 10 — Verify Inference is Working

To confirm that OWL reasoning is active and producing inferred triples, run the following test queries in the Fuseki web UI (`http://localhost:3030` → select `cds` → **Query** tab):

**Test 1 — Verify Subclass Inference**

```sparql
PREFIX cds: <http://example.org/cds#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

# This should return inferred types via subclass reasoning.
# E.g., if Diabetes rdfs:subClassOf ChronicDisease,
# then a patient diagnosed with Diabetes should also be
# inferred as having a ChronicDisease.

SELECT ?patient ?disease ?superclass WHERE {
    ?patient cds:hasDiagnosis ?disease .
    ?disease rdf:type ?type .
    ?type rdfs:subClassOf ?superclass .
}
LIMIT 20
```

**Test 2 — Verify Property Chain / Restriction Inference**

```sparql
PREFIX cds: <http://example.org/cds#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

# Check for inferred risk classifications.
# If reasoning is active, patients meeting certain criteria
# should be automatically classified into risk categories.

SELECT ?patient ?riskCategory WHERE {
    ?patient rdf:type cds:Patient .
    ?patient cds:hasRiskLevel ?riskCategory .
}
LIMIT 20
```

**Test 3 — Count Inferred vs. Explicit Triples**

```sparql
# Total triples (explicit + inferred)
SELECT (COUNT(*) AS ?total) WHERE { ?s ?p ?o }
```

> [!TIP]
> Compare this count with the count you got in [Step 4](#step-4--upload-the-rdf-patient-data-to-fuseki). If reasoning is active, the total should be **higher** due to inferred triples.

---

## 📄 Fuseki Reasoning Configuration Reference

Below is the complete `config.ttl` file with detailed comments for enabling OWL reasoning:

```turtle
# ═══════════════════════════════════════════════════════════════════
# Apache Jena Fuseki — Smart CDS Configuration with OWL Reasoning
# ═══════════════════════════════════════════════════════════════════
#
# Place this file in the Fuseki root directory and start with:
#   fuseki-server --config=config.ttl
#
# This configuration creates a dataset named "cds" backed by TDB2
# persistent storage with OWL Full reasoning enabled.
# ═══════════════════════════════════════════════════════════════════

@prefix :        <#> .
@prefix fuseki:  <http://jena.apache.org/fuseki#> .
@prefix rdf:     <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix rdfs:    <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ja:      <http://jena.hpl.hp.com/2005/11/Assembler#> .
@prefix tdb2:    <http://jena.apache.org/2016/tdb#> .

## ─── Service Definition ─────────────────────────────────────────
## Exposes the CDS dataset at http://localhost:3030/cds/
:service a fuseki:Service ;
    fuseki:name                "cds" ;

    # Query endpoints (accessible via /cds/sparql or /cds/query)
    fuseki:serviceQuery        "sparql" ;
    fuseki:serviceQuery        "query" ;

    # Update endpoint (for SPARQL UPDATE operations)
    fuseki:serviceUpdate       "update" ;

    # File upload endpoint
    fuseki:serviceUpload       "upload" ;

    # Graph Store Protocol (read/write)
    fuseki:serviceReadWriteGraphStore "data" ;

    # Link to the inference-enabled dataset
    fuseki:dataset             :inf_dataset ;
    .

## ─── Inference Model (OWL Reasoning Layer) ──────────────────────
## Wraps the base TDB2 dataset with an OWL reasoner.
## All SPARQL queries will see both explicit and inferred triples.
:inf_dataset a ja:InfModel ;
    # The underlying persistent dataset
    ja:baseModel :tdb_dataset ;

    # OWL Full reasoner (forward + backward chaining)
    # Supports: subclass inference, property domains/ranges,
    #           someValuesFrom, allValuesFrom, intersectionOf,
    #           hasValue restrictions, transitive/symmetric props,
    #           inverse properties, and more.
    ja:reasoner [
        ja:reasonerURL <http://jena.hpl.hp.com/2003/OWLFBRuleReasoner>
    ] ;
    .

## ─── Base TDB2 Dataset ─────────────────────────────────────────
## Persistent on-disk storage for RDF triples.
## Data directory is relative to where Fuseki is started.
:tdb_dataset a tdb2:DatasetTDB2 ;
    tdb2:location "databases/cds" ;
    .


# ═══════════════════════════════════════════════════════════════════
# Alternative Reasoner Options (uncomment one to switch):
# ═══════════════════════════════════════════════════════════════════
#
# ── OWL Micro (Fastest OWL — basic subclass + restriction) ──────
# :inf_dataset a ja:InfModel ;
#     ja:baseModel :tdb_dataset ;
#     ja:reasoner [
#         ja:reasonerURL <http://jena.hpl.hp.com/2003/OWLMicroFBRuleReasoner>
#     ] ;
#     .
#
# ── OWL Mini (Moderate — more rules than Micro) ─────────────────
# :inf_dataset a ja:InfModel ;
#     ja:baseModel :tdb_dataset ;
#     ja:reasoner [
#         ja:reasonerURL <http://jena.hpl.hp.com/2003/OWLMiniFBRuleReasoner>
#     ] ;
#     .
#
# ── RDFS Only (Lightest — subclass/subproperty/domain/range) ────
# :inf_dataset a ja:InfModel ;
#     ja:baseModel :tdb_dataset ;
#     ja:reasoner [
#         ja:reasonerURL <http://jena.hpl.hp.com/2003/RDFSRuleReasoner>
#     ] ;
#     .
# ═══════════════════════════════════════════════════════════════════
```

---

## 📁 Project Structure

```
smart-cds/
│
├── 📄 README.md                    # This deployment guide
├── 📄 composer.json                # PHP dependency definitions
├── 📄 composer.lock                # Locked dependency versions
├── 📄 config.php                   # Application configuration
├── 📄 index.php                    # Main entry point / router
├── 📄 .htaccess                    # Apache URL rewrite rules
│
├── 📂 ontology/                    # Semantic Web definitions
│   ├── 📄 cds_ontology.owl         # OWL 2.0 ontology (RDF/XML)
│   └── 📄 cds_ontology.ttl         # OWL ontology (Turtle format)
│
├── 📂 data/                        # RDF data files
│   └── 📄 patient_data.ttl         # Sample patient data (Turtle)
│
├── 📂 src/                         # PHP source code
│   ├── 📄 SparqlClient.php         # Fuseki SPARQL query client
│   ├── 📄 PatientService.php       # Patient data operations
│   ├── 📄 DiagnosisService.php     # Diagnosis inference logic
│   ├── 📄 DrugInteraction.php      # Drug interaction detection
│   └── 📄 RiskAssessment.php       # Patient risk stratification
│
├── 📂 api/                         # REST API endpoints
│   ├── 📄 patients.php             # GET /api/patients.php
│   ├── 📄 diagnoses.php            # GET /api/diagnoses.php
│   ├── 📄 drug-interactions.php    # GET /api/drug-interactions.php
│   ├── 📄 risk-assessment.php      # GET /api/risk-assessment.php
│   └── 📄 sparql.php               # POST /api/sparql.php (raw query)
│
├── 📂 public/                      # Frontend assets
│   ├── 📂 css/
│   │   └── 📄 style.css            # Main stylesheet
│   ├── 📂 js/
│   │   └── 📄 app.js               # Frontend JavaScript
│   └── 📂 img/
│       └── 📄 logo.png             # Application logo
│
├── 📂 templates/                   # PHP view templates
│   ├── 📄 layout.php               # Base HTML layout
│   ├── 📄 dashboard.php            # Dashboard view
│   ├── 📄 patient-detail.php       # Patient detail view
│   └── 📄 query-builder.php        # SPARQL query interface
│
├── 📂 fuseki-config/               # Fuseki configuration files
│   └── 📄 config.ttl               # Reasoning-enabled config
│
└── 📂 vendor/                      # Composer dependencies (auto-generated)
    └── ...
```

---

## 📦 `composer.json` Reference

```json
{
    "name": "smart-cds/clinical-decision-support",
    "description": "Smart Clinical Decision Support System powered by Semantic Web Technologies, OWL reasoning, and SPARQL queries via Apache Jena Fuseki.",
    "type": "project",
    "version": "1.0.0",
    "license": "MIT",
    "keywords": [
        "clinical-decision-support",
        "semantic-web",
        "owl-ontology",
        "sparql",
        "healthcare",
        "rdf",
        "fuseki",
        "easyrdf"
    ],
    "authors": [
        {
            "name": "Smart CDS Team",
            "email": "team@smart-cds.example.com"
        }
    ],
    "require": {
        "php": ">=8.0",
        "easyrdf/easyrdf": "^1.1"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "SmartCDS\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "SmartCDS\\Tests\\": "tests/"
        }
    },
    "config": {
        "optimize-autoloader": true,
        "sort-packages": true
    },
    "scripts": {
        "test": "phpunit --configuration phpunit.xml",
        "lint": "php -l src/"
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

---

## 🔧 Troubleshooting

### ❌ CORS Issues Between PHP and Fuseki

**Symptom:** Browser console shows `Access-Control-Allow-Origin` errors when the frontend JavaScript tries to call Fuseki directly.

**Solution A — Route all requests through PHP (Recommended)**

Ensure all SPARQL queries go through your PHP backend (`api/sparql.php`), not directly from the browser to Fuseki. This avoids CORS entirely.

**Solution B — Add CORS Headers to Fuseki**

Start Fuseki with CORS enabled:

```powershell
# Set the JAVA_OPTIONS environment variable before starting Fuseki
$env:JAVA_OPTIONS = "-Djetty.request.header.size=65536"

.\fuseki-server.bat --config=config.ttl
```

Alternatively, create a Jetty CORS configuration. Add the following to `webapp/WEB-INF/web.xml` in the Fuseki directory (before the closing `</web-app>` tag):

```xml
<filter>
    <filter-name>cross-origin</filter-name>
    <filter-class>org.eclipse.jetty.servlets.CrossOriginFilter</filter-class>
    <init-param>
        <param-name>allowedOrigins</param-name>
        <param-value>http://localhost,http://localhost:80</param-value>
    </init-param>
    <init-param>
        <param-name>allowedMethods</param-name>
        <param-value>GET,POST,OPTIONS,HEAD</param-value>
    </init-param>
    <init-param>
        <param-name>allowedHeaders</param-name>
        <param-value>Content-Type,Accept,Origin</param-value>
    </init-param>
</filter>
<filter-mapping>
    <filter-name>cross-origin</filter-name>
    <url-pattern>/*</url-pattern>
</filter-mapping>
```

---

### ❌ Fuseki Out of Memory / Slow with Large Ontologies

**Symptom:** Fuseki crashes with `java.lang.OutOfMemoryError` or queries take excessively long.

**Solution:** Increase JVM heap size before starting Fuseki:

```powershell
# Windows — PowerShell
$env:JVM_ARGS = "-Xmx4G -Xms2G"
.\fuseki-server.bat --config=config.ttl
```

```bash
# Linux / macOS
export JVM_ARGS="-Xmx4G -Xms2G"
./fuseki-server --config=config.ttl
```

| Flag | Description |
|---|---|
| `-Xmx4G` | Maximum heap size (4 GB) |
| `-Xms2G` | Initial heap size (2 GB) |

> [!TIP]
> For large ontologies with extensive reasoning, allocate at least **2–4 GB** of heap. The OWL Full reasoner (`OWLFBRuleReasoner`) is memory-intensive; consider switching to `OWLMicroFBRuleReasoner` if memory is constrained.

---

### ❌ PHP cURL Extension Not Enabled

**Symptom:** PHP throws `Call to undefined function curl_init()` or EasyRdf fails to make HTTP requests.

**Solution:**

1. Open the PHP configuration file:
   ```
   C:\xampp\php\php.ini
   ```

2. Find the following line:
   ```ini
   ;extension=curl
   ```

3. **Remove the semicolon** to uncomment it:
   ```ini
   extension=curl
   ```

4. **Restart Apache** in the XAMPP Control Panel.

5. Verify cURL is enabled:
   ```bash
   php -m | findstr curl
   ```

> [!IMPORTANT]
> Also ensure `extension=openssl` is uncommented, as EasyRdf may require it for HTTPS connections.

---

### ❌ OWL Reasoning Not Returning Inferred Results

**Symptom:** SPARQL queries only return explicitly asserted triples; no inferred triples appear.

**Possible Causes & Solutions:**

| Cause | Solution |
|---|---|
| **Fuseki started without `--config`** | Ensure you start with `fuseki-server --config=config.ttl` |
| **Dataset created via web UI** | Web-UI datasets don't use reasoning; you must use the config file approach |
| **Wrong reasoner URL** | Verify the `ja:reasonerURL` in `config.ttl` is spelled correctly |
| **Ontology not loaded** | Re-upload the `.owl` file after switching to the config-based dataset |
| **Namespace mismatch** | Ensure the namespace in your ontology matches the one used in patient data and queries |
| **Using `InfDataset` instead of `InfModel`** | Jena's `InfModel` applies reasoning to the default graph; for named graphs, use a different approach |

**Debug Query — Check if reasoner is active:**

```sparql
# If reasoning is active, this returns rdfs:subClassOf inferences
# that are NOT explicitly in your data
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl:  <http://www.w3.org/2002/07/owl#>

SELECT ?sub ?super WHERE {
    ?sub rdfs:subClassOf ?super .
    FILTER(?super != owl:Thing && ?sub != ?super)
}
LIMIT 20
```

---

### ❌ Common SPARQL Syntax Errors

**1. Missing PREFIX declarations**

```sparql
# ❌ Wrong — undefined prefix
SELECT ?patient WHERE { ?patient cds:hasName ?name }

# ✅ Correct — prefix declared
PREFIX cds: <http://example.org/cds#>
SELECT ?patient WHERE { ?patient cds:hasName ?name }
```

**2. Missing period (`.`) at end of triple patterns**

```sparql
# ❌ Wrong — missing dot separator
SELECT ?p ?n WHERE {
    ?p rdf:type cds:Patient
    ?p cds:hasName ?n
}

# ✅ Correct — dots separate triple patterns
SELECT ?p ?n WHERE {
    ?p rdf:type cds:Patient .
    ?p cds:hasName ?n .
}
```

**3. Using `=` instead of FILTER for comparisons**

```sparql
# ❌ Wrong
SELECT ?p WHERE { ?p cds:age = 65 }

# ✅ Correct
SELECT ?p ?age WHERE {
    ?p cds:age ?age .
    FILTER(?age = 65)
}
```

**4. Literal type mismatches**

```sparql
# ❌ Wrong — comparing string to integer
FILTER(?age > "60")

# ✅ Correct — explicit type or use xsd:integer
FILTER(?age > 60)
# or
FILTER(?age > "60"^^xsd:integer)
```

**5. Forgetting `rdf:type` shorthand `a`**

```sparql
# Both are equivalent:
?patient rdf:type cds:Patient .
?patient a cds:Patient .
```

---

## 🌐 API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/patients.php` | List all patients |
| `GET` | `/api/patients.php?id={id}` | Get patient details |
| `GET` | `/api/diagnoses.php?patient={id}` | Get diagnoses for a patient |
| `GET` | `/api/drug-interactions.php?patient={id}` | Check drug interactions |
| `GET` | `/api/risk-assessment.php?patient={id}` | Get risk assessment |
| `POST` | `/api/sparql.php` | Execute a raw SPARQL query |

---

## 📄 License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for details.

---

<p align="center">
  <strong>Smart Clinical Decision Support System</strong><br>
  Built with ❤️ using Semantic Web Technologies
</p>

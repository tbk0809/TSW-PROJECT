# 🚀 Quick Start Guide - Smart Clinical Decision System

This guide will help you get the system up and running in just a few simple steps.

## What You Need
1. **XAMPP** (Installed and running)
2. **Apache Jena Fuseki** (Downloaded and extracted)

---

## Step 1: Start Your Servers

1. **Start Apache**: Open your XAMPP Control Panel and click **Start** next to Apache.
2. **Start Fuseki**: 
   - Open your terminal or command prompt.
   - Go to your extracted Fuseki folder (e.g., `cd C:\tools\apache-jena-fuseki`).
   - Run `fuseki-server.bat`.

## Step 2: Set Up the Database

1. Open your web browser and go to **[http://localhost:3030](http://localhost:3030)** (This is the Fuseki dashboard).
2. Click **"Manage datasets"** -> **"add new dataset"**.
3. Name the dataset **`cds`**, select **TDB2 - Persistent**, and click Create.

## Step 3: Install PHP Dependencies

1. Open a terminal inside this project folder (`smart-cds`).
2. Run the command: `composer install`
*(This installs EasyRdf so PHP can talk to Fuseki).*

## Step 4: Use the System!

1. Move this entire `smart-cds` folder into your XAMPP's `htdocs` directory (usually `C:\xampp\htdocs\`).
2. Open your web browser and go to **[http://localhost/smart-cds/](http://localhost/smart-cds/)**.
3. **Important First Step**: The very first time you open the dashboard, you need to load the clinical data.
   - Go to the **SPARQL Console** or **Settings** page in the app and click **"Load Ontology and Data"**. 
   - *(Alternatively, you can manually upload `ontology/clinical-decision.owl` and `ontology/patient-data.ttl` directly via the Fuseki interface at localhost:3030).*

---

## How to use the Dashboard:
- **Dashboard Overview**: See patient statistics and risk levels at a glance.
- **Patients List**: View all loaded patient profiles. Click on a patient to see their full medical record, lab results, and inferred risk status.
- **Inference Engine**: Click "Run Classification" to let the system analyze symptoms and automatically assign risk levels or diagnose patients using the semantic rules.
- **Diagnosis Tools**: Manually select symptoms to get drug contraindication warnings and suggested treatments.

🎉 **You are now ready to use the Smart Clinical Decision System!**

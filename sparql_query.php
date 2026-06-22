<!-- SPARQL Query Explorer View -->
<style>
    .sparql-container {
        max-width: 1200px;
    }
    .query-selector-card {
        animation: fadeInUp 0.4s ease;
    }
    .sparql-toolbar {
        display: flex;
        gap: 16px;
        align-items: flex-end;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .sparql-toolbar .form-group { margin-bottom: 0; flex: 1; min-width: 260px; }

    .sparql-editor-wrapper {
        position: relative;
        margin-bottom: 20px;
    }
    .sparql-editor {
        width: 100%;
        min-height: 240px;
        padding: 20px;
        font-family: 'Cascadia Code', 'Fira Code', 'JetBrains Mono', 'Consolas', monospace;
        font-size: 14px;
        line-height: 1.7;
        color: #E0E6F0;
        background: #0D1B2A;
        border: 1.5px solid rgba(52,152,219,0.2);
        border-radius: var(--radius-md);
        resize: vertical;
        outline: none;
        tab-size: 2;
        transition: var(--transition);
    }
    .sparql-editor:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(52,152,219,0.15);
    }
    .sparql-editor::placeholder {
        color: rgba(224,230,240,0.3);
    }
    .editor-line-numbers {
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 44px;
        background: rgba(13,27,42,0.95);
        border-right: 1px solid rgba(52,152,219,0.12);
        border-radius: var(--radius-md) 0 0 var(--radius-md);
        padding: 20px 0;
        pointer-events: none;
        display: none; /* hidden for textarea, shown in enhanced mode */
    }

    .sparql-actions {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    .query-time {
        font-size: 13px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .query-time .time-value {
        font-weight: 700;
        color: var(--risk-low);
    }

    .results-card {
        animation: fadeInUp 0.5s ease;
    }
    .results-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 16px;
    }
    .result-count {
        font-size: 13px;
        color: var(--text-muted);
    }

    .sparql-results-table {
        font-size: 13px;
    }
    .sparql-results-table thead th {
        background: rgba(26,43,74,0.08);
        font-size: 11px;
        letter-spacing: 0.8px;
        padding: 12px 14px;
    }
    .sparql-results-table tbody tr:nth-child(even) {
        background: rgba(26,43,74,0.02);
    }
    .sparql-results-table tbody td {
        padding: 10px 14px;
        font-family: 'Cascadia Code', 'Consolas', monospace;
        font-size: 12px;
        word-break: break-word;
        max-width: 300px;
    }

    .error-display {
        background: rgba(231,76,60,0.06);
        border: 1.5px solid rgba(231,76,60,0.2);
        border-radius: var(--radius-md);
        padding: 20px;
        color: var(--risk-high);
        font-size: 14px;
        display: none;
        animation: fadeInUp 0.3s ease;
    }
    .error-display.active { display: block; }
    .error-display h4 { margin-bottom: 8px; }
    .error-display pre {
        font-family: 'Cascadia Code', monospace;
        font-size: 12px;
        background: rgba(231,76,60,0.06);
        padding: 12px;
        border-radius: 6px;
        overflow-x: auto;
        white-space: pre-wrap;
        color: var(--text-primary);
        margin-top: 8px;
    }

    @media (max-width: 768px) {
        .sparql-toolbar { flex-direction: column; align-items: stretch; }
        .sparql-toolbar .form-group { min-width: 100%; }
    }
</style>

<div class="sparql-container">
    <!-- Page Header -->
    <div class="page-header">
        <h2>🔍 SPARQL Query Explorer</h2>
        <p>Execute SPARQL queries against the clinical knowledge graph and explore RDF data</p>
    </div>

    <!-- Query Editor Card -->
    <div class="card query-selector-card" style="margin-bottom:28px;">
        <div class="card-header">
            <div class="card-title"><span class="icon">📝</span> Query Editor</div>
        </div>

        <!-- Pre-loaded Queries -->
        <div class="sparql-toolbar">
            <div class="form-group">
                <label class="form-label">Pre-loaded Queries</label>
                <select class="form-select" id="querySelector" onchange="loadPresetQuery()">
                    <option value="">— Select a query —</option>
                    <option value="highRisk">All High-Risk Patients</option>
                    <option value="medsForDisease">Medications for Disease</option>
                    <option value="patientSymptoms">Patient Symptoms by ID</option>
                    <option value="riskCount">Patient Count per Risk Level</option>
                    <option value="multiSymptom">Patients with &gt;2 Symptoms</option>
                    <option value="drugContra">Drug Contraindications</option>
                    <option value="clinicalSummary">Full Patient Clinical Summary</option>
                    <option value="ageDisease">Diseases in Patients Over 50</option>
                </select>
            </div>
        </div>

        <!-- SPARQL Editor -->
        <div class="sparql-editor-wrapper">
            <textarea class="sparql-editor" id="sparqlEditor" placeholder="Enter your SPARQL query here...

PREFIX cds: <http://example.org/smartcds#>
SELECT ?patient ?name
WHERE {
  ?patient a cds:Patient ;
           cds:hasName ?name .
}" spellcheck="false"></textarea>
        </div>

        <!-- Actions -->
        <div class="sparql-actions">
            <button class="btn btn-primary btn-lg" id="runQueryBtn" onclick="runQuery()">
                ▶ Run Query
            </button>
            <button class="btn btn-outline" onclick="clearQuery()">
                🗑️ Clear
            </button>
            <button class="btn btn-outline" id="exportCsvBtn" onclick="exportCSV()" style="display:none;">
                📥 Export to CSV
            </button>
            <div class="query-time" id="queryTime" style="display:none;">
                ⏱️ Execution time: <span class="time-value" id="queryTimeValue">0ms</span>
            </div>
        </div>
    </div>

    <!-- Error Display -->
    <div class="error-display" id="errorDisplay">
        <h4>❌ Query Error</h4>
        <p id="errorMessage"></p>
        <pre id="errorDetail"></pre>
    </div>

    <!-- Results -->
    <div class="card results-card" id="resultsCard" style="display:none;">
        <div class="results-header">
            <div class="card-title"><span class="icon">📊</span> Query Results</div>
            <div class="result-count" id="resultCount"></div>
        </div>
        <div class="table-container">
            <table class="sparql-results-table">
                <thead id="resultsHead"></thead>
                <tbody id="resultsBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    // --- Pre-loaded Queries ---
    const presetQueries = {
        highRisk: `PREFIX cds: <http://example.org/smartcds#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?patient ?name ?age ?riskLevel
WHERE {
  ?patient rdf:type cds:Patient ;
           cds:hasName ?name ;
           cds:hasAge ?age ;
           cds:hasRiskLevel ?riskLevel .
  FILTER(?riskLevel = "High")
}
ORDER BY ?name`,

        medsForDisease: `PREFIX cds: <http://example.org/smartcds#>

SELECT ?disease ?diseaseName ?medication ?medName
WHERE {
  ?disease a cds:Disease ;
           cds:hasName ?diseaseName .
  ?medication a cds:Medication ;
              cds:treatsDisease ?disease ;
              cds:hasName ?medName .
}
ORDER BY ?diseaseName`,

        patientSymptoms: `PREFIX cds: <http://example.org/smartcds#>

SELECT ?patient ?patientName ?symptom ?symptomName
WHERE {
  ?patient a cds:Patient ;
           cds:hasName ?patientName ;
           cds:hasSymptom ?symptom .
  ?symptom cds:hasName ?symptomName .
}
ORDER BY ?patientName`,

        riskCount: `PREFIX cds: <http://example.org/smartcds#>

SELECT ?riskLevel (COUNT(?patient) AS ?patientCount)
WHERE {
  ?patient a cds:Patient ;
           cds:hasRiskLevel ?riskLevel .
}
GROUP BY ?riskLevel
ORDER BY ?riskLevel`,

        multiSymptom: `PREFIX cds: <http://example.org/smartcds#>

SELECT ?patient ?name (COUNT(?symptom) AS ?symptomCount)
WHERE {
  ?patient a cds:Patient ;
           cds:hasName ?name ;
           cds:hasSymptom ?symptom .
}
GROUP BY ?patient ?name
HAVING (COUNT(?symptom) > 2)
ORDER BY DESC(?symptomCount)`,

        drugContra: `PREFIX cds: <http://example.org/smartcds#>

SELECT ?drug1 ?drug1Name ?drug2 ?drug2Name ?reason
WHERE {
  ?drug1 a cds:Medication ;
         cds:hasName ?drug1Name ;
         cds:contraindicatedWith ?drug2 .
  ?drug2 cds:hasName ?drug2Name .
  OPTIONAL { ?drug1 cds:contraindReason ?reason }
}`,

        clinicalSummary: `PREFIX cds: <http://example.org/smartcds#>

SELECT ?patient ?name ?age ?riskLevel ?disease ?symptom ?medication
WHERE {
  ?patient a cds:Patient ;
           cds:hasName ?name ;
           cds:hasAge ?age ;
           cds:hasRiskLevel ?riskLevel .
  OPTIONAL { ?patient cds:hasDiagnosis ?disease }
  OPTIONAL { ?patient cds:hasSymptom ?symptom }
  OPTIONAL { ?patient cds:takesMedication ?medication }
}
ORDER BY ?name
LIMIT 50`,

        ageDisease: `PREFIX cds: <http://example.org/smartcds#>

SELECT ?patient ?name ?age ?disease ?diseaseName
WHERE {
  ?patient a cds:Patient ;
           cds:hasName ?name ;
           cds:hasAge ?age ;
           cds:hasDiagnosis ?disease .
  ?disease cds:hasName ?diseaseName .
  FILTER(?age > 50)
}
ORDER BY DESC(?age)`
    };

    let lastResults = { headers: [], rows: [] };

    // --- Load Preset Query ---
    window.loadPresetQuery = function() {
        const key = document.getElementById('querySelector').value;
        if (key && presetQueries[key]) {
            document.getElementById('sparqlEditor').value = presetQueries[key];
        }
    };

    // --- Clear Query ---
    window.clearQuery = function() {
        document.getElementById('sparqlEditor').value = '';
        document.getElementById('querySelector').value = '';
        document.getElementById('resultsCard').style.display = 'none';
        document.getElementById('errorDisplay').classList.remove('active');
        document.getElementById('queryTime').style.display = 'none';
        document.getElementById('exportCsvBtn').style.display = 'none';
    };

    // --- Run Query ---
    window.runQuery = async function() {
        const query = document.getElementById('sparqlEditor').value.trim();
        if (!query) {
            showToast('warning', 'Empty Query', 'Please enter a SPARQL query.');
            return;
        }

        const btn = document.getElementById('runQueryBtn');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-inline"></span> Running...';
        btn.disabled = true;

        document.getElementById('errorDisplay').classList.remove('active');
        document.getElementById('resultsCard').style.display = 'none';

        const startTime = performance.now();

        try {
            const result = await apiFetch('sparql', {
                method: 'POST',
                body: query
            });

            const elapsed = Math.round(performance.now() - startTime);
            document.getElementById('queryTimeValue').textContent = elapsed + 'ms';
            document.getElementById('queryTime').style.display = 'flex';

            // Parse results
            let headers = [];
            let rows = [];

            if (result.results && result.results.bindings) {
                // Standard SPARQL JSON format
                headers = result.head?.vars || [];
                rows = result.results.bindings.map(binding => {
                    return headers.map(h => {
                        const v = binding[h];
                        return v ? (v.value || v) : '';
                    });
                });
            } else if (Array.isArray(result)) {
                if (result.length > 0) {
                    headers = Object.keys(result[0]);
                    rows = result.map(r => headers.map(h => {
                        const val = r[h];
                        return val !== null && val !== undefined ? String(val) : '';
                    }));
                }
            } else if (result.data && Array.isArray(result.data)) {
                headers = result.headers || (result.data.length ? Object.keys(result.data[0]) : []);
                rows = result.data.map(r => headers.map(h => String(r[h] || '')));
            } else if (result.columns && result.rows) {
                headers = result.columns;
                rows = result.rows;
            }

            lastResults = { headers, rows };
            renderResults(headers, rows);
            showToast('success', 'Query Complete', `${rows.length} result(s) returned in ${elapsed}ms`);

        } catch (err) {
            const elapsed = Math.round(performance.now() - startTime);
            document.getElementById('queryTimeValue').textContent = elapsed + 'ms';
            document.getElementById('queryTime').style.display = 'flex';

            const errorDisplay = document.getElementById('errorDisplay');
            document.getElementById('errorMessage').textContent = err.message || 'Query execution failed.';
            document.getElementById('errorDetail').textContent = query;
            errorDisplay.classList.add('active');
        } finally {
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    };

    // --- Render Results Table ---
    function renderResults(headers, rows) {
        const card = document.getElementById('resultsCard');
        const thead = document.getElementById('resultsHead');
        const tbody = document.getElementById('resultsBody');

        if (!headers.length || !rows.length) {
            card.style.display = 'block';
            thead.innerHTML = '';
            tbody.innerHTML = '<tr><td><div class="empty-state" style="padding:30px;"><div class="icon">📭</div><h3>No Results</h3><p>The query returned no results.</p></div></td></tr>';
            document.getElementById('resultCount').textContent = '0 results';
            document.getElementById('exportCsvBtn').style.display = 'none';
            return;
        }

        thead.innerHTML = '<tr>' + headers.map(h => `<th>${escapeHtml(h)}</th>`).join('') + '</tr>';
        tbody.innerHTML = rows.map((row, i) =>
            `<tr style="animation: fadeIn 0.2s ease ${i * 0.02}s both;">${row.map(cell => {
                let display = cell;
                // Shorten URIs
                if (typeof cell === 'string' && cell.startsWith('http')) {
                    const parts = cell.split(/[#\/]/);
                    display = parts[parts.length - 1] || cell;
                }
                return `<td title="${escapeHtml(cell)}">${escapeHtml(display)}</td>`;
            }).join('')}</tr>`
        ).join('');

        document.getElementById('resultCount').textContent = `${rows.length} result(s)`;
        document.getElementById('exportCsvBtn').style.display = 'inline-flex';
        card.style.display = 'block';
    }

    // --- Export CSV ---
    window.exportCSV = function() {
        const { headers, rows } = lastResults;
        if (!headers.length) {
            showToast('warning', 'No Data', 'No results to export.');
            return;
        }

        const escape = (str) => {
            if (typeof str !== 'string') str = String(str);
            if (str.includes(',') || str.includes('"') || str.includes('\n')) {
                return '"' + str.replace(/"/g, '""') + '"';
            }
            return str;
        };

        let csv = headers.map(escape).join(',') + '\n';
        rows.forEach(row => {
            csv += row.map(escape).join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `sparql_results_${new Date().toISOString().slice(0,10)}.csv`;
        link.click();
        URL.revokeObjectURL(link.href);
        showToast('success', 'Exported', 'CSV file downloaded successfully.');
    };

    // --- Tab Key in Textarea ---
    document.getElementById('sparqlEditor').addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            e.preventDefault();
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.substring(0, start) + '  ' + this.value.substring(end);
            this.selectionStart = this.selectionEnd = start + 2;
        }
        // Ctrl+Enter to run
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            runQuery();
        }
    });
})();
</script>

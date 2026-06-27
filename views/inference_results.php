<!-- Inference Results View -->
<style>
    .inference-container {
        max-width: 1200px;
    }

    .inference-controls {
        display: flex;
        align-items: flex-end;
        gap: 16px;
        margin-bottom: 28px;
        flex-wrap: wrap;
        animation: fadeInDown 0.4s ease;
    }
    .inference-controls .form-group {
        margin-bottom: 0;
        min-width: 240px;
    }

    .legend-card {
        animation: fadeInUp 0.5s ease 0.05s both;
        margin-bottom: 28px;
    }
    .legend-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 14px;
    }
    .legend-item {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 16px;
        border-radius: var(--radius-md);
        background: rgba(26,43,74,0.02);
        border: 1px solid var(--border);
        transition: var(--transition);
    }
    .legend-item:hover {
        background: rgba(52,152,219,0.04);
        border-color: var(--accent);
    }
    .legend-color {
        width: 40px; height: 40px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .legend-color-risk { background: var(--risk-high-bg); }
    .legend-color-diagnosis { background: rgba(52,152,219,0.12); }
    .legend-color-complex { background: rgba(155,89,182,0.12); }
    .legend-color-drug { background: rgba(243,156,18,0.12); }
    .legend-color-doctor { background: rgba(46,204,113,0.12); }
    .legend-color-related { background: rgba(26,43,74,0.08); }
    .legend-info h4 {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .legend-info p {
        font-size: 12px;
        color: var(--text-secondary);
        line-height: 1.5;
    }
    .legend-tag {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-left: 6px;
    }
    .tag-risk { background: var(--risk-high-bg); color: var(--risk-high); }
    .tag-diagnosis { background: var(--accent-light); color: var(--accent); }
    .tag-complex { background: rgba(155,89,182,0.12); color: #9B59B6; }
    .tag-drug { background: var(--risk-medium-bg); color: var(--risk-medium); }
    .tag-doctor { background: var(--risk-low-bg); color: #1B9E52; }
    .tag-related { background: rgba(26,43,74,0.08); color: var(--text-secondary); }

    .loading-animation {
        display: none;
        text-align: center;
        padding: 60px 20px;
        animation: fadeIn 0.3s ease;
    }
    .loading-animation.active { display: block; }
    .loading-brain {
        font-size: 64px;
        animation: pulse 1.5s ease infinite;
        margin-bottom: 16px;
    }
    .loading-bars {
        display: flex;
        justify-content: center;
        gap: 6px;
        margin-top: 20px;
    }
    .loading-bar {
        width: 6px;
        height: 30px;
        background: var(--accent);
        border-radius: 3px;
        animation: loadingBar 1.2s ease infinite;
    }
    .loading-bar:nth-child(2) { animation-delay: 0.1s; }
    .loading-bar:nth-child(3) { animation-delay: 0.2s; }
    .loading-bar:nth-child(4) { animation-delay: 0.3s; }
    .loading-bar:nth-child(5) { animation-delay: 0.4s; }
    @keyframes loadingBar {
        0%, 100% { height: 14px; opacity: 0.4; }
        50% { height: 36px; opacity: 1; }
    }

    .inference-results-section {
        display: none;
        animation: fadeInUp 0.5s ease;
    }
    .inference-results-section.active { display: block; }

    .triple-table {
        font-size: 13px;
    }
    .triple-table tbody td {
        font-family: 'Cascadia Code', 'Consolas', monospace;
        font-size: 12px;
        word-break: break-word;
        padding: 12px 14px;
    }
    .triple-type-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .triple-type-risk { background: var(--risk-high-bg); color: var(--risk-high); }
    .triple-type-drug { background: var(--risk-medium-bg); color: #C07E0A; }
    .triple-type-property { background: var(--accent-light); color: var(--accent); }
    .triple-type-classification { background: rgba(155,89,182,0.12); color: #9B59B6; }
    .triple-type-diagnosis { background: rgba(46,204,113,0.12); color: #1B9E52; }
    .triple-type-default { background: rgba(26,43,74,0.06); color: var(--text-secondary); }

    .explanation-card {
        margin-top: 28px;
        animation: fadeInUp 0.5s ease 0.1s both;
    }
    .explanation-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .explanation-entry {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 16px;
        border-radius: var(--radius-md);
        background: rgba(52,152,219,0.03);
        border: 1px solid rgba(52,152,219,0.08);
        transition: var(--transition);
    }
    .explanation-entry:hover {
        background: rgba(52,152,219,0.06);
    }
    .explanation-entry-icon {
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
        background: var(--accent-light);
    }
    .explanation-entry-text h4 {
        font-size: 14px;
        margin-bottom: 4px;
    }
    .explanation-entry-text p {
        font-size: 13px;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    @media (max-width: 768px) {
        .legend-grid { grid-template-columns: 1fr; }
        .inference-controls { flex-direction: column; align-items: stretch; }
        .inference-controls .form-group { min-width: 100%; }
    }
</style>

<div class="inference-container">
    <!-- Page Header -->
    <div class="page-header">
        <h2>🧠 OWL Inference Engine</h2>
        <p>Run OWL DL reasoning to classify patients, detect drug interactions, and discover implied clinical facts</p>
    </div>

    <!-- Controls -->
    <div class="inference-controls">
        <div class="form-group">
            <label class="form-label">Filter by Patient</label>
            <select class="form-select" id="patientSelector">
                <option value="">All Patients</option>
            </select>
        </div>
        <button class="btn btn-primary btn-lg" id="classifyBtn" onclick="runClassification()">
            🧠 Run OWL Classification
        </button>
        <button class="btn btn-outline" onclick="clearResults()">
            🗑️ Clear
        </button>
    </div>

    <!-- Visual Legend -->
    <div class="card legend-card">
        <div class="card-header">
            <div class="card-title"><span class="icon">📖</span> Inference Legend</div>
        </div>
        <div class="legend-grid">
            <div class="legend-item">
                <div class="legend-color legend-color-risk">⚠️</div>
                <div class="legend-info">
                    <h4>HighRiskPatient Classification <span class="legend-tag tag-risk">Risk</span></h4>
                    <p>Patients classified as high risk based on age, number of symptoms, and diagnosed conditions.</p>
                </div>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-color-diagnosis">🩺</div>
                <div class="legend-info">
                    <h4>DiagnosedPatient Inference <span class="legend-tag tag-diagnosis">Diagnosis</span></h4>
                    <p>Patients inferred to have diagnoses based on symptom-disease mappings in the ontology.</p>
                </div>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-color-complex">🔬</div>
                <div class="legend-info">
                    <h4>ComplexCase Identification <span class="legend-tag tag-complex">Complex</span></h4>
                    <p>Patients with multiple concurrent conditions identified as complex cases requiring specialist review.</p>
                </div>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-color-drug">💊</div>
                <div class="legend-info">
                    <h4>Drug Contraindication Detection <span class="legend-tag tag-drug">Drug</span></h4>
                    <p>Medications that interact dangerously when prescribed together, flagged by the reasoner.</p>
                </div>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-color-doctor">👨‍⚕️</div>
                <div class="legend-info">
                    <h4>Doctor Specialization (Property Chain) <span class="legend-tag tag-doctor">Chain</span></h4>
                    <p>Inferred doctor specialization assignments based on patient diagnoses via OWL property chains.</p>
                </div>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-color-related">🔗</div>
                <div class="legend-info">
                    <h4>Related Condition Transitivity <span class="legend-tag tag-related">Transitive</span></h4>
                    <p>Conditions linked through transitive relationships: if A is related to B and B to C, then A is related to C.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Animation -->
    <div class="loading-animation" id="loadingAnimation">
        <div class="loading-brain">🧠</div>
        <h3 style="color:var(--text-secondary);font-size:18px;">Running OWL DL Reasoner</h3>
        <p style="color:var(--text-muted);font-size:14px;margin-top:8px;">Classifying patients and detecting inferred facts...</p>
        <div class="loading-bars">
            <div class="loading-bar"></div>
            <div class="loading-bar"></div>
            <div class="loading-bar"></div>
            <div class="loading-bar"></div>
            <div class="loading-bar"></div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="inference-results-section" id="resultsSection">
        <!-- Triples Table -->
        <div class="card" style="margin-bottom:28px;">
            <div class="card-header">
                <div class="card-title"><span class="icon">📊</span> Inferred Triples</div>
                <div id="tripleCount" class="result-count" style="font-size:13px;color:var(--text-muted);"></div>
            </div>
            <div class="table-container">
                <table class="triple-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Predicate</th>
                            <th>Object</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody id="triplesBody"></tbody>
                </table>
            </div>
        </div>

        <!-- Explanations -->
        <div class="card explanation-card" id="explanationCard" style="display:none;">
            <div class="card-header">
                <div class="card-title"><span class="icon">💡</span> Inference Explanations</div>
            </div>
            <div class="explanation-list" id="explanationList"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    // --- Load patient selector ---
    async function loadPatients() {
        try {
            const data = await apiFetch('patients');
            const patients = Array.isArray(data) ? data : (data.patients || data.data || []);
            const selector = document.getElementById('patientSelector');
            patients.forEach(p => {
                // Prefer the full URI so the controller can resolve it directly;
                // fall back to patientID if no URI is present.
                const uri = p.uri || '';
                const id  = p.patientID || p.patientId || p.patient_id || '';
                const name = p.name || p.patientName || p.patient_name || 'Unknown';
                const opt = document.createElement('option');
                opt.value = uri || id;   // full URI preferred
                opt.textContent = `${name} (${id})`;
                selector.appendChild(opt);
            });
        } catch(e) { /* silent */ }
    }

    // --- Classify inference type ---
    function classifyTriple(subj, pred, obj) {
        const p = (pred || '').toLowerCase();
        const o = (obj || '').toLowerCase();
        const s = (subj || '').toLowerCase();

        if (o.includes('highrisk') || p.includes('risk') || o.includes('high risk'))
            return { type: 'risk', label: 'Risk', icon: '⚠️', cls: 'triple-type-risk' };
        if (p.includes('contraindic') || p.includes('interact') || p.includes('drug'))
            return { type: 'drug', label: 'Drug', icon: '💊', cls: 'triple-type-drug' };
        if (p.includes('chain') || p.includes('specializ') || p.includes('treats'))
            return { type: 'property', label: 'Property Chain', icon: '🔗', cls: 'triple-type-property' };
        if (o.includes('complex') || o.includes('complexcase'))
            return { type: 'classification', label: 'Complex Case', icon: '🔬', cls: 'triple-type-classification' };
        if (p.includes('diagnos') || p.includes('diagnosed') || o.includes('diagnosed'))
            return { type: 'diagnosis', label: 'Diagnosis', icon: '🩺', cls: 'triple-type-diagnosis' };
        return { type: 'default', label: 'Inferred', icon: '💡', cls: 'triple-type-default' };
    }

    // --- Shorten URI ---
    function shortenURI(uri) {
        if (!uri || typeof uri !== 'string') return uri || '';
        if (uri.startsWith('http')) {
            const parts = uri.split(/[#\/]/);
            return parts[parts.length - 1] || uri;
        }
        return uri;
    }

    // --- Run Classification ---
    window.runClassification = async function() {
        const patientId = document.getElementById('patientSelector').value;
        const loading = document.getElementById('loadingAnimation');
        const resultsSection = document.getElementById('resultsSection');

        resultsSection.classList.remove('active');
        loading.classList.add('active');

        const btn = document.getElementById('classifyBtn');
        btn.disabled = true;

        try {
            let params = {};
            if (patientId) params.patientId = patientId;

            const result = await apiFetch('classify', { params });

            loading.classList.remove('active');

            // Parse triples
            let triples = result.triples || result.inferences || result.inferredTriples || result.results || result.data || [];
            if (!Array.isArray(triples)) triples = [];

            // Render triples table
            const tbody = document.getElementById('triplesBody');

            if (triples.length) {
                tbody.innerHTML = triples.map((t, i) => {
                    let subj, pred, obj;
                    if (typeof t === 'string') {
                        const parts = t.split(/\s+/);
                        subj = parts[0] || '';
                        pred = parts[1] || '';
                        obj = parts.slice(2).join(' ') || '';
                    } else {
                        subj = t.subject || t.s || '';
                        pred = t.predicate || t.p || '';
                        obj = t.object || t.o || '';
                    }

                    const info = classifyTriple(subj, pred, obj);
                    return `
                        <tr style="animation: fadeIn 0.2s ease ${i * 0.03}s both;">
                            <td title="${escapeHtml(subj)}">${escapeHtml(shortenURI(subj))}</td>
                            <td title="${escapeHtml(pred)}">${escapeHtml(shortenURI(pred))}</td>
                            <td title="${escapeHtml(obj)}">${escapeHtml(shortenURI(obj))}</td>
                            <td><span class="triple-type-tag ${info.cls}">${info.icon} ${info.label}</span></td>
                        </tr>`;
                }).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="4"><div class="empty-state" style="padding:30px;"><div class="icon">📭</div><h3>No Inferences Generated</h3><p>The reasoner did not produce any new inferred triples.</p></div></td></tr>';
            }

            document.getElementById('tripleCount').textContent = `${triples.length} triple(s) inferred`;

            // Render explanations
            const explanations = result.explanations || result.explanation || result.reasoning || [];
            const explArr = Array.isArray(explanations) ? explanations : (typeof explanations === 'string' ? [explanations] : []);
            const explCard = document.getElementById('explanationCard');
            const explList = document.getElementById('explanationList');

            if (explArr.length) {
                explList.innerHTML = explArr.map(e => {
                    const text = typeof e === 'string' ? e : (e.description || e.message || e.text || JSON.stringify(e));
                    const title = typeof e === 'object' ? (e.title || e.rule || e.type || 'Inference Rule') : 'Inference Rule';
                    return `
                        <div class="explanation-entry">
                            <div class="explanation-entry-icon">💡</div>
                            <div class="explanation-entry-text">
                                <h4>${escapeHtml(typeof e === 'string' ? 'Inference Rule' : title)}</h4>
                                <p>${escapeHtml(typeof e === 'string' ? e : text)}</p>
                            </div>
                        </div>`;
                }).join('');
                explCard.style.display = 'block';
            } else {
                explCard.style.display = 'none';
            }

            resultsSection.classList.add('active');
            showToast('success', 'Classification Complete', `${triples.length} triple(s) inferred by OWL reasoner.`);
            resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

        } catch (err) {
            loading.classList.remove('active');
        } finally {
            btn.disabled = false;
        }
    };

    // --- Clear Results ---
    window.clearResults = function() {
        document.getElementById('resultsSection').classList.remove('active');
        document.getElementById('loadingAnimation').classList.remove('active');
        document.getElementById('patientSelector').value = '';
    };

    // --- Init ---
    await loadPatients();
});
</script>

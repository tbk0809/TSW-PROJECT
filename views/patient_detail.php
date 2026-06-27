<!-- Patient Detail View -->
<style>
    .detail-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--text-secondary);
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 20px;
        padding: 8px 16px;
        border-radius: var(--radius-sm);
        transition: var(--transition);
        cursor: pointer;
        background: none;
        border: none;
    }
    .detail-back:hover {
        color: var(--accent);
        background: var(--accent-light);
    }

    .patient-hero {
        background: linear-gradient(135deg, #1A2B4A, #2A4A7A, #3498DB);
        border-radius: var(--radius-xl);
        padding: 36px;
        color: white;
        margin-bottom: 28px;
        position: relative;
        overflow: hidden;
        animation: fadeInUp 0.5s ease;
    }
    .patient-hero::before {
        content: '';
        position: absolute;
        top: -60%; right: -20%;
        width: 50%; height: 120%;
        background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
        pointer-events: none;
    }
    .patient-hero-content {
        display: flex;
        align-items: center;
        gap: 28px;
        position: relative;
        z-index: 1;
        flex-wrap: wrap;
    }
    .patient-hero-avatar {
        width: 80px; height: 80px;
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: 800;
        flex-shrink: 0;
    }
    .patient-hero-info { flex: 1; }
    .patient-hero-name {
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -0.5px;
        margin-bottom: 4px;
    }
    .patient-hero-meta {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        margin-top: 10px;
    }
    .patient-hero-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        opacity: 0.85;
    }
    .patient-hero-risk {
        padding: 8px 20px;
        border-radius: 24px;
        font-weight: 700;
        font-size: 14px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .risk-hero-low { background: rgba(46,204,113,0.25); border: 1px solid rgba(46,204,113,0.4); }
    .risk-hero-medium { background: rgba(243,156,18,0.25); border: 1px solid rgba(243,156,18,0.4); }
    .risk-hero-high { background: rgba(231,76,60,0.25); border: 1px solid rgba(231,76,60,0.4); animation: pulse 2s ease infinite; }

    .detail-actions {
        display: flex;
        gap: 12px;
        margin-bottom: 28px;
        flex-wrap: wrap;
        animation: fadeInUp 0.5s ease 0.1s both;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
        margin-bottom: 28px;
    }
    .detail-grid .card {
        animation-delay: 0.15s;
    }
    .detail-grid .card:nth-child(2) { animation-delay: 0.2s; }
    .detail-grid .card:nth-child(3) { animation-delay: 0.25s; }
    .detail-grid .card:nth-child(4) { animation-delay: 0.3s; }

    .detail-section { margin-bottom: 28px; }

    .symptom-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .symptom-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: var(--accent-light);
        color: var(--accent);
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        transition: var(--transition);
        border: 1px solid rgba(52,152,219,0.15);
    }
    .symptom-tag:hover {
        background: rgba(52,152,219,0.2);
        transform: translateY(-1px);
    }

    .disease-list {
        list-style: none;
    }
    .disease-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 0;
        border-bottom: 1px solid var(--border);
        font-size: 15px;
    }
    .disease-item:last-child { border-bottom: none; }
    .disease-icon {
        width: 36px; height: 36px;
        background: var(--risk-high-bg);
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .med-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px;
        background: rgba(46,204,113,0.04);
        border: 1px solid rgba(46,204,113,0.1);
        border-radius: var(--radius-md);
        margin-bottom: 10px;
        transition: var(--transition);
    }
    .med-card:hover { background: rgba(46,204,113,0.08); }
    .med-icon {
        width: 36px; height: 36px;
        background: var(--risk-low-bg);
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .med-info { flex: 1; }
    .med-name { font-weight: 600; font-size: 14px; }
    .med-dosage { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

    .lab-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        font-weight: 600;
    }
    .lab-normal { color: var(--risk-low); }
    .lab-abnormal { color: var(--risk-high); }
    .lab-borderline { color: var(--risk-medium); }

    .inference-section {
        animation: fadeInUp 0.5s ease;
    }
    .inference-panel {
        background: rgba(52,152,219,0.04);
        border: 1px solid rgba(52,152,219,0.12);
        border-radius: var(--radius-lg);
        padding: 24px;
        min-height: 100px;
    }
    .inference-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(52,152,219,0.08);
        font-size: 14px;
    }
    .inference-item:last-child { border-bottom: none; }
    .inference-icon {
        width: 28px; height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
        background: var(--accent-light);
    }

    .contraindication-alert {
        background: rgba(231,76,60,0.06);
        border: 1.5px solid rgba(231,76,60,0.2);
        border-radius: var(--radius-lg);
        padding: 20px 24px;
        margin-top: 20px;
        animation: fadeInUp 0.5s ease;
    }
    .contraindication-alert h4 {
        color: var(--risk-high);
        font-size: 15px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .contraindication-item {
        padding: 8px 0;
        font-size: 14px;
        color: var(--text-primary);
        border-bottom: 1px solid rgba(231,76,60,0.08);
    }
    .contraindication-item:last-child { border-bottom: none; }

    .explanation-panel {
        background: rgba(46,204,113,0.04);
        border: 1px solid rgba(46,204,113,0.12);
        border-radius: var(--radius-lg);
        padding: 24px;
        margin-top: 20px;
    }
    .explanation-panel h4 {
        font-size: 15px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .explanation-text {
        font-size: 14px;
        line-height: 1.8;
        color: var(--text-secondary);
    }
    .explanation-text p { margin-bottom: 8px; }

    @media (max-width: 768px) {
        .detail-grid { grid-template-columns: 1fr; }
        .patient-hero { padding: 24px; }
        .patient-hero-name { font-size: 22px; }
        .patient-hero-avatar { width: 60px; height: 60px; font-size: 24px; }
    }
</style>

<?php $patientId = $_GET['id'] ?? ''; ?>

<!-- Back Button -->
<button class="detail-back" onclick="window.location='?page=patients'">
    ← Back to Patients
</button>

<!-- Patient Hero Card -->
<div class="patient-hero" id="patientHero">
    <div class="patient-hero-content">
        <div class="patient-hero-avatar" id="heroAvatar">—</div>
        <div class="patient-hero-info">
            <div class="patient-hero-name" id="heroName">Loading...</div>
            <div class="patient-hero-meta">
                <div class="patient-hero-meta-item">🆔 <span id="heroId">—</span></div>
                <div class="patient-hero-meta-item">🎂 Age: <span id="heroAge">—</span></div>
            </div>
        </div>
        <div class="patient-hero-risk" id="heroRisk">—</div>
    </div>
</div>

<!-- Action Buttons -->
<div class="detail-actions">
    <button class="btn btn-accent btn-lg" onclick="runInference()">
        🧠 Run Inference
    </button>
    <button class="btn btn-danger btn-lg" onclick="checkContraindications()">
        ⚠️ Check Drug Interactions
    </button>
    <button class="btn btn-outline btn-lg" onclick="window.location='?page=patients'">
        📋 All Patients
    </button>
</div>

<!-- Detail Grid -->
<div class="detail-grid">
    <!-- Symptoms -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><span class="icon">🤒</span> Symptoms</div>
        </div>
        <div class="symptom-tags" id="symptomTags">
            <div class="skeleton skeleton-line" style="width:80px;height:32px;border-radius:20px;"></div>
            <div class="skeleton skeleton-line" style="width:100px;height:32px;border-radius:20px;"></div>
            <div class="skeleton skeleton-line" style="width:70px;height:32px;border-radius:20px;"></div>
        </div>
    </div>

    <!-- Diagnosed Diseases -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><span class="icon">🦠</span> Diagnosed Diseases</div>
        </div>
        <ul class="disease-list" id="diseaseList">
            <li><div class="skeleton skeleton-line" style="width:60%;"></div></li>
        </ul>
    </div>

    <!-- Medications -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><span class="icon">💊</span> Prescribed Medications</div>
        </div>
        <div id="medicationsList">
            <div class="skeleton skeleton-line" style="width:80%;"></div>
            <div class="skeleton skeleton-line" style="width:60%;"></div>
        </div>
    </div>

    <!-- Lab Results -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><span class="icon">🔬</span> Lab Results</div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Test</th>
                        <th>Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="labResultsBody">
                    <tr><td colspan="3"><div class="skeleton skeleton-line"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Clinical Records -->
<div class="detail-section">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><span class="icon">📋</span> Clinical Records</div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Record</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="clinicalRecordsBody">
                    <tr><td colspan="3"><div class="skeleton skeleton-line"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Inferred Facts Section -->
<div class="detail-section inference-section" id="inferenceSection" style="display:none;">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><span class="icon">🧠</span> Inferred Facts</div>
        </div>
        <div class="inference-panel" id="inferencePanel">
            <div class="empty-state" style="padding:20px;">
                <p>Click "Run Inference" to generate inferred facts.</p>
            </div>
        </div>
    </div>
</div>

<!-- Contraindication Alerts -->
<div id="contraindicationSection" style="display:none;"></div>

<!-- Explanation Panel -->
<div id="explanationSection" style="display:none;"></div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const patientId = '<?= addslashes($patientId) ?>';
    let patientData = null;

    if (!patientId) {
        showToast('error', 'Error', 'No patient ID specified.');
        return;
    }

    // --- Load Patient Data ---
    async function loadPatient() {
        try {
            const data = await apiFetch('patient', { params: { id: patientId } });
            patientData = data.data || data.patient || data;
            renderPatient();
        } catch (err) {
            document.getElementById('heroName').textContent = 'Patient Not Found';
        }
    }

    // --- Render Patient ---
    function renderPatient() {
        const p = patientData;
        const name = p.name || p.patientName || p.patient_name || 'Unknown';
        const age = p.age || '—';
        const id = p.id || p.patientId || p.patient_id || patientId;
        const risk = p.riskLevel || p.risk_level || 'Low';
        const initials = name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();

        // Hero
        document.getElementById('heroAvatar').textContent = initials;
        document.getElementById('heroName').textContent = name;
        document.getElementById('heroId').textContent = id;
        document.getElementById('heroAge').textContent = age;

        const heroRisk = document.getElementById('heroRisk');
        const riskDisplay = risk.toLowerCase().includes('risk') ? risk : risk + ' Risk';
        heroRisk.textContent = riskDisplay;
        const rl = risk.toLowerCase().replace(/\s*risk\s*/i, '').trim();
        heroRisk.className = 'patient-hero-risk risk-hero-' + (rl === 'moderate' ? 'medium' : rl);

        // Symptoms
        const symptoms = p.symptoms || p.symptom || [];
        const symptomsArr = Array.isArray(symptoms) ? symptoms : (typeof symptoms === 'string' ? [symptoms] : []);
        const tagContainer = document.getElementById('symptomTags');
        if (symptomsArr.length) {
            tagContainer.innerHTML = symptomsArr.map(s => {
                const name = typeof s === 'string' ? s : (s.name || s.label || String(s));
                return `<span class="symptom-tag">🔹 ${escapeHtml(name)}</span>`;
            }).join('');
        } else {
            tagContainer.innerHTML = '<span style="color:var(--text-muted);font-size:13px;">No symptoms recorded</span>';
        }

        // Diseases
        const diseases = p.diseases || p.disease || p.primaryDisease || p.primary_disease || [];
        const diseasesArr = Array.isArray(diseases) ? diseases : (typeof diseases === 'string' ? [diseases] : []);
        const diseaseList = document.getElementById('diseaseList');
        if (diseasesArr.length) {
            diseaseList.innerHTML = diseasesArr.map(d => {
                const name = typeof d === 'string' ? d : (d.name || d.label || String(d));
                return `<li class="disease-item"><div class="disease-icon">🦠</div><span>${escapeHtml(name)}</span></li>`;
            }).join('');
        } else {
            diseaseList.innerHTML = '<li class="disease-item" style="color:var(--text-muted);border:none;">No diseases diagnosed</li>';
        }

        // Medications
        const meds = p.medications || p.medication || p.prescribedMedications || [];
        const medsArr = Array.isArray(meds) ? meds : (typeof meds === 'string' ? [meds] : []);
        const medsContainer = document.getElementById('medicationsList');
        if (medsArr.length) {
            medsContainer.innerHTML = medsArr.map(m => {
                const name = typeof m === 'string' ? m : (m.name || m.label || String(m));
                const dosage = typeof m === 'object' ? (m.dosage || m.dose || '') : '';
                return `
                    <div class="med-card">
                        <div class="med-icon">💊</div>
                        <div class="med-info">
                            <div class="med-name">${escapeHtml(name)}</div>
                            ${dosage ? `<div class="med-dosage">${escapeHtml(dosage)}</div>` : ''}
                        </div>
                    </div>`;
            }).join('');
        } else {
            medsContainer.innerHTML = '<p style="color:var(--text-muted);font-size:13px;">No medications prescribed</p>';
        }

        // Lab Results
        const labs = p.labResults || p.lab_results || p.labs || [];
        const labsArr = Array.isArray(labs) ? labs : [];
        const labsBody = document.getElementById('labResultsBody');
        if (labsArr.length) {
            labsBody.innerHTML = labsArr.map(l => {
                const test = l.test || l.testName || l.name || '—';
                const value = l.value || l.result || '—';
                const status = l.status || 'normal';
                const statusClass = status.toLowerCase() === 'normal' ? 'lab-normal' : (status.toLowerCase() === 'borderline' ? 'lab-borderline' : 'lab-abnormal');
                const statusIcon = status.toLowerCase() === 'normal' ? '✓' : (status.toLowerCase() === 'borderline' ? '◐' : '✗');
                return `
                    <tr>
                        <td>${escapeHtml(test)}</td>
                        <td><strong>${escapeHtml(String(value))}</strong></td>
                        <td><span class="lab-status ${statusClass}">${statusIcon} ${escapeHtml(status)}</span></td>
                    </tr>`;
            }).join('');
        } else {
            labsBody.innerHTML = '<tr><td colspan="3" style="color:var(--text-muted);text-align:center;padding:20px;">No lab results available</td></tr>';
        }

        // Clinical Records
        const records = p.clinicalRecords || p.clinical_records || p.records || [];
        const recordsArr = Array.isArray(records) ? records : [];
        const recordsBody = document.getElementById('clinicalRecordsBody');
        if (recordsArr.length) {
            recordsBody.innerHTML = recordsArr.map(r => {
                const date = r.date || r.recordDate || '—';
                const type = r.type || r.recordType || r.record || '—';
                const details = r.details || r.description || r.notes || '—';
                return `
                    <tr>
                        <td>${formatDate(date)}</td>
                        <td><strong>${escapeHtml(type)}</strong></td>
                        <td>${escapeHtml(details)}</td>
                    </tr>`;
            }).join('');
        } else {
            recordsBody.innerHTML = '<tr><td colspan="3" style="color:var(--text-muted);text-align:center;padding:20px;">No clinical records found</td></tr>';
        }
    }

    // --- Run Inference ---
    window.runInference = async function() {
        showSpinner('Running OWL inference engine...');
        try {
            const result = await apiFetch('inference', { params: { patientId } });
            hideSpinner();
            showToast('success', 'Inference Complete', 'Reasoning engine has finished processing.');

            const section = document.getElementById('inferenceSection');
            section.style.display = 'block';
            const panel = document.getElementById('inferencePanel');

            const facts = result.inferences || result.inferredFacts || result.facts || result.triples || result.results || [];
            const factsArr = Array.isArray(facts) ? facts : [];

            if (factsArr.length) {
                panel.innerHTML = factsArr.map(f => {
                    let text = '';
                    let icon = '💡';
                    if (typeof f === 'string') {
                        text = f;
                    } else {
                        const subj = f.subject || f.s || '';
                        const pred = f.predicate || f.p || '';
                        const obj = f.object || f.o || '';
                        text = `${subj} → ${pred} → ${obj}`;
                        if (f.type === 'risk' || pred.toLowerCase().includes('risk')) icon = '⚠️';
                        else if (f.type === 'drug' || pred.toLowerCase().includes('drug') || pred.toLowerCase().includes('medication')) icon = '💊';
                        else if (f.type === 'diagnosis' || pred.toLowerCase().includes('diagnos')) icon = '🩺';
                    }
                    return `<div class="inference-item"><div class="inference-icon">${icon}</div><span>${escapeHtml(text)}</span></div>`;
                }).join('');
            } else {
                panel.innerHTML = '<div class="empty-state" style="padding:20px;"><p>No additional inferences generated.</p></div>';
            }

            // Show explanations
            renderExplanations(result);

            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (err) {
            hideSpinner();
        }
    };

    // --- Check Contraindications ---
    window.checkContraindications = async function() {
        showSpinner('Checking drug interactions...');
        try {
            const result = await apiFetch('contraindications', { params: { patientId } });
            hideSpinner();

            const section = document.getElementById('contraindicationSection');
            const contras = result.contraindications || result.interactions || result.drugInteractions || result.results || [];
            const contrasArr = Array.isArray(contras) ? contras : [];

            if (contrasArr.length) {
                showToast('warning', 'Interactions Found', `${contrasArr.length} potential drug interaction(s) detected.`);
                section.innerHTML = `
                    <div class="contraindication-alert">
                        <h4>⚠️ Drug Interaction Warnings (${contrasArr.length})</h4>
                        ${contrasArr.map(c => {
                            const text = typeof c === 'string' ? c : (c.description || c.message || c.warning || `${c.drug1 || ''} ↔ ${c.drug2 || ''}: ${c.reason || c.effect || 'Potential interaction'}`);
                            return `<div class="contraindication-item">🔴 ${escapeHtml(text)}</div>`;
                        }).join('')}
                    </div>`;
            } else {
                showToast('success', 'No Interactions', 'No drug interactions detected for this patient.');
                section.innerHTML = `
                    <div class="explanation-panel" style="margin-top:20px;">
                        <h4>✅ No Drug Interactions Detected</h4>
                        <p class="explanation-text">All prescribed medications are safe to use together based on the current knowledge base.</p>
                    </div>`;
            }
            section.style.display = 'block';
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (err) {
            hideSpinner();
        }
    };

    // --- Render Explanations ---
    function renderExplanations(result) {
        const explanations = result.explanations || result.explanation || result.reasoning || [];
        const section = document.getElementById('explanationSection');

        let explArr = [];
        if (Array.isArray(explanations)) explArr = explanations;
        else if (typeof explanations === 'string') explArr = [explanations];
        else if (typeof explanations === 'object') {
            Object.entries(explanations).forEach(([key, val]) => {
                explArr.push(`<strong>${key}:</strong> ${val}`);
            });
        }

        if (explArr.length || result.summary) {
            section.innerHTML = `
                <div class="explanation-panel">
                    <h4>💡 Inference Explanation</h4>
                    <div class="explanation-text">
                        ${result.summary ? `<p><strong>Summary:</strong> ${escapeHtml(result.summary)}</p>` : ''}
                        ${explArr.map(e => `<p>• ${typeof e === 'string' && e.startsWith('<strong>') ? e : escapeHtml(e)}</p>`).join('')}
                    </div>
                </div>`;
            section.style.display = 'block';
        }
    }

    // --- Init ---
    await loadPatient();
});
</script>

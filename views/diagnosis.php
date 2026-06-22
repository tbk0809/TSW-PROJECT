<!-- Diagnosis View -->
<style>
    .diagnosis-container {
        max-width: 1200px;
    }

    .symptom-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(165px, 1fr));
        gap: 12px;
        margin-bottom: 28px;
    }
    .symptom-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 18px 12px;
        border-radius: var(--radius-md);
        background: var(--glass-bg);
        backdrop-filter: blur(8px);
        border: 1.5px solid var(--border);
        cursor: pointer;
        transition: var(--transition);
        text-align: center;
        user-select: none;
        position: relative;
        overflow: hidden;
    }
    .symptom-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(52,152,219,0.08), rgba(46,204,113,0.08));
        opacity: 0;
        transition: var(--transition);
    }
    .symptom-card:hover {
        border-color: var(--accent);
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
    }
    .symptom-card:hover::before { opacity: 1; }
    .symptom-card.selected {
        border-color: var(--accent);
        background: rgba(52,152,219,0.08);
        box-shadow: 0 0 0 3px rgba(52,152,219,0.12);
    }
    .symptom-card.selected::after {
        content: '✓';
        position: absolute;
        top: 8px; right: 10px;
        width: 22px; height: 22px;
        background: var(--accent);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        animation: scaleIn 0.2s ease;
    }
    .symptom-card-icon {
        font-size: 28px;
        position: relative;
        z-index: 1;
    }
    .symptom-card-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
        position: relative;
        z-index: 1;
    }

    .selected-count {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        background: var(--accent-light);
        color: var(--accent);
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 20px;
        transition: var(--transition);
    }

    .diagnosis-results {
        display: none;
        animation: fadeInUp 0.5s ease;
    }
    .diagnosis-results.active { display: block; }

    .disease-result-card {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        padding: 24px;
        margin-bottom: 16px;
        transition: var(--transition);
        animation: fadeInUp 0.4s ease both;
    }
    .disease-result-card:hover {
        box-shadow: var(--shadow-md);
    }
    .disease-result-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .disease-result-name {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .confidence-value {
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -0.5px;
    }
    .confidence-high { color: var(--risk-high); }
    .confidence-medium { color: var(--risk-medium); }
    .confidence-low { color: var(--risk-low); }

    .confidence-bar-wrapper {
        width: 100%;
        height: 10px;
        background: rgba(26,43,74,0.06);
        border-radius: 5px;
        overflow: hidden;
        margin-bottom: 16px;
    }
    .confidence-bar {
        height: 100%;
        border-radius: 5px;
        transition: width 1s cubic-bezier(0.4,0,0.2,1);
        width: 0;
    }
    .confidence-bar-high { background: linear-gradient(90deg, #E74C3C, #C0392B); }
    .confidence-bar-medium { background: linear-gradient(90deg, #F39C12, #E67E22); }
    .confidence-bar-low { background: linear-gradient(90deg, #2ECC71, #27AE60); }

    .suggested-meds {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .suggested-med {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: var(--risk-low-bg);
        color: #1B9E52;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .contra-warning-panel {
        background: rgba(231,76,60,0.06);
        border: 1.5px solid rgba(231,76,60,0.2);
        border-radius: var(--radius-lg);
        padding: 24px;
        margin-top: 24px;
        animation: fadeInUp 0.5s ease;
        display: none;
    }
    .contra-warning-panel.active { display: block; }
    .contra-warning-panel h4 {
        color: var(--risk-high);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
    }
    .contra-warning-item {
        padding: 10px 0;
        font-size: 14px;
        border-bottom: 1px solid rgba(231,76,60,0.08);
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }
    .contra-warning-item:last-child { border-bottom: none; }

    @media (max-width: 768px) {
        .symptom-grid { grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); }
    }
</style>

<div class="diagnosis-container">
    <!-- Page Header -->
    <div class="page-header">
        <h2>🩺 Clinical Diagnosis Assistant</h2>
        <p>Select patient symptoms to receive AI-powered diagnostic suggestions and treatment recommendations</p>
    </div>

    <!-- Symptom Selection -->
    <div class="card" style="margin-bottom: 28px;">
        <div class="card-header">
            <div class="card-title">
                <span class="icon">📋</span>
                Select Symptoms
            </div>
            <div class="selected-count" id="selectedCount">0 selected</div>
        </div>

        <div class="symptom-grid" id="symptomGrid"></div>

        <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
            <button class="btn btn-primary btn-lg" id="diagnoseBtn" onclick="getDiagnosis()">
                🩺 Get Diagnosis
            </button>
            <button class="btn btn-outline" onclick="clearSymptoms()">
                Clear All
            </button>
        </div>
    </div>

    <!-- Results -->
    <div class="diagnosis-results" id="diagnosisResults">
        <div class="page-header" style="margin-bottom:20px;">
            <h2 style="font-size:20px;">📊 Diagnosis Results</h2>
            <p id="diagnosisSummary">Based on selected symptoms</p>
        </div>
        <div id="diseaseResults"></div>
        <div class="contra-warning-panel" id="contraWarningPanel"></div>
    </div>
</div>

<script>
(function() {
    // --- Symptom Data ---
    const symptoms = [
        { name: 'Headache', icon: '🤕' },
        { name: 'Fever', icon: '🌡️' },
        { name: 'Cough', icon: '😷' },
        { name: 'Chest Pain', icon: '💔' },
        { name: 'Fatigue', icon: '😴' },
        { name: 'Shortness of Breath', icon: '😤' },
        { name: 'Nausea', icon: '🤢' },
        { name: 'Dizziness', icon: '😵' },
        { name: 'Joint Pain', icon: '🦴' },
        { name: 'Rash', icon: '🔴' },
        { name: 'Itching', icon: '✋' },
        { name: 'Blurred Vision', icon: '👁️' },
        { name: 'Abdominal Pain', icon: '🤰' },
        { name: 'Sore Throat', icon: '🗣️' },
        { name: 'Anxiety', icon: '😰' },
        { name: 'Insomnia', icon: '🌙' },
        { name: 'Swelling', icon: '🫧' },
        { name: 'Vomiting', icon: '🤮' },
        { name: 'Frequent Urination', icon: '🚻' }
    ];

    let selectedSymptoms = new Set();

    // --- Build Symptom Grid ---
    const grid = document.getElementById('symptomGrid');
    grid.innerHTML = symptoms.map((s, i) => `
        <div class="symptom-card" data-symptom="${s.name}" onclick="toggleSymptom(this)" style="animation: fadeInUp 0.3s ease ${i * 0.03}s both;">
            <div class="symptom-card-icon">${s.icon}</div>
            <div class="symptom-card-name">${s.name}</div>
        </div>
    `).join('');

    // --- Toggle Symptom ---
    window.toggleSymptom = function(el) {
        const symptom = el.dataset.symptom;
        if (selectedSymptoms.has(symptom)) {
            selectedSymptoms.delete(symptom);
            el.classList.remove('selected');
        } else {
            selectedSymptoms.add(symptom);
            el.classList.add('selected');
        }
        updateCount();
    };

    function updateCount() {
        document.getElementById('selectedCount').textContent = selectedSymptoms.size + ' selected';
    }

    // --- Clear All ---
    window.clearSymptoms = function() {
        selectedSymptoms.clear();
        document.querySelectorAll('.symptom-card.selected').forEach(el => el.classList.remove('selected'));
        updateCount();
        document.getElementById('diagnosisResults').classList.remove('active');
    };

    // --- Get Diagnosis ---
    window.getDiagnosis = async function() {
        if (selectedSymptoms.size === 0) {
            showToast('warning', 'No Symptoms', 'Please select at least one symptom to proceed.');
            return;
        }

        const btn = document.getElementById('diagnoseBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-inline"></span> Analyzing...';
        btn.disabled = true;

        try {
            const symptomStr = Array.from(selectedSymptoms).join(',');
            const result = await apiFetch('diagnose', { params: { symptoms: symptomStr } });

            const diseases = result.diagnoses || result.diseases || result.results || result.probableDiseases || [];
            const diseasesArr = Array.isArray(diseases) ? diseases : [];

            document.getElementById('diagnosisSummary').textContent =
                `Found ${diseasesArr.length} possible condition(s) based on ${selectedSymptoms.size} symptom(s)`;

            const container = document.getElementById('diseaseResults');

            if (diseasesArr.length) {
                container.innerHTML = diseasesArr.map((d, i) => {
                    const name = typeof d === 'string' ? d : (d.name || d.disease || d.label || 'Unknown');
                    const confidence = d.confidence || d.probability || d.score || d.matchPercentage || 0;
                    const confPercent = confidence > 1 ? confidence : Math.round(confidence * 100);
                    const confClass = confPercent >= 70 ? 'high' : (confPercent >= 40 ? 'medium' : 'low');

                    const meds = d.medications || d.suggestedMedications || d.treatments || [];
                    const medsArr = Array.isArray(meds) ? meds : (typeof meds === 'string' ? [meds] : []);

                    return `
                        <div class="disease-result-card" style="animation-delay: ${i * 0.1}s;">
                            <div class="disease-result-header">
                                <div class="disease-result-name">
                                    🦠 ${escapeHtml(name)}
                                </div>
                                <div class="confidence-value confidence-${confClass}">
                                    ${confPercent}%
                                </div>
                            </div>
                            <div class="confidence-bar-wrapper">
                                <div class="confidence-bar confidence-bar-${confClass}" id="confBar${i}" data-width="${confPercent}"></div>
                            </div>
                            ${medsArr.length ? `
                                <div style="margin-top:8px;">
                                    <div style="font-size:12px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Suggested Medications</div>
                                    <div class="suggested-meds">
                                        ${medsArr.map(m => {
                                            const mName = typeof m === 'string' ? m : (m.name || m.label || String(m));
                                            return `<span class="suggested-med">💊 ${escapeHtml(mName)}</span>`;
                                        }).join('')}
                                    </div>
                                </div>
                            ` : ''}
                        </div>`;
                }).join('');

                // Animate bars
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        diseasesArr.forEach((_, i) => {
                            const bar = document.getElementById('confBar' + i);
                            if (bar) bar.style.width = bar.dataset.width + '%';
                        });
                    }, 100);
                });
            } else {
                container.innerHTML = '<div class="empty-state"><div class="icon">🔍</div><h3>No Matches Found</h3><p>The selected symptoms did not match any known conditions in the knowledge base.</p></div>';
            }

            // Check for contraindications in results
            const contras = result.contraindications || result.warnings || result.drugInteractions || [];
            const contraPanel = document.getElementById('contraWarningPanel');
            if (contras.length) {
                contraPanel.innerHTML = `
                    <h4>⚠️ Drug Interaction Warnings</h4>
                    ${contras.map(c => {
                        const text = typeof c === 'string' ? c : (c.description || c.message || c.warning || `${c.drug1 || ''} ↔ ${c.drug2 || ''}`);
                        return `<div class="contra-warning-item"><span>🔴</span><span>${escapeHtml(text)}</span></div>`;
                    }).join('')}`;
                contraPanel.classList.add('active');
            } else {
                contraPanel.classList.remove('active');
            }

            document.getElementById('diagnosisResults').classList.add('active');
            document.getElementById('diagnosisResults').scrollIntoView({ behavior: 'smooth', block: 'start' });
            showToast('success', 'Diagnosis Complete', `${diseasesArr.length} condition(s) identified.`);

        } catch (err) {
            // Toast already shown by apiFetch
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    };
})();
</script>

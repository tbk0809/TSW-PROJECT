<!-- Dashboard View -->
<style>
    .dashboard-summary {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
        margin-bottom: 32px;
    }
    .summary-card {
        border-radius: var(--radius-lg);
        padding: 24px;
        position: relative;
        overflow: hidden;
        transition: var(--transition);
        animation: fadeInUp 0.5s ease both;
        border: 1px solid rgba(255,255,255,0.3);
    }
    .summary-card:nth-child(1) { animation-delay: 0.05s; }
    .summary-card:nth-child(2) { animation-delay: 0.1s; }
    .summary-card:nth-child(3) { animation-delay: 0.15s; }
    .summary-card:nth-child(4) { animation-delay: 0.2s; }
    .summary-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
    }
    .summary-card::before {
        content: '';
        position: absolute;
        top: -50%; right: -50%;
        width: 100%; height: 100%;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
        pointer-events: none;
    }
    .sc-gradient-1 {
        background: linear-gradient(135deg, #1A2B4A, #2A4A7A);
        color: white;
    }
    .sc-gradient-2 {
        background: linear-gradient(135deg, #E74C3C, #C0392B);
        color: white;
    }
    .sc-gradient-3 {
        background: linear-gradient(135deg, #3498DB, #2980B9);
        color: white;
    }
    .sc-gradient-4 {
        background: linear-gradient(135deg, #2ECC71, #27AE60);
        color: white;
    }
    .sc-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }
    .sc-icon {
        width: 48px; height: 48px;
        background: rgba(255,255,255,0.18);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        backdrop-filter: blur(8px);
    }
    .sc-change {
        font-size: 12px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
        background: rgba(255,255,255,0.18);
    }
    .sc-value {
        font-size: 36px;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 6px;
        letter-spacing: -1px;
    }
    .sc-label {
        font-size: 13px;
        opacity: 0.8;
        font-weight: 500;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 24px;
        margin-bottom: 32px;
    }

    .chart-container {
        position: relative;
        height: 320px;
        width: 100%;
    }

    .quick-search {
        margin-bottom: 28px;
        animation: fadeInDown 0.4s ease;
    }
    .quick-search .search-wrapper {
        position: relative;
        max-width: 500px;
    }
    .quick-search .search-wrapper .search-icon-qs {
        position: absolute;
        left: 16px; top: 50%;
        transform: translateY(-50%);
        font-size: 18px;
        color: var(--text-muted);
        pointer-events: none;
    }
    .quick-search .form-input {
        padding: 14px 20px 14px 48px;
        border-radius: 28px;
        font-size: 15px;
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        border: 1.5px solid var(--glass-border);
        box-shadow: var(--shadow-sm);
    }
    .quick-search .form-input:focus {
        box-shadow: 0 0 0 4px rgba(52,152,219,0.12);
    }
    .quick-search-results {
        position: absolute;
        top: 100%;
        left: 0; right: 0;
        background: var(--white);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        margin-top: 8px;
        max-height: 320px;
        overflow-y: auto;
        z-index: 100;
        display: none;
        border: 1px solid var(--border);
    }
    .quick-search-results.active { display: block; animation: fadeInDown 0.2s ease; }
    .qs-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 18px;
        cursor: pointer;
        transition: var(--transition);
        border-bottom: 1px solid var(--border);
    }
    .qs-item:last-child { border-bottom: none; }
    .qs-item:hover { background: rgba(52,152,219,0.04); }
    .qs-item-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: var(--accent-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        color: var(--accent);
        flex-shrink: 0;
    }
    .qs-item-info { flex: 1; }
    .qs-item-name { font-weight: 600; font-size: 14px; }
    .qs-item-meta { font-size: 12px; color: var(--text-muted); }

    .recent-table-wrapper {
        max-height: 400px;
        overflow-y: auto;
    }

    @media (max-width: 1200px) {
        .dashboard-summary { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .dashboard-summary { grid-template-columns: 1fr; }
        .dashboard-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- Page Header + Quick Search -->
<div class="page-header">
    <h2>Clinical Dashboard</h2>
    <p>Real-time overview of patient data, risk levels, and clinical records</p>
</div>

<div class="quick-search">
    <div class="search-wrapper">
        <span class="search-icon-qs">🔍</span>
        <input type="text" class="form-input" id="dashboardSearch" placeholder="Search patients by name..." autocomplete="off">
        <div class="quick-search-results" id="searchResults"></div>
    </div>
</div>

<!-- Summary Cards -->
<div class="dashboard-summary" id="summaryCards">
    <div class="summary-card sc-gradient-1">
        <div class="sc-top">
            <div class="sc-icon">👥</div>
            <span class="sc-change">Total</span>
        </div>
        <div class="sc-value" id="totalPatients">0</div>
        <div class="sc-label">Total Patients</div>
    </div>
    <div class="summary-card sc-gradient-2">
        <div class="sc-top">
            <div class="sc-icon">⚠️</div>
            <span class="sc-change">Alert</span>
        </div>
        <div class="sc-value" id="highRiskCount">0</div>
        <div class="sc-label">High Risk Patients</div>
    </div>
    <div class="summary-card sc-gradient-3">
        <div class="sc-top">
            <div class="sc-icon">🦠</div>
            <span class="sc-change">Tracked</span>
        </div>
        <div class="sc-value" id="diseasesTracked">0</div>
        <div class="sc-label">Diseases Tracked</div>
    </div>
    <div class="summary-card sc-gradient-4">
        <div class="sc-top">
            <div class="sc-icon">💊</div>
            <span class="sc-change">Active</span>
        </div>
        <div class="sc-value" id="activeMeds">0</div>
        <div class="sc-label">Active Medications</div>
    </div>
</div>

<!-- Charts + Recent Records -->
<div class="dashboard-grid">
    <!-- Risk Level Chart -->
    <div class="card" style="animation-delay: 0.25s;">
        <div class="card-header">
            <div class="card-title">
                <span class="icon">📊</span>
                Patients by Risk Level
            </div>
        </div>
        <div class="chart-container">
            <canvas id="riskChart"></canvas>
        </div>
    </div>

    <!-- Recent Clinical Records -->
    <div class="card" style="animation-delay: 0.3s;">
        <div class="card-header">
            <div class="card-title">
                <span class="icon">📋</span>
                Recent Clinical Records
            </div>
            <a href="?page=patients" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="recent-table-wrapper">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Age</th>
                            <th>Disease</th>
                            <th>Risk</th>
                        </tr>
                    </thead>
                    <tbody id="recentRecordsBody">
                        <tr><td colspan="4">
                            <div class="skeleton skeleton-row"></div>
                            <div class="skeleton skeleton-row"></div>
                            <div class="skeleton skeleton-row"></div>
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    let allPatients = [];
    let riskChartInstance = null;

    // --- Load Dashboard Data ---
    async function loadDashboard() {
        try {
            // Try dashboard endpoint first
            let dashData = null;
            try {
                dashData = await apiFetch('dashboard');
            } catch (e) {
                // fallback: compute from patients
            }

            // Load patients
            const patientsData = await apiFetch('patients');
            allPatients = Array.isArray(patientsData) ? patientsData : (patientsData.patients || patientsData.data || []);

            // Compute stats
            const total = allPatients.length;
            const highRisk = allPatients.filter(p => {
                const rl = (p.riskLevel || p.risk_level || '').toLowerCase();
                return rl === 'high' || rl === 'critical';
            }).length;

            // Unique diseases
            const diseases = new Set();
            const medications = new Set();
            allPatients.forEach(p => {
                const d = p.disease || p.primaryDisease || p.primary_disease || '';
                if (d) diseases.add(d);
                const meds = p.medications || p.medication || [];
                if (Array.isArray(meds)) meds.forEach(m => medications.add(typeof m === 'string' ? m : m.name || ''));
                else if (typeof meds === 'string' && meds) medications.add(meds);
            });

            // Use dashboard data if available
            const totalVal = dashData?.data?.totalPatients ?? dashData?.totalPatients ?? total;
            const highVal  = dashData?.data?.highRiskCount ?? dashData?.highRiskCount ?? highRisk;
            const disVal   = dashData?.data?.diseaseCount ?? dashData?.diseasesTracked ?? diseases.size;
            const medVal   = dashData?.data?.medicationCount ?? dashData?.activeMedications ?? medications.size;

            animateCounter(document.getElementById('totalPatients'), totalVal);
            animateCounter(document.getElementById('highRiskCount'), highVal);
            animateCounter(document.getElementById('diseasesTracked'), disVal);
            animateCounter(document.getElementById('activeMeds'), medVal);

            // Risk Level Chart
            const riskCounts = { Low: 0, Medium: 0, High: 0 };
            allPatients.forEach(p => {
                const rl = (p.riskLevel || p.risk_level || 'Low');
                const key = rl.charAt(0).toUpperCase() + rl.slice(1).toLowerCase();
                if (riskCounts.hasOwnProperty(key)) riskCounts[key]++;
                else if (key === 'Critical') riskCounts['High']++;
                else if (key === 'Moderate') riskCounts['Medium']++;
            });
            if (typeof Chart !== 'undefined') {
                renderRiskChart(riskCounts);
            }

            // Recent Records Table (last 5)
            renderRecentRecords(allPatients.slice(0, 5));

        } catch (err) {
            console.error('Dashboard load error:', err);
            showToast('error', 'Load Failed', 'Could not load dashboard data.');
        }
    }

    // --- Risk Chart ---
    function renderRiskChart(counts) {
        const ctx = document.getElementById('riskChart').getContext('2d');
        if (riskChartInstance) riskChartInstance.destroy();

        riskChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Low Risk', 'Medium Risk', 'High Risk'],
                datasets: [{
                    label: 'Patients',
                    data: [counts.Low, counts.Medium, counts.High],
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.75)',
                        'rgba(243, 156, 18, 0.75)',
                        'rgba(231, 76, 60, 0.75)'
                    ],
                    borderColor: [
                        '#2ECC71',
                        '#F39C12',
                        '#E74C3C'
                    ],
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                    barPercentage: 0.5,
                    categoryPercentage: 0.6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1A2B4A',
                        titleFont: { family: 'Inter', size: 13, weight: '600' },
                        bodyFont: { family: 'Inter', size: 12 },
                        cornerRadius: 8,
                        padding: 12,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: { family: 'Inter', size: 12 },
                            color: '#8A9BBF',
                            stepSize: 1
                        },
                        grid: { color: 'rgba(26,43,74,0.06)' }
                    },
                    x: {
                        ticks: {
                            font: { family: 'Inter', size: 12, weight: '500' },
                            color: '#5A6B8A'
                        },
                        grid: { display: false }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }

    // --- Recent Records ---
    function renderRecentRecords(patients) {
        const tbody = document.getElementById('recentRecordsBody');
        if (!patients.length) {
            tbody.innerHTML = '<tr><td colspan="4"><div class="empty-state"><p>No records found</p></div></td></tr>';
            return;
        }
        tbody.innerHTML = patients.map((p, i) => {
            const name = p.name || p.patientName || p.patient_name || 'Unknown';
            const age = p.age || '—';
            const disease = p.disease || p.primaryDisease || p.primary_disease || '—';
            const risk = p.riskLevel || p.risk_level || 'Low';
            const id = p.id || p.patientId || p.patient_id || '';
            return `
                <tr style="animation: fadeInUp 0.4s ease ${i * 0.05}s both; cursor:pointer;" onclick="window.location='?page=patient_detail&id=${escapeHtml(id)}'">
                    <td><strong>${escapeHtml(name)}</strong></td>
                    <td>${escapeHtml(String(age))}</td>
                    <td>${escapeHtml(disease)}</td>
                    <td>${riskBadge(risk)}</td>
                </tr>`;
        }).join('');
    }

    // --- Quick Search ---
    const searchInput = document.getElementById('dashboardSearch');
    const searchResults = document.getElementById('searchResults');

    searchInput.addEventListener('input', debounce(function() {
        const query = this.value.trim().toLowerCase();
        if (query.length < 2) {
            searchResults.classList.remove('active');
            return;
        }
        const matches = allPatients.filter(p => {
            const name = (p.name || p.patientName || p.patient_name || '').toLowerCase();
            return name.includes(query);
        }).slice(0, 8);

        if (!matches.length) {
            searchResults.innerHTML = '<div class="qs-item"><div class="qs-item-info"><div class="qs-item-name" style="color:var(--text-muted)">No patients found</div></div></div>';
        } else {
            searchResults.innerHTML = matches.map(p => {
                const name = p.name || p.patientName || p.patient_name || 'Unknown';
                const id = p.id || p.patientId || p.patient_id || '';
                const risk = p.riskLevel || p.risk_level || '';
                const disease = p.disease || p.primaryDisease || p.primary_disease || '';
                const initials = name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
                return `
                    <a href="?page=patient_detail&id=${encodeURIComponent(id)}" class="qs-item">
                        <div class="qs-item-avatar">${initials}</div>
                        <div class="qs-item-info">
                            <div class="qs-item-name">${escapeHtml(name)}</div>
                            <div class="qs-item-meta">${escapeHtml(disease)} · ${risk}</div>
                        </div>
                        ${riskBadge(risk)}
                    </a>`;
            }).join('');
        }
        searchResults.classList.add('active');
    }, 200));

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.remove('active');
        }
    });

    // --- Init ---
    await loadDashboard();
});
</script>

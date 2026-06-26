<!-- Patient List View -->
<style>
    .patient-toolbar {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
        animation: fadeInDown 0.4s ease;
    }
    .patient-toolbar .search-box {
        flex: 1;
        min-width: 240px;
    }
    .patient-toolbar .form-select {
        width: 180px;
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        border: 1.5px solid var(--border);
        font-size: 14px;
        color: var(--text-primary);
        background: var(--white);
        cursor: pointer;
    }
    .patient-count-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 16px;
        background: var(--accent-light);
        color: var(--accent);
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .patient-table-card {
        animation: fadeInUp 0.5s ease 0.1s both;
    }

    .patient-name-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .patient-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        flex-shrink: 0;
    }
    .patient-avatar-1 { background: rgba(52,152,219,0.12); color: #3498DB; }
    .patient-avatar-2 { background: rgba(46,204,113,0.12); color: #2ECC71; }
    .patient-avatar-3 { background: rgba(155,89,182,0.12); color: #9B59B6; }
    .patient-avatar-4 { background: rgba(243,156,18,0.12); color: #F39C12; }
    .patient-avatar-5 { background: rgba(231,76,60,0.12); color: #E74C3C; }

    .actions-cell {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .skeleton-table-body .skeleton-row {
        height: 56px;
        margin-bottom: 6px;
        border-radius: 6px;
    }

    @media (max-width: 768px) {
        .patient-toolbar { flex-direction: column; align-items: stretch; }
        .patient-toolbar .form-select { width: 100%; }
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h2>Patient Management</h2>
            <p>Browse, search, and manage all registered patients</p>
        </div>
        <div class="patient-count-badge" id="patientCountBadge">
            👥 <span id="patientTotalCount">Loading...</span> patients
        </div>
    </div>
</div>

<!-- Toolbar: Search + Filter -->
<div class="patient-toolbar">
    <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" class="form-input" id="patientSearch" placeholder="Search by patient name...">
    </div>
    <select class="form-select" id="riskFilter">
        <option value="all">All Risk Levels</option>
        <option value="low">🟢 Low Risk</option>
        <option value="medium">🟡 Medium Risk</option>
        <option value="high">🔴 High Risk</option>
    </select>
</div>

<!-- Patient Table -->
<div class="card patient-table-card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient Name</th>
                    <th>Age</th>
                    <th>Risk Level</th>
                    <th>Primary Disease</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="patientTableBody">
                <!-- Skeleton loading -->
                <tr><td colspan="6">
                    <div class="skeleton-table-body">
                        <div class="skeleton skeleton-row"></div>
                        <div class="skeleton skeleton-row"></div>
                        <div class="skeleton skeleton-row"></div>
                        <div class="skeleton skeleton-row"></div>
                        <div class="skeleton skeleton-row"></div>
                    </div>
                </td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination" id="pagination"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const PER_PAGE = 10;
    let allPatients = [];
    let filteredPatients = [];
    let currentPage = 1;

    // --- Load Patients ---
    async function loadPatients() {
        try {
            const data = await apiFetch('patients');
            allPatients = Array.isArray(data) ? data : (data.patients || data.data || []);
            document.getElementById('patientTotalCount').textContent = allPatients.length;
            applyFilters();
        } catch (err) {
            document.getElementById('patientTableBody').innerHTML =
                '<tr><td colspan="6"><div class="empty-state"><div class="icon">⚠️</div><h3>Failed to load patients</h3><p>Please check the API connection and try again.</p></div></td></tr>';
        }
    }

    // --- Filter Logic ---
    function applyFilters() {
        const searchTerm = document.getElementById('patientSearch').value.trim().toLowerCase();
        const riskFilter = document.getElementById('riskFilter').value;

        filteredPatients = allPatients.filter(p => {
            const name = (p.name || p.patientName || p.patient_name || '').toLowerCase();
            const risk = (p.riskLevel || p.risk_level || '').toLowerCase();
            const matchSearch = !searchTerm || name.includes(searchTerm);
            const matchRisk = riskFilter === 'all' || risk === riskFilter || (riskFilter === 'medium' && risk === 'moderate');
            return matchSearch && matchRisk;
        });

        currentPage = 1;
        renderTable();
        renderPagination();
    }

    // --- Render Table ---
    function renderTable() {
        const tbody = document.getElementById('patientTableBody');
        const start = (currentPage - 1) * PER_PAGE;
        const pageData = filteredPatients.slice(start, start + PER_PAGE);

        if (!filteredPatients.length) {
            tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="icon">🔍</div><h3>No patients found</h3><p>Try adjusting your search or filter criteria.</p></div></td></tr>';
            return;
        }

        const avatarColors = ['patient-avatar-1','patient-avatar-2','patient-avatar-3','patient-avatar-4','patient-avatar-5'];

        tbody.innerHTML = pageData.map((p, i) => {
            const id = p.patientID || p.id || p.patientId || p.patient_id || '';
            const name = p.name || p.patientName || p.patient_name || 'Unknown';
            const age = p.age || '—';
            const risk = p.riskLevel || p.risk_level || 'Low';
            const disease = p.diseases || p.disease || p.primaryDisease || p.primary_disease || '—';
            const initials = name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
            const avatarClass = avatarColors[i % avatarColors.length];

            return `
                <tr style="animation: fadeInUp 0.3s ease ${i * 0.04}s both;">
                    <td><code style="font-size:12px;color:var(--text-muted);background:rgba(26,43,74,0.04);padding:3px 8px;border-radius:4px;">${escapeHtml(String(id))}</code></td>
                    <td>
                        <div class="patient-name-cell">
                            <div class="patient-avatar ${avatarClass}">${initials}</div>
                            <strong>${escapeHtml(name)}</strong>
                        </div>
                    </td>
                    <td>${escapeHtml(String(age))}</td>
                    <td>${riskBadge(risk)}</td>
                    <td>${escapeHtml(disease)}</td>
                    <td>
                        <div class="actions-cell">
                            <a href="?page=patient_detail&id=${encodeURIComponent(id)}" class="btn btn-outline btn-sm">View Detail</a>
                            <button class="btn btn-accent btn-sm" onclick="runInference('${escapeHtml(String(id))}')">⚡ Inference</button>
                        </div>
                    </td>
                </tr>`;
        }).join('');
    }

    // --- Pagination ---
    function renderPagination() {
        const container = document.getElementById('pagination');
        const totalPages = Math.ceil(filteredPatients.length / PER_PAGE);
        if (totalPages <= 1) { container.innerHTML = ''; return; }

        let html = '';
        html += `<button ${currentPage === 1 ? 'disabled' : ''} onclick="window._patientGoPage(${currentPage - 1})">‹</button>`;

        const start = Math.max(1, currentPage - 2);
        const end = Math.min(totalPages, currentPage + 2);

        if (start > 1) {
            html += `<button onclick="window._patientGoPage(1)">1</button>`;
            if (start > 2) html += `<button disabled>…</button>`;
        }

        for (let i = start; i <= end; i++) {
            html += `<button class="${i === currentPage ? 'active' : ''}" onclick="window._patientGoPage(${i})">${i}</button>`;
        }

        if (end < totalPages) {
            if (end < totalPages - 1) html += `<button disabled>…</button>`;
            html += `<button onclick="window._patientGoPage(${totalPages})">${totalPages}</button>`;
        }

        html += `<button ${currentPage === totalPages ? 'disabled' : ''} onclick="window._patientGoPage(${currentPage + 1})">›</button>`;
        container.innerHTML = html;
    }

    window._patientGoPage = function(page) {
        currentPage = page;
        renderTable();
        renderPagination();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // --- Run Inference ---
    window.runInference = async function(patientId) {
        showSpinner('Running inference engine...');
        try {
            const result = await apiFetch('inference', { params: { patientId } });
            hideSpinner();
            showToast('success', 'Inference Complete', `Inference completed for patient ${patientId}.`);
            // Navigate to detail page
            window.location.href = `?page=patient_detail&id=${encodeURIComponent(patientId)}`;
        } catch (err) {
            hideSpinner();
        }
    };

    // --- Event Listeners ---
    document.getElementById('patientSearch').addEventListener('input', debounce(applyFilters, 250));
    document.getElementById('riskFilter').addEventListener('change', applyFilters);

    // --- Init ---
    await loadPatients();
});
</script>

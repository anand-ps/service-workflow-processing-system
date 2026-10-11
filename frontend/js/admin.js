// Administrator Dashboard Frontend Logic

async function loadAdminDashboard() {
    const user = requireAuthGuard(['admin']);
    if (!user) return;

    loadSystemReports();
    loadPendingProviders();
    loadAdminBookings();
}

// Load summary metrics & reports
async function loadSystemReports() {
    const res = await apiRequest('reports/summary.php');

    if (res.ok && res.data.status === 'success') {
        const data = res.data.data;
        const ov = data.overview;

        // Metric cards
        document.getElementById('stat-total-customers').textContent = ov.total_customers;
        document.getElementById('stat-active-providers').textContent = ov.active_providers;
        document.getElementById('stat-pending-approvals').textContent = ov.pending_approvals;
        document.getElementById('stat-total-bookings').textContent = ov.total_bookings;

        // Render Booking Status Distribution
        const statusReportBody = document.getElementById('report-status-body');
        if (statusReportBody) {
            statusReportBody.innerHTML = '';
            data.booking_status_report.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${escapeHtml(item.status)}</strong></td>
                    <td>${item.count}</td>
                `;
                statusReportBody.appendChild(tr);
            });
        }

        // Render Provider Performance Report
        const perfBody = document.getElementById('report-performance-body');
        if (perfBody) {
            perfBody.innerHTML = '';
            if (data.provider_performance.length === 0) {
                perfBody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #64748b;">No provider data available.</td></tr>';
            } else {
                data.provider_performance.forEach(p => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${escapeHtml(p.provider_name)}</strong></td>
                        <td>${escapeHtml(p.service_name)}</td>
                        <td>${escapeHtml(p.location)}</td>
                        <td>${p.total_jobs}</td>
                        <td><span style="color: #15803d; font-weight: 600;">${p.completed_jobs}</span></td>
                        <td><span class="rating-badge">★ ${p.average_rating} (${p.total_reviews})</span></td>
                    `;
                    perfBody.appendChild(tr);
                });
            }
        }
    }
}

// Load pending provider applications
async function loadPendingProviders() {
    const tbody = document.getElementById('pending-providers-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #64748b;">Checking pending registrations...</td></tr>';

    const res = await apiRequest('providers/pending.php');

    if (res.ok && res.data.status === 'success') {
        const providers = res.data.data.providers;
        tbody.innerHTML = '';

        if (providers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 24px; color: #64748b;">No providers waiting for verification. All clear!</td></tr>';
            return;
        }

        providers.forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${escapeHtml(p.provider_name)}</strong></td>
                <td>${escapeHtml(p.service_name)}</td>
                <td>📞 ${escapeHtml(p.provider_phone)}<br><small style="color: #64748b;">${escapeHtml(p.provider_email)}</small></td>
                <td>${p.experience_years} Years (₹${parseFloat(p.hourly_rate).toFixed(0)}/hr)</td>
                <td>📍 ${escapeHtml(p.location)}</td>
                <td>
                    <div style="display: flex; gap: 6px;">
                        <button onclick="handleApproveProvider(${p.provider_id}, 'approve')" class="btn btn-success btn-sm">Approve</button>
                        <button onclick="handleApproveProvider(${p.provider_id}, 'reject')" class="btn btn-danger btn-sm">Reject</button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }
}

// Approve or reject provider
async function handleApproveProvider(providerId, action) {
    const res = await apiRequest('providers/approve.php', 'POST', {
        provider_id: providerId,
        action: action
    });

    if (res.ok && res.data.status === 'success') {
        alert(res.data.message);
        loadPendingProviders();
        loadSystemReports();
    } else {
        alert(res.data.message || 'Action failed.');
    }
}

// Load master bookings table
async function loadAdminBookings() {
    const tbody = document.getElementById('admin-bookings-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #64748b;">Loading platform bookings...</td></tr>';

    const res = await apiRequest('bookings/list.php');

    if (res.ok && res.data.status === 'success') {
        const bookings = res.data.data.bookings;
        tbody.innerHTML = '';

        if (bookings.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 24px; color: #64748b;">No bookings found.</td></tr>';
            return;
        }

        bookings.forEach(b => {
            const tr = document.createElement('tr');

            let badgeClass = 'badge-pending';
            if (b.status === 'Accepted') badgeClass = 'badge-accepted';
            if (b.status === 'On the Way') badgeClass = 'badge-on-the-way';
            if (b.status === 'Completed') badgeClass = 'badge-completed';
            if (b.status === 'Rejected') badgeClass = 'badge-rejected';

            tr.innerHTML = `
                <td>#${b.booking_id}</td>
                <td><strong>${escapeHtml(b.service_name)}</strong></td>
                <td>${escapeHtml(b.customer_name)}<br><small style="color: #64748b;">📞 ${escapeHtml(b.customer_phone)}</small></td>
                <td>${escapeHtml(b.provider_name)}<br><small style="color: #64748b;">📞 ${escapeHtml(b.provider_phone)}</small></td>
                <td>${b.booking_date} (${escapeHtml(b.booking_time)})</td>
                <td><span class="badge ${badgeClass}">${b.status}</span></td>
                <td><small>${escapeHtml(b.problem_desc)}</small></td>
            `;
            tbody.appendChild(tr);
        });
    }
}

// Add service modal controls
function openAddServiceModal() {
    document.getElementById('service-name').value = '';
    document.getElementById('service-desc').value = '';
    document.getElementById('add-service-modal').classList.add('active');
}

function closeAddServiceModal() {
    document.getElementById('add-service-modal').classList.remove('active');
}

async function submitAddServiceForm(event) {
    event.preventDefault();

    const name = document.getElementById('service-name').value.trim();
    const desc = document.getElementById('service-desc').value.trim();
    const icon = document.getElementById('service-icon').value;

    if (!name) {
        alert('Please enter a service name.');
        return;
    }

    const res = await apiRequest('services/create.php', 'POST', {
        name: name,
        description: desc,
        icon: icon
    });

    if (res.ok && res.data.status === 'success') {
        alert('New service category added successfully!');
        closeAddServiceModal();
        loadSystemReports();
    } else {
        alert(res.data.message || 'Failed to add service.');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

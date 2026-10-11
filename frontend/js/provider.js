// Service Provider Dashboard Frontend Logic

async function loadProviderDashboard() {
    const user = requireAuthGuard(['provider']);
    if (!user) return;

    document.getElementById('provider-welcome-name').textContent = user.name;

    const tbody = document.getElementById('provider-bookings-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #64748b;">Loading requests...</td></tr>';

    const res = await apiRequest('bookings/list.php');

    if (res.ok && res.data.status === 'success') {
        const bookings = res.data.data.bookings;

        let totalCount = bookings.length;
        let pendingCount = 0;
        let completedCount = 0;

        tbody.innerHTML = '';

        if (bookings.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 30px; color: #64748b;">No booking requests received yet.</td></tr>';
        } else {
            bookings.forEach(b => {
                if (b.status === 'Pending') pendingCount++;
                if (b.status === 'Completed') completedCount++;

                const tr = document.createElement('tr');

                let badgeClass = 'badge-pending';
                if (b.status === 'Accepted') badgeClass = 'badge-accepted';
                if (b.status === 'On the Way') badgeClass = 'badge-on-the-way';
                if (b.status === 'Completed') badgeClass = 'badge-completed';
                if (b.status === 'Rejected') badgeClass = 'badge-rejected';

                let actionsHtml = '';

                if (b.status === 'Pending') {
                    actionsHtml = `
                        <div style="display: flex; gap: 6px;">
                            <button onclick="updateBookingStatus(${b.booking_id}, 'Accepted')" class="btn btn-success btn-sm">Accept</button>
                            <button onclick="updateBookingStatus(${b.booking_id}, 'Rejected')" class="btn btn-danger btn-sm">Reject</button>
                        </div>
                    `;
                } else if (b.status === 'Accepted') {
                    actionsHtml = `
                        <button onclick="updateBookingStatus(${b.booking_id}, 'On the Way')" class="btn btn-primary btn-sm">Mark On the Way</button>
                    `;
                } else if (b.status === 'On the Way') {
                    actionsHtml = `
                        <button onclick="updateBookingStatus(${b.booking_id}, 'Completed')" class="btn btn-success btn-sm">Mark Completed</button>
                    `;
                } else if (b.status === 'Completed') {
                    if (b.feedback_id) {
                        actionsHtml = `<span style="font-size: 0.85rem; color: #15803d;">★ ${b.feedback_rating}/5: "${escapeHtml(b.feedback_comments || 'Great job')}"</span>`;
                    } else {
                        actionsHtml = `<span style="font-size: 0.85rem; color: #64748b;">Awaiting Customer Review</span>`;
                    }
                } else {
                    actionsHtml = `<span style="font-size: 0.85rem; color: #64748b;">Rejected</span>`;
                }

                tr.innerHTML = `
                    <td>#${b.booking_id}</td>
                    <td>
                        <strong>${escapeHtml(b.customer_name)}</strong><br>
                        <small style="color: #64748b;">📞 ${escapeHtml(b.customer_phone)}</small>
                    </td>
                    <td>
                        <small>📍 ${escapeHtml(b.address)}</small>
                    </td>
                    <td>
                        <small>${escapeHtml(b.problem_desc)}</small>
                    </td>
                    <td>
                        ${b.booking_date}<br>
                        <small style="color: #64748b;">${escapeHtml(b.booking_time)}</small>
                    </td>
                    <td><span class="badge ${badgeClass}">${b.status}</span></td>
                    <td>${actionsHtml}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Update stats cards
        const totalEl = document.getElementById('stat-total-jobs');
        const pendingEl = document.getElementById('stat-pending-jobs');
        const completedEl = document.getElementById('stat-completed-jobs');

        if (totalEl) totalEl.textContent = totalCount;
        if (pendingEl) pendingEl.textContent = pendingCount;
        if (completedEl) completedEl.textContent = completedCount;
    }
}

// Update booking status from provider actions
async function updateBookingStatus(bookingId, newStatus) {
    const res = await apiRequest('bookings/update-status.php', 'POST', {
        booking_id: bookingId,
        status: newStatus
    });

    if (res.ok && res.data.status === 'success') {
        loadProviderDashboard();
    } else {
        alert(res.data.message || 'Failed to update booking status.');
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

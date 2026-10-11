// Customer / User Frontend Logic

let currentSelectedServiceId = 0;
let currentBookingProvider = null;
let currentFeedbackBookingId = null;
let currentSelectedRating = 5;

// Load categories on home page
async function loadHomeServices() {
    const grid = document.getElementById('categories-grid');
    if (!grid) return;

    grid.innerHTML = '<p class="text-muted">Loading available services...</p>';
    const res = await apiRequest('services/list.php');

    if (res.ok && res.data.status === 'success') {
        const services = res.data.data.services;
        grid.innerHTML = '';

        // "All Services" card
        const allCard = document.createElement('div');
        allCard.className = 'category-card active';
        allCard.id = 'cat-card-0';
        allCard.innerHTML = `
            <div class="category-icon">🛠️</div>
            <div class="category-name">All Services</div>
            <div class="category-count">Show all</div>
        `;
        allCard.onclick = () => selectCategory(0);
        grid.appendChild(allCard);

        const iconMap = {
            'zap': '⚡',
            'droplet': '💧',
            'hammer': '🔨',
            'brush': '🖌️',
            'tool': '🔧',
            'sparkles': '✨'
        };

        services.forEach(s => {
            const card = document.createElement('div');
            card.className = 'category-card';
            card.id = `cat-card-${s.id}`;
            const iconEmoji = iconMap[s.icon] || '🛠️';
            card.innerHTML = `
                <div class="category-icon">${iconEmoji}</div>
                <div class="category-name">${escapeHtml(s.name)}</div>
                <div class="category-count">${s.provider_count} Available</div>
            `;
            card.onclick = () => selectCategory(s.id);
            grid.appendChild(card);
        });
    }
}

// Category filter click
function selectCategory(serviceId) {
    currentSelectedServiceId = serviceId;
    document.querySelectorAll('.category-card').forEach(el => el.classList.remove('active'));
    const activeEl = document.getElementById(`cat-card-${serviceId}`);
    if (activeEl) activeEl.classList.add('active');
    loadProviders();
}

// Search input debounce/trigger
function handleSearchInput() {
    loadProviders();
}

// Load providers grid
async function loadProviders() {
    const grid = document.getElementById('providers-grid');
    if (!grid) return;

    const searchInput = document.getElementById('search-input');
    const searchVal = searchInput ? searchInput.value.trim() : '';

    let endpoint = `providers/list.php?service_id=${currentSelectedServiceId}&search=${encodeURIComponent(searchVal)}`;

    grid.innerHTML = '<p class="text-muted">Searching verified professionals...</p>';
    const res = await apiRequest(endpoint);

    if (res.ok && res.data.status === 'success') {
        const providers = res.data.data.providers;
        if (providers.length === 0) {
            grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #64748b;">No service providers found matching your criteria.</div>';
            return;
        }

        grid.innerHTML = '';
        providers.forEach(p => {
            const card = document.createElement('div');
            card.className = 'provider-card';
            card.innerHTML = `
                <div>
                    <div class="provider-header">
                        <div class="provider-avatar">${escapeHtml(p.provider_name.charAt(0))}</div>
                        <div class="provider-info">
                            <h3>${escapeHtml(p.provider_name)}</h3>
                            <div class="provider-service">${escapeHtml(p.service_name)} Specialist</div>
                        </div>
                    </div>
                    <div class="provider-meta">
                        <div class="provider-meta-row">
                            <span>Rating:</span>
                            <span class="rating-badge">★ ${p.avg_rating} (${p.review_count} reviews)</span>
                        </div>
                        <div class="provider-meta-row">
                            <span>Experience:</span>
                            <strong>${p.experience_years} Years</strong>
                        </div>
                        <div class="provider-meta-row">
                            <span>Location:</span>
                            <span>📍 ${escapeHtml(p.location)}</span>
                        </div>
                        <div class="provider-meta-row">
                            <span>Completed Jobs:</span>
                            <span>${p.completed_jobs} successful</span>
                        </div>
                    </div>
                </div>
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; margin-bottom: 14px;">
                        <span style="font-size: 0.85rem; color: #64748b;">Standard Rate:</span>
                        <span class="rate-badge">₹${parseFloat(p.hourly_rate).toFixed(0)}/hr</span>
                    </div>
                    <button onclick="openBookingModal(${p.provider_id}, '${escapeHtml(p.provider_name)}', '${escapeHtml(p.service_name)}', ${p.hourly_rate})" class="btn btn-primary btn-block">Book Service</button>
                </div>
            `;
            grid.appendChild(card);
        });
    }
}

// Open booking modal
function openBookingModal(providerId, providerName, serviceName, rate) {
    const user = getStoredUser();
    if (!user) {
        alert('Please login or register to book a service.');
        window.location.href = 'login.html';
        return;
    }

    if (user.role !== 'user') {
        alert('Service booking is only available for Customer accounts.');
        return;
    }

    currentBookingProvider = { id: providerId, name: providerName, service: serviceName, rate };

    document.getElementById('modal-provider-name').textContent = providerName;
    document.getElementById('modal-service-name').textContent = serviceName;
    document.getElementById('modal-hourly-rate').textContent = `₹${parseFloat(rate).toFixed(0)}/hr`;

    // Default tomorrow's date
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('book-date').value = tomorrow.toISOString().split('T')[0];

    document.getElementById('booking-modal').classList.add('active');
}

function closeBookingModal() {
    document.getElementById('booking-modal').classList.remove('active');
}

// Submit booking form
async function submitBookingForm(event) {
    event.preventDefault();
    if (!currentBookingProvider) return;

    const date = document.getElementById('book-date').value;
    const time = document.getElementById('book-time').value;
    const address = document.getElementById('book-address').value.trim();
    const problemDesc = document.getElementById('book-desc').value.trim();

    if (!date || !time || !address || !problemDesc) {
        alert('Please fill in all booking fields.');
        return;
    }

    const payload = {
        provider_id: currentBookingProvider.id,
        booking_date: date,
        booking_time: time,
        address: address,
        problem_desc: problemDesc
    };

    const res = await apiRequest('bookings/create.php', 'POST', payload);

    if (res.ok && res.data.status === 'success') {
        alert('Service booking request placed successfully! You can track its status in your dashboard.');
        closeBookingModal();
        window.location.href = 'user-dashboard.html';
    } else {
        alert(res.data.message || 'Unable to place booking.');
    }
}

// User Dashboard: Load my bookings
async function loadUserBookings() {
    const user = requireAuthGuard(['user']);
    if (!user) return;

    const tbody = document.getElementById('user-bookings-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #64748b;">Loading your bookings...</td></tr>';

    const res = await apiRequest('bookings/list.php');

    if (res.ok && res.data.status === 'success') {
        const bookings = res.data.data.bookings;
        if (bookings.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 30px; color: #64748b;">You have not placed any service bookings yet. <a href="index.html">Book a service</a>.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        bookings.forEach(b => {
            const tr = document.createElement('tr');

            let badgeClass = 'badge-pending';
            if (b.status === 'Accepted') badgeClass = 'badge-accepted';
            if (b.status === 'On the Way') badgeClass = 'badge-on-the-way';
            if (b.status === 'Completed') badgeClass = 'badge-completed';
            if (b.status === 'Rejected') badgeClass = 'badge-rejected';

            let actionHtml = '';
            if (b.status === 'Pending') {
                actionHtml = `<button onclick="cancelBooking(${b.booking_id})" class="btn btn-outline btn-sm text-danger">Cancel</button>`;
            } else if (b.status === 'Completed') {
                if (b.feedback_id) {
                    actionHtml = `<span style="font-size: 0.85rem; color: #15803d; font-weight: 600;">★ Rated ${b.feedback_rating}/5</span>`;
                } else {
                    actionHtml = `<button onclick="openFeedbackModal(${b.booking_id}, '${escapeHtml(b.provider_name)}')" class="btn btn-primary btn-sm">Rate & Review</button>`;
                }
            } else {
                actionHtml = `<span style="font-size: 0.85rem; color: #64748b;">In Progress</span>`;
            }

            tr.innerHTML = `
                <td>#${b.booking_id}</td>
                <td>
                    <strong>${escapeHtml(b.service_name)}</strong><br>
                    <small style="color: #64748b;">${escapeHtml(b.problem_desc)}</small>
                </td>
                <td>
                    <strong>${escapeHtml(b.provider_name)}</strong><br>
                    <small style="color: #64748b;">📞 ${escapeHtml(b.provider_phone)}</small>
                </td>
                <td>
                    ${b.booking_date}<br>
                    <small style="color: #64748b;">${escapeHtml(b.booking_time)}</small>
                </td>
                <td>₹${parseFloat(b.provider_rate).toFixed(0)}/hr</td>
                <td><span class="badge ${badgeClass}">${b.status}</span></td>
                <td>${actionHtml}</td>
            `;
            tbody.appendChild(tr);
        });
    }
}

// Cancel pending booking
async function cancelBooking(bookingId) {
    if (!confirm('Are you sure you want to cancel this booking request?')) return;

    const res = await apiRequest('bookings/update-status.php', 'POST', {
        booking_id: bookingId,
        status: 'Rejected'
    });

    if (res.ok && res.data.status === 'success') {
        alert('Booking request cancelled.');
        loadUserBookings();
    } else {
        alert(res.data.message || 'Failed to cancel booking.');
    }
}

// Feedback Modal
function openFeedbackModal(bookingId, providerName) {
    currentFeedbackBookingId = bookingId;
    currentSelectedRating = 5;
    document.getElementById('feedback-provider-name').textContent = providerName;
    document.getElementById('feedback-comment').value = '';
    updateStarUI(5);
    document.getElementById('feedback-modal').classList.add('active');
}

function closeFeedbackModal() {
    document.getElementById('feedback-modal').classList.remove('active');
}

function selectRating(starVal) {
    currentSelectedRating = starVal;
    updateStarUI(starVal);
}

function updateStarUI(val) {
    const stars = document.querySelectorAll('#star-widget .star');
    stars.forEach(s => {
        const starNum = parseInt(s.getAttribute('data-val'), 10);
        if (starNum <= val) {
            s.classList.add('selected');
        } else {
            s.classList.remove('selected');
        }
    });
}

async function submitFeedbackForm(event) {
    event.preventDefault();
    if (!currentFeedbackBookingId) return;

    const comments = document.getElementById('feedback-comment').value.trim();

    const res = await apiRequest('feedback/create.php', 'POST', {
        booking_id: currentFeedbackBookingId,
        rating: currentSelectedRating,
        comments: comments
    });

    if (res.ok && res.data.status === 'success') {
        alert('Thank you! Your feedback has been recorded.');
        closeFeedbackModal();
        loadUserBookings();
    } else {
        alert(res.data.message || 'Failed to submit feedback.');
    }
}

// Utility to escape HTML strings safely
function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

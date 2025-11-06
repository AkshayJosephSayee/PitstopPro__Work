<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Require session check (redirect to login if not admin)
if (file_exists('check_admin_session.php')) include 'check_admin_session.php';
require_once 'admin_functions.php';

// Fetch data for page
$dashboardStats = function_exists('getDashboardStats') ? getDashboardStats() : ['totalBookings'=>0,'pendingServices'=>0,'completedToday'=>0,'totalRevenue'=>0];
$recentBookings = function_exists('getRecentBookings') ? getRecentBookings() : [];
$allUsers = function_exists('getAllUsers') ? getAllUsers() : [];
$allServices = function_exists('getAllServices') ? getAllServices() : [];
$completedBookings = function_exists('getCompletedBookings') ? getCompletedBookings() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pitstop Pro - Admin Dashboard</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="vendor/jquery/jquery.min.js"></script>
    <style>
        /* keep same styles as admin.html (trimmed for brevity) */
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh}
        .header{background:rgba(232, 231, 231, 0.95);padding:20px 40px;display:flex;justify-content:space-between;align-items:center}
        .tab-btn{padding:12px 30px;border-radius:8px;border:none;cursor:pointer}
        .tab-btn.active{background:#667eea;color:#fff}
        .tab-content{display:none;background:rgba(230, 227, 227, 1);padding:30px;border-radius:15px}
        .tab-content.active{display:block}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px}
        .stat-card{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:25px;border-radius:12px}
        .status-badge{padding:5px 12px;border-radius:20px;font-weight:600}
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🏁 Pitstop Pro</div>
        <div class="admin-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['Username'] ?? 'Admin'); ?></span>
            <a id="logoutBtn" class="btn btn-danger" href="admin_logout.php" onclick="logout()" style="margin-left:12px;">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="nav-tabs" style="margin-top:18px;">
            <button class="tab-btn active" data-tab="dashboard" onclick="showTab('dashboard', this)">Dashboard</button>
            <button class="tab-btn" data-tab="reports" onclick="showTab('reports', this)">Reports</button>
            <button class="tab-btn" data-tab="users" onclick="showTab('users', this)">Manage Users</button>
            <button class="tab-btn" data-tab="services" onclick="showTab('services', this)">Manage Services</button>
            <button class="tab-btn" data-tab="bills" onclick="showTab('bills', this)">Generate Bills</button>
        </div>

        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content active">
            <h2 style="margin-bottom:20px">Dashboard Overview</h2>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-value"><?php echo (int)$dashboardStats['totalBookings']; ?></div><div class="stat-label">Total Bookings</div></div>
                <div class="stat-card"><div class="stat-value"><?php echo (int)$dashboardStats['pendingServices']; ?></div><div class="stat-label">Pending Services</div></div>
                <div class="stat-card"><div class="stat-value"><?php echo (int)$dashboardStats['completedToday']; ?></div><div class="stat-label">Completed Today</div></div>
                <div class="stat-card"><div class="stat-value">₹<?php echo number_format($dashboardStats['totalRevenue']); ?></div><div class="stat-label">Total Revenue</div></div>
            </div>

            <h3>Recent Bookings</h3>
            <table id="recentBookingsTable" class="table table-striped">
                <thead>
                    <tr><th>Booking ID</th><th>Customer</th><th>Service</th><th>Date</th><th>Status</th></tr>
                </thead>
                <tbody id="recentBookings"></tbody>
            </table>
        </div>

        <!-- Reports Tab -->
        <div id="reports" class="tab-content">
            <div class="report-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <h2>Generate Reports</h2>
                <button class="btn btn-primary" onclick="exportReport()">Export PDF</button>
            </div>
            <div class="report-filters" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px">
                <div class="form-group"><label>Report Type</label><select id="reportType"><option value="booking">Booking Status Report</option><option value="service">Service Status Report</option><option value="revenue">Revenue Report</option></select></div>
                <div class="form-group"><label>From Date</label><input type="date" id="reportFromDate"></div>
                <div class="form-group"><label>To Date</label><input type="date" id="reportToDate"></div>
                <div class="form-group" style="align-self:end"><button class="btn btn-primary" onclick="generateReport()">Generate Report</button></div>
            </div>
            <div id="reportResults"></div>
        </div>

        <!-- Users Tab -->
        <div id="users" class="tab-content">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px"><h2>Manage Users</h2><button class="btn btn-primary" onclick="showAddUserModal()">Add New User</button></div>
            <table class="table"><thead><tr><th>User ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Actions</th></tr></thead><tbody id="usersTable"></tbody></table>
        </div>

        <!-- Services Tab -->
        <div id="services" class="tab-content">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px"><h2>Manage Services</h2><button class="btn btn-primary" onclick="showAddServiceModal()">Add New Service</button></div>
            <table class="table"><thead><tr><th>Service ID</th><th>Service Name</th><th>Description</th><th>Price</th><th>Duration</th><th>Actions</th></tr></thead><tbody id="servicesTable"></tbody></table>
        </div>

        <!-- Bills Tab -->
        <div id="bills" class="tab-content">
            <h2 style="margin-bottom:20px">Generate Bill</h2>
            <div class="form-grid">
                <div class="form-group"><label>Select Booking</label><select id="billBooking" onchange="loadBookingDetails()"><option value="">Select a booking</option></select></div>
                <div class="form-group"><label>Customer Name</label><input type="text" id="billCustomerName" readonly></div>
                <div class="form-group"><label>Vehicle Number</label><input type="text" id="billVehicle" readonly></div>
                <div class="form-group"><label>Service</label><input type="text" id="billService" readonly></div>
            </div>
            <h3>Additional Items</h3>
            <div id="additionalItems"></div>
            <button class="btn btn-success" onclick="addBillItem()">Add Item</button>
            <div class="bill-preview" id="billPreview" style="margin-top:16px"></div>
            <button class="btn btn-primary" style="margin-top:20px" onclick="generateBill()">Generate & Print Bill</button>
        </div>
    </div>

    <!-- Modals (users/services) -->
    <div id="userModal" class="modal" style="display:none"><div class="modal-content"><div class="modal-header"><h3 id="userModalTitle">Add New User</h3><span class="close-modal" onclick="closeModal('userModal')">&times;</span></div><form id="userForm"><input type="hidden" id="editUserId"><div class="form-group"><label>Full Name</label><input type="text" id="userName" required></div><div class="form-group"><label>Email</label><input type="email" id="userEmail" required></div><div class="form-group"><label>Phone</label><input type="tel" id="userPhone" required></div><div class="form-group"><label>Role</label><select id="userRole" required><option value="customer">Customer</option><option value="mechanic">Mechanic</option><option value="admin">Admin</option></select></div><button type="submit" class="btn btn-primary">Save User</button></form></div></div>

    <div id="serviceModal" class="modal" style="display:none"><div class="modal-content"><div class="modal-header"><h3 id="serviceModalTitle">Add New Service</h3><span class="close-modal" onclick="closeModal('serviceModal')">&times;</span></div><form id="serviceForm"><input type="hidden" id="editServiceId"><div class="form-group"><label>Service Name</label><input type="text" id="serviceName" required></div><div class="form-group"><label>Description</label><textarea id="serviceDescription" required></textarea></div><div class="form-group"><label>Price (₹)</label><input type="number" id="servicePrice" required></div><div class="form-group"><label>Duration (hours)</label><input type="number" id="serviceDuration" step="0.5" required></div><button type="submit" class="btn btn-primary">Save Service</button></form></div></div>

    <script>
        // Inject server data into JS
        let bookings = <?php echo json_encode($recentBookings, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?> || [];
        let users = <?php echo json_encode($allUsers, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?> || [];
        let services = <?php echo json_encode($allServices, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?> || [];
        let billItems = [];

        // Small helper to POST to admin_ajax.php
        async function postAction(action, payload = {}) {
            const form = new URLSearchParams();
            form.append('action', action);
            if (payload && Object.keys(payload).length) {
                // For complex payloads use 'data' JSON param
                form.append('data', JSON.stringify(payload));
            }
            const res = await fetch('admin_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: form.toString()
            });
            return res.json();
        }

        function showTab(tabName, btn){
            document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            if(btn) btn.classList.add('active');
            // Load fresh data when switching
            if(tabName==='dashboard') loadDashboard();
            if(tabName==='users') loadUsers();
            if(tabName==='services') loadServices();
            if(tabName==='bills') loadBillBookings();
        }

        // Dashboard: stats + bookings
        async function loadDashboard(){
            try {
                const statsRes = await postAction('getStats');
                if (statsRes.success) {
                    const s = statsRes.data || {};
                    document.querySelector('.stat-card .stat-value') && (document.querySelectorAll('.stat-value')[0].textContent = s.totalBookings || 0);
                    document.querySelectorAll('.stat-value')[1].textContent = s.pendingServices || 0;
                    document.querySelectorAll('.stat-value')[2].textContent = s.completedToday || 0;
                    document.querySelectorAll('.stat-value')[3].textContent = '₹' + Number(s.totalRevenue || 0).toLocaleString();
                }

                const bookingsRes = await postAction('getBookings', { limit: 10 });
                if (bookingsRes.success) {
                    bookings = bookingsRes.data || [];
                    renderDashboard();
                }
            } catch (e) {
                console.error('Dashboard load error', e);
            }
        }

        function renderDashboard(){
            const tbody = document.getElementById('recentBookings');
            tbody.innerHTML = bookings.map(b=>`<tr><td>${b.booking_id||b.id||''}</td><td>${b.Username||b.username||b.customer||''}</td><td>${b.service_type||b.service||''}</td><td>${b.booking_date||b.date||''}</td><td><span class="status-badge status-${(b.b_status||b.status||'').toLowerCase()}">${(b.b_status||b.status||'').toString().toUpperCase()}</span></td></tr>`).join('');
        }

        // Users
        async function loadUsers(){
            try {
                const res = await postAction('getUsers');
                if (res.success) {
                    users = res.data || [];
                    renderUsers();
                }
            } catch (e) { console.error(e); }
        }

        function renderUsers(){
            document.getElementById('usersTable').innerHTML = users.map(u=>`<tr><td>${u.id||u.User_id_||''}</td><td>${u.name||u.Username||''}</td><td>${u.email||''}</td><td>${u.phone||u.Phone||''}</td><td>${u.role||'customer'}</td><td><button class="btn btn-warning" onclick="showEditUser(${u.id||u.User_id_||0})">Edit</button> <button class="btn btn-danger" onclick="deleteUser(${u.id||u.User_id_||0})">Delete</button></td></tr>`).join('');
        }

        function showEditUser(id){
            const user = users.find(u => (u.id||u.User_id_) == id);
            if(!user) return alert('User not found');
            document.getElementById('userModal').style.display='block';
            document.getElementById('editUserId').value = user.id || user.User_id_ || '';
            document.getElementById('userName').value = user.name || user.Username || '';
            document.getElementById('userEmail').value = user.email || '';
            document.getElementById('userPhone').value = user.phone || user.Phone || '';
            document.getElementById('userRole').value = user.role || 'customer';
        }

        async function deleteUser(id){
            if (!confirm('Delete this user?')) return;
            try {
                const res = await fetch('admin_ajax.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: `action=deleteUser&id=${id}` });
                const j = await res.json();
                if (j.success) loadUsers(); else alert(j.error || 'Failed');
            } catch (e) { console.error(e); }
        }

        // Service CRUD
        async function loadServices(){
            try {
                const res = await postAction('getServices');
                if (res.success) { services = res.data || []; renderServices(); }
            } catch (e) { console.error(e); }
        }

        function renderServices(){
            document.getElementById('servicesTable').innerHTML = services.map(s=>`<tr><td>${s.id||s.Service_id||''}</td><td>${s.name||s.service_name||''}</td><td>${s.description||''}</td><td>₹${s.price||0}</td><td>${s.duration||s.Estimated_duration||''}</td><td><button class="btn btn-warning" onclick="showEditService(${s.id||s.Service_id||0})">Edit</button> <button class="btn btn-danger" onclick="deleteService(${s.id||s.Service_id||0})">Delete</button></td></tr>`).join('');
        }

        function showEditService(id){
            const svc = services.find(s => (s.id||s.Service_id) == id);
            if(!svc) return alert('Service not found');
            document.getElementById('serviceModal').style.display='block';
            document.getElementById('editServiceId').value = svc.id || svc.Service_id || '';
            document.getElementById('serviceName').value = svc.name || svc.service_name || '';
            document.getElementById('serviceDescription').value = svc.description || '';
            document.getElementById('servicePrice').value = svc.price || 0;
            document.getElementById('serviceDuration').value = svc.duration || svc.Estimated_duration || 0;
        }

        async function deleteService(id){
            if (!confirm('Delete this service?')) return;
            try {
                const res = await fetch('admin_ajax.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: `action=deleteService&id=${id}` });
                const j = await res.json();
                if (j.success) loadServices(); else alert(j.error || 'Failed');
            } catch (e) { console.error(e); }
        }

        // Bills
        async function loadBillBookings(){
            // refresh bookings from server
            const res = await postAction('getBookings', { limit: 100 });
            if (res.success) bookings = res.data || [];
            const sel = document.getElementById('billBooking');
            sel.innerHTML = '<option value="">Select a booking</option>' + bookings.filter(b=> (b.b_status||b.status)==='completed').map(b=>`<option value="${b.booking_id||b.id}">${b.booking_id||b.id} - ${b.Username||b.customer} - ${b.service_name||b.service}</option>`).join('');
            billItems = [];
            document.getElementById('additionalItems').innerHTML = '';
            updateBillPreview();
        }

        function loadBookingDetails(){ const id=document.getElementById('billBooking').value; const b = bookings.find(x => (x.booking_id||x.id)==id); if(!b) return; document.getElementById('billCustomerName').value = b.Username||''; document.getElementById('billService').value = b.service_name||''; updateBillPreview(); }

        function addBillItem(){ const id=billItems.length; const html=`<div id="item-${id}" style="margin-bottom:12px"><input type="text" id="itemName-${id}" placeholder="Item name"> <input type="number" id="itemPrice-${id}" placeholder="Price"> <button class="btn btn-danger" onclick="removeItem(${id})">Remove</button></div>`; document.getElementById('additionalItems').insertAdjacentHTML('beforeend', html); billItems.push({id}); }
        function removeItem(id){ const el=document.getElementById('item-'+id); if(el) el.remove(); billItems=billItems.filter(i=>i.id!==id); updateBillPreview(); }

        async function updateBillPreview(){ const id=document.getElementById('billBooking').value; if(!id){ document.getElementById('billPreview').innerHTML = '<p>Please select a booking first</p>'; return;} const b = bookings.find(x => (x.booking_id||x.id)==id); let total = parseFloat(b.price||0); let html='<h3>Bill Preview</h3>'; html+=`<div class="bill-row"><strong>Customer:</strong> ${b.Username||b.customer||''}</div>`; html+=`<div class="bill-row"><strong>Service:</strong> ${b.service_type||b.service||''}</div>`; billItems.forEach(it=>{ const name=document.getElementById('itemName-'+it.id)?.value||''; const price=parseFloat(document.getElementById('itemPrice-'+it.id)?.value)||0; if(name && price){ html+=`<div class="bill-row"><span>${name}</span><span>₹${price}</span></div>`; total+=price; }}); html+=`<div class="bill-row bill-total"><span>Total Amount:</span><span>₹${total}</span></div>`; document.getElementById('billPreview').innerHTML = html; }

        async function generateBill(){ const booking_id=document.getElementById('billBooking').value; if(!booking_id){alert('Please select a booking'); return;} const items = billItems.map(i=>({ name: document.getElementById('itemName-'+i.id)?.value || '', price: parseFloat(document.getElementById('itemPrice-'+i.id)?.value) || 0 })).filter(it=>it.name && it.price>0); try{ const res = await fetch('admin_ajax.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: `action=generateBill&bookingId=${encodeURIComponent(booking_id)}&items=${encodeURIComponent(JSON.stringify(items))}` }); const j = await res.json(); if(j.success){ alert('Bill generated. Total: ' + (j.data?.total || j.data?.total_amount || 'N/A')); window.print(); } else alert(j.error || 'Failed'); }catch(e){console.error(e);} }

        // Form handlers (users/services)
        document.getElementById('userForm').addEventListener('submit', async function(e){ e.preventDefault(); const id = document.getElementById('editUserId').value; const payload = { name: document.getElementById('userName').value, email: document.getElementById('userEmail').value, phone: document.getElementById('userPhone').value }; if(id) payload.id = id; const action = id ? 'updateUser' : 'addUser'; const res = await postAction(action, payload); if(res.success){ document.getElementById('userModal').style.display='none'; loadUsers(); } else alert(res.error || 'Error'); });

                document.getElementById('serviceForm').addEventListener('submit', async function(e){ e.preventDefault(); const id = document.getElementById('editServiceId').value; const payload = { name: document.getElementById('serviceName').value, description: document.getElementById('serviceDescription').value, price: parseFloat(document.getElementById('servicePrice').value)||0, duration: parseFloat(document.getElementById('serviceDuration').value)||0 }; if(id) payload.id = id; const action = id ? 'updateService' : 'addService'; const res = await postAction(action, payload); if(res.success){ document.getElementById('serviceModal').style.display='none'; loadServices(); } else alert(res.error || 'Error'); });

        // init - load fresh data from server
        loadDashboard(); loadUsers(); loadServices(); loadBillBookings();

        // Logout helper: call server to destroy session then redirect to login page
        async function logout(){
            try {
                // Call admin_logout.php to destroy the session
                const response = await fetch('admin_logout.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                });
                // Redirect to login page regardless of response
                window.location.href = 'login.html';
            } catch(e) {
                // If fetch fails, fallback to direct navigation
                window.location.href = 'admin_logout.php';
            }
        }

        // init - load fresh data from server
        loadDashboard(); loadUsers(); loadServices(); loadBillBookings();

        // Logout helper: call server to destroy session then redirect to login page
        async function logout(){
            try{
                // Best-effort AJAX call to destroy session server-side
                await fetch('admin_logout.php', { method: 'GET', credentials: 'same-origin' });
            }catch(e){
                console.error('Logout request failed:', e);
            }
            // Fallback: navigate to server logout which will destroy session and redirect to login
            window.location.href = 'admin_logout.php';
        }
    </script>
</body>
</html>


/* assets/js/main.js - Frontend JavaScript */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Character counter for textareas with maxlength attribute
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(textarea => {
        const counter = document.createElement('div');
        counter.className = 'form-text text-end';
        counter.innerHTML = `<span class="current">0</span>/<span class="maximum">${textarea.maxLength}</span>`;
        textarea.parentNode.appendChild(counter);
        
        textarea.addEventListener('input', function() {
            this.parentNode.querySelector('.current').textContent = this.value.length;
        });
        
        // Initial count
        textarea.dispatchEvent(new Event('input'));
    });
    
    // Load notifications from API
    const loadNotifications = async () => {
        try {
            const response = await fetch('ajax/load_notifications.php');
            const data = await response.json();
            
            if (data.success) {
                // Update notification badge
                const notificationBadges = document.querySelectorAll('.notification-badge');
                notificationBadges.forEach(badge => {
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                });
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    };
    
    // Check for new notifications periodically
    if (document.body.classList.contains('dashboard-page')) {
        loadNotifications();
        setInterval(loadNotifications, 60000); // Check every minute
    }
    
    // Transfer form validation
    const transferForm = document.getElementById('transferForm');
    if (transferForm) {
        transferForm.addEventListener('submit', function(event) {
            const receiverAccount = document.getElementById('receiver_account').value.trim();
            const amount = parseFloat(document.getElementById('amount').value);
            const receiverInfo = document.getElementById('receiverInfo');
            
            if (!receiverAccount) {
                event.preventDefault();
                receiverInfo.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        <strong>ত্রুটি:</strong> প্রাপকের অ্যাকাউন্ট নম্বর প্রয়োজন
                    </div>
                `;
                return;
            }
            
            if (isNaN(amount) || amount <= 0) {
                event.preventDefault();
                alert('সঠিক পরিমাণ দিন');
                return;
            }
            
            // Check if receiver exists
            if (!receiverInfo.innerHTML.includes('সফল')) {
                event.preventDefault();
                alert('সঠিক প্রাপক নির্বাচন করুন');
                return;
            }
        });
    }
    
    // Package purchase confirmation
    const packageForms = document.querySelectorAll('form[action="earning_plans.php"]');
    packageForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!confirm('আপনি কি নিশ্চিত যে আপনি এই প্যাকেজটি কিনতে চান?')) {
                event.preventDefault();
            }
        });
    });
});


/* assets/js/admin.js - Admin panel JavaScript */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Refresh dashboard stats
    const refreshStatsButton = document.getElementById('refreshStats');
    if (refreshStatsButton) {
        refreshStatsButton.addEventListener('click', async function() {
            try {
                this.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> লোড হচ্ছে...';
                this.disabled = true;
                
                const response = await fetch('ajax/admin/dashboard_stats.php');
                const data = await response.json();
                
                if (data.success) {
                    // Update stats
                    document.getElementById('totalUsers').textContent = data.stats.total_users;
                    document.getElementById('totalDeposits').textContent = data.stats.total_deposits;
                    document.getElementById('totalWithdrawals').textContent = data.stats.total_withdrawals;
                    document.getElementById('totalPackages').textContent = data.stats.total_packages;
                    document.getElementById('todayTransactions').textContent = data.stats.today_transactions;
                    document.getElementById('pendingDeposits').textContent = data.stats.pending_deposits;
                    document.getElementById('pendingWithdrawals').textContent = data.stats.pending_withdrawals;
                    
                    // Show success message
                    const alertBox = document.createElement('div');
                    alertBox.className = 'alert alert-success alert-dismissible fade show';
                    alertBox.innerHTML = `
                        <strong>সফল!</strong> ড্যাশবোর্ড আপডেট করা হয়েছে।
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    document.querySelector('.content-wrapper').prepend(alertBox);
                    
                    // Auto dismiss alert after 3 seconds
                    setTimeout(() => {
                        const bsAlert = new bootstrap.Alert(alertBox);
                        bsAlert.close();
                    }, 3000);
                }
                
                this.innerHTML = '<i class="fas fa-sync-alt"></i> রিফ্রেশ';
                this.disabled = false;
            } catch (error) {
                console.error('Error refreshing stats:', error);
                this.innerHTML = '<i class="fas fa-sync-alt"></i> রিফ্রেশ';
                this.disabled = false;
            }
        });
    }
    
    // User search autocomplete
    const userSearchInput = document.getElementById('userSearch');
    if (userSearchInput) {
        userSearchInput.addEventListener('input', async function() {
            const searchTerm = this.value.trim();
            const resultsContainer = document.getElementById('searchResults');
            
            if (searchTerm.length < 3) {
                resultsContainer.innerHTML = '';
                return;
            }
            
            try {
                const response = await fetch(`ajax/admin/search_user.php?q=${encodeURIComponent(searchTerm)}`);
                const data = await response.json();
                
                if (data.success && data.users.length > 0) {
                    let html = '<div class="list-group">';
                    data.users.forEach(user => {
                        html += `
                            <a href="user_details.php?id=${user.id}" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${user.username}</strong> (#${user.account_number})
                                        <div class="text-muted">${user.mobile}</div>
                                    </div>
                                    <div>
                                        <span class="badge ${user.active_package !== 'none' ? 'bg-success' : 'bg-secondary'}">
                                            ${user.active_package !== 'none' ? user.active_package : 'বেসিক'}
                                        </span>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    html += '</div>';
                    resultsContainer.innerHTML = html;
                } else {
                    resultsContainer.innerHTML = '<div class="alert alert-info">কোন ইউজার পাওয়া যায়নি</div>';
                }
            } catch (error) {
                console.error('Error searching users:', error);
                resultsContainer.innerHTML = '<div class="alert alert-danger">সার্চ করতে সমস্যা হয়েছে</div>';
            }
        });
    }
    
    // Deposit/Withdrawal confirmation dialogs
    const confirmForms = document.querySelectorAll('.confirm-action-form');
    confirmForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            const action = this.querySelector('input[name="action"]').value;
            const type = this.getAttribute('data-type');
            
            let message = '';
            if (action === 'approve') {
                message = `আপনি কি নিশ্চিত যে আপনি এই ${type} অনুমোদন করতে চান?`;
            } else if (action === 'reject') {
                message = `আপনি কি নিশ্চিত যে আপনি এই ${type} বাতিল করতে চান?`;
            }
            
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });
    
    // File upload preview
    const fileInputs = document.querySelectorAll('.custom-file-input');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'কোন ফাইল নির্বাচন করা হয়নি';
            this.nextElementSibling.textContent = fileName;
            
            // If there's a preview container, show image preview
            const previewContainer = this.closest('.form-group').querySelector('.image-preview');
            if (previewContainer && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.innerHTML = `<img src="${e.target.result}" class="img-fluid" alt="Preview">`;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
});
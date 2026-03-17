// ==================== GLOBAL FUNCTIONS ====================

// Toggle password visibility
function togglePassword(passwordFieldId, toggleButton) {
    const passwordField = document.getElementById(passwordFieldId);
    if (!passwordField) return;
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i> Hide';
    } else {
        passwordField.type = 'password';
        toggleButton.innerHTML = '<i class="fas fa-eye"></i> Show';
    }
}

// Show loading spinner
function showLoading(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    button.disabled = true;
    return originalText;
}

// Hide loading spinner
function hideLoading(button, originalText) {
    button.innerHTML = originalText;
    button.disabled = false;
}

// Show toast notification
function showToast(message, type = 'success') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => toast.remove());
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add toast styles
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#2ecc71' : type === 'error' ? '#e74c3c' : '#3498db'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-width: 300px;
        max-width: 400px;
        z-index: 9999;
        animation: slideInRight 0.3s ease-out;
        font-weight: 500;
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }
    }, 5000);
}

// Confirm dialog
function confirmAction(message, callback) {
    const modal = document.createElement('div');
    modal.className = 'confirm-modal';
    modal.innerHTML = `
        <div class="confirm-content">
            <div class="confirm-icon">
                <i class="fas fa-question-circle"></i>
            </div>
            <h3>Confirmation Required</h3>
            <p>${message}</p>
            <div class="confirm-buttons">
                <button class="btn btn-secondary" onclick="this.parentElement.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-danger" onclick="
                    if (typeof callback === 'function') callback();
                    this.parentElement.parentElement.parentElement.remove();
                ">
                    <i class="fas fa-check"></i> Confirm
                </button>
            </div>
        </div>
    `;
    
    // Add modal styles
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    
    const content = modal.querySelector('.confirm-content');
    content.style.cssText = `
        background: white;
        padding: 30px;
        border-radius: 15px;
        max-width: 400px;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    `;
    
    document.body.appendChild(modal);
}

// ==================== FORM VALIDATION ====================
function validateLoginForm() {
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    
    if (!username.value.trim()) {
        showToast('Please enter username', 'error');
        username.focus();
        return false;
    }
    
    if (!password.value) {
        showToast('Please enter password', 'error');
        password.focus();
        return false;
    }
    
    return true;
}

function validateEvaluationForm() {
    const stars = document.querySelectorAll('.star.selected');
    const report = document.getElementById('report');
    
    if (stars.length < 10) {
        showToast('Please rate all 10 questions', 'error');
        return false;
    }
    
    if (!report.value.trim()) {
        showToast('Please provide your feedback/suggestions', 'error');
        report.focus();
        return false;
    }
    
    return true;
}

// ==================== STAR RATING SYSTEM ====================
function initializeStarRating() {
    const starContainers = document.querySelectorAll('.star-rating');
    
    starContainers.forEach(container => {
        const stars = container.querySelectorAll('.star');
        const questionIndex = container.dataset.questionIndex;
        
        stars.forEach((star, index) => {
            star.addEventListener('mouseover', () => {
                // Highlight stars on hover
                for (let i = 0; i <= index; i++) {
                    stars[i].classList.add('hover');
                }
            });
            
            star.addEventListener('mouseout', () => {
                // Remove hover class
                stars.forEach(s => s.classList.remove('hover'));
            });
            
            star.addEventListener('click', () => {
                // Set selected stars
                stars.forEach((s, i) => {
                    if (i <= index) {
                        s.classList.add('selected');
                    } else {
                        s.classList.remove('selected');
                    }
                });
                
                // Update hidden input if exists
                const hiddenInput = container.parentElement.querySelector('input[type="hidden"]');
                if (hiddenInput) {
                    hiddenInput.value = index + 1;
                }
                
                // Update rating text
                const ratingTexts = ['Very Poor', 'Poor', 'Fair', 'Good', 'Excellent'];
                const ratingText = document.getElementById(`rating-text-${questionIndex}`);
                if (ratingText) {
                    ratingText.textContent = `Selected: ${index + 1} star${index > 0 ? 's' : ''} (${ratingTexts[index]})`;
                    ratingText.style.color = '#27ae60';
                }
            });
        });
    });
}

// ==================== CHARTS AND GRAPHS ====================
function createRatingChart(canvasId, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    // Destroy existing chart if it exists
    if (window.ratingChart) {
        window.ratingChart.destroy();
    }
    
    window.ratingChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: data.label || 'Average Rating',
                data: data.values,
                backgroundColor: [
                    'rgba(221, 3, 3, 0.8)',
                    'rgba(250, 177, 47, 0.8)',
                    'rgba(250, 129, 47, 0.8)',
                    'rgba(46, 204, 113, 0.8)',
                    'rgba(52, 152, 219, 0.8)'
                ],
                borderColor: [
                    'rgba(221, 3, 3, 1)',
                    'rgba(250, 177, 47, 1)',
                    'rgba(250, 129, 47, 1)',
                    'rgba(46, 204, 113, 1)',
                    'rgba(52, 152, 219, 1)'
                ],
                borderWidth: 2,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5,
                    ticks: {
                        stepSize: 1
                    },
                    title: {
                        display: true,
                        text: 'Rating (1-5)'
                    }
                }
            }
        }
    });
}

function createPieChart(canvasId, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    if (window.pieChart) {
        window.pieChart.destroy();
    }
    
    window.pieChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: [
                    'rgba(231, 76, 60, 0.8)',    // Red
                    'rgba(230, 126, 34, 0.8)',    // Orange
                    'rgba(241, 196, 15, 0.8)',    // Yellow
                    'rgba(46, 204, 113, 0.8)',    // Green
                    'rgba(52, 152, 219, 0.8)'     // Blue
                ],
                borderColor: 'white',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// ==================== AJAX FUNCTIONS ====================
async function submitEvaluationAjax(formData) {
    try {
        const response = await fetch('submit_evaluation.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Evaluation submitted successfully!', 'success');
            // Redirect or update UI
            setTimeout(() => {
                window.location.href = 'student/dashboard.php';
            }, 2000);
        } else {
            showToast(result.error || 'Submission failed', 'error');
        }
        
        return result;
    } catch (error) {
        showToast('Network error. Please try again.', 'error');
        console.error('Error:', error);
        return { success: false, error: 'Network error' };
    }
}

async function loadOfficeStats(officeId) {
    try {
        const response = await fetch(`get_office_stats.php?id=${officeId}`);
        const stats = await response.json();
        
        // Update UI with stats
        if (stats.success) {
            updateStatsDisplay(stats.data);
        }
        
        return stats;
    } catch (error) {
        console.error('Error loading stats:', error);
        return null;
    }
}

// ==================== UI UPDATES ====================
function updateStatsDisplay(stats) {
    const elements = {
        'total-respondents': stats.total_respondents,
        'average-rating': stats.average_rating,
        'total-responses': stats.total_responses
    };
    
    for (const [id, value] of Object.entries(elements)) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }
}

function updateProgressBars(distribution) {
    const total = Object.values(distribution).reduce((a, b) => a + b, 0);
    
    for (let rating = 1; rating <= 5; rating++) {
        const count = distribution[rating] || 0;
        const percentage = total > 0 ? (count / total * 100).toFixed(1) : 0;
        
        const bar = document.getElementById(`progress-bar-${rating}`);
        const text = document.getElementById(`progress-text-${rating}`);
        
        if (bar) {
            bar.style.width = `${percentage}%`;
        }
        
        if (text) {
            text.textContent = `${count} (${percentage}%)`;
        }
    }
}

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize star ratings
    if (document.querySelector('.star-rating')) {
        initializeStarRating();
    }
    
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'custom-tooltip';
            tooltip.textContent = this.dataset.tooltip;
            tooltip.style.cssText = `
                position: absolute;
                background: rgba(0,0,0,0.8);
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 10000;
                white-space: nowrap;
                pointer-events: none;
            `;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
            tooltip.style.left = (rect.left + rect.width/2 - tooltip.offsetWidth/2) + 'px';
            
            this.tooltipElement = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this.tooltipElement && this.tooltipElement.parentNode) {
                this.tooltipElement.parentNode.removeChild(this.tooltipElement);
            }
        });
    });
    
    // Add animation to stats cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('animate__animated', 'animate__fadeInUp');
    });
});

// ==================== ANIMATION STYLES ====================
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .star.hover {
        color: #f1c40f !important;
        transform: scale(1.2);
    }
    
    .animate__animated {
        animation-duration: 0.5s;
        animation-fill-mode: both;
    }
    
    .animate__fadeInUp {
        animation-name: fadeInUp;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .confirm-icon {
        font-size: 48px;
        color: #e74c3c;
        margin-bottom: 15px;
    }
    
    .confirm-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        justify-content: center;
    }
    
    .progress-container {
        background: #ecf0f1;
        border-radius: 10px;
        overflow: hidden;
        margin: 10px 0;
    }
    
    .progress-bar {
        height: 20px;
        background: linear-gradient(90deg, #e74c3c, #e67e22, #f1c40f, #2ecc71);
        border-radius: 10px;
        transition: width 0.5s ease;
    }
`;
document.head.appendChild(style);

// ==================== EXPORT FUNCTIONS ====================
window.EVSU = {
    showToast,
    confirmAction,
    validateForm: validateLoginForm,
    submitEvaluation: submitEvaluationAjax,
    createChart: createRatingChart
};

// ==================== LOGOUT FUNCTION ====================
function logoutUser(redirect_url = 'index.php') {
    if (confirm('Are you sure you want to logout?')) {
        // Show loading
        document.body.innerHTML += `
            <div id="logoutOverlay" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 99999;
            ">
                <div style="
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    text-align: center;
                ">
                    <i class="fas fa-spinner fa-spin fa-3x" style="color: var(--evsu-red); margin-bottom: 20px;"></i>
                    <h3>Logging out...</h3>
                    <p>Please wait while we secure your session.</p>
                </div>
            </div>
        `;
        
        // Perform logout
        setTimeout(() => {
            window.location.href = redirect_url;
        }, 1000);
        
        return true;
    }
    return false;
}
<?php
$page_title = 'Contact Us';
$active_page = 'contact';
require_once 'includes/header.php';

// Handle contact form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $category = $_POST['category'];
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // In a real application, you would send an email or save to database
        // For now, we'll simulate success
        $success_message = "Thank you for contacting us, $name! We have received your message and will respond within 24-48 hours.";
        
        // Clear form fields
        $name = $email = $subject = $message = '';
        $category = 'general';
    }
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <h2 class="mb-4" style="color: #8B0000;">Send Us a Message</h2>
            <p class="mb-4">Have questions about the evaluation system or need assistance? Fill out the form below and our support team will get back to you as soon as possible.</p>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required 
                               value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                        <div class="invalid-feedback">Please enter your name.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="category" class="form-label">Inquiry Category <span class="text-danger">*</span></label>
                    <select class="form-control" id="category" name="category" required>
                        <option value="general" <?php echo (isset($category) && $category == 'general') ? 'selected' : ''; ?>>General Inquiry</option>
                        <option value="technical" <?php echo (isset($category) && $category == 'technical') ? 'selected' : ''; ?>>Technical Support</option>
                        <option value="evaluation" <?php echo (isset($category) && $category == 'evaluation') ? 'selected' : ''; ?>>Evaluation System</option>
                        <option value="account" <?php echo (isset($category) && $category == 'account') ? 'selected' : ''; ?>>Account Issues</option>
                        <option value="feedback" <?php echo (isset($category) && $category == 'feedback') ? 'selected' : ''; ?>>Feedback/Suggestions</option>
                        <option value="other" <?php echo (isset($category) && $category == 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="subject" name="subject" required 
                           value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>">
                    <div class="invalid-feedback">Please enter a subject for your message.</div>
                </div>
                
                <div class="mb-3">
                    <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="message" name="message" rows="6" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                    <div class="invalid-feedback">Please enter your message.</div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="copy" name="copy">
                    <label class="form-check-label" for="copy">Send me a copy of this message</label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg" style="background-color: #8B0000; border-color: #8B0000;">
                    <i class="fas fa-paper-plane mr-2"></i> Send Message
                </button>
            </form>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header" style="background-color: #8B0000; color: white;">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt mr-2"></i> Campus Location</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        <strong>EVSU - Ormoc City Campus</strong><br>
                        Ormoc City, Leyte 6541<br>
                        Philippines
                    </p>
                    
                    <div class="mt-3">
                        <h6 class="mb-2">Directions:</h6>
                        <p class="small">The campus is located in Ormoc City proper, accessible via public transportation from the city center.</p>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header" style="background-color: #8B0000; color: white;">
                    <h5 class="mb-0"><i class="fas fa-phone-alt mr-2"></i> Contact Details</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <div class="mr-3" style="color: #8B0000;">
                            <i class="fas fa-phone fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Telephone</h6>
                            <p class="mb-0">(053) 555-1234</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start mb-3">
                        <div class="mr-3" style="color: #8B0000;">
                            <i class="fas fa-fax fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Fax</h6>
                            <p class="mb-0">(053) 555-5678</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start mb-3">
                        <div class="mr-3" style="color: #8B0000;">
                            <i class="fas fa-envelope fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Email</h6>
                            <p class="mb-0">occ@evsu.edu.ph</p>
                            <p class="small text-muted mb-0">For general inquiries</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start">
                        <div class="mr-3" style="color: #8B0000;">
                            <i class="fas fa-headset fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Technical Support</h6>
                            <p class="mb-0">support.evalsys@evsu.edu.ph</p>
                            <p class="small text-muted mb-0">For evaluation system issues</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: #8B0000; color: white;">
                    <h5 class="mb-0"><i class="fas fa-clock mr-2"></i> Office Hours</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <td><strong>Monday - Friday</strong></td>
                                <td>8:00 AM - 5:00 PM</td>
                            </tr>
                            <tr>
                                <td><strong>Saturday</strong></td>
                                <td>9:00 AM - 12:00 PM</td>
                            </tr>
                            <tr>
                                <td><strong>Sunday</strong></td>
                                <td>Closed</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="small text-muted mb-0">* Holidays may affect office hours. Please check announcements for holiday schedules.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="background-color: #f8f9fa; padding: 60px 0;">
    <div class="container">
        <h2 class="text-center mb-5" style="color: #8B0000;">Frequently Asked Questions</h2>
        <div class="accordion" id="faqAccordion">
            <div class="card mb-3">
                <div class="card-header" id="faqOne" style="background-color: rgba(139, 0, 0, 0.05);">
                    <h5 class="mb-0">
                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="color: #8B0000; text-decoration: none;">
                            <i class="fas fa-question-circle mr-2"></i> How do I reset my evaluation system password?
                        </button>
                    </h5>
                </div>
                <div id="collapseOne" class="collapse show" aria-labelledby="faqOne" data-parent="#faqAccordion">
                    <div class="card-body">
                        You can reset your password by clicking the "Forgot Password?" link on the login page. You will receive an email with instructions to reset your password. If you don't receive the email, please contact technical support.
                    </div>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-header" id="faqTwo" style="background-color: rgba(139, 0, 0, 0.05);">
                    <h5 class="mb-0">
                        <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" style="color: #8B0000; text-decoration: none;">
                            <i class="fas fa-question-circle mr-2"></i> When is the evaluation period?
                        </button>
                    </h5>
                </div>
                <div id="collapseTwo" class="collapse" aria-labelledby="faqTwo" data-parent="#faqAccordion">
                    <div class="card-body">
                        The evaluation period is typically during the last two weeks of each semester. Notifications will be sent to your registered email address when evaluations are open. The exact dates are also posted on the system dashboard and campus bulletin.
                    </div>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-header" id="faqThree" style="background-color: rgba(139, 0, 0, 0.05);">
                    <h5 class="mb-0">
                        <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" style="color: #8B0000; text-decoration: none;">
                            <i class="fas fa-question-circle mr-2"></i> Are evaluations anonymous?
                        </button>
                    </h5>
                </div>
                <div id="collapseThree" class="collapse" aria-labelledby="faqThree" data-parent="#faqAccordion">
                    <div class="card-body">
                        Yes, all evaluations are completely anonymous. The system is designed to protect your identity while allowing you to provide honest feedback. Only aggregate results are shared with faculty and administrators.
                    </div>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-header" id="faqFour" style="background-color: rgba(139, 0, 0, 0.05);">
                    <h5 class="mb-0">
                        <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour" style="color: #8B0000; text-decoration: none;">
                            <i class="fas fa-question-circle mr-2"></i> Who can I contact for technical issues?
                        </button>
                    </h5>
                </div>
                <div id="collapseFour" class="collapse" aria-labelledby="faqFour" data-parent="#faqAccordion">
                    <div class="card-body">
                        For technical issues with the evaluation system, please email <strong>support.evalsys@evsu.edu.ph</strong> or call the IT Support Office at (053) 555-4321 during office hours. Please provide detailed information about the issue you're experiencing.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation script
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php require_once 'includes/footer.php'; ?>
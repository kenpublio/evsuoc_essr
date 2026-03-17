<?php
$page_title = 'Terms of Use';
$active_page = 'terms';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3">
            <div class="sticky-top" style="top: 20px;">
                <div class="list-group mb-4">
                    <a href="#acceptance" class="list-group-item list-group-item-action">
                        <i class="fas fa-check-circle mr-2"></i> Acceptance of Terms
                    </a>
                    <a href="#description" class="list-group-item list-group-item-action">
                        <i class="fas fa-info-circle mr-2"></i> Service Description
                    </a>
                    <a href="#eligibility" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-check mr-2"></i> User Eligibility
                    </a>
                    <a href="#accounts" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-shield mr-2"></i> User Accounts
                    </a>
                    <a href="#conduct" class="list-group-item list-group-item-action">
                        <i class="fas fa-gavel mr-2"></i> User Conduct
                    </a>
                    <a href="#privacy" class="list-group-item list-group-item-action">
                        <i class="fas fa-shield-alt mr-2"></i> Privacy Policy
                    </a>
                    <a href="#intellectual" class="list-group-item list-group-item-action">
                        <i class="fas fa-copyright mr-2"></i> Intellectual Property
                    </a>
                    <a href="#disclaimer" class="list-group-item list-group-item-action">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Disclaimer
                    </a>
                    <a href="#liability" class="list-group-item list-group-item-action">
                        <i class="fas fa-balance-scale mr-2"></i> Limitation of Liability
                    </a>
                    <a href="#modifications" class="list-group-item list-group-item-action">
                        <i class="fas fa-edit mr-2"></i> Modifications
                    </a>
                    <a href="#termination" class="list-group-item list-group-item-action">
                        <i class="fas fa-ban mr-2"></i> Termination
                    </a>
                    <a href="#contact" class="list-group-item list-group-item-action">
                        <i class="fas fa-headset mr-2"></i> Contact Information
                    </a>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-header" style="background-color: #8B0000; color: white;">
                        <h6 class="mb-0"><i class="fas fa-file-contract mr-2"></i> Document Info</h6>
                    </div>
                    <div class="card-body">
                        <p class="small mb-1"><strong>Version:</strong> 2.0</p>
                        <p class="small mb-1"><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>
                        <p class="small mb-1"><strong>Effective Date:</strong> September 1, 2023</p>
                        <p class="small mb-0"><strong>Applies To:</strong> All EVSU-OCC Evaluation System Users</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="alert alert-warning mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Important Notice:</strong> Please read these Terms of Use carefully before using the EVSU-OCC Evaluation System. By accessing or using the system, you agree to be bound by these terms and conditions.
            </div>
            
            <section id="acceptance" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">
                    <i class="fas fa-check-circle mr-2"></i> 1. Acceptance of Terms
                </h2>
                <p>By accessing and using the Eastern Visayas State University - Ormoc City Campus (EVSU-OCC) Evaluation System, you acknowledge that you have read, understood, and agree to be bound by these Terms of Use and our Privacy Policy.</p>
                
                <p>If you do not agree to these terms, please do not use this system. These terms constitute a legally binding agreement between you and EVSU-OCC regarding your use of the evaluation system.</p>
                
                <div class="card bg-light border-left-4" style="border-left-color: #8B0000;">
                    <div class="card-body">
                        <h6><i class="fas fa-info-circle mr-2" style="color: #8B0000;"></i> Note</h6>
                        <p class="mb-0">These terms may be updated periodically. Continued use of the system after changes constitutes acceptance of the modified terms.</p>
                    </div>
                </div>
            </section>
            
            <section id="description" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">
                    <i class="fas fa-info-circle mr-2"></i> 2. Service Description
                </h2>
                
                <p>The EVSU-OCC Evaluation System is an online platform designed to facilitate:</p>
                
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-top-4" style="border-top-color: #8B0000;">
                            <div class="card-body">
                                <h5 class="card-title" style="color: #8B0000;"><i class="fas fa-clipboard-check mr-2"></i> Course Evaluations</h5>
                                <p class="card-text">Collection of student feedback on courses and instructors to enhance teaching quality and academic programs.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-top-4" style="border-top-color: #A52A2A;">
                            <div class="card-body">
                                <h5 class="card-title" style="color: #A52A2A;"><i class="fas fa-chart-bar mr-2"></i> Data Analysis</h5>
                                <p class="card-text">Compilation and analysis of evaluation data for administrative decision-making and quality improvement.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h5 class="mt-4 mb-3">System Features Include:</h5>
                <ul>
                    <li>Secure user authentication and authorization</li>
                    <li>Anonymous evaluation submission for students</li>
                    <li>Real-time evaluation tracking and monitoring</li>
                    <li>Comprehensive reporting tools for administrators</li>
                    <li>Data export capabilities for analysis</li>
                    <li>Mobile-responsive interface</li>
                </ul>
                
                <p class="mt-3">EVSU-OCC reserves the right to modify, suspend, or discontinue any aspect of the evaluation system at any time without prior notice.</p>
            </section>
            
            <section id="eligibility" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">
                    <i class="fas fa-user-check mr-2"></i> 3. User Eligibility
                </h2>
                
                <p>Access to the EVSU-OCC Evaluation System is restricted to authorized users only:</p>
                
                <div class="row mt-4">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header" style="background-color: #8B0000; color: white;">
                                <h6 class="mb-0"><i class="fas fa-user-graduate mr-2"></i> Students</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Currently enrolled EVSU-OCC students</li>
                                    <li>Registered in the current academic term</li>
                                    <li>Have valid student credentials</li>
                                    <li>Courses enrolled in the current semester</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header" style="background-color: #A52A2A; color: white;">
                                <h6 class="mb-0"><i class="fas fa-chalkboard-teacher mr-2"></i> Faculty/Staff</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Current EVSU-OCC faculty members</li>
                                    <li>Administrative staff with evaluation privileges</li>
                                    <li>Authorized by campus administration</li>
                                    <li>Valid employee credentials</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-light border-left-4" style="border-left-color: #8B0000;">
                    <div class="card-body">
                        <h6><i class="fas fa-exclamation-circle mr-2" style="color: #8B0000;"></i> Important</h6>
                        <p class="mb-0">By using this system, you represent and warrant that you meet all eligibility requirements. Providing false information to gain access may result in disciplinary action and legal consequences.</p>
                    </div>
                </div>
            </section>
            
            <section id="accounts" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">
                    <i class="fas fa-user-shield mr-2"></i> 4. User Accounts and Security
                </h2>
                
                <h5 class="mt-4 mb-3">Account Creation</h5>
                <p>To use the evaluation system, you must create an account with accurate and complete information. You are responsible for:</p>
                <ul>
                    <li>Providing accurate registration information</li>
                    <li>Maintaining the confidentiality of your account credentials</li>
                    <li>All activities that occur under your account</li>
                    <li>Promptly notifying EVSU-OCC of any unauthorized access</li>
                </ul>
                
                <h5 class="mt-4 mb-3">Password Security</h5>
                <p>Users must adhere to the following password requirements:</p>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Requirement</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Minimum Length</strong></td>
                                <td>At least 8 characters</td>
                            </tr>
                            <tr>
                                <td><strong>Complexity</strong></td>
                                <td>Combination of letters and numbers</td>
                            </tr>
                            <tr>
                                <td><strong>Confidentiality</strong></td>
                                <td>Not to be shared with anyone</td>
                            </tr>
                            <tr>
                                <td><strong>Regular Updates</strong></td>
                                <td>Change every 6 months recommended</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <h5 class="mt-4 mb-3">Prohibited Activities</h5>
                <p>Users are strictly prohibited from:</p>
                <ul>
                    <li>Creating multiple accounts</li>
                    <li>Sharing account credentials with others</li>
                    <li>Using another user's account</li>
                    <li>Attempting to gain unauthorized access to the system</li>
                    <li>Bypassing security measures</li>
                </ul>
                
                <div class="alert alert-danger">
                    <i class="fas fa-ban mr-2"></i>
                    <strong>Violation Consequences:</strong> Violation of account security rules may result in account suspension, disciplinary action, and/or legal prosecution.
                </div>
            </section>
            
            <section id="conduct" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">
                    <i class="fas fa-gavel mr-2"></i> 5. User Conduct and Responsibilities
                </h2>
                
                <p>When using the EVSU-OCC Evaluation System, you agree to:</p>
                
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-left-4" style="border-left-color: #28a745;">
                            <div class="card-body">
                                <h6 class="card-title" style="color: #28a745;"><i class="fas fa-thumbs-up mr-2"></i> Required Conduct</h6>
                                <ul class="mb-0 pl-3">
                                    <li>Provide honest and constructive feedback</li>
                                    <li>Respect the anonymity of the evaluation process</li>
                                    <li>Complete evaluations within specified timeframes</li>
                                    <li>Use the system for its intended educational purposes</li>
                                    <li>Report technical issues promptly</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-left-4" style="border-left-color: #dc3545;">
                            <div class="card-body">
                                <h6 class="card-title" style="color: #dc3545;"><i class="fas fa-thumbs-down mr-2"></i> Prohibited Conduct</h6>
                                <ul class="mb-0 pl-3">
                                    <li>Submitting false or misleading evaluations</li>
                                    <li>Harassing or defaming instructors/students</li>
                                    <li>Using inappropriate or offensive language</li>
                                    <li>Attempting to manipulate evaluation results</li>
                                    <li>Violating others' privacy rights</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h5 class="mt-4 mb-3">Evaluation Integrity</h5>
                <p>All evaluations must be completed personally by the registered student. The following are strictly prohibited:</p>
                <ul>
                    <li>Completing evaluations on behalf of others</li>
                    <li>Coordinating evaluation responses with other students</li>
                    <li>Submitting identical or substantially similar evaluations</li>
                    <li>Using automated systems or bots to submit evaluations</li>
                </ul>
                
                <div class="card bg-light border-left-4" style="border-left-color: #8B0000;">
                    <div class="card-body">
                        <h6><i class="fas fa-balance-scale mr-2" style="color: #8B0000;"></i> Academic Integrity</h6>
                        <p class="mb-0">Violation of evaluation integrity may be considered academic dishonesty and may result in consequences under the EVSU Student Code of Conduct.</p>
                    </div>
                </div>
            </section>
            
            <section id="privacy" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">
                    <i class="fas fa-shield-alt mr-2"></i> 6. Privacy Policy
                </h2>
                
                <p>Your use of the EVSU-OCC Evaluation System is also governed by our Privacy Policy. By using the system, you consent to the collection, use, and disclosure of your information as described in the Privacy Policy.</p>
                
                <h5 class="mt-4 mb-3">Key Privacy Principles:</h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <div class="mr-3" style="color: #8B0000;">
                                <i class="fas fa-user-secret fa-2x"></i>
                            </div>
                            <div>
                                <h6>Anonymity</h6>
                                <p class="small mb-0">Individual evaluation responses are anonymous. Faculty and administrators see only aggregated data.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <div class="mr-3" style="color: #8B0000;">
                                <i class="fas fa-lock fa-2x"></i>
                            </div>
                            <div>
                                <h6>Data Security</h6>
                                <p class="small mb-0">Personal information is protected using industry-standard security measures.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <div class="mr-3" style="color: #8B0000;">
                                <i class="fas fa-eye-slash fa-2x"></i>
                            </div>
                            <div>
                                <h6>Limited Access</h6>
                                <p class="small mb-0">Access to personal data is restricted to authorized personnel only.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <div class="mr-3" style="color: #8B0000;">
                                <i class="fas fa-database fa-2x"></i>
                            </div>
                            <div>
                                <h6>Data Retention</h6>
                                <p class="small mb-0">Evaluation data is retained according to EVSU's data retention policies.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="mt-3">For complete details, please review our <a href="privacy.php" class="form-link">Privacy Policy</a>.</p>
            </section>
            
            <section id="intellectual" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">
                    <i class="fas fa-copyright mr-2"></i> 7. Intellectual Property Rights
                </h2>
                
                <h5 class="mt-4 mb-3">EVSU-OCC Ownership</h5>
                <p>The EVSU-OCC Evaluation System and all associated content, features, and functionality are owned by Eastern Visayas State University and are protected by copyright, trademark, and other intellectual property laws.</p>
                
                <p>This includes, but is not limited to:</p>
                <ul>
                    <li>Software code and algorithms</li>
                    <li>System design and user interface</li>
                    <li>Documentation and manuals</li>
                    <li>Logos, trademarks, and branding elements</li>
                    <li>Evaluation forms and survey instruments</li>
                </ul>
                
                <h5 class="mt-4 mb-3">User Content</h5>
                <p>By submitting evaluations through the system, you grant EVSU-OCC a non-exclusive, royalty-free, perpetual license to:</p>
                <ul>
                    <li>Use, reproduce, and analyze evaluation responses</li>
                    <li>Create aggregated reports from evaluation data</li>
                    <li>Use anonymized data for research and improvement purposes</li>
                    <li>Archive evaluation data according to retention policies</li>
                </ul>
                
                <div class="card bg-light border-left-4" style="border-left-color: #8B0000;">
                    <div class="card-body">
                        <h6><i class="fas fa-exclamation-triangle mr-2" style="color: #8B0000;"></i> Restrictions</h6>
                        <p class="mb-0">Users may not copy, modify, distribute, sell, or lease any part of the evaluation system without express written permission from EVSU-OCC.</p>
                    </div>
                </div>
            </section>
            
            <section id="disclaimer" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">
                    <i class="fas fa-exclamation-triangle mr-2"></i> 8. Disclaimer of Warranties
                </h2>
                
                <p>The EVSU-OCC Evaluation System is provided "as is" and "as available" without warranties of any kind, either express or implied.</p>
                
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title" style="color: #8B0000;"><i class="fas fa-times-circle mr-2"></i> No Guarantees</h6>
                                <p class="card-text small">EVSU-OCC does not guarantee that the system will be uninterrupted, timely, secure, or error-free.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title" style="color: #8B0000;"><i class="fas fa-exclamation-circle mr-2"></i> Technical Issues</h6>
                                <p class="card-text small">EVSU-OCC is not responsible for technical issues beyond its control that may affect system access.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title" style="color: #8B0000;"><i class="fas fa-database mr-2"></i> Data Accuracy</h6>
                                <p class="card-text small">While we strive for accuracy, EVSU-OCC does not warrant the completeness or accuracy of evaluation data.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title" style="color: #8B0000;"><i class="fas fa-shield-alt mr-2"></i> Security</h6>
                                <p class="card-text small">EVSU-OCC does not guarantee that the system will be free from viruses or other harmful components.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="mt-3">Users acknowledge that they use the evaluation system at their own risk and are responsible for implementing sufficient security measures.</p>
            </section>
            
            <section id="liability" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">
                    <i class="fas fa-balance-scale mr-2"></i> 9. Limitation of Liability
                </h2>
                
                <p>To the fullest extent permitted by applicable law, EVSU-OCC shall not be liable for:</p>
                
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Type of Liability</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Direct Damages</strong></td>
                                <td>Any direct, indirect, incidental, special, or consequential damages</td>
                            </tr>
                            <tr>
                                <td><strong>Data Loss</strong></td>
                                <td>Loss of data, profits, or business opportunities</td>
                            </tr>
                            <tr>
                                <td><strong>Service Interruption</strong></td>
                                <td>Costs related to system unavailability or downtime</td>
                            </tr>
                            <tr>
                                <td><strong>Third-Party Actions</strong></td>
                                <td>Actions or omissions of third parties</td>
                            </tr>
                            <tr>
                                <td><strong>User Errors</strong></td>
                                <td>Errors or omissions by users of the system</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Legal Notice:</strong> This limitation of liability applies regardless of the form of action, whether in contract, tort, negligence, strict liability, or otherwise, even if EVSU-OCC has been advised of the possibility of such damages.
                </div>
            </section>
            
            <section id="modifications" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">
                    <i class="fas fa-edit mr-2"></i> 10. Modifications to Terms
                </h2>
                
                <p>EVSU-OCC reserves the right to modify these Terms of Use at any time. When we make changes, we will:</p>
                
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <div class="mb-3">
                                    <i class="fas fa-bell fa-3x" style="color: #8B0000;"></i>
                                </div>
                                <h5 class="card-title">Notification</h5>
                                <p class="card-text">Post the updated terms on this page with a new "Last Updated" date</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <div class="mb-3">
                                    <i class="fas fa-envelope-open-text fa-3x" style="color: #A52A2A;"></i>
                                </div>
                                <h5 class="card-title">Communication</h5>
                                <p class="card-text">Notify registered users of significant changes via email</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="mt-4">Your continued use of the evaluation system after the effective date of the revised Terms of Use constitutes your acceptance of the changes.</p>
                
                <div class="card bg-light border-left-4" style="border-left-color: #8B0000;">
                    <div class="card-body">
                        <h6><i class="fas fa-history mr-2" style="color: #8B0000;"></i> Review Responsibility</h6>
                        <p class="mb-0">It is your responsibility to periodically review these Terms of Use to stay informed of updates. The date at the top of this page indicates when these terms were last revised.</p>
                    </div>
                </div>
            </section>
            
            <section id="termination" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">
                    <i class="fas fa-ban mr-2"></i> 11. Termination
                </h2>
                
                <p>EVSU-OCC reserves the right to terminate or suspend your access to the evaluation system immediately, without prior notice or liability, for any reason, including but not limited to:</p>
                
                <div class="row mt-4">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header" style="background-color: #dc3545; color: white;">
                                <h6 class="mb-0">Violation of Terms</h6>
                            </div>
                            <div class="card-body">
                                <p class="small mb-0">Breach of any provision of these Terms of Use</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header" style="background-color: #ffc107; color: #212529;">
                                <h6 class="mb-0">Academic Status</h6>
                            </div>
                            <div class="card-body">
                                <p class="small mb-0">Graduation, withdrawal, or expulsion from EVSU-OCC</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header" style="background-color: #6c757d; color: white;">
                                <h6 class="mb-0">Inactivity</h6>
                            </div>
                            <div class="card-body">
                                <p class="small mb-0">Prolonged account inactivity (typically 1 year)</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h5 class="mt-4 mb-3">Upon Termination:</h5>
                <ul>
                    <li>Your right to use the evaluation system will immediately cease</li>
                    <li>You must cease all use of the system</li>
                    <li>EVSU-OCC may delete or deactivate your account</li>
                    <li>Historical evaluation data may be retained according to data retention policies</li>
                </ul>
                
                <p class="mt-3">All provisions of these Terms of Use which by their nature should survive termination shall survive termination, including ownership provisions, warranty disclaimers, indemnity, and limitations of liability.</p>
            </section>
            
            <section id="contact" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">
                    <i class="fas fa-headset mr-2"></i> 12. Contact Information
                </h2>
                
                <p>If you have any questions about these Terms of Use, please contact us:</p>
                
                <div class="row mt-4">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header" style="background-color: #8B0000; color: white;">
                                <h6 class="mb-0"><i class="fas fa-user-tie mr-2"></i> System Administrator</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>EVSU-OCC Evaluation System Administrator</strong></p>
                                <p class="mb-2"><i class="fas fa-envelope mr-2" style="color: #8B0000;"></i> eval.admin@evsu.edu.ph</p>
                                <p class="mb-2"><i class="fas fa-phone-alt mr-2" style="color: #8B0000;"></i> (053) 555-4321</p>
                                <p class="mb-0"><i class="fas fa-map-marker-alt mr-2" style="color: #8B0000;"></i> Registrar's Office, EVSU-OCC, Ormoc City</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header" style="background-color: #A52A2A; color: white;">
                                <h6 class="mb-0"><i class="fas fa-user-shield mr-2"></i> Data Protection Officer</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>EVSU-OCC Data Protection Office</strong></p>
                                <p class="mb-2"><i class="fas fa-envelope mr-2" style="color: #A52A2A;"></i> dpo.occ@evsu.edu.ph</p>
                                <p class="mb-2"><i class="fas fa-phone-alt mr-2" style="color: #A52A2A;"></i> (053) 555-7890</p>
                                <p class="mb-0"><i class="fas fa-map-marker-alt mr-2" style="color: #A52A2A;"></i> Administration Building, EVSU-OCC, Ormoc City</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-success mt-4">
                    <i class="fas fa-file-signature mr-2"></i>
                    <strong>Governing Law:</strong> These Terms of Use shall be governed by and construed in accordance with the laws of the Republic of the Philippines. Any disputes arising from these terms shall be subject to the exclusive jurisdiction of the courts of Ormoc City, Leyte.
                </div>
            </section>
            
            <div class="card text-center mt-5">
                <div class="card-body">
                    <h5 class="card-title" style="color: #8B0000;">Acceptance Confirmation</h5>
                    <p class="card-text">By using the EVSU-OCC Evaluation System, you acknowledge that you have read, understood, and agree to be bound by these Terms of Use.</p>
                    <div class="mt-4">
                        <a href="login.php" class="btn btn-primary mr-3" style="background-color: #8B0000; border-color: #8B0000;">
                            <i class="fas fa-sign-in-alt mr-2"></i> Proceed to Login
                        </a>
                        <a href="privacy.php" class="btn btn-outline-primary" style="color: #8B0000; border-color: #8B0000;">
                            <i class="fas fa-shield-alt mr-2"></i> View Privacy Policy
                        </a>
                    </div>
                    <p class="card-text mt-3"><small class="text-muted">Last updated: <?php echo date('F d, Y'); ?></small></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
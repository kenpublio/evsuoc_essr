<?php
$page_title = 'Privacy Statement';
$active_page = 'privacy';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3">
            <div class="sticky-top" style="top: 20px;">
                <div class="list-group mb-4">
                    <a href="#overview" class="list-group-item list-group-item-action">
                        <i class="fas fa-eye mr-2"></i> Overview
                    </a>
                    <a href="#collection" class="list-group-item list-group-item-action">
                        <i class="fas fa-database mr-2"></i> Data Collection
                    </a>
                    <a href="#usage" class="list-group-item list-group-item-action">
                        <i class="fas fa-cogs mr-2"></i> Data Usage
                    </a>
                    <a href="#protection" class="list-group-item list-group-item-action">
                        <i class="fas fa-shield-alt mr-2"></i> Data Protection
                    </a>
                    <a href="#rights" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-check mr-2"></i> Your Rights
                    </a>
                    <a href="#cookies" class="list-group-item list-group-item-action">
                        <i class="fas fa-cookie-bite mr-2"></i> Cookies
                    </a>
                    <a href="#contact" class="list-group-item list-group-item-action">
                        <i class="fas fa-headset mr-2"></i> Contact Us
                    </a>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-header" style="background-color: #8B0000; color: white;">
                        <h6 class="mb-0"><i class="fas fa-file-alt mr-2"></i> Document Info</h6>
                    </div>
                    <div class="card-body">
                        <p class="small mb-1"><strong>Version:</strong> 2.1</p>
                        <p class="small mb-1"><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>
                        <p class="small mb-1"><strong>Effective Date:</strong> September 1, 2023</p>
                        <p class="small mb-0"><strong>Applies To:</strong> All EVSU-OCC Evaluation System Users</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Notice:</strong> This privacy statement explains how EVSU-OCC collects, uses, and protects your personal information when you use our Evaluation Survey System.
            </div>
            
            <section id="overview" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">Overview</h2>
                <p>The Eastern Visayas State University - Ormoc City Campus (EVSU-OCC) is committed to protecting the privacy and security of your personal information. This Privacy Statement applies to all users of the EVSU-OCC Evaluation Survey System.</p>
                
                <p>We comply with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong> and other applicable data protection laws in the Philippines. This statement outlines our practices regarding the collection, use, storage, and disclosure of personal data.</p>
                
                <p>By using the EVSU-OCC Evaluation System, you consent to the data practices described in this statement. If you do not agree with any part of this statement, please do not use our system.</p>
            </section>
            
            <section id="collection" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">Data We Collect</h2>
                
                <h5 class="mt-4 mb-3">Personal Identification Information</h5>
                <p>When you register for and use the evaluation system, we may collect:</p>
                <ul>
                    <li>Full name</li>
                    <li>Student ID number</li>
                    <li>Email address</li>
                    <li>Username and password</li>
                    <li>Academic program and year level</li>
                    <li>Course enrollment information</li>
                </ul>
                
                <h5 class="mt-4 mb-3">Evaluation Data</h5>
                <p>When you participate in evaluations, we collect:</p>
                <ul>
                    <li>Course and instructor evaluations</li>
                    <li>Responses to survey questions</li>
                    <li>Timestamps of evaluation submissions</li>
                    <li>Completion status of evaluations</li>
                </ul>
                
                <h5 class="mt-4 mb-3">Automatically Collected Information</h5>
                <p>Our system automatically collects certain information when you visit:</p>
                <ul>
                    <li>IP address and device information</li>
                    <li>Browser type and version</li>
                    <li>Pages visited and time spent on pages</li>
                    <li>System usage patterns</li>
                </ul>
                
                <div class="card bg-light border-left-4" style="border-left-color: #8B0000;">
                    <div class="card-body">
                        <h6><i class="fas fa-exclamation-triangle mr-2" style="color: #8B0000;"></i> Important Note</h6>
                        <p class="mb-0">Evaluation responses are <strong>anonymous and confidential</strong>. While we track completion status, individual responses cannot be linked to specific users in the reports provided to faculty and administrators.</p>
                    </div>
                </div>
            </section>
            
            <section id="usage" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">How We Use Your Data</h2>
                
                <p>EVSU-OCC uses the collected data for the following purposes:</p>
                
                <div class="row mt-4">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-top-4" style="border-top-color: #8B0000;">
                            <div class="card-body">
                                <h5 class="card-title" style="color: #8B0000;"><i class="fas fa-user-check mr-2"></i> System Access</h5>
                                <p class="card-text">To authenticate users and provide secure access to the evaluation system.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-top-4" style="border-top-color: #A52A2A;">
                            <div class="card-body">
                                <h5 class="card-title" style="color: #A52A2A;"><i class="fas fa-clipboard-check mr-2"></i> Evaluation Management</h5>
                                <p class="card-text">To manage evaluation assignments, track completion, and compile results.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-top-4" style="border-top-color: #D11111;">
                            <div class="card-body">
                                <h5 class="card-title" style="color: #D11111;"><i class="fas fa-chart-line mr-2"></i> Quality Improvement</h5>
                                <p class="card-text">To analyze trends and improve teaching quality and academic programs.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-top-4" style="border-top-color: #8B0000;">
                            <div class="card-body">
                                <h5 class="card-title" style="color: #8B0000;"><i class="fas fa-comments mr-2"></i> Communication</h5>
                                <p class="card-text">To send important notifications about evaluation periods and system updates.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h5 class="mt-4 mb-3">Data Sharing and Disclosure</h5>
                <p>EVSU-OCC does not sell, trade, or rent your personal identification information to others. We may share aggregated demographic information (not linked to any personal identification) with our academic departments for quality improvement purposes.</p>
                
                <p>We may disclose your personal information if required to do so by law or in the good faith belief that such action is necessary to:</p>
                <ul>
                    <li>Comply with legal obligations</li>
                    <li>Protect and defend the rights or property of EVSU-OCC</li>
                    <li>Prevent or investigate possible wrongdoing in connection with the service</li>
                    <li>Protect the personal safety of users or the public</li>
                </ul>
            </section>
            
            <section id="protection" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">Data Protection & Security</h2>
                
                <p>EVSU-OCC implements appropriate security measures to protect against unauthorized access, alteration, disclosure, or destruction of your personal information.</p>
                
                <h5 class="mt-4 mb-3">Security Measures</h5>
                <ul>
                    <li><strong>Encryption:</strong> All data transmissions are encrypted using SSL/TLS technology</li>
                    <li><strong>Access Controls:</strong> Strict access controls and authentication mechanisms</li>
                    <li><strong>Regular Audits:</strong> Security audits and vulnerability assessments</li>
                    <li><strong>Data Minimization:</strong> Collection of only necessary data</li>
                    <li><strong>Secure Storage:</strong> Data stored on secure servers with firewall protection</li>
                    <li><strong>Employee Training:</strong> Regular privacy and security training for staff</li>
                </ul>
                
                <div class="card bg-light border-left-4" style="border-left-color: #8B0000;">
                    <div class="card-body">
                        <h6><i class="fas fa-lock mr-2" style="color: #8B0000;"></i> Security Commitment</h6>
                        <p class="mb-0">While we strive to use commercially acceptable means to protect your personal information, no method of transmission over the Internet or electronic storage is 100% secure. We continuously update our security practices to address emerging threats.</p>
                    </div>
                </div>
            </section>
            
            <section id="rights" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">Your Data Privacy Rights</h2>
                
                <p>Under the Data Privacy Act of 2012, you have the following rights regarding your personal data:</p>
                
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <div class="mr-3" style="color: #8B0000;">
                                <i class="fas fa-eye fa-2x"></i>
                            </div>
                            <div>
                                <h5>Right to Access</h5>
                                <p class="mb-0">You have the right to request access to the personal data we hold about you.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <div class="mr-3" style="color: #8B0000;">
                                <i class="fas fa-edit fa-2x"></i>
                            </div>
                            <div>
                                <h5>Right to Correction</h5>
                                <p class="mb-0">You have the right to request correction of inaccurate or incomplete data.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <div class="mr-3" style="color: #8B0000;">
                                <i class="fas fa-ban fa-2x"></i>
                            </div>
                            <div>
                                <h5>Right to Object</h5>
                                <p class="mb-0">You have the right to object to the processing of your personal data.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <div class="mr-3" style="color: #8B0000;">
                                <i class="fas fa-trash-alt fa-2x"></i>
                            </div>
                            <div>
                                <h5>Right to Erasure</h5>
                                <p class="mb-0">You have the right to request deletion of your personal data under certain conditions.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p>To exercise any of these rights, please contact our Data Protection Officer using the contact information provided at the end of this statement.</p>
            </section>
            
            <section id="cookies" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">Cookies Policy</h2>
                
                <p>Our evaluation system uses cookies to enhance user experience. Cookies are small files placed on your device that help the system recognize you and remember your preferences.</p>
                
                <h5 class="mt-4 mb-3">Types of Cookies We Use</h5>
                
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Cookie Type</th>
                                <th>Purpose</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Essential Cookies</strong></td>
                                <td>Required for system functionality, user authentication, and security</td>
                                <td>Session or persistent</td>
                            </tr>
                            <tr>
                                <td><strong>Preference Cookies</strong></td>
                                <td>Remember your settings and preferences</td>
                                <td>Persistent (varies)</td>
                            </tr>
                            <tr>
                                <td><strong>Analytics Cookies</strong></td>
                                <td>Help us understand how users interact with our system</td>
                                <td>Persistent (up to 2 years)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <p>You can control cookie settings through your browser. However, disabling essential cookies may affect the functionality of the evaluation system.</p>
            </section>
            
            <section id="contact" class="mb-5">
                <h2 class="mb-3" style="color: #8B0000; border-bottom: 2px solid #8B0000; padding-bottom: 10px;">Contact Information</h2>
                
                <p>If you have any questions about this Privacy Statement, our data practices, or wish to exercise your data privacy rights, please contact:</p>
                
                <div class="card mt-4">
                    <div class="card-header" style="background-color: #8B0000; color: white;">
                        <h5 class="mb-0"><i class="fas fa-user-shield mr-2"></i> Data Protection Officer</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>EVSU-OCC Data Protection Office</strong></p>
                        <p class="mb-2"><i class="fas fa-envelope mr-2" style="color: #8B0000;"></i> dpo.occ@evsu.edu.ph</p>
                        <p class="mb-2"><i class="fas fa-phone-alt mr-2" style="color: #8B0000;"></i> (053) 555-7890</p>
                        <p class="mb-0"><i class="fas fa-map-marker-alt mr-2" style="color: #8B0000;"></i> Administration Building, EVSU-OCC, Ormoc City, Leyte</p>
                    </div>
                </div>
                
                <div class="alert alert-warning mt-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Changes to This Statement:</strong> EVSU-OCC may update this Privacy Statement periodically. We will notify you of any significant changes by posting the new statement on this page and updating the "Last Updated" date. We encourage you to review this statement periodically.
                </div>
            </section>
            
            <div class="card text-center mt-5">
                <div class="card-body">
                    <h5 class="card-title" style="color: #8B0000;">EVSU-OCC Commitment to Privacy</h5>
                    <p class="card-text">We are committed to protecting your privacy and ensuring the security of your personal information. Thank you for trusting EVSU-OCC with your data as we work together to improve the quality of education through our evaluation system.</p>
                    <p class="card-text"><small class="text-muted">Last updated: <?php echo date('F d, Y'); ?></small></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
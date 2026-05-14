<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Require admin access
requireRole('admin');

// Get parameters
$report_type = isset($_GET['type']) ? $_GET['type'] : 'monthly';
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

// Get database connection
$conn = getDB();

// Get service name
$service_name = 'All Services';
if ($service_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM service_types WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $service_name = $row['name'];
    }
}

// Build query
$where_conditions = [];

if ($report_type == 'monthly') {
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($month));
    $where_conditions[] = "DATE(r.submitted_at) BETWEEN '$start_date' AND '$end_date'";
    $title = "Monthly Report - " . date('F Y', strtotime($month));
} else {
    $title = "Evaluation Report";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(r.submitted_at) >= '$date_from'";
    $title .= " (From: " . date('M d, Y', strtotime($date_from));
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(r.submitted_at) <= '$date_to'";
    $title .= " To: " . date('M d, Y', strtotime($date_to)) . ")";
}

if ($service_id > 0) {
    $where_conditions[] = "r.service_type_id = $service_id";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get data grouped by submission
$query = "
    SELECT 
        MIN(r.id) as id,
        r.user_id,
        r.service_type_id,
        ROUND(AVG(r.rating), 1) as rating,
        MAX(r.answer) as comment,
        MAX(r.submitted_at) as submitted_at,
        u.fullname,
        u.student_id,
        u.email,
        COALESCE(st.name, 'General') as service_name
    FROM responses r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN service_types st ON r.service_type_id = st.id
    $where_clause
    GROUP BY CONCAT(r.user_id, '-', DATE(r.submitted_at), '-', IFNULL(r.service_type_id, 0))
    ORDER BY submitted_at DESC
";

$result = $conn->query($query);
$evaluations = $result->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_evaluations = count($evaluations);
$total_rating = 0;
foreach ($evaluations as $eval) {
    $total_rating += $eval['rating'];
}
$average_rating = $total_evaluations > 0 ? round($total_rating / $total_evaluations, 1) : 0;

// Rating distribution
$rating_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
foreach ($evaluations as $eval) {
    $rating = round($eval['rating']);
    if (isset($rating_counts[(int)$rating])) {
        $rating_counts[(int)$rating]++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - EVSU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            padding: 30px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
            .page-break {
                page-break-before: always;
            }
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #8B0000;
        }
        
        .print-header h1 {
            color: #8B0000;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .print-header h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .print-header p {
            color: #666;
            font-size: 12px;
        }
        
        .print-actions {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin: 0 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #8B0000;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        
        .stat-card h3 {
            font-size: 28px;
            color: #8B0000;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #666;
            font-size: 12px;
        }
        
        .rating-distribution {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .rating-distribution h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .rating-label {
            width: 60px;
            font-size: 12px;
        }
        
        .rating-bar-fill {
            flex: 1;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .rating-bar-fill span {
            display: block;
            height: 100%;
            background: #ffc107;
            border-radius: 10px;
        }
        
        .rating-count {
            width: 40px;
            font-size: 12px;
            text-align: right;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background: #8B0000;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 12px;
        }
        
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e9ecef;
            font-size: 11px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .service-badge {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            display: inline-block;
        }
        
        .rating-stars {
            display: inline-flex;
            gap: 2px;
        }
        
        .rating-stars i {
            color: #ffc107;
            font-size: 10px;
        }
        
        .rating-stars i.empty {
            color: #ddd;
        }
    </style>
</head>
<body>
    <div class="print-actions no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            <i class="fas fa-times"></i> Close
        </button>
    </div>
    
    <div class="print-header">
        <h1>EVSU - Ormoc Campus</h1>
        <h2><?php echo $title; ?></h2>
        <p>Service Type: <strong><?php echo htmlspecialchars($service_name); ?></strong></p>
        <p>Generated on: <?php echo date('F d, Y h:i A'); ?></p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $total_evaluations; ?></h3>
            <p>Total Evaluations</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $average_rating; ?>/5</h3>
            <p>Average Rating</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $total_evaluations > 0 ? round(($rating_counts[5] / $total_evaluations) * 100) : 0; ?>%</h3>
            <p>Satisfaction Rate (5 Stars)</p>
        </div>
    </div>
    
    <div class="rating-distribution">
        <h3>Rating Distribution</h3>
        <?php for ($i = 5; $i >= 1; $i--): ?>
            <div class="rating-bar">
                <div class="rating-label"><?php echo $i; ?> Star<?php echo $i != 1 ? 's' : ''; ?></div>
                <div class="rating-bar-fill">
                    <span style="width: <?php echo $total_evaluations > 0 ? ($rating_counts[$i] / $total_evaluations) * 100 : 0; ?>%"></span>
                </div>
                <div class="rating-count"><?php echo $rating_counts[$i]; ?></div>
            </div>
        <?php endfor; ?>
    </div>
    
    <?php if (!empty($evaluations)): ?>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Service</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($evaluations as $eval): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($eval['fullname'] ?? 'Unknown'); ?></td>
                        <td><?php echo htmlspecialchars($eval['student_id'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="service-badge">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($eval['service_name'] ?? 'General'); ?>
                            </span>
                        </td>
                        <td>
                            <div class="rating-stars">
                                <?php 
                                $rating = (float)($eval['rating'] ?? 0);
                                for ($i = 1; $i <= 5; $i++):
                                    if ($i <= round($rating)):
                                ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star empty"></i>
                                <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <?php echo $rating; ?>/5
                        </td>
                        <td><?php echo !empty($eval['comment']) ? htmlspecialchars(substr($eval['comment'], 0, 100)) : '—'; ?></td>
                        <td><?php echo date('M d, Y', strtotime($eval['submitted_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center; padding: 50px;">No evaluation records found.</p>
    <?php endif; ?>
    
    <div class="footer">
        <p>This is a computer-generated report. No signature is required.</p>
        <p>EVSU Registrar Evaluation System &copy; <?php echo date('Y'); ?></p>
    </div>
</body>
</html>
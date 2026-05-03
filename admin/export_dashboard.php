<?php
ob_start(); // Start output buffering
require('../config/db.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 1 to display errors

$type = $_POST['export_type'];

try {
    // Dashboard stats
    $students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $rooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
    $hostels = $pdo->query("SELECT COUNT(*) FROM hostels")->fetchColumn();

    // Hostels list
    $hostelStmt = $pdo->query("SELECT id, name, owner_name FROM hostels");
    $hostelsData = $hostelStmt->fetchAll(PDO::FETCH_ASSOC);

    // Room occupancy
    $roomStmt = $pdo->query("SELECT h.name AS hostel_name, r.room_number, r.capacity, r.occupied
                             FROM rooms r
                             JOIN hostels h ON r.hostel_id = h.id
                             ORDER BY h.name, r.room_number");
    $roomsData = $roomStmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary per hostel
    $summaryStmt = $pdo->query("SELECT h.name AS hostel_name, COUNT(r.id) AS total_rooms,
                                      SUM(r.capacity) AS total_capacity,
                                      SUM(r.occupied) AS total_occupied
                               FROM hostels h
                               LEFT JOIN rooms r ON h.id = r.hostel_id
                               GROUP BY h.id");
    $summaryData = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($type === 'excel') {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=dashboard_full_export.xls");

        echo "Dashboard Stats\n";
        echo "Category\tTotal\n";
        echo "Students\t$students\n";
        echo "Rooms\t$rooms\n";
        echo "Hostels\t$hostels\n\n";

        echo "Hostel List\n";
        echo "ID\tHostel Name\tOwner Name\n";
        foreach ($hostelsData as $h) {
            echo "$h[id]\t$h[name]\t$h[owner_name]\n";
        }

        echo "\nRoom Occupancy\n";
        echo "Hostel Name\tRoom Number\tCapacity\tOccupied\n";
        foreach ($roomsData as $r) {
            echo "$r[hostel_name]\t$r[room_number]\t$r[capacity]\t$r[occupied]\n";
        }

        echo "\nHostel Summary\n";
        echo "Hostel Name\tTotal Rooms\tTotal Capacity\tTotal Occupied\n";
        foreach ($summaryData as $s) {
            echo "$s[hostel_name]\t$s[total_rooms]\t$s[total_capacity]\t$s[total_occupied]\n";
        }

        exit();

    } elseif ($type === 'pdf') {
        // Include TCPDF from project root directory
        require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Hostel Management System');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Hostel Dashboard Export');
        $pdf->SetSubject('Hostel Statistics');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(10, 10, 10);
        
        // Add a page
        $pdf->AddPage();
        
        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'HOSTEL MANAGEMENT SYSTEM - DASHBOARD EXPORT', 0, 1, 'C');
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Summary Statistics
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Summary Statistics', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->Cell(60, 7, 'Category', 1, 0, 'C');
        $pdf->Cell(60, 7, 'Count', 1, 1, 'C');
        
        $stats = [
            ['Students', $students],
            ['Rooms', $rooms],
            ['Hostels', $hostels]
        ];
        
        foreach ($stats as $stat) {
            $pdf->Cell(60, 7, $stat[0], 1, 0, 'L');
            $pdf->Cell(60, 7, $stat[1], 1, 1, 'C');
        }
        $pdf->Ln(15);
        
        // Hostel Directory
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Hostel Directory', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->Cell(20, 7, 'ID', 1, 0, 'C');
        $pdf->Cell(70, 7, 'Hostel Name', 1, 0, 'C');
        $pdf->Cell(70, 7, 'Owner', 1, 1, 'C');
        
        foreach ($hostelsData as $h) {
            $pdf->Cell(20, 7, $h['id'], 1, 0, 'C');
            $pdf->Cell(70, 7, $h['name'], 1, 0, 'L');
            $pdf->Cell(70, 7, $h['owner_name'], 1, 1, 'L');
        }
        $pdf->Ln(15);
        
        // Room Occupancy
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Room Occupancy', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->Cell(50, 7, 'Hostel', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Room No.', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Capacity', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Occupied', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Availability', 1, 1, 'C');
        
        foreach ($roomsData as $r) {
            $available = $r['capacity'] - $r['occupied'];
            $pdf->Cell(50, 7, $r['hostel_name'], 1, 0, 'L');
            $pdf->Cell(30, 7, $r['room_number'], 1, 0, 'C');
            $pdf->Cell(30, 7, $r['capacity'], 1, 0, 'C');
            $pdf->Cell(30, 7, $r['occupied'], 1, 0, 'C');
            $pdf->Cell(30, 7, $available, 1, 1, 'C');
        }
        $pdf->Ln(15);
        
        // Hostel Summary
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Hostel Summary', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->Cell(50, 7, 'Hostel', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Rooms', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Capacity', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Occupied', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Occupancy %', 1, 1, 'C');
        
        foreach ($summaryData as $s) {
            $occupancy_rate = ($s['total_capacity'] > 0) ? round(($s['total_occupied']/$s['total_capacity'])*100) : 0;
            $pdf->Cell(50, 7, $s['hostel_name'], 1, 0, 'L');
            $pdf->Cell(30, 7, $s['total_rooms'], 1, 0, 'C');
            $pdf->Cell(30, 7, $s['total_capacity'], 1, 0, 'C');
            $pdf->Cell(30, 7, $s['total_occupied'], 1, 0, 'C');
            $pdf->Cell(30, 7, $occupancy_rate.'%', 1, 1, 'C');
        }
        
        // Output the PDF
        $pdf->Output('hostel_dashboard_'.date('Ymd_His').'.pdf', 'D');
        exit();
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage();
} finally {
    ob_end_clean(); // Clean the output buffer
}
?>

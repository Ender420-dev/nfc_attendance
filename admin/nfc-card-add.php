<?php
// nfc-card-add.php
header('Content-Type: application/json');
session_start();
include '../db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['CardUID']) || empty($data['CardUID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Card UID is required.']);
    exit;
}

$CardUID = $data['CardUID'];
$IsActive = isset($data['IsActive']) ? (int)$data['IsActive'] : 1;
$IssueDate = $data['IssueDate'] ?? date('Y-m-d');
$ExpiryDate = $data['ExpiryDate'] ?? date('Y-m-d', strtotime('+1 year'));

// Prepare statement to avoid duplicate CardUIDs
$stmt = $conn->prepare("INSERT INTO nfc_card (CardUID, IssueDate, ExpiryDate, IsActive) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssi", $CardUID, $IssueDate, $ExpiryDate, $IsActive);

try {
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Card added successfully.']);
} catch (mysqli_sql_exception $e) {
    // Check for duplicate CardUID
    if ($e->getCode() == 1062) {
        echo json_encode(['status' => 'error', 'message' => 'Card UID already exists.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

$stmt->close();
$conn->close();

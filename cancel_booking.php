<?php
session_start();
include 'includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = intval($input['booking_id'] ?? 0);

if ($booking_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit();
}

// Verify booking belongs to user and is cancellable
$stmt = $conn->prepare('SELECT status FROM bookings WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit();
}

$booking = $result->fetch_assoc();
$stmt->close();

if ($booking['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Booking cannot be cancelled']);
    exit();
}

// Cancel the booking
$stmt = $conn->prepare('UPDATE bookings SET status = "cancelled" WHERE id = ?');
$stmt->bind_param('i', $booking_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking']);
}

$stmt->close();
?>
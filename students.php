<?php
// Add CORS headers at the very top
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// Set the content type to JSON
header('Content-Type: application/json');

// Include database connection
include 'db.php';

// Function to validate input
function validateInput($data) {
    return !empty($data['firstName']) && !empty($data['lastName']) && !empty($data['course']) && !empty($data['year']);
}

// Helper function to convert numeric year to string
function convertYearToString($year) {
    switch ($year) {
        case 1:
            return 'First Year';
        case 2:
            return 'Second Year';
        case 3:
            return 'Third Year';
        case 4:
            return 'Fourth Year';
        case 5:
            return 'Fifth Year';
        default:
            return 'First Year'; // Default to "First Year"
    }
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Pagination
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;

        if (isset($_GET['id'])) {
            // Fetch single student by ID
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            echo json_encode($student ?: ['status' => 'error', 'message' => 'Student not found']);
        } else {
            // Fetch all students with pagination
            $stmt = $conn->prepare("SELECT * FROM students LIMIT ? OFFSET ?");
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $students = $result->fetch_all(MYSQLI_ASSOC);

            echo json_encode($students);
        }
        break;

    case 'POST':
        // Handle POST request
        $data = json_decode(file_get_contents('php://input'), true);

        if (!validateInput($data)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            break;
        }

        $firstName = $data['firstName'];
        $lastName = $data['lastName'];
        $course = $data['course'];
        $year = $data['year']; // Use the string year directly
        $enrolled = $data['enrolled'] ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO students (firstName, lastName, course, year, enrolled) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $firstName, $lastName, $course, $year, $enrolled);

        if ($stmt->execute()) {
            $studentId = $stmt->insert_id;
            $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            if ($student) {
                echo json_encode([
                    'status' => 'success',
                    'student' => $student
                ]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        break;

    case 'PUT':
        // Handle PUT request
        $data = json_decode(file_get_contents('php://input'), true);

        if (!validateInput($data)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            break;
        }

        $id = intval($data['id']);
        $firstName = $data['firstName'];
        $lastName = $data['lastName'];
        $course = $data['course'];
        $year = $data['year']; // Keep the year as a string
        $enrolled = $data['enrolled'] ? 1 : 0;  // Convert boolean to integer

        $stmt = $conn->prepare("UPDATE students SET firstName=?, lastName=?, course=?, year=?, enrolled=? WHERE id=?");
        $stmt->bind_param("ssssii", $firstName, $lastName, $course, $year, $enrolled, $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $student = $result->fetch_assoc();
                echo json_encode(['status' => 'success', 'student' => $student]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No changes made or student not found']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        break;

    case 'DELETE':
        // Handle DELETE request
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Student not found']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => $stmt->error]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No ID provided']);
        }
        break;
}

$conn->close();
?>

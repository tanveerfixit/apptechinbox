<?php
// abjad/api.php

header('Content-Type: application/json; charset=utf-8');

// Include main database connection
require_once dirname(__DIR__) . '/db.php';

// Ensure the calculations table exists in the connected database
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS calculations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            total INT NOT NULL,
            single INT NOT NULL,
            origin VARCHAR(255) DEFAULT NULL,
            meanings TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database initialization failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON payload
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($action === 'save') {
        $name = $data['name'] ?? null;
        $total = $data['total'] ?? null;
        $single = $data['single'] ?? null;
        $origin = $data['origin'] ?? '';
        $meanings = $data['meanings'] ?? '';

        if (empty($name) || $total === null || $single === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        try {
            $stmt = $db->prepare("INSERT INTO calculations (name, total, single, origin, meanings) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $total, $single, $origin, $meanings]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'edit') {
        $id = $data['id'] ?? null;
        $name = $data['name'] ?? null;
        $total = $data['total'] ?? null;
        $single = $data['single'] ?? null;
        $origin = $data['origin'] ?? '';
        $meanings = $data['meanings'] ?? '';

        if (!$id || empty($name) || $total === null || $single === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE calculations SET name = ?, total = ?, single = ?, origin = ?, meanings = ? WHERE id = ?");
            $stmt->execute([$name, $total, $single, $origin, $meanings, $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = $data['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field id']);
            exit;
        }

        try {
            $stmt = $db->prepare("DELETE FROM calculations WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'history') {
        try {
            $stmt = $db->query("SELECT id, name, total, single, origin, meanings FROM calculations ORDER BY id DESC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rows);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}

// 404 for other route configurations
http_response_code(404);
echo json_encode(['error' => 'Endpoint not found']);

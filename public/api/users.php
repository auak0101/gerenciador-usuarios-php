<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/DTO/UserDTO.php';
use App\DTO\UserDTO;
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Obter um usuário específico
            $id = (int) $_GET['id'];
            $stmt = $conn->prepare(
                "SELECT id, name, email, created_at FROM users WHERE id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $user = new UserDTO(
                    $row['id'],$row['name'],$row['email'],'',$row['created_at']
                );
                echo json_encode($user->toArray());
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Usuário não encontrado.']);
            }
            $stmt->close();
        } else {
            // Obter todos os usuários
            $users = [];
            $result = $conn->query("SELECT id, name, email, created_at FROM users");
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $users[] = (new UserDTO(
                        $row['id'],
                        $row['name'],
                        $row['email'],
                        '',
                        $row['created_at']
                    ))->toArray();
                }
            }
            echo json_encode($users);
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        if (empty($name) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(
                ['message' => 'Dados incompletos para criação de usuário.']
            );
            exit();
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userDTO = new UserDTO(null, $name, $email, $hashedPassword);
        $stmt = $conn->prepare(
            "INSERT INTO users (name, email, password) VALUES (?, ?, ?)"
        );
        $stmt->bind_param(
            "sss",
            $userDTO->name,
            $userDTO->email,
            $userDTO->password
        );
        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(['message' => 'Usuário criado com sucesso!', 'id' => $conn->insert_id]);
        } else {

            http_response_code(500);
            echo json_encode(['message' => 'Erro ao criar usuário: ' . $stmt->error]);
        }
        $stmt->close();
        break;
    case 'PUT':
        $id = $_GET['id'] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        if (empty($id) || empty($name) || empty($email)) {
            http_response_code(400);
            echo json_encode(['message' => 'Dados incompletos para atualização de usuário.']);


            exit();
        }
        $sql = "UPDATE users SET name = ?, email = ?";
        $params = "ss";
        $values = [$name, $email];
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql .= ", password = ?";
            $params .= "s";
            $values[] = $hashedPassword;
        }
        $sql .= " WHERE id = ?";
        $params .= "i";
        $values[] = $id;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($params, ...$values);
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['message' => 'Usuário atualizado com sucesso!']);
        } else {
            http_response_code(500);
            echo json_encode(
                ['message' => 'Erro ao atualizar usuário: ' . $stmt->error]
            );
        }
        $stmt->close();
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['message' => 'ID de usuário não fornecido para exclusão.']);
            exit();
        }
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['message' => 'Usuário excluído com sucesso!']);
        } 
        else {
            http_response_code(500);
            echo json_encode(
                ['message' => 'Erro ao excluir usuário: ' . $stmt->error]
            );
        }
        $stmt->close();
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Método não permitido.']);
        break;
}
$conn->close();
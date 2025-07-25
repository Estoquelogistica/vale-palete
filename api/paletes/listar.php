<?php
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, tipo_palete, valor FROM valor_unitario ORDER BY tipo_palete ASC";

$stmt = $db->prepare($query);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

?>
<?php
function get_setting($key, $default = null) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();

        return $value !== false ? $value : $default;
    } catch (Exception $e) {
        // Optional: log error
        // error_log("get_setting error: " . $e->getMessage());
        return $default;
    }
}
?>

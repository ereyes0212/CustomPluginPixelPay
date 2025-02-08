<?php
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        error_log('Update Order Webhook: Invalid request method');
        wp_send_json_error(['status' => 'error', 'message' => 'Invalid request method']);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Update Order Webhook: JSON decode error - ' . json_last_error_msg());
        wp_send_json_error(['status' => 'error', 'message' => 'Invalid JSON']);
    }

    // Log the data received from the request
    error_log('Update Order Webhook: Received data - ' . print_r($data, true));

    $order_id = isset($data['order_id']) ? ($data['order_id']) : null;
    $payment_status = isset($data['status']) ? sanitize_text_field($data['status']) : null;

    if (!$order_id || !$payment_status) {
        error_log('Update Order Webhook: Missing order_id or status');
        wp_send_json_error(['status' => 'error', 'message' => 'Missing order_id or status']);
    }

    wp_send_json_success(['status' => 'success', 'message' => 'Order updated successfully']);
} catch (Exception $e) {
    error_log('Update Order Webhook: Error - ' . $e->getMessage());
    wp_send_json_error(['status' => 'error', 'message' => 'Error processing request']);
}

exit;

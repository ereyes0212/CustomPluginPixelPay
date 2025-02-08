<?php

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        error_log('Create Order Webhook: Invalid request method');
        wp_send_json_error(['status' => 'error', 'message' => 'Invalid request method']);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Create Order Webhook: JSON decode error - ' . json_last_error_msg());
        wp_send_json_error(['status' => 'error', 'message' => 'Invalid JSON']);
    }

    $subscription_id = isset($data['subscription_id']) ? sanitize_text_field($data['subscription_id']) : null;
    
    if (!$subscription_id) {
        error_log('Create Order Webhook: Missing subscription_id');
        wp_send_json_error(['status' => 'error', 'message' => 'Missing subscription_id']);
    }

    global $wpdb;

    // Buscar la suscripción en la tabla
    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pmpro_subscriptions WHERE subscription_transaction_id = %s",
        $subscription_id
    ));

    if (!$subscription) {
        error_log('Create Order Webhook: Subscription not found - ' . $subscription_id);
        wp_send_json_error(['status' => 'error', 'message' => 'Subscription not found']);
    }

    // Crear la orden en estado "Pendiente"
    $order = new MemberOrder();
    $order->user_id = $subscription->user_id;
    $order->membership_id = $subscription->membership_level_id;
    $order->status = 'pending';
    $order->total = $subscription->billing_amount; // Inicialmente en cero
    $order->subscription_transaction_id = $subscription_id;
    $order->payment_type = 'PixelPay';
    $order->gateway = 'pixelpay';
    $order->saveOrder();

    if ($order->code) {
        error_log('Create Order Webhook: Order created successfully - Order ID: ' . $order->id);
        wp_send_json_success(['status' => 'success', 'order_id' => $order->code]);
    } else {
        error_log('Create Order Webhook: Failed to create order for subscription ' . $subscription_id);
        wp_send_json_error(['status' => 'error', 'message' => 'Failed to create order']);
    }
} catch (Exception $e) {
    error_log('Create Order Webhook: Error - ' . $e->getMessage());
    wp_send_json_error(['status' => 'error', 'message' => 'Error processing request']);
}

exit;

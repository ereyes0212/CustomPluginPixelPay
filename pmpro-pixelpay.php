<?php
/*
Plugin Name: Paid Memberships Pro - PixelPay Gateway
Description: Plugin para integrar pasarela de pago pixelpay a paidmembreship pro
Version: 1.0.6
Author: Medios Publicitarios
Text Domain: pmpro-pixelpay
Domain Path: /languages
*/

define("PMPRO_PIXELPAY_DIR", dirname(__FILE__));

/**
 * Load the PixelPay Gateway if PMPro is active.
 */
function pmpro_pixelpay_load_gateway()
{
    try {

        if (class_exists('PMProGateway')) {
            require_once(PMPRO_PIXELPAY_DIR . '/classes/class.pmprogateway_pixelpay.php');
        } else {
        }
    } catch (Exception $e) {
    }
}
add_action('plugins_loaded', 'pmpro_pixelpay_load_gateway');

/**
 * Webhook para crear una orden pendiente.
 */
function pmpro_pixelpay_create_order()
{
    try {
        require_once(PMPRO_PIXELPAY_DIR . "/webhooks/create-order.php");
    } catch (Exception $e) {
    } finally {
        exit;
    }
}

/**
 * Webhook para actualizar una orden existente.
 */
function pmpro_pixelpay_update_order()
{
    try {
        require_once(PMPRO_PIXELPAY_DIR . "/webhooks/update-order.php");
    } catch (Exception $e) {
        error_log('pmpro_pixelpay_update_order: Error al procesar la solicitud - ' . $e->getMessage());
    } finally {
        exit;
    }
}

/**
 * Load the languages folder for translations.
 */
function pmpro_pixelpay_load_textdomain()
{
    try {
        load_plugin_textdomain('pmpro-pixelpay');
    } catch (Exception $e) {
        error_log('pmpro_pixelpay_load_textdomain: Error al cargar el texto del plugin - ' . $e->getMessage());
    }
}
add_action('plugins_loaded', 'pmpro_pixelpay_load_textdomain');

// Función para modificar la longitud del campo subscription_transaction_id
function modificar_subscription_transaction_id()
{
    global $wpdb;

    // Modificar la longitud del campo a 64 caracteres
    $wpdb->query("ALTER TABLE {$wpdb->prefix}pmpro_subscriptions MODIFY subscription_transaction_id VARCHAR(64) NOT NULL");
    $wpdb->query("ALTER TABLE {$wpdb->prefix}pmpro_membership_orders MODIFY subscription_transaction_id VARCHAR(64) NOT NULL");
}

// Ejecutar la función cuando se activa el plugin
register_activation_hook(__FILE__, 'modificar_subscription_transaction_id');

// Filtro para personalizar los campos de facturación

// Forzar la moneda a HNL en PMPro
function my_pmpro_set_currency()
{
    return 'HNL'; // Cambiar 'HNL' a la moneda deseada
}
add_filter('pmpro_currency', 'my_pmpro_set_currency');

// Asegurarse de que la moneda se envíe correctamente a la API de PixelPay
function my_pmpro_send_to_pixelpay_with_currency($args)
{
    $args['currency'] = 'HNL'; // Asegúrate de enviar la moneda correcta a la API de PixelPay
    return $args;
}
add_filter('pmpro_pixelpay_api_request', 'my_pmpro_send_to_pixelpay_with_currency');

// Agregar HNL a la lista de monedas de PMPro
function pmpro_currencies_hnl($currencies)
{
    $currencies['HNL'] = __('Lempira Hondureño (HNL)', 'pmpro');
    return $currencies;
}
add_filter('pmpro_currencies', 'pmpro_currencies_hnl');

// Función para cargar los scripts necesarios
function agregar_pixelpay_sdk()
{
    if (pmpro_is_checkout()) { // Solo en la página de checkout de PMPro
        // Cargar el script del SDK de PixelPay
        wp_enqueue_script('pixelpay-sdk', 'https://unpkg.com/@pixelpay/sdk-core', array(), null, true);

        // Cargar el script de SweetAlert desde CDN
        wp_enqueue_script('sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
    }
}
add_action('wp_enqueue_scripts', 'agregar_pixelpay_sdk');






add_action('wp_ajax_crear_orden_pixelpay', 'crear_orden_pixelpay');
add_action('wp_ajax_nopriv_crear_orden_pixelpay', 'crear_orden_pixelpay');

function crear_orden_pixelpay()
{
    check_ajax_referer('pmpro_checkout_nonce', 'nonce');

    global $pmpro_checkout_redirect;

    // Evitar que PMPro redirija automáticamente
    add_filter('pmpro_checkout_redirect_url', '__return_false', 20);

    // Verificar que se reciba el ID de la membresía
    if (empty($_POST['membership_id'])) {
        wp_send_json_error(['message' => 'Falta el ID de la membresía.']);
    }
    $membership_id = intval($_POST['membership_id']);

    // Si el usuario está logueado, usar el usuario actual; de lo contrario, crear la orden sin usuario (user_id = 0)
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $user_id      = $current_user->ID;
    } else {
        $user_id = 0;
    }

    // Obtener el nivel de membresía
    $membership_level = new PMPro_Membership_Level();
    $level            = $membership_level->get_membership_level($membership_id);
    if (! $level || empty($level->initial_payment)) {
        wp_send_json_error(['message' => 'No se pudo obtener el precio de la membresía.']);
    }
    $monto = floatval($level->initial_payment);

    // Crear la orden en PMPro
    $order                      = new MemberOrder();
    $order->Gateway             = "pixelpay";
    $order->Gateway_environment = pmpro_getOption("gateway_environment");
    $order->total               = $monto;
    $order->subtotal            = $monto;
    $order->membership_id       = $membership_id;
    $order->user_id = $user_id; // Siempre asignar user_id, aunque sea 0

    $order->saveOrder();

    if (! $order->id) {
        wp_send_json_error(['message' => 'No se pudo generar la orden.']);
    }


    // Procesar la orden con PMPro
    $gateway = new PMProGateway("pixelpay");
    $result  = $gateway->process($order);

    // Si el usuario está logueado, asignar el nivel de membresía al usuario
    if ($user_id > 0) {
        pmpro_changeMembershipLevel($membership_id, $user_id);
    }

    // Actualizar los detalles del nivel en la tabla pmpro_memberships_users
    global $wpdb;
    $cycle_number    = $level->cycle_number;
    $cycle_period    = $level->cycle_period;
    $initial_payment = $level->initial_payment;
    $billing_amount  = $level->billing_amount;

    $wpdb->update(
        $wpdb->prefix . 'pmpro_memberships_users',
        array(
            'cycle_number'    => $cycle_number,
            'cycle_period'    => $cycle_period,
            'initial_payment' => $initial_payment,
            'billing_amount'  => $billing_amount,
        ),
        array('user_id' => $user_id),
        array('%s', '%s', '%s', '%s'),
        array('%d')
    );

    if (! empty($order->error) || ! $result) {
        wp_send_json_error(['message' => ! empty($order->error) ? $order->error : 'Error procesando la orden.']);
    }

    // Devolver la respuesta con la orden generada y otros datos importantes
    wp_send_json_success([
        'order_id'      => $order->code,
        'monto'         => $order->total,
        'currency'      => $order->currency,
        'membership_id' => $membership_id,
        'user_id'       => $user_id,
    ]);
}







function cargar_pixelpay_js()
{
    if (pmpro_is_checkout()) { // Solo en la página de checkout de PMPro
        $script_path = plugin_dir_path(__FILE__) . 'js/pixelpay-payment.js';
        $script_url  = plugins_url('js/pixelpay-payment.js', __FILE__);
        $version     = filemtime($script_path); // Usa la fecha de modificación como versión

        wp_enqueue_script(
            'pixelpay-payment',
            $script_url,
            array('jquery'),
            $version,
            true
        );

        // Pasar datos de WordPress a JavaScript (opcional)
        wp_localize_script('pixelpay-payment', 'pixelpayData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('pmpro_checkout_nonce'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'cargar_pixelpay_js');







//////////////////////////////////////////////////////////////////
// Función para borrar la orden y el usuario si la transacción falla
// Función para borrar la orden y el usuario si la transacción falla
add_action('wp_ajax_borrar_orden_pixelpay', 'borrar_orden_pixelpay');
add_action('wp_ajax_nopriv_borrar_orden_pixelpay', 'borrar_orden_pixelpay');

function borrar_orden_pixelpay() {
    // Verificar nonce para seguridad
    check_ajax_referer('pmpro_checkout_nonce', 'nonce');

    if (empty($_POST['order_id'])) {
        wp_send_json_error(['message' => 'No se recibió el ID de la orden.']);
    }

    $order_id = sanitize_text_field($_POST['order_id']);

    // Buscar la orden en PMPro
    $order = new MemberOrder($order_id);
    if (!$order->id) {
        wp_send_json_error(['message' => 'No se encontró la orden.']);
    }

    // Obtenemos el ID del usuario y la membresía de la nueva orden (la que se intentó contratar)
    $user_id = $order->user_id;
    $new_membership_id = $order->membership_id;

    // Borrar la orden en PMPro
    $order->deleteMe();

    global $wpdb;
    $tabla = $wpdb->prefix . 'pmpro_memberships_users';

    // 1. Eliminar el registro de la nueva membresía (la contratación fallida)
    $wpdb->delete(
        $tabla,
        array(
            'user_id'       => $user_id,
            'membership_id' => $new_membership_id,
        ),
        array('%d', '%d')
    );

    // 2. Reactivar la membresía anterior: actualizar el registro en estado "changed" a "active"
    $wpdb->update(
        $tabla,
        array('status' => 'active'),
        array(
            'user_id' => $user_id,
            'status'  => 'changed'
        ),
        array('%s'),
        array('%d', '%s')
    );

    // 3. Si el usuario no está logueado, se asume que es un usuario recién creado.
    //    En ese caso, verificar si el usuario tiene alguna membresía activa, y en caso contrario, eliminarlo.
    if (!is_user_logged_in()) {
        $active_membership = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $tabla WHERE user_id = %d AND status = %s",
                $user_id,
                'active'
            )
        );
        if (!$active_membership) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            $deleted = wp_delete_user($user_id);
            if (!$deleted) {
                wp_send_json_error(['message' => 'No se pudo eliminar el usuario.']);
            }
        }
    }

    wp_send_json_success([
        'message' => 'Orden eliminada, se borró la nueva membresía fallida y se reactivó la membresía anterior sin modificar la fecha de finalización.',
    ]);
}



// Función para crear el usuario
add_action('wp_ajax_crear_usuario', 'crear_usuario');
add_action('wp_ajax_nopriv_crear_usuario', 'crear_usuario');

function crear_usuario()
{
    check_ajax_referer('pmpro_checkout_nonce', 'nonce');

    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        wp_send_json_success([
            'message' => 'Usuario ya logueado.',
            'user_id' => $current_user->ID,
        ]);
    }

    if (empty($_POST['user_email']) || empty($_POST['username']) || empty($_POST['password']) || empty($_POST['first_name']) || empty($_POST['last_name'])) {
        wp_send_json_error(['message' => 'Faltan datos para crear el usuario.']);
    }

    $user_email = sanitize_email($_POST['user_email']);
    $username   = sanitize_user($_POST['username']);
    $password   = sanitize_text_field($_POST['password']);
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name  = sanitize_text_field($_POST['last_name']);

    if (email_exists($user_email) || username_exists($username)) {
        wp_send_json_error(['message' => 'El usuario ya existe.']);
    }

    $user_id = wp_create_user($username, $password, $user_email);
    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => 'Error al crear el usuario: ' . $user_id->get_error_message()]);
    }

    // Asignar nombre y apellido
    update_user_meta($user_id, 'first_name', $first_name);
    update_user_meta($user_id, 'last_name', $last_name);

    // Asignar rol de suscriptor
    $user = get_user_by('id', $user_id);
    $user->set_role('subscriber');

    // Autenticar usuario automáticamente después de crearlo
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    wp_send_json_success([
        'message'  => 'Usuario creado correctamente.',
        'user_id'  => $user_id,
        'first_name' => $first_name,
        'last_name'  => $last_name,
    ]);
}






add_action('wp_ajax_asignar_orden_usuario', 'asignar_orden_usuario');
add_action('wp_ajax_nopriv_asignar_orden_usuario', 'asignar_orden_usuario');

function asignar_orden_usuario() {
    try {
        error_log('Inicio de asignar_orden_usuario');
        escribirEnArchivo('Inicio de asignar_orden_usuario');

        // Verificar nonce
        check_ajax_referer('pmpro_checkout_nonce', 'nonce');
        error_log('Nonce verificado');
        escribirEnArchivo('Nonce verificado');

        // Verificar si los parámetros 'user_id' y 'order_id' están presentes
        if (empty($_POST['user_id']) || empty($_POST['order_id'])) {
            error_log('Faltan parámetros: ' . json_encode($_POST));
            escribirEnArchivo('Faltan parámetros: ' . json_encode($_POST));
            throw new Exception('Faltan datos para asociar la orden.');
        }

        $user_id = intval($_POST['user_id']);
        $order_code = sanitize_text_field($_POST['order_id']);
        error_log("Parámetros recibidos - user_id: $user_id, order_code: $order_code");
        escribirEnArchivo("Parámetros recibidos - user_id: $user_id, order_code: $order_code");

        // Buscar la orden en la base de datos
        global $wpdb;
        $table_name = $wpdb->prefix . 'pmpro_membership_orders'; // Ajusta el nombre si es necesario
        $order_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE code = %s LIMIT 1",
            $order_code
        ));

        if (!$order_data) {
            error_log('Orden no encontrada: ' . $order_code);
            escribirEnArchivo('Orden no encontrada: ' . $order_code);
            throw new Exception('Orden no encontrada para el código: ' . $order_code);
        }
        error_log('Orden encontrada - ID: ' . $order_data->id);

        // Cargar la orden existente
        $order = new MemberOrder();
        $order->getMemberOrderByID($order_data->id);

        // Actualizar el user_id en la orden
        $order->user_id = $user_id;
        $order->saveOrder();
        error_log('Orden actualizada con nuevo user_id');
        escribirEnArchivo('Orden actualizada con nuevo user_id');

        // Respuesta final
        wp_send_json_success([
            'message'    => 'Orden asociada correctamente.',
            'order_code' => $order_code,
            'user_id'    => $user_id
        ]);
        error_log('Respuesta enviada correctamente');
        escribirEnArchivo('Respuesta enviada correctamente');

    } catch (Exception $e) {
        error_log('Error en asignar_orden_usuario: ' . $e->getMessage());
        escribirEnArchivo('Error en asignar_orden_usuario: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
    }
}















add_action('wp_ajax_asignar_membresia_detalles', 'asignar_membresia_detalles');
add_action('wp_ajax_nopriv_asignar_membresia_detalles', 'asignar_membresia_detalles');

function asignar_membresia_detalles()
{
    try {
        check_ajax_referer('pmpro_checkout_nonce', 'nonce');

        if (empty($_POST['user_id']) || empty($_POST['membership_id'])) {
            wp_send_json_error(['message' => 'Faltan datos para asignar la membresía.']);
        }

        $user_id       = intval($_POST['user_id']);
        $membership_id = intval($_POST['membership_id']);

        // Asignar la membresía utilizando la función de PMPro
        pmpro_changeMembershipLevel($membership_id, $user_id);

        // Obtener los detalles del nivel de membresía
        $membership_level = new PMPro_Membership_Level();
        $level = $membership_level->get_membership_level($membership_id);

        if ($level) {
            global $wpdb;
            $result = $wpdb->update(
                $wpdb->prefix . 'pmpro_memberships_users',
                array(
                    'cycle_number'    => $level->cycle_number,
                    'cycle_period'    => $level->cycle_period,
                    'initial_payment' => $level->initial_payment,
                    'billing_amount'  => $level->billing_amount,
                    'user_id'         => $user_id
                ),
                array(
                    'membership_id' => $membership_id,
                    'user_id'       => 0
                ),
                array('%s', '%s', '%s', '%s', '%d'),
                array('%d', '%d')
            );
            if ($result === false) {
                throw new Exception("Error al actualizar los datos de membresía: " . $wpdb->last_error);
            }
        }

        wp_send_json_success([
            'message'     => 'Membresía asignada correctamente.',
            'user_id'     => $user_id,
            'membership_id' => $membership_id,
        ]);
    } catch (Exception $e) {
        error_log("Error en asignar_membresia_detalles: " . $e->getMessage());
        wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
    }
}










//funcion para generar la url 

function obtener_url_factura()
{
    check_ajax_referer('pmpro_checkout_nonce', 'nonce');

    if (empty($_POST['order_id'])) {
        wp_send_json_error(['message' => 'Falta el ID de la orden.']);
    }

    $order_id = sanitize_text_field($_POST['order_id']);

    // Aquí construimos la URL relativa para la factura
    $invoice_url = '/index.php/cuenta-de-membresia/pedidos-de-membresia/?invoice=' . $order_id;

    if ($invoice_url) {
        wp_send_json_success([
            'invoice_url' => $invoice_url,
        ]);
    } else {
        wp_send_json_error(['message' => 'No se pudo generar la URL de la factura.']);
    }
}

add_action('wp_ajax_obtener_url_factura', 'obtener_url_factura');
add_action('wp_ajax_nopriv_obtener_url_factura', 'obtener_url_factura');




//////////////////////////////////////////////////////////////////
//Funcion para generar nuevo NONCE

add_action('wp_ajax_generar_nuevo_nonce', 'generar_nuevo_nonce');
add_action('wp_ajax_nopriv_generar_nuevo_nonce', 'generar_nuevo_nonce');

function generar_nuevo_nonce()
{
    wp_send_json_success([
        'nonce' => wp_create_nonce('pmpro_checkout_nonce') // Genera un nuevo nonce
    ]);
}



//Funcion para actualizar el token
add_action('wp_ajax_actualizar_token_transaccion', 'actualizar_token_transaccion');
function actualizar_token_transaccion()
{
    if (isset($_POST['order_id']) && isset($_POST['token'])) {
        global $wpdb;

        $order_id = sanitize_text_field($_POST['order_id']);
        $token = sanitize_text_field($_POST['token']);

        // Actualizar el token en la tabla wp_pmpro_membership_orders
        $updated = $wpdb->update(
            $wpdb->prefix . 'pmpro_membership_orders',
            array('subscription_transaction_id' => $token), // Nuevos datos
            array('code' => $order_id), // Condición de la consulta
            array('%s'),
            array('%d')
        );

        if ($updated !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Error al actualizar el token de transacción');
        }
    } else {
        wp_send_json_error('Faltan parámetros');
    }
}




//Funcion para actualizar el fin de membresia 


add_action('wp_ajax_actualizar_fecha_fin_membresia', 'actualizar_fecha_fin_membresia');

function actualizar_fecha_fin_membresia()
{
    if (!isset($_POST['order_id'])) {
        error_log("Error: Falta el parámetro order_id.");
        wp_send_json_error('Faltan parámetros');
    }

    global $wpdb;

    $order_id = sanitize_text_field($_POST['order_id']);
    error_log("Procesando actualización de membresía para la orden con código: " . $order_id);

    // Buscar la orden en la base de datos utilizando el código (no el id numérico)
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT user_id, membership_id FROM {$wpdb->prefix}pmpro_membership_orders WHERE code = %s",
        $order_id
    ));

    if (!$order) {
        error_log("Error: No se encontró la orden con código " . $order_id);
        wp_send_json_error('No se encontró el pedido o el usuario asociado.');
    }

    $user_id = $order->user_id;
    $membership_id = $order->membership_id;
    error_log("Orden encontrada. User ID asociado: " . $user_id . " - Membership ID: " . $membership_id);

    // Obtener la membresía del usuario (la fila más reciente en pmpro_memberships_users)
    $membership_user = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pmpro_memberships_users WHERE user_id = %d ORDER BY id DESC LIMIT 1",
        $user_id
    ));

    if (!$membership_user) {
        error_log("Error: No se encontró una membresía activa para el usuario ID " . $user_id);
        wp_send_json_error('No se encontró una membresía activa para este usuario.');
    }

    error_log("Membresía encontrada para el usuario ID " . $user_id . " - Fecha actual de expiración: " . $membership_user->enddate);

    // Obtener el ciclo de la membresía (cycle_period) consultando el nivel en pmpro_memberships_levels
    $level_data = $wpdb->get_row($wpdb->prepare(
        "SELECT cycle_period FROM {$wpdb->prefix}pmpro_membership_levels WHERE id = %d",
        $membership_id
    ));

    if (!$level_data || empty($level_data->cycle_period)) {
        error_log("Error: No se encontró el cycle_period para la membresía ID " . $membership_id);
        wp_send_json_error('No se pudo obtener el ciclo de la membresía.');
    }

    $cycle_period = strtolower($level_data->cycle_period); // Convertir a minúsculas para comparar

    error_log("Cycle period obtenido: " . $cycle_period);

    // Obtener la fecha base a partir de la enddate de la membresía.
    $currentEnddate = $membership_user->enddate;
    if ($currentEnddate == '0000-00-00 00:00:00' || strtotime($currentEnddate) <= 0) {
        $base_timestamp = time();
        error_log("No se pudo parsear la fecha de expiración actual ('{$currentEnddate}'). Se usará la fecha actual como base.");
    } else {
        $base_timestamp = strtotime($currentEnddate);
    }

    // Calcular la nueva fecha de expiración según el ciclo
    switch ($cycle_period) {
        case 'day':
            $new_timestamp = strtotime('+1 day', $base_timestamp);
            break;
        case 'week':
            $new_timestamp = strtotime('+1 week', $base_timestamp);
            break;
        case 'month':
            $new_timestamp = strtotime('+1 month', $base_timestamp);
            break;
        case 'year':
            $new_timestamp = strtotime('+1 year', $base_timestamp);
            break;
        default:
            error_log("Error: Tipo de ciclo no válido: " . $cycle_period);
            wp_send_json_error('Tipo de membresía no válido');
    }

    $nueva_fecha_expiracion = date('Y-m-d H:i:s', $new_timestamp);
    error_log("Nueva fecha de expiración calculada: " . $nueva_fecha_expiracion);

    // Actualizar la membresía con la nueva fecha en wp_pmpro_memberships_users
    $updated = $wpdb->update(
        "{$wpdb->prefix}pmpro_memberships_users",
        array('enddate' => $nueva_fecha_expiracion),
        array('user_id' => $user_id),
        array('%s'),
        array('%d')
    );

    if ($updated !== false) {
        error_log("Fecha de expiración actualizada correctamente para el usuario ID " . $user_id);
        wp_send_json_success('Fecha de expiración actualizada.');
    } else {
        error_log("Error al actualizar la fecha de expiración para el usuario ID " . $user_id);
        wp_send_json_error('Error al actualizar la fecha de expiración.');
    }
}


//HOOK para eliminar el usuario cuando una membresia expira 

add_action('pmpro_membership_post_membership_expiry', function ($user_id, $membership_id) {
    require_once ABSPATH . 'wp-admin/includes/user.php'; // Incluir funciones para eliminar usuarios.

    $deleted = wp_delete_user($user_id);

    if ($deleted) {
        error_log("Usuario ID $user_id eliminado porque su membresía (ID: $membership_id) expiró.");
    } else {
        error_log("Error al intentar eliminar el usuario ID $user_id.");
    }
}, 10, 2);


//Funcion para modificar el cron de pago recurrente
function escribirEnArchivo($cadena) {
    // Abre el archivo en modo de escritura (crea el archivo si no existe)
    $archivoHandle = fopen('/var/www/tiempoDevelopment/mz2h324.txt', "a"); // "a" para agregar al final
    
    if ($archivoHandle === false) {
        return false; // Error al abrir el archivo
    }
    
    // Escribe la cadena en el archivo
    fwrite($archivoHandle, $cadena . PHP_EOL);
    
    // Cierra el archivo
    fclose($archivoHandle);
    
    return true; // Escritura exitosa
}
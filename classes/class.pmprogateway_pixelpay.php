<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Ajusta la ruta si es necesario

use PixelPay\Sdk\Entities\TransactionResult;
use PixelPay\Sdk\Requests\AuthTransaction;
use PixelPay\Sdk\Resources\Locations;
use PixelPay\Sdk\Models\Settings;
use PixelPay\Sdk\Models\Card;
use PixelPay\Sdk\Models\Billing;
use PixelPay\Sdk\Models\Order;
use PixelPay\Sdk\Requests\SaleTransaction;
use PixelPay\Sdk\Services\Transaction;

// Cargar el método de inicialización de la clase Pixelpay
add_action('init', array('PMProGateway_Pixelpay', 'init'));

// Filtrar la disponibilidad de Pixelpay en PMPro
add_filter('pmpro_is_ready', array('PMProGateway_Pixelpay', 'pmpro_is_pixelpay_ready'), 999, 1);


class PMProGateway_Pixelpay extends PMProGateway
{

    function __construct($gateway = NULL)
    {
        // Asignar el gateway a la propiedad
        $this->gateway = $gateway;
        return $this->gateway;
    }

    /**
     * Run on WP init
     *
     * @since 1.8
     */
    static function init()
    {
        // Asegura que Pixelpay sea una opción de gateway
        add_filter('pmpro_gateways', array('PMProGateway_Pixelpay', 'pmpro_gateways'));
        // add_filter( 'pmpro_gateways_with_pending_status', array( 'PMProGateway_Pixelpay', 'pmpro_gateways_with_pending_status' ) );

        // // Añadir campos a la configuración de pago
        // add_filter( 'pmpro_payment_options', array( 'PMProGateway_Pixelpay', 'pmpro_payment_options' ));

        add_filter('pmpro_currency', array('PMProGateway_Pixelpay', 'pmpro_change_currency_to_hnl'));

        // Código a agregar en el checkout
        $gateway = pmpro_getGateway();



        if ($gateway == "pixelpay") {

            add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_Pixelpay', 'pmpro_checkout_default_submit_button'));
        }


        add_filter('pmpro_countries', array('PMProGateway_Pixelpay', 'paises_facturacion'));
        add_action('pmpro_checkout_after_pricing_fields', array('PMProGateway_Pixelpay', 'my_pmpro_custom_billing_fields'));

        add_filter('pmpro_include_billing_address_fields', '__return_false');

        add_filter('pmpro_checkout_fields', function ($fields) {
            return $fields;
        }, 10, 1);

        add_action('pmpro_required_billing_fields', array('PMProGateway_Pixelpay', 'my_pmpro_required_billing_fields'));
        add_action('pmpro_billing_fields', array('PMProGateway_Pixelpay', 'my_pmpro_remove_billing_fields'));
        add_filter('pmpro_currencies', array('PMProGateway_Pixelpay', 'mi_moneda_personalizada_pmpro'));
        add_filter('pmpro_currency', array('PMProGateway_Pixelpay', 'my_pmpro_set_currency'));
        add_filter('pmpro_currencies', array('PMProGateway_Pixelpay', 'pmpro_currencies_hnl'));
        add_action('pmpro_save_membership_level', array('PMProGateway_Pixelpay', 'pmpro_pixelpay_membership_level_created'), 10, 1);
        add_filter('pmpro_registration_checks', array('PMProGateway_Pixelpay', 'my_pmpro_custom_registration_validation'));
        add_action('wp_ajax_get_states', array('PMProGateway_Pixelpay', 'get_states_ajax_handler'));
        add_action('wp_ajax_nopriv_get_states', array('PMProGateway_Pixelpay', 'get_states_ajax_handler'));
        add_action('pmpro_after_checkout', array('PMProGateway_Pixelpay', 'custom_after_checkout_logic'), 10, 2);
        add_action('pmpro_checkout_before_processing', array('PMProGateway_Pixelpay','custom_create_order_and_send_to_front'), 10, 1);

        
    }
    // Función que se ejecuta después del checkout
    static function custom_after_checkout_logic($user_id, $morder) {
        // Verificar si $morder es un objeto
        if (is_object($morder)) {
            // Imprimir el contenido de $morder para depuración
            error_log('Contenido de morder: ' . print_r($morder, true));
    
            // Verificar si el campo subscription_transaction_id es null o vacío
            if (empty($morder->subscription_transaction_id)) {
                // El usuario no tiene una suscripción activa, procederemos a actualizar la fecha de vencimiento
    
                // Verificar si el objeto tiene el método getMembershipLevel
                if (method_exists($morder, 'getMembershipLevel')) {
                    // Obtener el nivel de membresía
                    $membership_level = $morder->getMembershipLevel();
    
                    // Verificar si el nivel de membresía está disponible
                    if ($membership_level) {
                        // Obtener la duración del ciclo
                        $cycle_number = $membership_level->cycle_number;
                        $cycle_period = $membership_level->cycle_period;
    
                        // Validar si tenemos un ciclo válido
                        if ($cycle_number > 0 && !empty($cycle_period)) {
                            // Calcular la fecha de vencimiento según el ciclo
                            $new_end_date = current_time('timestamp'); // Fecha actual
    
                            // Dependiendo del período del ciclo, ajustar la fecha de vencimiento
                            switch (strtolower($cycle_period)) {
                                case 'day':
                                    $new_end_date = strtotime("+$cycle_number days", $new_end_date);
                                    break;
                                case 'week':
                                    $new_end_date = strtotime("+$cycle_number weeks", $new_end_date);
                                    break;
                                case 'month':
                                    $new_end_date = strtotime("+$cycle_number months", $new_end_date);
                                    break;
                                case 'year':
                                    $new_end_date = strtotime("+$cycle_number years", $new_end_date);
                                    break;
                                default:
                                    error_log('Ciclo desconocido: ' . $cycle_period);
                                    return;
                            }
    
                            // Formatear la nueva fecha de vencimiento
                            $new_end_date_formatted = date('Y-m-d H:i:s', $new_end_date);
    
                            // Actualizar la fecha de vencimiento en la tabla pmpro_memberships_users
                            global $wpdb;
                            $wpdb->update(
                                $wpdb->prefix . 'pmpro_memberships_users',
                                array('enddate' => $new_end_date_formatted),  // Datos a actualizar
                                array('user_id' => $user_id, 'membership_id' => $membership_level->id), // Condición para la fila correcta
                                array('%s'),  // Formato del dato (fecha)
                                array('%d', '%d') // Formato de las condiciones (user_id y membership_id)
                            );
    
                            // Opcional: loguear el cambio
                            error_log('Fecha de vencimiento actualizada a: ' . $new_end_date_formatted);
                        } else {
                            error_log('Ciclo no válido o no especificado en la membresía.');
                        }
                    } else {
                        error_log('No se pudo obtener el nivel de membresía.');
                    }
                } else {
                    error_log('El objeto no tiene el método getMembershipLevel.');
                }
            } else {
                // Si el campo subscription_transaction_id tiene valor, no realizamos ninguna acción
                error_log('El usuario ya tiene una suscripción activa (subscription_transaction_id: ' . $morder->subscription_transaction_id . '). No se actualizará la fecha de vencimiento.');
            }
        } else {
            // Si $morder no es un objeto, mostrar su contenido
            error_log('El parámetro $morder no es un objeto. Contenido: ' . print_r($morder, true));
        }
    }
    
    
    
    
    
    // // Función para calcular la nueva fecha de finalización
    // static function calculate_new_end_date($membership_level) {
    //     // Lógica para calcular la nueva fecha de finalización según la recurrencia
    //     $recurrence = $membership_level->cycle_period; // Ejemplo de recurrencia, puede ser 'Day', 'Month', etc.
    //     $cycle_number = $membership_level->cycle_number;

    //     // Suponiendo que estamos trabajando con fechas en formato 'Y-m-d'
    //     $current_date = new DateTime();
    //     switch ($recurrence) {
    //         case 'Day':
    //             $current_date->modify("+$cycle_number day");
    //             break;
    //         case 'Month':
    //             $current_date->modify("+$cycle_number month");
    //             break;
    //         case 'Year':
    //             $current_date->modify("+$cycle_number year");
    //             break;
    //         // Agregar más casos si es necesario
    //     }

    //     return $current_date->format('Y-m-d');
    // }

    static function my_pmpro_custom_registration_validation($pmpro_continue_registration)
    {
        // Inicializar variable de error en false
        $errors = [];

        // Validar el nombre (mínimo 3, máximo 120 caracteres)
        if (empty($_REQUEST['bfirstname']) || strlen($_REQUEST['bfirstname']) < 3 || strlen($_REQUEST['bfirstname']) > 120) {
            $errors[] = 'El campo "Nombre" debe tener entre 3 y 120 caracteres.';
        }

        // Validar los apellidos (mínimo 3, máximo 120 caracteres)
        if (empty($_REQUEST['blastname']) || strlen($_REQUEST['blastname']) < 3 || strlen($_REQUEST['blastname']) > 120) {
            $errors[] = 'El campo "Apellidos" debe tener entre 3 y 120 caracteres.';
        }

        // Validar dirección (obligatoria)
        if (empty($_REQUEST['baddress1'])) {
            $errors[] = 'El campo "Dirección 1" es obligatorio.';
        }

        // Validar país (mínimo 3, máximo 15 caracteres)
        if (empty($_REQUEST['bcountry']) || strlen($_REQUEST['bcountry']) < 2 || strlen($_REQUEST['bcountry']) > 15) {
            $errors[] = 'El campo "País" debe tener entre 2 y 15 caracteres.';
        }

        // Validar departamento (selección requerida)
        if (empty($_REQUEST['bcity'])) {
            $errors[] = 'Debe seleccionar un "Departamento".';
        }

        // Validar código postal (alfanumérico)
        if (empty($_REQUEST['bzipcode']) || !ctype_alnum($_REQUEST['bzipcode'])) {
            $errors[] = 'El campo "Código Postal" debe ser alfanumérico.';
        }

        // Validar teléfono (exactamente 8 dígitos)
        if (empty($_REQUEST['bphone']) || !preg_match('/^\d{8}$/', $_REQUEST['bphone'])) {
            $errors[] = 'El campo "Teléfono" debe contener exactamente 8 dígitos numéricos.';
        }

        // Validar correo electrónico (formato válido)
        if (empty($_REQUEST['bemail']) || !filter_var($_REQUEST['bemail'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingrese un "Correo Electrónico" válido.';
        }

        // Validar confirmación de correo electrónico
        if ($_REQUEST['bemail'] !== $_REQUEST['bconfirmemail']) {
            $errors[] = 'Los correos electrónicos no coinciden.';
        }

        // Si hay errores, detener el registro y mostrar mensajes
        if (!empty($errors)) {
            foreach ($errors as $error) {
                pmpro_setMessage($error, 'pmpro_error');
            }
            return false;  // Cancelar el registro
        }

        return $pmpro_continue_registration;  // Continuar con el proceso si no hay errores
    }


    static function pmpro_pixelpay_membership_level_created($level_id)
    {

        try {
            // Obtener el nivel de membresía usando el ID proporcionado
            $level = new PMPro_Membership_Level($level_id);
            $recurrencia = self::obtenerRecurrencia($level->__get('cycle_period'));
            // Acceder a las propiedades del nivel de membresía utilizando __get
            $data_to_send = array(
                'id'                => $level->__get('id'), // ID del nivel de membresía
                'nombre'            => $level->__get('name'), // Nombre del nivel
                'precio'            => $level->__get('initial_payment'), // Pago inicial
                'tipo_recurrencia'  => $recurrencia, // Tipo de recurrencia
                'descripcion'       => $level->__get('description'), // Descripción del nivel
            );


            // Realizar la llamada a la API de PixelPay
            $response = wp_remote_post('http://127.0.0.1:8082/createmembresia', array(
                'method'    => 'POST',
                'body'      => json_encode($data_to_send),
                'headers'   => array(
                    'Content-Type' => 'application/json',
                ),
            ));

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                error_log('Error al enviar datos a la API: ' . $error_message);
            } else {
            }
        } catch (Exception $e) {
            error_log('Error al manejar la creación del nivel de membresía: ' . $e->getMessage());
        }
    }

    // Enganchar la función al evento de creación de nivel de membresía







    // Forzar la moneda a HNL en PMPro
    static function my_pmpro_set_currency()
    {
        return 'HNL'; // Cambiar 'HNL' a la moneda deseada
    }



    // Agregar HNL a la lista de monedas de PMPro
    static function pmpro_currencies_hnl($currencies)
    {
        $currencies['HNL'] = __('Lempira Hondureña (HNL)', 'pmpro');
        return $currencies;
    }

    // Agregar acción de AJAX en WordPress


    static function get_states_ajax_handler()
    {
        // Verificar si se recibe el código del país
        if (isset($_GET['countryCode'])) {
            $countryCode = sanitize_text_field($_GET['countryCode']); // sanitizar el código del país
            $states = Locations::statesList($countryCode); // Obtener los estados

            // Devolver los estados como respuesta JSON
            wp_send_json_success($states); // wp_send_json_success devuelve un JSON con 'success' y los datos
        } else {
            wp_send_json_error('No se proporcionó un código de país.');
        }
    }


    static function my_pmpro_custom_billing_fields()
    {

        $states = Locations::statesList('HN');
        $countries = Locations::countriesList();
        // Verificar si hay errores de validación almacenados en la sesión

        // Verificar si hay un mensaje de error general
        if (isset($_SESSION['pmpro_error_message'])) {
            echo "<p class='pmpro_message pmpro_error'>{$_SESSION['pmpro_error_message']}</p>";
        }

        // Verificar si hay errores de validación específicos
        if (isset($_SESSION['pmpro_validation_errors']) && !empty($_SESSION['pmpro_validation_errors'])) {
            echo "<ul class='pmpro_message pmpro_error'>";
            foreach ($_SESSION['pmpro_validation_errors'] as $error) {
                echo "<li>{$error}</li>";
            }
            echo "</ul>";
        }

        // Limpiar los mensajes de la sesión después de mostrarlos
        unset($_SESSION['pmpro_error_message']);
        unset($_SESSION['pmpro_validation_errors']);

        // Mostrar el formulario de facturación
?>
        <div class="pmpro_card">
            <div class="pmpro_card_content">
                <legend class="pmpro_form_legend">
                    <h2 class="pmpro_form_heading pmpro_font-large">Información de facturación</h2>
                </legend>

                <div class="pmpro_cols-2">
                    <div class="pmpro_form_field ">
                        <label for="bfirstname" class="pmpro_form_label">Nombre<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span></label>
                        <input id="bfirstname" name="bfirstname" type="text" class="pmpro_form_input pmpro_form_input-text pmpro_form_input-required" required />
                    </div>

                    <div class="pmpro_form_field ">
                        <label for="blastname" class="pmpro_form_label">Apellidos<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span></label>
                        <input id="blastname" name="blastname" type="text" class="pmpro_form_input pmpro_form_input-text pmpro_form_input-required" required />
                    </div>
                </div>

                <div class="pmpro_cols-2">
                    <div class="pmpro_form_field ">
                        <label for="baddress1" class="pmpro_form_label">Dirección 1<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span></label>
                        <input id="baddress1" name="baddress1" type="text" class="pmpro_form_input pmpro_form_input-text pmpro_form_input-required" required />
                    </div>

                    <div class="pmpro_form_field">
                        <label for="bcountry" class="pmpro_form_label">País<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span></label>
                        <select id="bcountry" name="bcountry" class="pmpro_form_input pmpro_form_input-select" required>
                            <?php foreach ($countries as $code => $country): ?>
                                <option value="<?php echo $code; ?>" <?php echo ($code === '') ? 'selected' : ''; ?>><?php echo $country; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="pmpro_cols-2">
                    <div class="pmpro_form_field">
                        <label for="bcity" class="pmpro_form_label">Departamento<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span></label>
                        <select id="bcity" name="bcity" class="pmpro_form_input pmpro_form_input-select" required>
                            <option value="">Selecciona un departamento</option>
                        </select>
                    </div>

                    <div class="pmpro_form_field ">
                        <label for="bzipcode" class="pmpro_form_label">Código Postal<span class="pmpro_asterisk"> </label>
                        <input id="bzipcode" name="bzipcode" type="text" class="pmpro_form_input pmpro_form_input-text pmpro_form_input-required" />
                    </div>
                </div>

                <div class="pmpro_cols-2">
                    <div class="pmpro_form_field ">
                        <label for="bphone" class="pmpro_form_label">Teléfono<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span></label>
                        <input id="bphone" name="bphone" type="text" class="pmpro_form_input pmpro_form_input-text pmpro_form_input-required" required />
                    </div>

                    <div class="pmpro_form_field ">
                        <label for="pemail" class="pmpro_form_label">Correo Electrónico<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span></label>
                        <input id="pemail" name="pemail" type="email" class="pmpro_form_input pmpro_form_input-email pmpro_form_input-required" required />
                    </div>
                </div>

                <div class="pmpro_cols-2">
                    <div class="pmpro_form_field ">
                        <label for="pconfirmemail" class="pmpro_form_label">Confirmar Correo Electrónico<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span></label>
                        <input id="pconfirmemail" name="pconfirmemail" type="email" class="pmpro_form_input pmpro_form_input-email pmpro_form_input-required" required />
                    </div>
                </div>

                <!-- Checkbox para pago recurrente -->
                <div class="pmpro_form_field">
                    <label for="recurring_payment" class="pmpro_form_label">
                        <input id="recurring_payment" name="recurring_payment" type="checkbox" class="pmpro_form_input" />
                        ¿Desea realizar un pago recurrente?
                    </label>
                </div>

            </div>
        </div>
        <script>
            document.getElementById('bcountry').addEventListener('change', function() {
                var countryCode = this.value;
                var citySelect = document.getElementById('bcity');

                // Limpiar el select de ciudades
                citySelect.innerHTML = '<option value="">Selecciona un departamento</option>';

                if (countryCode) {
                    // Realizar la solicitud AJAX para obtener los estados de este país
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', '<?php echo admin_url('admin-ajax.php'); ?>?action=get_states&countryCode=' + countryCode, true);

                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            var response = JSON.parse(xhr.responseText); // Parsear la respuesta JSON
                            if (response.success) {
                                var states = response.data; // Obtener los estados de la respuesta
                                // Añadir los estados al select de ciudades
                                Object.entries(states).forEach(function([stateCode, stateName]) {
                                    var option = document.createElement('option');
                                    option.value = stateCode;
                                    option.textContent = stateName;
                                    citySelect.appendChild(option);
                                });
                            } else {
                                console.log("Error: No se pudieron cargar los estados.");
                            }
                        } else {
                            console.log("Error al realizar la solicitud AJAX.");
                        }
                    };

                    xhr.send();
                }
            });
        </script>
    <?php
    }


    function mi_moneda_personalizada_pmpro($pmpro_currencies)
    {
        // Definir la moneda personalizada para HNL
        $pmpro_currencies['HNL'] = array(
            'name' => 'Lempira Hondureña',  // Nombre de la moneda
            'decimals' => '2',  // Número de decimales
            'thousands_separator' => ',',  // Separador de miles (opcional)
            'decimal_separator' => '.',  // Separador decimal (opcional)
            'symbol' => 'L',  // Símbolo de la moneda (UTF-8, 'L' para Lempira Hondureña)
            'position' => 'left',  // Posición del símbolo (izquierda o derecha)
        );

        return $pmpro_currencies;
    }


    static function my_pmpro_required_billing_fields($fields)
    {
        if (is_array($fields)) {
            unset($fields['bcity']);
            unset($fields['bstate']);
            unset($fields['bcountry']);
        }

        return $fields;
    }


    static function my_pmpro_remove_billing_fields($fields)
    {
        unset($fields['bcity']);
        unset($fields['bstate']);
        unset($fields['bcountry']);
        return $fields;
    }

    static function pmpro_change_currency_to_hnl($currency)
    {
        return 'HNL';
    }

    static function paises_facturacion($countries)
    {
        return array(
            'HN' => 'Honduras'
        );
    }


    static function sendToPixelpay($order)
    {
        // Datos de la orden que enviarás a la API
        $data = array(
            'order_id' => $order->id,
            'amount'   => $order->total, // Total de la orden
            'currency' => $order->currency, // Moneda de la orden
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            // Agrega cualquier otro dato que la API requiera
        );

        // La URL de la API
        $url = 'http://127.0.0.1:8000/api/procesar_suscripcion'; // URL de la API

        // Prepara los datos en formato JSON
        $json_data = json_encode($data);

        // Configura las opciones de cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Para que cURL devuelva la respuesta
        curl_setopt($ch, CURLOPT_POST, true); // Usamos el método POST
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json', // Indicamos que los datos son en formato JSON
            // Si necesitas autenticación, añade el token aquí:
            // 'Authorization: Bearer YOUR_API_KEY' // Si tu API requiere autenticación
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data); // Datos a enviar

        // Ejecuta la solicitud y obtiene la respuesta
        $response = curl_exec($ch);

        // Verifica si hubo un error en la ejecución de cURL
        if (curl_errno($ch)) {
            // Si hay error, lo registramos o mostramos
            error_log('Error en cURL: ' . curl_error($ch));
            return false; // O devuelve un mensaje de error
        }

        // Cierra la sesión de cURL
        curl_close($ch);

        // Decodifica la respuesta (si la API devuelve datos en JSON)
        $response_data = json_decode($response, true);

        // Verifica si la respuesta de la API es exitosa
        if (isset($response_data['status']) && $response_data['status'] == 'success') {
            // Procesa la respuesta de la API (por ejemplo, confirma la suscripción)
            return true;
        } else {
            // Si la respuesta no es exitosa, maneja el error
            error_log('Error en la respuesta de la API: ' . print_r($response_data, true));
            return false;
        }
    }


    static function getDepartmentISOCode($department)
    {
        $departments = [
            'Atlántida' => 'HN-AT',
            'Choluteca' => 'HN-CH',
            'Colón' => 'HN-CL',
            'Comayagua' => 'HN-CM',
            'Copán' => 'HN-CP',
            'Cortés' => 'HN-CR',
            'El Paraíso' => 'HN-EP',
            'Francisco Morazán' => 'HN-FM',
            'Gracias a Dios' => 'HN-GD',
            'Intibucá' => 'HN-IN',
            'Islas de la Bahía' => 'HN-IB',
            'La Paz' => 'HN-LP',
            'Lempira' => 'HN-LE',
            'Ocotepeque' => 'HN-OC',
            'Olancho' => 'HN-OL',
            'Santa Bárbara' => 'HN-SB',
            'Valle' => 'HN-VA',
            'Yoro' => 'HN-YO'
        ];

        // Normalizar entrada para evitar problemas de mayúsculas/minúsculas
        $department = ucwords(strtolower(trim($department)));

        if (array_key_exists($department, $departments)) {
            return $departments[$department];
        } else {
            return 'Código no encontrado';
        }
    }

    /**
     * Asegúrate de que esta pasarela esté en la lista de pasarelas.
     *
     * @since 1.8
     */
    static function pmpro_gateways($gateways)
    {

        if (empty($gateways['pixelpay'])) {
            $gateways['pixelpay'] = __('Pixelpay', 'pmpro-pixelpay');
        }

        return $gateways;
    }


    /**
     * Verificar si todos los campos requeridos para Pixelpay están completos
     */
    static function pmpro_is_pixelpay_ready($ready)
    {


        return $ready;
    }


    /**
     * Intercambiar el botón de envío por el de Pixelpay.
     */
    static function pmpro_checkout_default_submit_button($show)
    {

        global $gateway, $pmpro_requirebilling;

    ?>
        <span id="pmpro_submit_span">
            <input type="hidden" name="submit-checkout" value="1" />
            <input type="submit" id="pmpro_btn-submit" class="<?php echo esc_attr(pmpro_get_element_class('pmpro_btn pmpro_btn-submit-checkout')); ?>" value="<?php if ($pmpro_requirebilling) {
                                                                                                                                                                    esc_html_e('Pagar con PixelPay', 'pmpro-pixelpay');
                                                                                                                                                                } else {
                                                                                                                                                                    esc_html_e('Enviar y confimar', 'pmpro-pixelpay');
                                                                                                                                                                } ?>" />
        </span>
<?php

        // No mostrar el botón predeterminado de PMPro
        return false;
    }


    public function process(&$order) {
        global $pmpro_currency;
    
        // Asegurar que el usuario y la membresía están disponibles
        $user_id = $order->user_id;
        $membership_id = $order->membership_id;
    
        if (empty($user_id) || empty($membership_id)) {
            $order->error = "Faltan datos del usuario o membresía.";
            return false;
        }
    
        // Configurar los datos de la orden
        $order->payment_type = "PixelPay";
        $order->gateway = "pixelpay";
        $order->status = "success"; 
        $order->currency = $pmpro_currency;
    
        // Guardar la orden en PMPro
        $order->saveOrder();
    
        // Verificar si la orden se creó correctamente
        if (empty($order->id)) {
            $order->error = "No se pudo generar la orden en PMPro.";
            return false;
        }
    
    
        return true;
    }
    
    
    



    static function obtenerRecurrencia($periodo)
    {
        // Convertimos el valor de entrada a minúsculas por si viene en mayúsculas o mixto
        $periodo = strtolower($periodo);

        // Retornamos la recurrencia basada en la palabra clave proporcionada
        switch ($periodo) {
            case 'day':
                return 'Diario';
            case 'week':
                return 'Semanal';
            case 'month':
                return 'Mensual';
            case 'year':
                return 'Anual';
            default:
                return 'Período no válido'; // Si no se pasa un valor correcto
        }
    }


    static function custom_create_order_and_send_to_front($order) {
        // Aquí generas la orden en PMPro, puedes crearla o actualizarla
        // Ejemplo con valores de ejemplo:
        $order_id = $order->get_id();
        $amount = $order->get_total();
        $currency = $order->get_currency();
    
        // Retorna los datos al frontend (esto puede hacerse a través de AJAX, por ejemplo)
        wp_send_json_success([
            'order_id' => $order_id,
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }



    static function send_to_pixelpay_api(
        $customer_name,
        $card_number,
        $card_holder,
        $year_expire,
        $month_expire,
        $card_cvv,
        $customer_email,
        $billing_address,
        $billing_city,
        $billing_country,
        $billing_phone,
        $order_currency,
        $order_amount,
        $recurrence,
        $billing_state,
        $order_id,
        $membresia_id,
        $recurring_payment
    ) {
        // Configurar los settings
        $settings = new Settings();
        $settings->setupEndpoint("https://hn.ficoposonline.com");
        $settings->setupCredentials("FH1828955021", "f480b93fb75f7f3f3cce20e60190e2f7");
    
        // Crear objeto de tarjeta
        $card = new Card();
        $card->number = $card_number;
        $card->cvv2 = $card_cvv;
        $card->expire_month = $month_expire;
        $card->expire_year = $year_expire;
        $card->cardholder = $card_holder;
    
        // Crear objeto de facturación
        $billing = new Billing();
        $billing->address = $billing_address;
        $billing->city = $billing_city;
        $billing->country = $billing_country;
        $billing->state = $billing_state;
        $billing->phone = $billing_phone;
    
        // Crear objeto de orden
        $order = new Order();
        $order->id = $order_id;
        $order->amount = $order_amount;
        $order->currency = $order_currency;
        $order->customer_name = $customer_name;
        $order->customer_email = $customer_email;
    
        // Crear objeto de transacción de venta
        $sale = new SaleTransaction();
        $sale->setOrder($order);
        $sale->setCard($card);
        $sale->setBilling($billing);
    
        // Crear objeto de autenticación para la transacción
        $authRequest = new AuthTransaction();
        $authRequest->withAuthenticationRequest("FH1828955021", "f480b93fb75f7f3f3cce20e60190e2f7");
    
        // Realizar la transacción
        $transactionService = new Transaction($settings);
        $transactionService->doAuth($authRequest);
    
        try {
            $response = $transactionService->doSale($sale);
    
            if (TransactionResult::validateResponse($response)) {
                $result = TransactionResult::fromResponse($response);
    
                $is_valid_payment = $transactionService->verifyPaymentHash(
                    $result->payment_hash,
                    $order_id,
                    "792848c0c64352d76ed5d08ebb0d6114f8afa22a29037ef3bb3f8edf8725bd5a0e2ad15ec59e0377548e762730e7434c7bd9e636a7e9527ba2b857d9e70d9faa" // La clave secreta para verificar el pago
                );
    
                if ($is_valid_payment) {
                    // SUCCESS Valid Payment
                    return $result;
                } else {
                    // La verificación de la transacción falló
                    error_log("Error en la verificación del pago.");
                    return false;
                }
            } else {
                // La respuesta de la transacción no es válida
                error_log("Error en la respuesta de la transacción: " . $response->message);
                return false;
            }
        } catch (Exception $e) {
            // Manejar error en la transacción
            error_log("Error al procesar la transacción: " . $e->getMessage());
            return false;
        }
    }
    
    
}

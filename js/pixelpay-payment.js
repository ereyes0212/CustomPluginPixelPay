
document.addEventListener("DOMContentLoaded", function () {
    console.log(window);
    const checkoutForm = document.getElementById("pmpro_form");
    const submitButton = document.getElementById("pmpro_btn-submit");
    const membershipInput = document.getElementById("pmpro_level");

    if (checkoutForm && submitButton && membershipInput) {
        submitButton.addEventListener("click", async function (event) {
            event.preventDefault();

            const formData = new FormData(checkoutForm);
            const membership_id = membershipInput?.value?.trim(); // Asegurar que tiene valor

            // Obtener los valores del formulario
            const username = formData.get("username")?.trim();
            const password = formData.get("password")?.trim();
            const password2 = formData.get("password2")?.trim();
            const email = formData.get("bemail")?.trim();
            console.log("🚀 ~ email:", email);
            const confirmEmail = formData.get("bconfirmemail")?.trim();


            if (email === undefined || confirmEmail === undefined) {
                console.log("Email o Confirm Email no definidos, se permite continuar.");
            } else {
                // Expresión regular para validar el formato del correo electrónico
                const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

                // Validar si el correo tiene un formato válido
                if (!emailPattern.test(email)) {
                    // Mostrar SweetAlert si el formato del correo es incorrecto
                    await Swal.fire({
                        title: "Error",
                        text: "El formato del correo electrónico no es válido.",
                        icon: "error",
                        confirmButtonText: "Aceptar"
                    });
                    return false;  // Detener el flujo si el email no es válido
                }

                // Verificar si ambos correos coinciden
                if (email !== confirmEmail) {
                    // Mostrar SweetAlert si los correos no coinciden
                    await Swal.fire({
                        title: "Error",
                        text: "Los correos electrónicos no coinciden.",
                        icon: "error",
                        confirmButtonText: "Aceptar"
                    });
                    return false;  // Detener el flujo si los correos no coinciden
                }
            }
            // Validar que las contraseñas coincidan
            if (password !== password2) {
                Swal.fire({
                    title: 'Error',
                    text: 'Las contraseñas no coinciden.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }


            // Validar si hay una membresía seleccionada
            if (!membership_id) {
                Swal.fire({
                    title: 'Error',
                    text: 'Debe seleccionar una membresía válida.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            try {
                // Deshabilitar botón para evitar múltiples envíos
                submitButton.disabled = true;

                await procesarPago(username, password, email, membership_id, formData);


            } catch (error) {
                console.error("❌ Error en AJAX", error);
                Swal.fire({
                    title: 'Error de conexión',
                    text: error.message || 'Error de conexión con el servidor.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            } finally {
                // Volver a habilitar el botón después del proceso
                submitButton.disabled = false;
            }
        });
    } else {
        console.error("❌ Elementos no encontrados en el DOM.");
    }
});



async function procesarPago(username, password, email, membership_id, formData) {
    let userCreated = false;  // Indicador de si el usuario fue creado exitosamente
    await obtenerNuevoNonce(); // Obtener el nuevo nonce antes de la siguiente petición
    try {
        // 1️⃣ **Crear Usuario**
        const userResponse = await fetch(pixelpayData.ajax_url, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "crear_usuario",
                nonce: pixelpayData.nonce,
                username: username,
                password: password,
                user_email: email
            }),
        });

        const userData = await userResponse.json();
        if (!userData.success) {
            Swal.fire({
                title: "Error",
                text: userData.data.message,
                icon: "error",
                confirmButtonText: "Aceptar"
            });
            return; // Si el usuario no se crea, terminar la función
        }

        userCreated = true;  // El usuario fue creado exitosamente
        await obtenerNuevoNonce(); // Obtener el nuevo nonce antes de la siguiente petición
        console.log("✅ Usuario creado o ya existente:", userData);

        // 2️⃣ **Crear Orden**
        const orderResponse = await fetch(pixelpayData.ajax_url, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "crear_orden_pixelpay",
                nonce: pixelpayData.nonce,
                membership_id: membership_id,
                username: username
            }),
        });

        const orderData = await orderResponse.json();
        if (!orderData.success) {
            Swal.fire({
                title: "Error al crear la orden",
                text: orderData.data.message,
                icon: "error",
                confirmButtonText: "Aceptar"
            });
            return;
        }

        console.log("✅ Orden creada:", orderData);

        // Verificar que la orden tiene un ID válido antes de continuar
        if (!orderData.data?.order_id) {
            console.error("❌ Error: La orden no tiene un ID válido.");
            return;
        }

        // 3️⃣ **Procesar Pago**
        const checkoutFormData = {
            customerName: `${formData.get("bfirstname")} ${formData.get("blastname")}`,
            customerEmail: formData.get("pemail"),
            address: formData.get("baddress1"),
            country: formData.get("bcountry"),
            state: formData.get("bcity"),
            city: formData.get("bcity"),
            phone: formData.get("bphone"),
            cardNumber: formData.get("AccountNumber"),
            cardCVV: formData.get("CVV"),
            expireMonth: formData.get("ExpirationMonth"),
            expireYear: formData.get("ExpirationYear"),
            zip: formData.get("bzipcode"),
            recurrencia: formData.get("recurring_payment"),
            cardholderName: `${formData.get("bfirstname")} ${formData.get("blastname")}`,
        };
        console.log("🚀 ~ procesarPago ~ checkoutFormData.recurrencia:", checkoutFormData.recurrencia)
        console.log("checkoutFormData:", checkoutFormData);

        // Configuración del servicio
        const settings = new window.Models.Settings();
        settings.setupEndpoint("https://hn.ficoposonline.com");
        settings.setupCredentials("FH1828955021", "f480b93fb75f7f3f3cce20e60190e2f7");
        // settings.setupSandbox();


        // Tarjeta
        const card = new window.Models.Card();
        card.number = checkoutFormData.cardNumber;
        card.cvv2 = checkoutFormData.cardCVV;
        card.expire_month = checkoutFormData.expireMonth;
        card.expire_year = checkoutFormData.expireYear;
        card.cardholder = checkoutFormData.cardholderName;

        // Facturación
        const billing = new window.Models.Billing();
        billing.address = checkoutFormData.address;
        billing.country = checkoutFormData.country;
        billing.state = checkoutFormData.state;
        billing.city = checkoutFormData.city;
        billing.phone = checkoutFormData.phone;

        // Orden
        const order = new window.Models.Order();
        order.id = orderData.data.order_id;
        order.currency = "HNL";
        order.customer_name = checkoutFormData.customerName;
        order.customer_email = checkoutFormData.customerEmail;
        order.amount = orderData.data.monto;

        // Crear transacción
        const authRequest = new window.Requests.SaleTransaction();
        authRequest.setOrder(order);
        authRequest.setCard(card);
        authRequest.setBilling(billing);
        authRequest.order_amount = orderData.data.monto;
        authRequest.withAuthenticationRequest();

        // Ejecutar la transacción
        const service = new window.Services.Transaction(settings);
        const authResponse = await service.doSale(authRequest);

        if (!window.Entities.TransactionResult.validateResponse(authResponse)) {
            console.error("Error en la autenticación 3D Secure:", authResponse.message);

            let mensaje = authResponse.message || "Error desconocido en la autenticación";

            // Verificar si existen errores y construir la lista de errores correctamente
            if (authResponse.errors && typeof authResponse.errors === "object" && Object.keys(authResponse.errors).length > 0) {
                mensaje += "<ul>";

                // Recorremos todos los campos de errores que puedan venir
                Object.keys(authResponse.errors).forEach(campo => {
                    const erroresCampo = authResponse.errors[campo];

                    // Si es un array de errores, los mostramos
                    if (Array.isArray(erroresCampo)) {
                        erroresCampo.forEach(error => {
                            mensaje += `<li>${campo}: ${error}</li>`;  // Mostrar el campo junto al error
                        });
                    } else {
                        mensaje += `<li>${campo}: ${erroresCampo}</li>`;  // Para el caso de un único error no en un array
                    }
                });

                mensaje += "</ul>";
            }

            // Mostrar el SweetAlert
            await Swal.fire({
                title: "Error en la autenticación",
                html: mensaje,
                icon: "error",
                timer: 5000,
                timerProgressBar: true
            });

            // Borrar la orden en WordPress
            await borrarOrdenWordpress(orderData.data.order_id);
            await obtenerNuevoNonce();



            return false;  // Regresar false para indicar que la transacción falló
        }

        const authResult = window.Entities.TransactionResult.fromResponse(authResponse);
        console.log("✅ Autenticación 3D Secure exitosa:", authResult);
        if (authResult.response_approved) {
            // 4️⃣ **Tokenización si es necesario**
            if (checkoutFormData.recurrencia == 'on') {
                console.log("Iniciando tokenización de tarjeta para recurrencia...");
                await realizarTokenizacion(
                    checkoutFormData.cardNumber,
                    checkoutFormData.cardCVV,
                    checkoutFormData.expireMonth,
                    checkoutFormData.expireYear,
                    checkoutFormData.cardholderName,
                    checkoutFormData.address,
                    checkoutFormData.country,
                    checkoutFormData.state,
                    checkoutFormData.city,
                    checkoutFormData.zip,
                    checkoutFormData.phone,
                    orderData.data.order_id
                );
            } else {
                await actualizarFechaFinMembresia(orderData.data.order_id);
            }

            // 5️⃣ **Redirigir al usuario a su factura**
            window.location.href = `index.php/cuenta-de-membresia/pedidos-de-membresia/?invoice=${orderData.data.order_id}`;

        }
        else {
            Swal.fire({
                title: "Error en la autenticación",
                html: authResult.response_reason,
                icon: "error",
                timer: 5000,
                timerProgressBar: true
            });
            await borrarOrdenWordpress(orderData.data.order_id);
            await obtenerNuevoNonce();
        }


        return authResult;
    } catch (error) {
        console.error("❌ Error inesperado al procesar el pago:", error);
        return false;
    }
}




// Función para realizar la tokenización
async function realizarTokenizacion(cardNumber, cardCVV, expireMonth, expireYear, cardholderName, address, country, state, city, zip, phone, orderId) {
    const cardData = {
        cvv2: cardCVV, // CVV de la tarjeta
        number: cardNumber, // Número de la tarjeta
        expire_month: expireMonth, // Mes de expiración
        expire_year: expireYear, // Año de expiración
        cardholder: cardholderName, // Nombre del titular de la tarjeta
        address: address, // Dirección
        country: country, // País
        city: city, // Ciudad
        state: state, // Estado o provincia
        zip: zip, // Código postal
        phone: phone, // Teléfono
        lang: "es", // Idioma
        orderId: orderId // Asegurarse de que el orderId esté en cardData
    };

    try {
        const response = await fetch('https://hn.ficoposonline.com/api/v2/tokenization/card', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'x-auth-key': 'FH1828955021',
                'x-auth-hash': 'f480b93fb75f7f3f3cce20e60190e2f7'
            },
            body: JSON.stringify(cardData),
            mode: 'cors',
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            console.log('Tokenización exitosa:', data);
            console.log(data.data.token)
            // Llamar a la función para actualizar el token de la transacción
            await actualizarTokenTransaccion(orderId, data.data.token);
        } else {
            console.error('Error en la tokenización:', data.message);
            alert('Hubo un problema al tokenizar la tarjeta. Por favor, intente nuevamente.');
        }
    } catch (error) {
        console.error('Error de red:', error);
        alert('Hubo un problema con la conexión. Intente nuevamente más tarde.');
    }
}






//Funcion para eliminar la orden


function borrarOrdenWordpress(orderId) {
    // Datos para eliminar la orden
    const formData = {
        order_id: orderId, // El ID de la orden que deseas eliminar
        nonce: pixelpayData.nonce, // El nonce de seguridad que se pasa desde WordPress
    };

    // Hacer la solicitud AJAX para borrar la orden
    jQuery.ajax({
        url: pixelpayData.ajax_url, // URL de la solicitud AJAX, proveniente de wp_localize_script
        method: 'POST', // Usamos POST para enviar los datos
        data: {
            action: 'borrar_orden_pixelpay', // Acción personalizada registrada en WordPress
            ...formData, // Se agrega el contenido de formData al cuerpo de la solicitud
        },
        success: function (response) {
            if (response.success) {
                // Si la orden se elimina correctamente
                console.log(response.data.message); // Mensaje de éxito recibido del servidor
                // Aquí puedes redirigir al usuario o mostrar un mensaje de confirmación
            } else {
                // Si ocurre un error al eliminar la orden
                Swal.fire({
                    title: 'Error al eliminar la orden',
                    text: response.data.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });

            }
        },
        error: function (error) {
            // Manejar cualquier otro tipo de error AJAX (problemas con la conexión, etc.)
            Swal.fire({
                title: 'Error en la solicitud AJAX',
                text: error.statusText,
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });

        }
    });
}


async function obtenerNuevoNonce() {
    const response = await fetch(pixelpayData.ajax_url, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            action: "generar_nuevo_nonce"
        }),
    });

    const data = await response.json();
    if (data.success) {
        pixelpayData.nonce = data.data.nonce; // Actualiza el nonce en el cliente
        console.log("Nuevo nonce obtenido:", pixelpayData.nonce);
    } else {
        console.error("Error al obtener el nuevo nonce");
    }
}



async function actualizarTokenTransaccion(orderId, token) {
    const data = new URLSearchParams({
        action: 'actualizar_token_transaccion',
        order_id: orderId,
        token: token
    });

    try {
        const response = await fetch(pixelpayData.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: data
        });

        const result = await response.json();
        if (result.success) {
            console.log('Token de transacción actualizado correctamente.');
        } else {
            console.error('Error al actualizar el token de transacción:', result.data);
        }
    } catch (error) {
        console.error('Error al hacer la solicitud:', error);
    }
}



async function actualizarFechaFinMembresia(orderId) {
    if (!pixelpayData || !pixelpayData.ajax_url) {
        console.error("Error: 'pixelpayData.ajax_url' no está definido.");
        return;
    }

    const formData = new URLSearchParams();
    formData.append('action', 'actualizar_fecha_fin_membresia');
    formData.append('order_id', orderId);

    try {
        const response = await fetch(pixelpayData.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });

        // Verificar si la respuesta es válida y tiene formato JSON
        if (!response.ok) {
            console.error('Error en la petición:', response.status, response.statusText);
            alert('Error al comunicarse con el servidor.');
            return;
        }

        const result = await response.json();

        if (result.success) {
            console.log('Fecha de finalización actualizada correctamente.');
        } else {
            const errorMessage = result.data || result.message || 'Hubo un problema al actualizar la fecha de finalización.';
            console.error('Error al actualizar la fecha de finalización:', errorMessage);
            alert(errorMessage);
        }
    } catch (error) {
        console.error('Error de red:', error);
        alert('Hubo un problema con la conexión. Intente nuevamente más tarde.');
    }
}




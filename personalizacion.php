<?php
/**
 * Plugin Name: Personalización Calculadora Woo
 * Plugin URI: https://certerus.com
 * Description: Calculadora en los productos de WooCommerce
 * Version: 1.0
 * Author: Certerus
 * Author URI: https://certerus.com
 */

// Evitar el acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Verifica si WooCommerce está activado.
 *
 * @return bool Devuelve true si WooCommerce está activado, de lo contrario, false.
 */
function is_woocommerce_activated() {
    return class_exists('WooCommerce');
}

/**
 * Muestra una notificación en el panel de administración si WooCommerce no está activo.
 */
function personalizacion_admin_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>Personalización Calculadora Woo:</strong> Este plugin requiere WooCommerce para funcionar. Activa WooCommerce e intenta de nuevo.</p>
    </div>
    <?php
}

/**
 * Se ejecuta cuando se activa el plugin. Verifica si WooCommerce está activado y detiene la activación si no es así.
 */
function personalizacion_activate() {
    if (!is_woocommerce_activated()) {
        deactivate_plugins(plugin_basename(__FILE__)); // Desactiva el plugin si WooCommerce no está activo
        wp_die(
            __('Este plugin requiere WooCommerce para funcionar. Activa WooCommerce e intenta de nuevo.', 'personalizacion'),
            'Error',
            ['back_link' => true]
        );
    }
}
register_activation_hook(__FILE__, 'personalizacion_activate');

/**
 * Verifica si WooCommerce está activo al cargar el panel de administración y muestra una alerta si no lo está.
 * También desactiva el plugin automáticamente.
 */
function personalizacion_check_woocommerce() {
    if (!is_woocommerce_activated()) {
        add_action('admin_notices', 'personalizacion_admin_notice'); // Muestra la alerta en el panel de administración
        deactivate_plugins(plugin_basename(__FILE__)); // Desactiva el plugin
    }
}
add_action('admin_init', 'personalizacion_check_woocommerce');

/**
 * Agrega la calculadora de productos en la página del producto de WooCommerce.
 * Esta calculadora permite calcular cuántos productos se necesitan según la longitud deseada.
 */
function calculadora_producto() {
    if (!is_product()) return; // Solo se muestra en la página de un producto

    global $post;
    $producto = wc_get_product($post->ID);
    if (!$producto) return; // Si no es un producto válido, no hacer nada

    $producto_longitud = $producto->get_length(); // Obtener la longitud del producto
    if (!$producto_longitud) return; // Si el producto no tiene longitud, no hacer nada

    ?>
    <div id="calculadora">
        <label for="longitud-deseada">Centímetros a cubrir:</label>
        <input type="number" id="longitud-deseada" min="<?php echo esc_attr($producto_longitud); ?>" step="1" placeholder="CM">
        <button id="calcular-btn">Calcular</button>
        <p id="resultado"></p>
    </div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Variables de longitud y precio del producto
    const productoLongitud = <?php echo esc_js($producto_longitud); ?>;
    const productoPrecio = <?php echo esc_js($producto->get_price()); ?>;
    const input = document.getElementById("longitud-deseada");
    const resultado = document.getElementById("resultado");

    // Evento al hacer clic en el botón "Calcular"
    document.getElementById("calcular-btn").addEventListener("click", function() {
        let longitudDeseada = parseFloat(input.value.trim());

        // Validación de la longitud ingresada
        if (isNaN(longitudDeseada) || longitudDeseada <= 0) {
            resultado.innerText = "Por favor, ingrese un valor válido.";
            return;
        }

        // Validación de longitud mínima
        if (longitudDeseada < productoLongitud) {
            resultado.innerText = `La longitud mínima es ${productoLongitud} cm.`;
            return;
        }

        // Calcular la cantidad de productos necesarios y el precio total
        let cantidadDeseada = Math.ceil(longitudDeseada / productoLongitud);
        let totalPrice = cantidadDeseada * productoPrecio;

        // Mostrar resultados
        resultado.innerHTML = `Necesitarás <strong>${cantidadDeseada}</strong> productos. <br> Precio estimado: <strong>$${totalPrice.toFixed(2)}</strong>`;

        // Actualizar la cantidad del producto en el carrito
        let inputQty = document.querySelector(".qty");
        if (inputQty) {
            inputQty.value = cantidadDeseada;
        }
    });
});
</script>

    <style>
        #calculadora {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }
        #calculadora input {
            width: 80px;
            margin-left: 10px;
        }
        #calculadora button {
            margin-left: 10px;
        }
        #calcular-btn {
            padding: 10px;
            background-color: #152c8a;
            color: #fff;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
    </style>
    <?php
}
add_action('woocommerce_single_product_summary', 'calculadora_producto', 25);

/**
 * Muestra una advertencia en la lista de plugins si WooCommerce no está activo.
 * Esto ayuda a que los usuarios sepan que el plugin requiere WooCommerce para funcionar.
 */
function personalizacion_plugin_row_notice($plugin_file, $plugin_data, $status) {
    if ($plugin_file === plugin_basename(__FILE__) && !is_woocommerce_activated()) {
        echo '<tr class="plugin-update-tr active">
                <td colspan="3" class="plugin-update colspanchange">
                    <div class="update-message notice-error notice-alt" style="padding: 10px; border-left: 4px solid #dc3232;">
                        <p><strong>Atención:</strong> Este plugin requiere WooCommerce para funcionar. Por favor, activa WooCommerce.</p>
                    </div>
                </td>
              </tr>';
    }
}
add_action('after_plugin_row_' . plugin_basename(__FILE__), 'personalizacion_plugin_row_notice', 10, 3);

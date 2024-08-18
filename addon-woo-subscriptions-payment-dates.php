<?php
/*
* Plugin Name: Addon for Woo Subscriptions: Payment Dates
* Plugin URI: https://davidrd.es/noticias/addon-woo-subscriptions-fechas-de-pago/
* Description: 🇪🇸 Addon para Woo Subscriptions, para mostrar las fechas de pago en base a la configuración de las suscripciones 🇬🇧 Addon for Woo Subscriptions to display payment dates based on subscription settings
* Version: 2.6
* Author: alcaudon89
* Author URI: https://davidrd.es/
* License: GPLv3 or later
* Text Domain: addon-woo-subscriptions-payment-dates
* Domain Path: /languages
*/

function mu_mostrar_precio_personalizado_suscripcion( $price, $product ) {
    // Verificar si el producto es una instancia válida
    if ( ! is_a( $product, 'WC_Product' ) ) {
        return $price; // Si no es un producto válido, retornar el precio original
    }

    // Verificar si el producto es una suscripción
    if ( class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $product ) ) {

        // Obtener el precio recurrente del producto (los pagos después del primer pago)
        $importe_recurrente = WC_Subscriptions_Product::get_price( $product );
        $importe_recurrente_formateado = formatear_precio( $importe_recurrente ); // Formatear correctamente

        // Obtener el importe del primer pago (puede ser diferente al importe recurrente)
        $importe_primer_pago = WC_Subscriptions_Product::get_sign_up_fee( $product );
        if ( $importe_primer_pago == 0 ) {
            // Si no hay una cuota de registro, el primer pago es el mismo que el importe recurrente
            $importe_primer_pago = $importe_recurrente;
        }
        $importe_primer_pago_formateado = formatear_precio( $importe_primer_pago ); // Formatear correctamente

        // Obtener detalles de la suscripción
        $interval = WC_Subscriptions_Product::get_interval( $product ); // Intervalo de facturación
        $period = WC_Subscriptions_Product::get_period( $product ); // Período de facturación (día, semana, mes, año)
        $billing_cycles = WC_Subscriptions_Product::get_length( $product ); // Obtener la duración total de la suscripción

        // Caso 1: Si no hay ciclos de facturación definidos (suscripción indefinida)
        if ( $billing_cycles === 0 ) {
            $interval_text = ($interval > 1) ? "$interval " : ""; // Omitir "1" si el intervalo es 1
            return sprintf( __( '%s cada %s%s, hasta cancelar', 'addon-woo-subscriptions-payment-dates' ), $importe_recurrente_formateado, $interval_text, traducir_periodo($period) );
        }

        // Inicializar el texto del precio personalizado
        $texto_precio = '<ul>';

        // Caso 2: Si solo hay un pago, mostrar "Único pago"
        if ( $billing_cycles == 1 ) {
            $texto_precio .= "<li style='margin-bottom: 10px;'>" . sprintf( __( 'Único pago de %s', 'addon-woo-subscriptions-payment-dates' ), $importe_primer_pago_formateado ) . "</li>";
        } else {
            // Mostrar el primer pago de manera independiente
            $texto_precio .= "<li style='margin-bottom: 10px;'>" . sprintf( __( '1º pago hoy de %s', 'addon-woo-subscriptions-payment-dates' ), $importe_primer_pago_formateado ) . "</li>";

            // Caso 3: Mostrar el resto de los pagos si hay más de 1 ciclo
            $texto_precio .= "<li>&nbsp;</li><li style='margin-bottom: 10px;'>" . sprintf( __( 'Resto de pagos: %s', 'addon-woo-subscriptions-payment-dates' ), $importe_recurrente_formateado ) . "</li>";

            // Iterar para calcular los siguientes pagos correctamente
            for ( $i = 1; $i < $billing_cycles; $i++ ) {
                // Calcular la fecha del siguiente pago dependiendo del intervalo y período
                $siguiente_fecha_pago = date( 'd/m/Y', strtotime( "+".($i * $interval)." $period", strtotime( current_time( 'Y-m-d' ) ) ) );

                // Añadir al texto el siguiente pago con espaciado y salto de línea
                $texto_precio .= "<li style='margin-bottom: 10px;'>" . sprintf( __( '%sº pago el %s', 'addon-woo-subscriptions-payment-dates' ), ($i + 1), $siguiente_fecha_pago ) . "</li>";
            }
        }

        // Cerrar la lista HTML
        $texto_precio .= '</ul>';

        // Devolver solo el texto personalizado en formato lista
        return $texto_precio;
    }

    // Si no es un producto de suscripción, devolver el precio original
    return $price;
}

// Función auxiliar para traducir el período de facturación a formato legible
function traducir_periodo( $period ) {
    $periodos = array(
        'day'   => __( 'día', 'addon-woo-subscriptions-payment-dates' ),
        'week'  => __( 'semana', 'addon-woo-subscriptions-payment-dates' ),
        'month' => __( 'mes', 'addon-woo-subscriptions-payment-dates' ),
        'year'  => __( 'año', 'addon-woo-subscriptions-payment-dates' ),
    );
    return isset( $periodos[ $period ] ) ? $periodos[ $period ] : $period;
}

// Función personalizada para formatear el precio
function formatear_precio( $importe ) {
    // Verificar si el importe tiene decimales
    if ( intval( $importe ) == $importe ) {
        // Si el importe no tiene decimales o son "00", lo mostramos sin decimales
        return wc_price( $importe, array( 'decimals' => 0 ) );
    } else {
        // Si el importe tiene decimales diferentes de "00", lo mostramos con decimales
        return wc_price( $importe );
    }
}

// Cargar los archivos de traducción
function cargar_textdomain_addon_woo() {
    load_plugin_textdomain( 'addon-woo-subscriptions-payment-dates', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'cargar_textdomain_addon_woo' );

// Usar este filtro para reemplazar todo el contenido del precio con nuestro personalizado
add_filter( 'woocommerce_get_price_html', 'mu_mostrar_precio_personalizado_suscripcion', 10, 2 );

<?php
/*
  Plugin Name: WooCommerce WebpayPlus Chile
  Description: Sistema de pagos de tarjetas de crédito y débito para WooCommerce con WebPayPlus
  Author: Cristian Tala Sánchez
  Version: 3.7.0.0
  Author URI: www.cristiantala.cl
  Plugin URI: https://bitbucket.org/ctala/woocommerce-webpay/wiki/Home
  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License or any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.

  Copyright 2011-2015 Cristian Tala Sánchez
  Si estás leyendo esta parte existe la posibilidad de que quieras modificar
  incluso vender este código. Solo quiero aclarar que estás en todo el derecho
  de hacerlo, sin embargo, no incluir el autor original del código es una
  infracción a la licencia GPLv3 y se pueden realizar acciones legales para
  quienes recurran en este acto.
  Por mi parte llevo años trabajando en este código no para hacerme millonario,
  si no, para ayudar a la comunidad y un poco de reconocimiento no le hace mal a
  nadie. En resumen no seas cagado y copiando y pegando  un código que no te pertenece
  sin dar las referencias necesarias.

 */

include_once 'helpers/webpay_debug.php';
include_once 'helpers/webpay_install.php';
include_once 'classes/WC_Gateway_Webpayplus.php';

register_activation_hook(__FILE__, 'webpayplus_install');
add_action('plugins_loaded', 'init_webpayplus_class');
add_shortcode('webpay_thankyou', 'webpayThankYou');

function webpayThankYou() {
    log_me("Entrando al ThankYouPage");

    //Variable que permite ver el contenido.
    $validoMostrar = true;
    if (isset($_GET['order']) && isset($_GET['key']) && isset($_GET['status'])) {
        $order_id = absint($_GET['order']);
        $order_key = $_GET['key'];
        $status = $_GET['status'];

        //Reviso si la orden existe
        $order = new WC_Order($order_id);
        if (!$order)
            die("Orden no existe");
        //Reviso si el estatus corresponde a la orden
        if (($order->status == "failure"))
            $status = "failure";

        //Reviso si status es valido.
        if (!WC_Gateway_Webpayplus::webpay_status_valido($status))
            $validoMostrar = $validoMostrar && false;
        //Reviso si corresponde la orden con el key
        if (!WC_Gateway_Webpayplus::webpay_orden_valida($order_id, $order_key))
            $validoMostrar = $validoMostrar && false;
        //Muestro los datos de la orden si es valida
        if ($validoMostrar) {
            WC_Gateway_Webpayplus::order_received($order_id);
        } else {
            WC_Gateway_Webpayplus::webpay_pagina_error($order_id);
        }
    } else {
        if (isset($_GET['order'])) {
            $order_id = absint($_GET['order']);
            WC_Gateway_Webpayplus::webpay_pagina_error($order_id);
        } else {
            WC_Gateway_Webpayplus::webpay_pagina_error();
        }
    }


    log_me("Saliendo al ThankYouPage");
}

// change municipio to region ****
add_filter('gettext', 'translate_text');
add_filter('ngettext', 'translate_text');

function translate_text($translated) {
    $translated = str_ireplace('Municipio', 'Región', $translated);
    return $translated;
}

/*
 * Esta función solo agregará información de webpayplus al email si la orden corresponde a webpayplus.
 */
add_action('woocommerce_email_after_order_table', 'webpayplus_email_data', 15, 2);

function webpayplus_email_data($order) {
    $tipoPago = strtolower(str_replace(" ", "", $order->payment_method_title));
    $webpayplus = "webpayplus";
    log_me("Agregando Información extra de la Orden al Email " . $tipoPago, "WPP_MAIL");
    $strcmp = strcmp($tipoPago, $webpayplus);

    if ($strcmp == 0) {
        echo '<p><strong>Tipo de Pago:</strong> ' . $order->payment_method_title . '</p>';
    }
}

function ctwpp_error_notice() {
    ?>
    <div class="error notice">
        <p>El plugin de webpayplus dejará de funcionar el 31 de Diciembre del 2017. Por favor lee el siguiente <a href="https://www.cristiantala.cl/el-fin-una-era-adios-kcc-de-transbank/" target="_blank">artículo</a> para mayor información o cuéntanos si te podemos ayudar en <a href="mailto:info@pagofacil.org?subject=Woocommerce en <?=site_url();?>">info@pagofacil.org</a>. No olvides revisar con tiempo para no perder ventas en tu eCommerce.</p>
    </div>
    <?php
}

add_action('admin_notices', 'ctwpp_error_notice');

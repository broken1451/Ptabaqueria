<?php
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Plugin Name: Webpay Plus Webservices de Transbank para WooCommerce
 * Plugin URI: https://www.sitiowebonline.cl
 * Description: Recibe pagos en línea con Tarjetas de Crédito y Redcompra en tu WooCommerce a través de Webpay Plus (basado en la versión oficial).
 * Version: 10.1.5
 * Author: Andrés Reyes Galgani
 * Author URI: https://andres.reyes.tech
 *
 * Requires at least: 4.5
 * Tested up to: 5.2.2
 * Requires PHP: 5.6+
 *
 * WC requires at least: 2.5
 * WC tested up to: 3.7
 */

add_action('plugins_loaded', 'woocommerce_transbank_init', 0);

require_once( ABSPATH . "wp-includes/pluggable.php" );

function woocommerce_transbank_init()
{

    require_once( dirname(__FILE__) . '/libwebpay/webpay-soap.php' );
    if (!class_exists("WC_Payment_Gateway")){
        return;
    }

    class WC_Gateway_Transbank extends WC_Payment_Gateway
    {

        var $notify_url;

        /**
         * Constructor de gateway
         **/
        public function __construct()
        {

            $this->id = 'transbank';
            $this->icon = "https://payment.swo.cl/host/logo";
            $this->method_title = __('Webpay Plus Webservices');
            $this->notify_url = add_query_arg('wc-api', 'WC_Gateway_' . $this->id, home_url('/'));
            $this->integration = include( 'integration/integration.php' );

            /**
             * Carga configuración y variables de inicio
             **/
            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            $this->config = array(
                "MODO" => $this->get_option('webpay_test_mode'),
                "PRIVATE_KEY" => $this->get_option('webpay_private_key'),
                "PUBLIC_CERT" => $this->get_option('webpay_public_cert'),
                "WEBPAY_CERT" => $this->get_option('webpay_webpay_cert'),
                "CODIGO_COMERCIO" => $this->get_option('webpay_commerce_code'),
                "URL_RETURN" => home_url('/') . '?wc-api=WC_Gateway_' . $this->id,
                "URL_FINAL" => "_URL_",
                "VENTA_DESC" => array(
                    "VD" => "Venta Débito",
                    "VN" => "Venta Normal",
                    "VC" => "Venta en Cuotas",
                    "SI" => "3 cuotas sin interés",
                    "S2" => "2 cuotas sin interés",
                    "NC" => "N cuotas sin interés",
                ),
            );

            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'check_ipn_response'));

            if (!$this->is_valid_for_use()) {
                $this->enabled = false;
            }
        }

        /**
         * Comprueba configuración de moneda (Peso Chileno)
         **/
        function is_valid_for_use()
        {
            if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_' . $this->id . '_supported_currencies', array('CLP')))) {
                return false;
            }
            return true;
        }

        /**
         * Inicializar campos de formulario
         **/
        function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Activar/Desactivar', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Activar Transbank', 'woocommerce'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('T&iacute;tulo', 'woocommerce'),
                    'type' => 'text',
                    'default' => __('Tarjetas de Crédito o Redcompra — Webpay Plus', 'woocommerce')
                ),
                'description' => array(
                    'title' => __('Descripci&oacute;n', 'woocommerce'),
                    'type' => 'textarea',
                    'default' => __('Pague con Tarjetas de Crédito y Redcompra a través de Webpay Plus de Transbank', 'woocommerce')
                ),
                'webpay_test_mode' => array(
                    'title' => __('Ambiente', 'woocommerce'),
                    'type' => 'select',
                    'options' => array('INTEGRACION' => 'Integraci&oacute;n', 'CERTIFICACION' => 'Certificaci&oacute;n', 'PRODUCCION' => 'Producci&oacute;n'),
                    'default'     => __( 'INTEGRACION', 'woocommerce' ),
                    'custom_attributes' => array(
                        'onchange' => "webpay_mode('".$this->integration[commerce_code]."', '".$this->integration[private_key]."', '".$this->integration[public_cert]."', '".$this->integration[webpay_cert]."')",
                    )
                ),
                'webpay_commerce_code' => array(
                    'title' => __('C&oacute;digo de Comercio', 'woocommerce'),
                    'type' => 'text',
                    'default' => __($this->integration[commerce_code], 'woocommerce'),
                ),
                'webpay_private_key' => array(
                    'title' => __('Llave Privada', 'woocommerce'),
                    'type' => 'textarea',
                    'default' => __(str_replace("<br/>", "\n", $this->integration[private_key]), 'woocommerce'),
                    'css' => 'font-family: monospace',
                ),
                'webpay_public_cert' => array(
                    'title' => __('Certificado', 'woocommerce'),
                    'type' => 'textarea',
                    'default' => __(str_replace("<br/>", "\n", $this->integration[public_cert]), 'woocommerce'),
                    'css' => 'font-family: monospace',
                ),
                'webpay_webpay_cert' => array(
                    'title' => __('Certificado Transbank', 'woocommerce'),
                    'type' => 'textarea',
                    'default' => __(str_replace("<br/>", "\n", $this->integration[webpay_cert]), 'woocommerce'),
                    'css' => 'font-family: monospace',
                ),
            );
        }

        /**
         * Pagina Receptora
         **/
        function receipt_page($order)
        {
            echo $this->generate_transbank_payment($order);
        }

        /**
         * Obtiene respuesta IPN (Instant Payment Notification)
         **/
        function check_ipn_response()
        {
            @ob_clean();

            if (isset($_POST)) {
                header('HTTP/1.1 200 OK');
                $this->check_ipn_request_is_valid($_POST);
            } else {
                echo "Ocurrio un error al procesar su Compra";
            }
        }

        /**
         * Valida respuesta IPN (Instant Payment Notification)
         **/
        public function check_ipn_request_is_valid($data)
        {

            $voucher = false;

            try {

                if (isset($data["token_ws"])) {
                    $token_ws = $data["token_ws"];
                } else {
                    $token_ws = 0;
                }

                $webpay = new WebPaySoap($this->config);
                $result = $webpay->webpayNormal->getTransactionResult($token_ws);

            } catch (Exception $e) {

                $result["error"] = "Error conectando a Webpay";
                $result["detail"] = $e->getMessage();

            }

            $order_info = new WC_Order($result->buyOrder);

            WC()->session->set($order_info->order_key, $result);

            if ($result->buyOrder && $order_info) {

                if (($result->VCI == "TSY" || $result->VCI == "") && $result->detailOutput->responseCode == 0) {

                    $voucher = true;
                    WC()->session->set($order_info->order_key . "_transaction_paid", 1);

                    WebPaySOAP::redirect($result->urlRedirection, array("token_ws" => $token_ws));

                    $order_info->add_order_note(__('Pago con WEBPAY PLUS', 'woocommerce'));
                    $order_info->update_status('processing');

					$to = get_bloginfo('admin_email');
                    $subject = 'Comprobante de Pago Webpay Plus';

                    //Datos de Transbank
                    $tbk_invoice_buyOrder = $result->buyOrder;
                    $tbk_invoice_authorizationCode = $result->detailOutput->authorizationCode;

                    $date_accepted = new DateTime($finalResponse->transactionDate);
                    $tbk_invoice_transactionDate = $date_accepted->format('d-m-Y H:i:s');

                    $tbk_invoice_cardNumber = $result->cardDetail->cardNumber;

                    $paymentTypeCode = $result->detailOutput->paymentTypeCode;

                    switch ($paymentTypeCode) {
                        case "VD":
                        $tbk_invoice_paymenCodeResult = "Venta Deb&iacute;to";
                        break;
                        case "VN":
                        $tbk_invoice_paymenCodeResult = "Venta Normal";
                        break;
                        case "VC":
                        $tbk_invoice_paymenCodeResult = "Venta en cuotas";
                        break;
                        case "SI":
                        $tbk_invoice_paymenCodeResult = "3 cuotas sin inter&eacute;s";
                        break;
                        case "S2":
                        $tbk_invoice_paymenCodeResult = "2 cuotas sin inter&eacute;s";
                        break;
                        case "NC":
                        $tbk_invoice_paymenCodeResult = "N cuotas sin inter&eacute;s";
                        break;
                        default:
                        $tbk_invoice_paymenCodeResult = "—";
                        break;
                    }

                    $tbk_invoice_amount = number_format($result->detailOutput->amount, 0, ',', '.');
                    $tbk_invoice_sharesNumber = $result->detailOutput->sharesNumber;

                    //Datos Cliente
                    $tbk_invoice_nombre = $order_info->get_billing_first_name() . ' ' . $order_info->get_billing_last_name();
                    $tbk_invoice_correo = $order_info->get_billing_email();

                    $formato = '<ul><li><strong>Respuesta de la Transacción</strong>: ACEPTADO</li><li><strong>Orden de Compra:</strong> %s</li><li><strong>Codigo de Autorización:</strong> %s</li><li><strong>Fecha y Hora de la Transacción:</strong> %s</li><li><strong>Tarjeta de Crédito:</strong> ···· ···· ···· %s</li><li><strong>Tipo de Pago:</strong> %s</li><li><strong>Monto Compra: </strong>$%s</li><li><strong>Número de Cuotas:</strong> %s</li></ul>';

                    $order_info->add_order_note(sprintf($formato, $tbk_invoice_buyOrder, $tbk_invoice_authorizationCode, $tbk_invoice_transactionDate, $tbk_invoice_cardNumber, $tbk_invoice_paymenCodeResult, $tbk_invoice_amount, $tbk_invoice_sharesNumber));


					$body = <<<EOT
						<!DOCTYPE html>
						<html lang="en">

						<head>
						    <meta charset="UTF-8">
						    <meta name="viewport" content="width=device-width, initial-scale=1.0">
						    <meta http-equiv="X-UA-Compatible" content="ie=edge">
						    <title>Comprobante Webpay Plus</title>
						</head>

						<body style="padding: 30px 15% 0; font-family: Arial, Helvetica, sans-serif; font-size: 0.85rem;">

						    <div style="width: 100%; text-align: center;">
						        <img src="https://payment.swo.cl/host/mail" width="250px" />
						    </div>
						    <div>
						        <h1 style="font-size: 25px; text-transform: uppercase; text-align: center;">Notificación de Pago</h1>
						        <p>Estimado usuario, se ha realizado un pago con los siguientes datos:</p>
						        <hr />
						        <h3>Detalle de Transacción</h3>
						        <table style="width: 100%;" border="1">
						            <tbody>
						                <tr>
						                    <td style="width: 50%"><strong>Respuesta de la Transacción</strong></td>
						                    <td style="width: 50%"><strong>ACEPTADO</strong></td>
						                </tr>
						                <tr>
						                    <td>Orden de Compra</td>
						                    <td>$tbk_invoice_buyOrder</td>
						                </tr>
						                <tr>
						                    <td>Codigo de Autorización</td>
						                    <td>$tbk_invoice_authorizationCode</td>
						                </tr>
						                <tr>
						                    <td>Fecha y Hora de la Transacción</td>
						                    <td>$tbk_invoice_transactionDate</td>
						                </tr>
						                <tr>
						                    <td>Tarjeta de Crédito</td>
						                    <td>···· ···· ···· $tbk_invoice_cardNumber</td>
						                </tr>
						                <tr>
						                    <td>Tipo de Pago</td>
						                    <td>$tbk_invoice_paymenCodeResult</td>
						                </tr>
						                <tr>
						                    <td>Monto Compra</td>
						                    <td>$$tbk_invoice_amount</td>
						                </tr>
						                <tr>
						                    <td>Número de Cuotas</td>
						                    <td>$tbk_invoice_sharesNumber</td>
						                </tr>
						            </tbody>
						        </table>
						        <hr />
						        <h3>Detalle de Orden</h3>
						        <table style="width: 100%;" border="1">
						            <tbody>
						                <tr>
						                    <td style="width: 50%">Nombre de Cliente:</td>
						                    <td style="width: 50%">$tbk_invoice_nombre</td>
						                </tr>
						                <tr>
						                    <td>Correo Electrónico</td>
						                    <td>$tbk_invoice_correo</td>
						                </tr>
						            </tbody>
						        </table>
						        <p>La información contenida en este correo electrónico es informatica y ha sido enviada como respaldo de la transacción
						            cursada con tarjta de crédito o RedCompra. El siguiente pago ha sido consignado directamente en la cuenta del
						            usuario realizando las actualizaciones correspondientes a la orden de compra indicada.
						        </p>
						    </div>
						</body>

						</html>
EOT;

					$headers = array('Content-Type: text/html; charset=UTF-8');

					wp_mail( $to, $subject, $body, $headers );

                    $order_info->reduce_order_stock();

                } else {

                    $responseDescription = htmlentities($result->detailOutput->responseDescription);
                }
            }

            if (!$voucher) {

                $date = new DateTime($result->transactionDate);

                WC()->session->set($order_info->order_key, "");

                $error_message = "Estimado cliente, le informamos que su orden número ". $result->buyOrder . ", realizada el " . $date->format('d-m-Y H:i:s') . " termin&oacute; de forma inesperada ( " . $responseDescription . " ) ";
                wc_add_notice(__('ERROR: ', 'woothemes') . $error_message, 'error');

                $redirectOrderReceived = $order_info->get_checkout_payment_url();
                WebPaySOAP::redirect($redirectOrderReceived, array("token_ws" => $token_ws));
            }

            die;
        }

        /**
         * Generar pago en Transbank
         **/
        function generate_transbank_payment($order_id)
        {

            $order = new WC_Order($order_id);
            $amount = (int) number_format($order->get_total(), 0, ',', '');

            $urlFinal = str_replace("_URL_", add_query_arg('key', $order->order_key, $order->get_checkout_order_received_url()), $this->config["URL_FINAL"]);

            try {

                $webpay = new WebPaySoap($this->config);
                $result = $webpay->webpayNormal->initTransaction($amount, $sessionId = "", $order_id, $urlFinal);

            } catch (Exception $e) {

                $result["error"] = "Error conectando a Webpay";
                $result["detail"] = $e->getMessage();
            }

            if (isset($result["token_ws"])) {

                $url = $result["url"];
                $token = $result["token_ws"];

                echo "<br/>Gracias por su pedido, por favor haga clic en el bot&oacute;n de abajo para pagar con WebPay.<br/><br/>";

                return '<form id="tbk_form" action="' . $url . '" method="post">' .
                        '<input type="hidden" name="token_ws" value="' . $token . '"></input>' .
                        '<input type="submit" value="WebPay"></input>' .
                        '</form>' .
                        '<script>' .
                        'document.getElementById("tbk_form").submit();' .
                        '</script>';
            } else {

                wc_add_notice(__('ERROR: ', 'woothemes') . 'Ocurri&oacute; un error al intentar conectar con WebPay Plus. Por favor intenta mas tarde.<br/>', 'error');
            }
        }

        /**
         * Procesar pago y retornar resultado
         **/
        function process_payment($order_id)
        {
            $order = new WC_Order($order_id);
            return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url(true));
        }

        /**
         * Opciones panel de administración
         **/
        public function admin_options()
        {
            ?>
            <h3><?php _e('Transbank', 'woocommerce'); ?></h3>
            <p><?php _e('Transbank es la empresa l&iacute;der en negocios de medio de pago seguros en Chile.'); ?></p>
            <div class="wrap">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                        <?php if ($this->is_valid_for_use()) : ?>
                            <?php if (empty($this->config["CODIGO_COMERCIO"])) : ?>
                                <div class="inline error">
                                    <p><strong><?php _e('C&oacute;digo de Comercio', 'woocommerce'); ?></strong>: <?php _e('Para el correcto funcionamiento de Webpay Plus debes ingresar tu C&oacute;digo de Comercio', 'woocommerce'); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (empty($this->config["PRIVATE_KEY"])) : ?>
                                <div class="inline error">
                                    <p><strong><?php _e('Llave Privada', 'woocommerce'); ?></strong>: <?php _e('Para el correcto funcionamiento de Webpay Plus debes ingresar tu Llave Privada', 'woocommerce'); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (empty($this->config["PUBLIC_CERT"])) : ?>
                                <div class="inline error">
                                    <p><strong><?php _e('Certificado', 'woocommerce'); ?></strong>: <?php _e('Para el correcto funcionamiento de Webpay Plus debes ingresar tu Certificado', 'woocommerce'); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (empty($this->config["WEBPAY_CERT"])) : ?>
                                <div class="inline error">
                                    <p><strong><?php _e('Certificado Transbank', 'woocommerce'); ?></strong>: <?php _e('Para el correcto funcionamiento de Webpay Plus debes ingresar tu Certificado Transbank', 'woocommerce'); ?></p>
                                </div>
                            <?php endif; ?>

                            <table class="form-table">
                            <?php
                            $this->generate_settings_html();
                            ?>
                            </table>

                        <?php else : ?>
                            <div class="inline error">
                                <p>
                                    <strong><?php _e('Webpay Plus ', 'woocommerce');
                            ?></strong>: <?php _e('Este Plugin est&aacute; dise&ntilde;ado para operar con Webpay Plus solo en Pesos Chilenos.', 'woocommerce');
                            ?>
                                </p>
                            </div>
                        <?php
                        endif;
                        ?>
                        </div>
                        
                        <div id="postbox-container-1" class="postbox-container">
                            <div class="">
                                <div class="postbox">
                                    <h2 class="hndle"><span>¿Ayuda con la Integración?</span></h2>

                                    <div class="inside" style="text-align: justify">
                                        <p>Hola! soy <strong><a href="//andres.reyes.tech" target="_blank">Andrés Reyes Galgani</a></strong> Integrador de Transbank.<p>
                                        <p>Además de instalar este plugin, necesitas realizar un procedimiento técnico que yo realizo por ti por un único monto de CLP $50.000 + IVA.</p>
                                        <p>Si lo quieres comunícate conmigo a través de <!--l siguiente formulario o por -->WhatsApp presionando <a href="https://link.reyes.dev/webpay-plus-woocommerce?text=Hola, mi nombre es <?= get_currentuserinfo()->user_firstname . ' ' . get_currentuserinfo()->user_lastname ?> y necesito integrar Webpay Plus en el dominio <?= get_home_url() ?>" target="_blank"><strong>aquí</strong></a>.</p>
                                        <p style="text-align: center;"><a href="https://link.reyes.dev/webpay-plus-woocommerce?text=Hola, mi nombre es <?= get_currentuserinfo()->user_firstname . ' ' . get_currentuserinfo()->user_lastname ?> y necesito integrar Webpay Plus en el dominio <?= get_home_url() ?>" target="_blank"><img src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/img/whatsapp.svg'; ?>" width="50px" /></a></p>
                                        <!--hr />
                                        <h3>Formulario</h3-->
                                        <!--form id="webpay-contact-form" method="post" action="https://contact.reyes.dev/">
                                            <p>Nombre y Apellido:<br /><input type="text" name="txtFullName" placeholder="Nombre y Apellido" style="width: 100%" value="<?= get_currentuserinfo()->user_firstname . ' ' . get_currentuserinfo()->user_lastname ?>"></p>
                                            <p>Correo Electrónico:<br /><input type="email" name="txtEmail" placeholder="Correo Electrónico" style="width: 100%" value="<?= get_currentuserinfo()->user_email ?>"></p>
                                            <p>Teléfono:<br /><input type="text" name="txtPhone" placeholder="Teléfono" style="width: 100%"></p>
                                            <p>Página Web:<br /><input type="text" name="txtURL" placeholder="Página Web" style="width: 100%" value="<?= get_home_url() ?>"></p>
                                            <p><button type="submit" class="button">Enviar Formulario</button>
                                            <input type="hidden" name="data_plugin" value="webpay_plus_autorizacion_y_captura_simultanea_woocommerce"></p>
                                        </form-->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br class="clear">
            <?php
        }

    }

    /**
     * Añadir Transbank Plus a Woocommerce
     **/
    function woocommerce_add_transbank_gateway($methods)
    {
        $methods[] = 'WC_Gateway_transbank';
        return $methods;
    }

    /**
     * Muestra detalle de pago a Cliente a finalizar compra
     **/
    function pay_content($order_id)
    {
        $order_info = new WC_Order($order_id);
        $transbank_data = new WC_Gateway_transbank;

        if ($order_info->payment_method_title == $transbank_data->title) {

            if (WC()->session->get($order_info->order_key . "_transaction_paid") == "" && WC()->session->get($order_info->order_key) == "") {

                wc_add_notice(__('Compra <strong>Anulada</strong>', 'woocommerce') . ' por usuario. Recuerda que puedes pagar o
                    cancelar tu compra cuando lo desees desde <a href="' . wc_get_page_permalink('myaccount') . '">' . __('Tu Cuenta', 'woocommerce') . '</a>', 'error');
                wp_redirect($order_info->get_checkout_payment_url());

                die;
            }

        } else {
            return;
        }

        $finalResponse = WC()->session->get($order_info->order_key);
        WC()->session->set($order_info->order_key, "");

        $paymentTypeCode = $finalResponse->detailOutput->paymentTypeCode;
        $paymenCodeResult = $transbank_data->config['VENTA_DESC'][$paymentTypeCode];

        if ($finalResponse->detailOutput->responseCode == 0) {
            $transactionResponse = "Aceptado";
        } else {
            $transactionResponse = "Rechazado [" . $finalResponse->detailOutput->responseCode . "]";
        }

        $date_accepted = new DateTime($finalResponse->transactionDate);

        if ($finalResponse != null) {

            echo '</br><h2>Detalles del pago</h2>' .
            '<table class="shop_table order_details">' .
            '<tfoot>' .
            '<tr>' .
            '<th scope="row">Respuesta de la Transacci&oacute;n:</th>' .
            '<td><span class="RT">' . $transactionResponse . '</span></td>' .
            '</tr>' .
            '<tr>' .
            '<th scope="row">Orden de Compra:</th>' .
            '<td><span class="RT">' . $finalResponse->buyOrder . '</span></td>' .
            '</tr>' .
            '<tr>' .
            '<th scope="row">Codigo de Autorizaci&oacute;n:</th>' .
            '<td><span class="CA">' . $finalResponse->detailOutput->authorizationCode . '</span></td>' .
            '</tr>' .
            '<tr>' .
            '<th scope="row">Fecha Transacci&oacute;n:</th>' .
            '<td><span class="FC">' . $date_accepted->format('d-m-Y') . '</span></td>' .
            '</tr>' .
            '<tr>' .
            '<th scope="row"> Hora Transacci&oacute;n:</th>' .
            '<td><span class="FT">' . $date_accepted->format('H:i:s') . '</span></td>' .
            '</tr>' .
            '<tr>' .
            '<th scope="row">Tarjeta de Cr&eacute;dito:</th>' .
            '<td><span class="TC">************' . $finalResponse->cardDetail->cardNumber . '</span></td>' .
            '</tr>' .
            '<tr>' .
            '<th scope="row">Tipo de Pago:</th>' .
            '<td><span class="TP">' . $paymenCodeResult . '</span></td>' .
            '</tr>' .
            /*'<tr>' .
            '<th scope="row">Tipo de Cuotas:</th>' .
            '<td><span class="TP"></span></td>' .
            '</tr>' .*/
            '<tr>' .
            '<th scope="row">Monto Compra:</th>' .
            '<td><span class="amount">' . $finalResponse->detailOutput->amount . '</span></td>' .
            '</tr>' .
            '<tr>' .
            '<th scope="row">N&uacute;mero de Cuotas:</th>' .
            '<td><span class="NC">' . $finalResponse->detailOutput->sharesNumber . '</span></td>' .
            '</tr>' .
            '</tfoot>' .
            '</table><br/>';
        }
    }

    add_action('woocommerce_thankyou', 'pay_content', 1);
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_transbank_gateway');

    function woocommerce_transbank_enqueue_script() {
        wp_enqueue_script( 'woocommerce_transbank_integracion', plugin_dir_url( __FILE__ ) . 'integration/js/integration.js', array('jquery'), '1.0' );
    }

    if (strpos($_SERVER['REQUEST_URI'], "wc_gateway_transbank") && is_user_logged_in()){
        add_action('wp_enqueue_scripts', 'woocommerce_transbank_enqueue_script');
    }
}

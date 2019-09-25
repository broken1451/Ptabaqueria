<?php
//IF IT IS A WEBPAY PAYMENT
global $webpay_table_name, $wpdb;

$paramArr = array();
$myOrderDetails = $wpdb->get_row("SELECT * FROM $webpay_table_name WHERE idOrder = $order_id", ARRAY_A);
if ($myOrderDetails):
    $order = new WC_Order($order_id);

    //Fechas
    if(strlen($myOrderDetails["TBK_FECHA_TRANSACCION"]) < 4 OR strlen($myOrderDetails["TBK_FECHA_CONTABLE"]) < 4) {
        //Con tres dígitos
        $orden_fecha = substr($myOrderDetails["TBK_FECHA_TRANSACCION"],1,2)."-".substr($myOrderDetails["TBK_FECHA_TRANSACCION"],0,1)."-".date('Y');
        $orden_fecha_contable = substr($myOrderDetails["TBK_FECHA_CONTABLE"],1,2)."-".substr($myOrderDetails["TBK_FECHA_CONTABLE"],0,1)."-".date('Y');
    } else {
        //Con cuatro dígitos
        $orden_fecha = substr($myOrderDetails["TBK_FECHA_TRANSACCION"],2,2)."-".substr($myOrderDetails["TBK_FECHA_TRANSACCION"],0,2)."-".date('Y');
        $orden_fecha_contable = substr($myOrderDetails["TBK_FECHA_CONTABLE"],2,2)."-".substr($myOrderDetails["TBK_FECHA_CONTABLE"],0,2)."-".date('Y');
    }

    //Hora
    $orden_hora = substr($myOrderDetails["TBK_HORA_TRANSACCION"],0,2).":".substr($myOrderDetails["TBK_HORA_TRANSACCION"],2,2).":".substr($myOrderDetails["TBK_HORA_TRANSACCION"],4,2);

    ?>
    <h2 class="related_products_title order_confirmed"><?php echo "Información Extra de la Transacción"; ?></h2>
    <div class="clear"></div>
    <table class="shop_table order_details">

        <tfoot>

            <tr>
                <th>Tipo de Transacción</th>
                <th>Venta</th>
            </tr>
            <tr>
                <th>Nombre del Comercio</th>
                <th><?php echo WC_Gateway_Webpayplus::webpay_get_option('trade_name'); ?></th>
            </tr>
            <tr>
                <th>URL Comercio</th>
                <th><?php echo WC_Gateway_Webpayplus::webpay_get_option('url_commerce'); ?></th>
            </tr>
            <tr>
                <th>Cliente</th>
                <th><?php echo $order->billing_first_name." ".$order->billing_last_name; ?></th>
            </tr>
            <tr>
                <th>Código de Autorización</th>
                <th><?php echo $myOrderDetails['TBK_CODIGO_AUTORIZACION']; ?></th>
            </tr>
            <tr>
                <th>Final de Tarjeta</th>
                <th><?php echo $myOrderDetails['TBK_FINAL_NUMERO_TARJETA']; ?></th>
            </tr>
            <tr>
                <th>Fecha de la Transacción</th>
                <th><?php echo $orden_fecha; ?></th>
            </tr>
            <tr>
                <th>Fecha contable</th>
                <th><?php echo $orden_fecha_contable; ?></th>
            </tr>
            <tr>
                <th>Hora de la Transacción</th>
                <th><?php echo $orden_hora; ?></th>
            </tr>
            <tr>
                <th>Tipo de pago</th>
                <th><?php
                    if ($myOrderDetails['TBK_TIPO_PAGO'] == "VD") {
                        echo "Redcompra </th></tr>";
                        echo "<tr><td>Tipo de Cuota</td><td>Débito</td></tr>";
                    } else {
                        echo "Crédito </th></tr>";
                        echo '<tr><td>Tipo de Cuota</td><td>';
                        switch ($myOrderDetails['TBK_TIPO_PAGO']) {
                            case 'VN':
                                echo 'Sin Cuotas';
                                break;
                            case 'VC':
                                echo 'Cuotas Normales';
                                break;
                            case 'SI':
                                echo 'Sin interés';
                                break;
                            case 'CI':
                                echo 'Cuotas Comercio';
                                break;

                            default:
                                echo $myOrderDetails['TBK_TIPO_PAGO'];
                                break;
                        }
                    }
                    ?>
                    </td>
            </tr>

            <?php
            if (!($myOrderDetails['TBK_TIPO_PAGO'] == "VD") || true):
                ?>
                <tr>
                    <th>Número de Cuotas</th>
                    <th><?php
                        if (!($myOrderDetails['TBK_NUMERO_CUOTAS'] == "0")) {
                            echo $myOrderDetails['TBK_NUMERO_CUOTAS'];
                        } else {
                            echo "00";
                        }
                        ?></th>

                </tr>
                <?php
            endif;
            ?>
        </tfoot>
    </table>
    <?php
endif;
?>

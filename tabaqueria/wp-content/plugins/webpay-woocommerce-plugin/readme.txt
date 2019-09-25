=== WooCommerce Webpay Gateway Chilean  ===

Contributors: Cristian Tala S.



Tags: 

Requires at least: 3, 3.3+ and WooCommerce 2.1.1+

Tested up to: 4.3.1

Stable tag: 3.5.9.1

== Description ==

This plugin enables to pay with webpay plus ( Chile ) in Woocommerce 

Wiki Home : https://bitbucket.org/ctala/woocommerce-webpay/wiki/Home

Más información sovre webpay  : https://www.cristiantala.cl/tag/webpay/

Ya ha sido bastante tiempo en el que me han preguntado por esto en los comentarios y al fin decidí liberar el código de manera OpenSource.

Algunos de ustedes se preguntarán el por qué no cobro por este plugin, tomando en consideración que mucha gente ofrece una buena cantidad por el servicio. Bueno, la respuesta es simple; Creo que junto a la comunidad de desarrolladores podemos mejorar mucho más este código y así todos tener un plugin de una calidad mucho mayor que por la que podríamos pagar ( Suena bien no ? ).


A considerar :

* El Código se distribuye bajo GPLV3.
* Este código YA es compatible con la última versión de WooCommerce (Version > 2.1.12)
* La última versión de este código, no es compatible con las versiones antiguas de woocommerce ( < 2.1.1 )
* El código no presenta garantía de ningún tipo.
* Se puso a disposición un Wiki para la instalación
* Se puso a disposición un BugTracker para que podamos ver los problemas que vayan saliendo en conjunto.
* Se asume que ya se hizo la configuración de los CGI para Transbank
* Si necesitan los CGI de Transbank los pueden descargar de : https://bitbucket.org/ctala/webpayconector. Ya deberían estar listos para usarlos. Con estos parto para generar un eCommerce.
* Para los que quieren mejorar el código, y nunca han ocupado una herramienta de control de versiones, les recomiendo que lean sobre GIT, además de lo que es un FORK.



== Changelog ==

= V3.7.0.0 =

* Se agrega mensaje de caducidad del servicio.

= V3.5.9.1 =

Se agrega la información de la fecha de la transacción a la página final.

= V3.5.7.0 =
Se agregan más validaciones al monto de la transacción.
= V3.5.4.1 =
 Se cambia la palabra Fracasada por Rechazada en la página de fracaso.
= V3.5.4.0 =
 Se cambia el formato de versiones. Se arregla pantalla blanca al actualizar woocommerce.


= 3.0.5 =
* Se arregla bug con los permalinks.

= 3.0.4 =
* Se agregan los datos del cliente en la información extra de la transacción.
* Se modifica el mensaje de error para agregar débito y crédito.

= 3.0.3 =
* Se elimina mensaje de contacto al banco en caso de error.
* Se elimina la información extra en caso de failure.

= 3.0.2 =

* Se agregan las políticas de devoluciones.
* Se elimina frase repetida en fracaso

= 3.0.1 =

Se agrega al mail de administrador y cliente el método de pago cuando es WebPayPlus.

= 3.0 =

ReMake del plugin para compatibilidad de WooCommerce 2.1

= 2.4 = 

Versión Certificada

= 2.3.2 =

Se valida que la página de éxito venga de un POST.
Se arregla problema de validación del KEY


= 2.3.1 =

Merge con el código de Felipe Egas

= 2.3 =

Se agregan los templates para los pagos con webpay.
- Template para la página de Agradecimientos
- Template para la página de detalles de la orden

Esto se hizo debido a que Transbank pide información que antes no pedía en la página de éxito.


= 2.2 =

Se establece el short-code [webpay-thankyou] para realizar las validaciones necesarias por parte de transbank. Es necesario cambiar en la página de recepción del pedido por [woocommerce-thankyou]. 


= 2.1.9 =
Se cambian y estandarizan las recepciones de los status según el manual de woocommerce.
*Pending – Order received (unpaid)
*Failed – Payment failed or was declined (unpaid)
*Processing – Payment received and stock has been reduced- the order is awaiting fulfilment
*Completed – Order fulfilled and complete – requires no further action
*On-Hold – Awaiting payment – stock is reduced, but you need to confirm payment
*Cancelled – Cancelled by an admin or the customer – no further action required
*Refunded – Refunded by an admin - no further action required

= 2.1.8 =
Arreglada posible duplicidad cuando la orden pasa a on-hold.

= 2.1.7 = 
Arreglado el error de callback con las direcciones web largas. Se usan los shortlinks para no tener problemas.

= 2.1.6 =
Modificado para que funcionara con versiones más antiguas de php.

= 2.1.3 =
Agregado Mensaje en Notas del pedido.

= 2.1.2 =
Arreglada la creación automática del directorio común.
Ahora la carpeta común estará bajo uploadas para que no se borre al actualizar el plugin.

= 2.1 =
Added the eCommerce Variables.

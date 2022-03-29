<?php

/**
 * @author        TechSolitaire
 * @testedwith    WooCommerce 3.5.2
 */
define('SOAP_URL', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('SERVICE_URL', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('SOAPAction_CREATE_ORDER', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('SOAPAction_GetOrderStatuses', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('SOAPAction_GetOrderStatusesId', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('ADDRESSING_URL', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('GDDIRECT_USER', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('GDDIRECT_PASSWORD', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

/**
 * Add Snippet to WC woocommerce_thank_you_page()
 */
if ( ! function_exists( 'woocommerce_thank_you_order_send' ) ) {
    /**
     * Send Data to 3PL Admin Panel Via API.
     */
    function woocommerce_thank_you_order_send() {
        global $wp;
        $pagename = get_query_var( 'pagename' );
        $order_id = get_query_var( 'order-received' );
        $order    = wc_get_order( $order_id );
        if ( $pagename === 'checkout' && ! empty( $order_id ) ) {
            $country = WC()->countries->countries[ $order->get_shipping_country() ];
            $billing_country = $order->get_billing_country();
            $shipping_country = $order->get_shipping_country();
            $currency = $order->get_currency();
            $discount_tax = $order->get_discount_tax();
            $discount = $order->get_discount_to_display();
            $discount_total = $order->get_discount_total();
            $order_date = date(DATE_ATOM, strtotime($order->order_date));
            $phone = !empty($order->shipping_phone) ? $order->shipping_phone : $order->billing_phone;
            $shipping_email = !empty($order->shipping_email) ? $order->shipping_email : $order->get_billing_email();
            $customer_note = $order->get_customer_note();
            foreach ( $order->get_items() as $item_id => $item ) {
                $product_id = $item->get_product_id();
                $variation_id = $item->get_variation_id();
                $product = $item->get_product();
                $name = $item->get_name();
                $sku = array_slice(explode('-', $product->get_sku()), -1)[0];
                $threeplsku = array_slice(explode('-', $product->get_sku()), 0)[0];
                $quantity = $item->get_quantity();
                $subtotal = $item->get_subtotal();
                $total = $item->get_total();
                $tax = $item->get_subtotal_tax();
                $taxclass = $item->get_tax_class();
                $taxstat = $item->get_tax_status();
                $allmeta = $item->get_meta_data();
                $somemeta = $item->get_meta( '_whatever', true );
                $type = $item->get_type();
                $weight = $product->get_weight();
                $lineItem[] = array(
                    'DiscountAmountExclTax'=> '0',
                    'DiscountAmountInclTax'=> '0',
                    'ExternalReference'=> $sku,
                    'ItemWeight'=> $weight ,
                    'OrderLineNo'=> '',
                    'PriceExclTax'=> $subtotal,
                    'PriceInclTax'=> $total,
                    'Quantity'=> $quantity,
                    'UnitPriceExclTax'=> $subtotal,
                    'UnitPriceInclTax'=> $total,
                );
            }
            if ( (  is_wc_endpoint_url( 'order-received' ) && ( $billing_country == 'CA' ) ) || ( is_wc_endpoint_url( 'order-received' ) && ( $shipping_country == 'CA' ) ) ) {
                $client = new SoapClient(SOAP_URL, [
                    "soap_version" => SOAP_1_2,
                    "SOAPAction"=>SOAPAction_CREATE_ORDER,
                    "Endpoint"=>SERVICE_URL,
                    'cache_wsdl' => WSDL_CACHE_NONE, // WSDL_CACHE_MEMORY
                    'trace' => 1,
                    'exception' => 1,
                    'keep_alive' => false,
                    'connection_timeout' => 500000
                ]);
                $action = new \SoapHeader(ADDRESSING_URL, 'Action', SOAPAction_CREATE_ORDER);
                $to = new \SoapHeader(ADDRESSING_URL, 'To', SERVICE_URL);
                $client->__setSoapHeaders([$action, $to]);
                $parameters = array(
                    'order'=> array(
                        'AdditionalPO' => '',
                        'AffiliateId' => '',
                        'BillingAddress' => array(
                            'Address1' => $order->get_billing_address_1(),
                            'Address2' =>$order->get_billing_address_2(),
                            'City' => $order->get_billing_city(),
                            'Company' =>$order->get_billing_company(),
                            'Country' => $country,
                            'Email' =>$order->get_billing_email(),
                            'FirstName' => $order->get_billing_first_name(),
                            'LastName' =>$order->get_billing_last_name(),
                            'PhoneNumber' =>$order->get_billing_phone(),
                            'PostalCode' => $order->get_billing_postcode(),
                            'StateProvince' =>$order->get_billing_state(),
                        ),
                        'CurrencyCode' => $order->get_currency(),
                        'CustomValuesXml' => '',
                        'Errors' => array(
                            'ErrorMessage' =>'' ,
                            'ErrorCode' => '',
                            'Reason' => '',
                        ),
                        'ExternalOrderReference' => $order->get_order_number().'-'.$sku,
                        'Id' => $order->get_order_number(),
                        // Get and Loop Over Order Items
                        'LineItems' => array(
                            'WSLineItem'   =>   $lineItem,
                        ),
                        'OrderDiscount' => '0',
                        'OrderNote' => $customer_note,
                        'OrderStatus' => $order->get_status(),
                        'OrderTax' => '0',
                        'OrderTotal' => $order->get_total(),
                        'OrderType' => '',
                        'PaidDateUtc' => $order_date,
                        'PaymentMethod' => $order->get_payment_method(),
                        'PaymentNotRequired' => 'false',
                        'PaymentStatus' => 'Paid',
                        'ShippingAddress' => array(
                            'Address1' => $order->get_shipping_address_1(),
                            'Address2' =>$order->get_shipping_address_2(),
                            'City' => $order->get_shipping_city(),
                            'Company' =>$order->get_shipping_company(),
                            'Country' => $country,
                            'Email' =>$shipping_email,
                            'FirstName' =>$order->get_shipping_first_name(),
                            'LastName' =>$order->get_shipping_last_name(),
                            'PhoneNumber' =>$phone,
                            'PostalCode' => $order->get_shipping_postcode(),
                            'StateProvince' => $order->get_shipping_state(),
                        ),
                        'ShippingAddressSameAsBilling' => '',
                        'ShippingMethod' => '',
                        'ShippingStatus' => 'NotYetShipped',
                    ),


                    'userName' => GDDIRECT_USER,
                    'userPassword' => GDDIRECT_PASSWORD,
                    'options'=> array(
                        'SendEmailToBillingEmail'=> $order->get_billing_email(),
                        'SendEmailToShippingEmail'=>$shipping_email,
                    ),

                );
                $response = $client->__soapCall("CreateOrder", array($parameters));
                $error = $response->CreateOrderResult->Errors->ErrorMessage->Reason;
                $gd_id = $response->CreateOrderResult->Id;
                if(!empty($error)){
                    global $woocommerce;
                    // Create a mailer
                    $mailer = $woocommerce->mailer();
                    $message_body =  sprintf( __( '%s' ), $error );
                    $message = $mailer->wrap_message(
                    // Message head and message body.
                        sprintf( __( 'Order %s not processed Because' ), $order->get_order_number() ), $message_body );
                    // Cliente email, email subject and message.
                    $mailer->send( 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', sprintf( __( 'GD Order %s is not processed' ), $order->get_order_number() ), $message );
                }
            }
        }
    }
}
add_action( 'woocommerce_thankyou', 'woocommerce_thank_you_order_send' );

/* GDDIRECT code Start to add menu on admin bar */

add_action('admin_bar_menu', 'add_item', 100);

function add_item( $admin_bar ){
    global $pagenow;
    $admin_bar->add_menu( array( 'id'=>'gd-fetch-cron','title'=>'Fetch GD Orders','href'=>'#' ) );
}

/* Here you trigger the ajax handler function using jQuery */

add_action( 'admin_footer', 'cache_purge_action_js' );

function cache_purge_action_js() { ?>
    <script type="text/javascript" >
        jQuery("li#wp-admin-bar-gd-fetch-cron .ab-item").on( "click", function() {
            var data = {
                'action': 'gd_fetch_order_id',
            };
            /* since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php */
            jQuery.post(ajaxurl, data, function(response) {
                alert('Orders are fetched');
                window.location.reload();
            });
        });
    </script> <?php
}

/* Here you hook and define ajax handler function */

add_action( 'wp_ajax_gd_fetch_order_id', 'gd_fetch_order_id' );

function gd_fetch_order_id() {
    $client = new SoapClient(SOAP_URL, [
        "soap_version" => SOAP_1_2,
        "SOAPAction"=>SOAPAction_GetOrderStatuses,
        'cache_wsdl' => WSDL_CACHE_NONE, // WSDL_CACHE_MEMORY
        'trace' => 1,
        'exception' => 1,
        'keep_alive' => false,
        'connection_timeout' => 500000
    ]);
    $action = new \SoapHeader(ADDRESSING_URL, 'Action', SOAPAction_GetOrderStatuses);
    $to = new \SoapHeader(ADDRESSING_URL, 'To', SERVICE_URL);
    $client->__setSoapHeaders([$action, $to]);
    $start_date = date(DATE_ATOM, strtotime("now"));
    $fetch_hours = get_field('fetch_order_time','options');
    if( ! empty( $fetch_hours ) && $fetch_hours > 12 ) {
        $end_date = date(DATE_ATOM, strtotime("-".$fetch_hours."hours"));
    } else {
        $end_date = date(DATE_ATOM, strtotime("-12 hours"));
    }
    $parameters = array(
        'orderFilter' => array (
            'DateRange' => array (
                'EndDate' => $start_date,
                'StartDate' => $end_date,
            ),
            'OrderStatusOption' => 'Complete',
            'ShippingStatusOption' => 'Shipped',
        ),
        'userName' => GDDIRECT_USER ,
        'userPassword' => GDDIRECT_PASSWORD,
    );

    $response = $client->__soapCall("GetOrderStatuses", array($parameters));
    $order_data = $response->GetOrderStatusesResult->OrderStatuses->WSOrderStatus;
    foreach ($order_data as $data_key => $data){
        if( $data->OrderStatus == 'Complete' && $data->ShippingStatus == 'Shipped' ){
            $client = new SoapClient(SOAP_URL, [
                "soap_version" => SOAP_1_2,
                "SOAPAction"=>SOAPAction_GetOrderStatusesId,
                'cache_wsdl' => WSDL_CACHE_NONE, // WSDL_CACHE_MEMORY
                'trace' => 1,
                'exception' => 1,
                'keep_alive' => false,
                'connection_timeout' => 500000
            ]);
            $action = new \SoapHeader(ADDRESSING_URL, 'Action', SOAPAction_GetOrderStatusesId);
            $to = new \SoapHeader(ADDRESSING_URL, 'To', SERVICE_URL);
            $client->__setSoapHeaders([$action, $to]);
            $parameters = array('Id' => $data->Id,
                'userName' => GDDIRECT_USER,
                'userPassword' => GDDIRECT_PASSWORD
            );
            $response = $client->__soapCall("GetOrderStatusById", array($parameters));
            $shipment = $response;
            $order_id = array_slice(explode('-', $shipment->GetOrderStatusByIdResult->OrderStatuses->WSOrderStatus->ExternalOrderReference ) , 0)[0];
            $orderid_exist = wc_get_order($order_id);
            $raw_order_id = $orderid_exist->id;
            if( empty ( $orderid_exist ) ){
                echo 'This id not exist';
            }else{
                $live_order_id = $raw_order_id ;
                $order_exist_tracking = wc_get_order($order_id);
                $order_data = $order_exist_tracking->get_meta('_wc_shipment_tracking_items');
            }
            if($live_order_id == $order_id && empty($order_data[0]['tracking_number'])){
                $shipment = $shipment->GetOrderStatusByIdResult->OrderStatuses->WSOrderStatus->Shipments;
                foreach($shipment as $ship) {
                    if(array_key_exists('1',$ship)){
                        $mship = $shipment->WSShipment;
                        foreach($mship as $sship){
                            $order_tracking_number = $sship->TrackingNumber;
                            $array_tracking_number = explode(';',$order_tracking_number);
                            $order_tracking_url =  $sship->TrackingNumberURL;
                            $array_tracking_url = explode('|',$order_tracking_url);
                            $order_shipment_date = $sship->ShippedDate;
                            $order_shipment_carrier = $sship->Carrier;
                            if ( function_exists( 'wc_st_add_tracking_number' ) ) {
                                for($i=0;$i<count($array_tracking_number);$i++) {
                                    wc_st_add_tracking_number($live_order_id, $array_tracking_number[$i], $order_shipment_carrier, date(DATE_ATOM, $order_shipment_date), $array_tracking_url[$i]);
                                }
                                $order = new WC_Order($live_order_id);
                                if (!empty($live_order_id)) {
                                    $order->update_status( 'completed' );
                                }
                            }
                        }
                    } else {
                        $order_tracking_number = $ship->TrackingNumber;
                        $array_tracking_number = explode(';',$order_tracking_number);
                        $order_tracking_url =  $ship->TrackingNumberURL;
                        $array_tracking_url = explode('|',$order_tracking_url);
                        $order_shipment_date = $ship->ShippedDate;
                        $order_shipment_carrier = $ship->Carrier;
                        if ( function_exists( 'wc_st_add_tracking_number' ) ) {
                            for($i=0;$i<count($array_tracking_number);$i++) {
                                wc_st_add_tracking_number($live_order_id, $array_tracking_number[$i], $order_shipment_carrier, date(DATE_ATOM, $order_shipment_date), $array_tracking_url[$i]);
                            }
                            $order = new WC_Order($live_order_id);
                            if (!empty($live_order_id)) {
                                $order->update_status( 'completed' );
                            }
                        }
                    }

                }
                global $woocommerce;
                // Create a mailer
                $mailer = $woocommerce->mailer();
                $message_body = __( 'Order Processed from GD' );
                $message = $mailer->wrap_message(
                // Message head and message body.
                    sprintf( __( 'GD Order %s Fetched' ), $order->get_order_number() ), $message_body );
                // Cliente email, email subject and message.
                $mailer->send( 'xxxxxxxxxxxxxxxxxxx', sprintf( __( 'GD Order %s received' ), $order->get_order_number() ), $message );
            }
        }
    }
    exit();
}

function gd_process_loss_order(){
    $order_numbers = get_field('number_of_order','options');
    $startDate = date('F j, Y', strtotime('+12 hours'));
    $endDate = get_field('create_order_time','options');
    if( ! empty( $endDate ) && $endDate > 12 ) {
        $endDate = date('F j, Y', strtotime("-".$endDate."hours"));
    } else {
        $endDate = date('F j, Y', strtotime("-12 hours"));
    }
    $customer_orders = get_posts( array(
        'numberposts'    => ( $order_numbers > 10 )  ? $order_numbers : 10,
        'post_type' => 'shop_order',
        'post_status' => 'wc-processing',
        'date_query' => array(
            'after' => $endDate,
            'before' => $startDate
        )
    ) );
    foreach ($customer_orders as $order_data){
        $order_id = $order_data->ID;
        $order = new WC_Order($order_id);
        if($order->has_status('processing')){
            $country = WC()->countries->countries[ $order->get_shipping_country() ];
            $billing_country = $order->get_billing_country();
            $shipping_country = $order->get_shipping_country();
            $currency = $order->get_currency();
            $discount_tax = $order->get_discount_tax();
            $discount = $order->get_discount_to_display();
            $discount_total = $order->get_discount_total();
            $order_date = date(DATE_ATOM, strtotime($order->order_date));
            $phone = !empty($order->shipping_phone) ? $order->shipping_phone : $order->billing_phone;
            $shipping_email = !empty($order->shipping_email) ? $order->shipping_email : $order->get_billing_email();
            $lineItem=array();
            foreach ( $order->get_items() as $item_id => $item ) {
                $product_id = $item->get_product_id();
                $variation_id = $item->get_variation_id();
                $product = $item->get_product();
                $name = $item->get_name();
                $sku = array_slice(explode('-', $product->get_sku()), -1)[0];
                $threeplsku = array_slice(explode('-', $product->get_sku()), 0)[0];
                $quantity = $item->get_quantity();
                $subtotal = $item->get_subtotal();
                $total = $item->get_total();
                $tax = $item->get_subtotal_tax();
                $taxclass = $item->get_tax_class();
                $taxstat = $item->get_tax_status();
                $allmeta = $item->get_meta_data();
                $somemeta = $item->get_meta( '_whatever', true );
                $type = $item->get_type();
                $weight = $product->get_weight();
                $lineItem[] = array(
                    'DiscountAmountExclTax'=> '0',
                    'DiscountAmountInclTax'=> '0',
                    'ExternalReference'=> $sku,
                    'ItemWeight'=> $weight ,
                    'OrderLineNo'=> '',
                    'PriceExclTax'=> $subtotal,
                    'PriceInclTax'=> $total,
                    'Quantity'=> $quantity,
                    'UnitPriceExclTax'=> $subtotal,
                    'UnitPriceInclTax'=> $total,
                );
            }
        }
        if ( (  $order->has_status('processing')  && ( $billing_country == 'CA' ) ) || ( $order->has_status('processing')  && ( $shipping_country == 'CA' ) ) ) {
            $client = new SoapClient(SOAP_URL, [
                "soap_version" => SOAP_1_2,
                "SOAPAction"=>SOAPAction_CREATE_ORDER,
                "Endpoint"=>SERVICE_URL,
                'cache_wsdl' => WSDL_CACHE_NONE, // WSDL_CACHE_MEMORY
                'trace' => 1,
                "stream_context"=>stream_context_create(
                    array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    )),
                'exception' => true,
                'keep_alive' => false,
                'connection_timeout' => 500000
            ]);
            $action = new \SoapHeader(ADDRESSING_URL, 'Action', SOAPAction_CREATE_ORDER);
            $to = new \SoapHeader(ADDRESSING_URL, 'To', SERVICE_URL);
            $client->__setSoapHeaders([$action, $to]);
            $parameters = array(
                'order'=> array(
                    'AdditionalPO' => '',
                    'AffiliateId' => '',
                    'BillingAddress' => array(
                        'Address1' => $order->get_billing_address_1(),
                        'Address2' =>$order->get_billing_address_2(),
                        'City' => $order->get_billing_city(),
                        'Company' =>$order->get_billing_company(),
                        'Country' => $country,
                        'Email' =>$order->get_billing_email(),
                        'FirstName' => $order->get_billing_first_name(),
                        'LastName' =>$order->get_billing_last_name(),
                        'PhoneNumber' =>$order->get_billing_phone(),
                        'PostalCode' => $order->get_billing_postcode(),
                        'StateProvince' =>$order->get_billing_state(),
                    ),
                    'CurrencyCode' => $order->get_currency(),
                    'CustomValuesXml' => '',
                    'Errors' => array(
                        'ErrorMessage' =>'' ,
                        'ErrorCode' => '',
                        'Reason' => '',
                    ),
                    'ExternalOrderReference' => $order->get_order_number().'-'.$sku,
                    'Id' => $order->get_order_number(),
                    // Get and Loop Over Order Items
                    'LineItems' => array(
                        'WSLineItem'   =>   $lineItem,
                    ),
                    'OrderDiscount' => '0',
                    'OrderNote' => '',
                    'OrderStatus' => $order->get_status(),
                    'OrderTax' => '0',
                    'OrderTotal' => $order->get_total(),
                    'OrderType' => '',
                    'PaidDateUtc' => $order_date,
                    'PaymentMethod' => $order->get_payment_method(),
                    'PaymentNotRequired' => 'false',
                    'PaymentStatus' => 'Paid',
                    'ShippingAddress' => array(
                        'Address1' => $order->get_shipping_address_1(),
                        'Address2' =>$order->get_shipping_address_2(),
                        'City' => $order->get_shipping_city(),
                        'Company' =>$order->get_shipping_company(),
                        'Country' => $country,
                        'Email' =>$shipping_email,
                        'FirstName' =>$order->get_shipping_first_name(),
                        'LastName' =>$order->get_shipping_last_name(),
                        'PhoneNumber' =>$phone,
                        'PostalCode' => $order->get_shipping_postcode(),
                        'StateProvince' => $order->get_shipping_state(),
                    ),
                    'ShippingAddressSameAsBilling' => 'true',
                    'ShippingMethod' => '',
                    'ShippingStatus' => 'NotYetShipped',
                ),


                'userName' => GDDIRECT_USER,
                'userPassword' => GDDIRECT_PASSWORD,
                'options'=> array(
                    'SendEmailToBillingEmail'=> $order->get_billing_email(),
                    'SendEmailToShippingEmail'=>$shipping_email,
                ),

            );
            $response = $client->__soapCall("CreateOrder", array($parameters));
            $error = $response->CreateOrderResult->Errors->ErrorMessage->Reason;
            $gd_id = $response->CreateOrderResult->Id;
            if(!empty($error)){
                global $woocommerce;
                // Create a mailer
                $mailer = $woocommerce->mailer();
                $message_body =  sprintf( __( '%s' ), $error );
                $message = $mailer->wrap_message(
                // Message head and message body.
                    sprintf( __( 'Order %s not processed Because' ), $order->get_order_number() ), $message_body );
                // Cliente email, email subject and message.
                $mailer->send( 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', sprintf( __( 'GD Order %s is not processed' ), $order->get_order_number() ), $message );
            }
        }
    }
}

/* GDDIRECT code Start to add menu on admin bar */

add_action('admin_bar_menu', 'process_item', 100);

function process_item( $admin_bar ){
    global $pagenow;
    $admin_bar->add_menu( array( 'id'=>'gd-process-cron','title'=>'Create GD Orders','href'=>'#' ) );
}

/* Here you trigger the ajax handler function using jQuery */

add_action( 'admin_footer', 'process_item_script' );

function process_item_script() { ?>
    <script type="text/javascript" >
        jQuery("li#wp-admin-bar-gd-process-cron .ab-item").on( "click", function() {
            var data = {
                'action': 'gd_process_loss_order',
            };
            /* since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php */
            jQuery.post(ajaxurl, data, function(response) {
                alert('Orders are Processed');
                window.location.reload();
            });
        });
    </script> <?php
}

/* Here you hook and define ajax handler function */

add_action( 'wp_ajax_gd_process_loss_order', 'gd_process_loss_order' );
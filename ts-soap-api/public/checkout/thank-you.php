<?php

    if ( ! function_exists( 'woocommerce_thank_you_order_send' ) ) {
        /**
        * Send Data to 3PL Admin Panel Via API.
        */
        function woocommerce_thank_you_order_send() {
            die('tetstts');
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
                    $mailer->send( 'xxxxxxxxxxxxxxxx', sprintf( __( 'GD Order %s is not processed' ), $order->get_order_number() ), $message );
                    }
                }
            }
        }
    }
add_action( 'woocommerce_thankyou', 'woocommerce_thank_you_order_send' );
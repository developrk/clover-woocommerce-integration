add_action( 'template_redirect', 'wildwood_create_order_in_clover' );

        function wildwood_create_order_in_clover (){
            if(is_page(11)){ // your checkout page id

            if( !is_wc_endpoint_url( 'order-received' ) || empty( $_GET['key'] ) ) {
                return;
            }
            
            $order_id = wc_get_order_id_by_order_key( $_GET['key'] );
            //$order = wc_get_order( $order_id );
            $order = wc_get_order( $order_id );
				//echo "<pre>";print_r($order);die;
            $payment_order = $order->get_payment_method();
            //echo $payment_order; die('jdsfhdf');
            $total_price2 = $order->get_total();
            $order_note = $order->get_customer_note();
            $total_price = str_replace('.', '',$total_price2);
            $currency = $order->currency;
            $order_type = ucfirst(get_post_meta( $order_id, '_wfs_service_type', true ));
            $order_time = get_post_meta( $order_id, '_wfs_service_time', true ); 
            //$add_on =  get_post_meta( $order_id, '_addon_items', true );
            
           
            $order_meta_data = get_post_meta($order_id);
            $items = $order->get_items();
            //echo "<pre>";print_r($order_meta_data);die('hgvfgv');
            //$order_spl_note = get_post_meta( $order_id, '_special_note', true );
            $address = $order->data['billing']['city'];
            $f_name = $order->get_billing_first_name();
            $number = $order->get_billing_phone();
            $street_ad = $order->data['billing']['address_1'];
            $order_zip = $order->data['billing']['postcode'];
            $payment_order = $order->get_payment_method();
            $street_ad .= " ".$address." ".$order_zip;
            $delivery_exists = strpos(strtolower($order_type), 'delivery') !== false;
               $note = "This order is from pizzaheaven online  \n" . $f_name . " ". $order_type . " " . $order_time. "\n" .$number;
                //$note .= $number;
               if ($delivery_exists) {
                   $note .= " - Delivery address -> ".$street_ad;
               }
                if($payment_order){
                    if($payment_order == 'clover_payments'){
                        $note .= " Payment - Paid Via Card";
                    }
                    if($payment_order == 'cod'){
                        $note .= " Payment - Cash on Delivery";
                    }
                }
                if($order_note){
                     $note .= " -Cutstomer Note:".$order_note;
                }
            
            
            
           
            $api_url = 'https://api.clover.com/v3/merchants/merchant_id/orders';
            $api_token = '';

            $data = array(
                    "employee" => array(
                        "id" => ""
                    ),
                    "title" => $order_id,
                    "total" => $total_price,
                    "note" => $note,
                    "state" => "PAID"
                );
            $json_data = json_encode($data);
            //echo "<pre>";print_r($data);die;
            $title = $data['title'];
            // Create cURL request
            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_token,
            ]);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $order_status = $order->get_status();
            //echo $http_code; 
            //die('test');
            if ($http_code === 200 && $order_status != 'failed') {
                $responseData = json_decode($response, true);
                //echo "{".$responseData['id']."}";die;
                foreach ($order->get_items() as $item_id => $item) {
                    if (isset($item)){
                        
                    
                        $product = wc_get_product($item->get_product_id());
                        $variation_id = $item->get_variation_id(); // Get the variation ID
                        $variation_data = wc_get_product_variation_attributes($variation_id); // Get variation data
                            // Add variation data to the note
                        $attr_quantity = $variation_data['attribute_quantity'];
                        
                        //$note .= "\nVariation: " . implode(', ', $variation_data);
                        //echo "<pre>";print_r($note); die('variation');
                        $spl_not = wc_get_order_item_meta( $item_id, '_special_note', true); // Variation ID
                        $add_on1 =  wc_get_order_item_meta( $item_id, '_addon_items', true);
                        $add_on_data = [];

                        foreach ($add_on1 as $add_on2) {
                            $add_on_data[] = $add_on2['addon_item']['name'];
                            
                            //echo "<pre>"; print_r($addon_item);
                        }
                        $add_on_use = implode(",", array_map('ucfirst', $add_on_data));
                        //echo "pre";print_r($add_on_use);die('test');
                        //echo "<pre>";print_r($add_on1);
                        $p_name = $product->name;
                        if ($variation_data) {
                                   $p_name .= ' (Option Selected: '. implode(', ', $variation_data) . ')';
                           
                               }
                        if($add_on_use){
                            $spl_not .= ' (Add On Selected: '. $add_on_use . ')';
                        }
                       
                        $quantity = $item->get_quantity();
                        $price_item = $product->get_price();
                        $total_price1 = str_replace('.', '',$price_item);
                        if ($quantity > 0) {
                            for ($i = 0; $i < $quantity; $i++) {
                                
                                if (isset($responseData['id'])) {
                                    $order_number = $responseData['title'];
                                        $orderId = $responseData['id'];
                                    $apiUrl = 'https://api.clover.com/v3/merchants/merchant_id/orders/'.$orderId.'/bulk_line_items';
                                    $accessToken = '';
                                    
                                    $data = [
                                        'items' => [
                                            [
                                                'item' => [
                                                    'id' => 'gfgfggfgfg'
                                                ],
                                                'printed' => 'true',
                                                'exchanged' => 'false',
                                                'refunded' => 'false',
                                                'refund' => [
                                                    'transactionInfo' => [
                                                        'isTokenBasedTx' => 'false',
                                                        'emergencyFlag' => 'false'
                                                    ]
                                                ],
                                                'isRevenue' => 'false',
                                                'name' => $p_name,
                                                'price' => $total_price1,
                                                'note' => $spl_not
                                            ],
                                        ]
                                    ];
                                
                                    $dataJson = json_encode($data);
                                    $ch = curl_init();
                                    curl_setopt($ch, CURLOPT_URL, $apiUrl);
                                    curl_setopt($ch, CURLOPT_POST, 1);
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                        'Content-Type: application/json',
                                        'Authorization: Bearer ' . $accessToken
                                    ]);
                                    $response = curl_exec($ch);
                                    if (curl_errno($ch)) {
                                        echo 'cURL error: ' . curl_error($ch);
                                    }
                                    curl_close($ch);
                                   // echo "<div id='order_alert_div' style='position: absolute;right: 0;background: green;padding: 10px;color: #fff;border-radius: 15px;'>Order created successfully. Order ID: $orderId</div>";
                                }else{
                                    echo "Not yet created line items";
                                }
                               }
                        }
                    }else{
                        echo "Not found";
                    }
                  }
                
                
                
                $data = [
                    'orderRef' => [
                        'id' => $responseData['id']
                    ]
                ];

                // Encode the data as JSON
                $jsonData = json_encode($data);
                $accessToken = '';
                // Define the API endpoint
                $apiUrl = 'https://api.clover.com/v3/merchants/merchant_id/print_event'; // Replace mId with your actual merchant ID

                // Set up cURL
                $ch = curl_init($apiUrl);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'content-type: application/json',
                    'Authorization: Bearer ' . $accessToken
                ]);

                // Execute cURL request
                $response = curl_exec($ch);
				//echo "<pre>";print_r($response);die('test');

                // Check for cURL errors
                if (curl_errno($ch)) {
                    echo 'cURL error: ' . curl_error($ch);
                }

                // Close cURL session
                curl_close($ch);
 
                
                echo "<script>
                          
                                window.location.href = 'https://www.yousite.com/my-account/orders/';
                           
                        </script>";
                 
                //wp_safe_redirect('https://yousite.in/order-online/');
                exit();
                
                }else{
                    echo "Not yet";
                }
        

}
}	

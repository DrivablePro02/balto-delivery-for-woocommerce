<?php
add_action('woocommerce_thankyou', 'send_appointment_data', 40, 1);

function send_appointment_data($order_id){

    $order = wc_get_order($order_id);
    
    $data = array(
        'order_id'              => $order->get_id(),
        'currency'              => $order->get_currency(),
        'discounted_price_tax'  => $order->get_discount_tax(),
        'discounted_price'      => $order->get_discount_to_display(),
    );

    $appt_fields = array(
        'category'  => '_category_id',
        'doctor'    => '_doctor_id',
        'service'   => '_service_id',
    );
    foreach($appt_fields as $key => $meta_value) {
        $meta_value = $order->get_meta($meta_value, true );

        if(!empty($meta_value)) {
            $data[$key] = $meta_value;
        }
    };

    $response = wp_remote_get('https://panel.mymeatshop.co.uk/webhooks', array(
        'method'        => 'POST',
        'body'          => json_encode($data),
        'timeout'       => 45,
        'headers'       => [
        'Content-type'  => 'application/json'
        ]
    ));

    if(is_wp_error($response)) error_log('error happened while sending order'. $order->get_id());
}



<?php

class DOTPAY_SMS extends WC_Payment_Gateway {

    function __construct() {
        $this->id = 'dotpay_sms';
        $this->method_title = __("Dotpay SMS", 'dotpay_sms');
        $this->description = __("Dotpay SMS Payment Gateway Plug-in form WooCommerce");
        $this->title = __("Dotpay SMS", 'dotpay_sms');
        $this->icon = null;
        $this->has_fields = true;

        $this->init_form_fields();
        $this->init_settings();

        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }
    }

    function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable / Disable', 'dotpay_sms'),
                'label' => __('Enable this payment gateway', 'dotpay_sms'),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'dotpay_sms'),
                'type' => 'text',
                'desc_tip' => __('Dotpay SMS'),
                'default' => __('Dotpay SMS', 'dotpay_sms')
            ),
            'description' => array(
                'title' => __('Description', 'dotpay_sms'),
                'type' => 'textarea',
                'desc_tip' => __('Dotpay SMS'),
                'default' => __('Dotpay SMS', 'dotpay_sms'),
                'css' => 'max-width: 350px'
            ),
            'dotpay_id' => array(
                'title' => __('Dotpay ID', 'dotpay_sms'),
                'type' => 'text',
                'desc_tip' => __('Enter Dotpay ID', 'dotpay_sms'),
            ),
            'service_name' => array(
                'title' => __('Dotpay service name', 'dotpay_sms'),
                'type' => 'text',
                'desc_tip' => __('Service name without prefix.', 'dotpay_sms')
            ),
            'vat' => array(
                'title' => __('VAT', 'dotpay_sms'),
                'type' => 'text',
                'desc_tip' => __('Tax', 'dotpay_sms')
            ),
//            'dotpay_ssl' => array(
//                'title' => __('Dotpay SSL', 'dotpay_sms'),
//                'type' => 'checkbox',
//                'desc_tip' => __('Check if you are using SSL to connect with Dotpay.')
//            ),
            'test_mode' => array(
                'title' => __('Test mode', 'dotpay_sms'),
                'type' => 'checkbox',
                'desc_tip' => __('Check if you are testing payment method')
            )
        );
    }

    function payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        ?>
        <p class="form-row form-row-first phone-input">
            <label style="display: block !important;" for="code_check"><?php echo __('Enter your code', 'dotpay_sms') ?> <span class="required">*</span></label>
            <input type="text" name="code_check"  />
        </p>
        <?php

    }

    private function get_post($name) {
        if (isset($_POST[$name])) {
            return filter_var($_POST[$name], FILTER_SANITIZE_STRING);
        }
        return null;
    }

    public function process_payment($order_id) {

        global $woocommerce;
        $customer_order = new WC_Order($order_id);

        if (!$this->check_needed_fields()) {
            return;
        }

        $data = $this->dotpay_code_check();
        $status = $data[0];
        $price = (float) $data[3];
        $total = $customer_order->get_total();
        $total_netto = $total / (1 + ($this->vat / 100 ));
        if ($this->test_mode) {
            $total_netto = 0;
        }

        if ($status && $total_netto == $price) {
            $customer_order->add_order_note(__('Order payed by Dotpay SMS.', 'dotpay_sms'));
            $customer_order->payment_complete();
            $woocommerce->cart->empty_cart();
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($customer_order),
            );
        } else {
            wc_add_notice('Kod wygasł lub został wprowadzony nieprawidłowo.', 'error');
            $customer_order->add_order_note('Error: Kod wygasł lub został wprowadzony nieprawidłowo.');
        }
    }

    public function dotpay_code_check() {
        $check = $this->get_post('code_check');
        //$id = 81599;
        $id = $this->dotpay_id;
        //$code = "XRAN";
        $code = $this->service_name;
        $type = "sms";
        $del = 0;

//      $handle = fopen("http://dotpay.pl/check_code.php?&check=" . $check . "&id=" . $id . "&code=" . $code . "&type=" . $type . "&del=" . $del, 'r');
//      $status = fgets($handle, 8);
//      fclose($handle);

        $request = array();
        $request['check'] = $check;
        $request['code'] = $code;
        $request['id'] = $id;
        $request['type'] = $type;
        $request['del'] = $del;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ssl.dotpay.pl/check_code_fullinfo.php");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        $response = curl_exec($ch);
        curl_close($ch);

        return explode("\n", $response);
    }

    public function validate_fields() {
        if (!$this->get_post('code_check') || strlen($this->get_post('code_check')) != 8) {
            wc_add_notice(__('Kod błędnie wpisany. Poprawny kod powinien zawierać 8 znaków.', 'dotpay_sms'), 'error');
            return false;
        }
        return true;
    }

    public function check_needed_fields() {
        if (!$this->get_post('code_check') || strlen($this->get_post('code_check')) != 8) {
            return false;
        }
        return true;
    }

}

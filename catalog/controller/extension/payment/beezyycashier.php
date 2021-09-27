<?php

/**
 *
 */
class ControllerExtensionPaymentBeezyycashier extends Controller
{
    /**
     * @return false
     */
    public function index()
    {
        $data['button_confirm'] = $this->language->get('button_confirm');

        $this->load->model('checkout/order');

        if (!isset($this->session->data['order_id'])) {
            return false;
        }

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $requestData = [];
        $requestData['reference'] = strval($this->session->data['order_id']);
        $requestData['amount'] = $order_info['total'];
        $requestData['currency'] = $order_info['currency_code'];
        $requestData['customer']['email'] = $order_info['email'];
        $requestData['customer']['name'] = $order_info['firstname'] . ' ' . $order_info['lastname'];
        $requestData['customer']['ip'] = $order_info['ip'];
        $requestData['urls']['success'] = $this->url->link('checkout/success', false);
        $requestData['urls']['fail'] = $this->url->link('checkout/failure', false);
        $requestData['urls']['notification'] = $this->url->link('extension/payment/beezyycashier/callback', false);
        $requestData['payment_method'] = $this->config->get('payment_beezyycashier_payment_method');

        $invoice = $this->createInvoice($requestData);

        //save extradata
        $this->load->model('extension/payment/beezyycashier');
        $extraData = [
            'reference' => $invoice['data']['reference'],
            'payment_id' => $invoice['data']['payment_id'],
        ];
        $extraData = json_encode($extraData, JSON_FORCE_OBJECT);
        $this->model_extension_payment_beezyycashier->setExtradata($this->session->data['order_id'], $extraData);

        $data['action'] = $invoice['data']['url'];

        return $this->load->view('extension/payment/beezyycashier', $data);
    }

    /**
     * @param $data
     * @return mixed
     */
    private function createInvoice($data)
    {
        $payload = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.beezyycashier.com/v1/payment/create");
        $this->request($ch);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        return json_decode($result, true);
    }

    /**
     * @param $payment_id
     * @return mixed
     */
    private function checkIPN($payment_id)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.beezyycashier.com/v1/payment/$payment_id");
        $this->request($ch);
        $result = curl_exec($ch);
        $result = json_decode($result, true);
        return $result['data'];
    }

    /**
     *
     */
    public function callback()
    {
        $this->response->addHeader('Content-Type: application/json');
        $result = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postedData = json_decode(file_get_contents('php://input'), true);
            if (!is_array($postedData)) {
                $postedData = [];
                $result['message'] = 'Invalid IPN';
            }
            if (!empty($postedData)) {
                $this->load->model('checkout/order');

                $order_info = $this->model_checkout_order->getOrder($postedData['reference']);
                $checkIPN = $this->checkIPN($postedData['payment_id']);
                if ($order_info) {
                    if ($checkIPN['amount'] == $postedData['amount'] && $checkIPN['currency'] == $postedData['currency'] && $checkIPN['status'] == $postedData['status'] && $checkIPN['reference'] == $postedData['reference']) {
                        $success_ipn = true;
                    } else {
                        $success_ipn = false;
                        $result['message'] = 'Invalid IPN Data';
                    }
                } else {
                    $success_ipn = false;
                    $result['message'] = 'Invalid Order';
                }
                if (!empty($checkIPN)) {
                    if ($success_ipn && $order_info['total'] == $postedData['amount'] && $order_info['currency_code'] == $postedData['currency'] && $postedData['reference'] == $order_info['order_id']) {
                        if ($postedData['status'] == 'success') {
                            $this->model_checkout_order->addOrderHistory($postedData['reference'], $this->config->get('payment_beezyycashier_order_status_id'));
                        } else {
                            $this->model_checkout_order->addOrderHistory($postedData['reference'], $this->config->get('payment_beezyycashier_order_fail_status'));
                        }
                        $result['message'] = 'Success';
                    }
                }
            }

        } else {
            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 405 Method Not Allowed');
            $result['message'] = 'Method Not Allowed';
        }
        $this->response->setOutput(json_encode($result));
    }

    /**
     * @param $ch
     */
    private function request($ch): void
    {
        $headers = array();
        $headers[] = "Accept: application/json";
        $headers[] = "Content-Type: application/json";
        $headers[] = "Cache-Control: no-cache";
        $headers[] = "Authorization: Bearer " . $this->config->get('payment_beezyycashier_secret_key');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    }
}
<?php

class ControllerExtensionPaymentBeezyycashier extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/beezyycashier');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->request->post['payment_beezyycashier_pm'] = serialize($this->request->post['payment_beezyycashier_pm']);
            $this->model_setting_setting->editSetting('payment_beezyycashier', $this->request->post);

        $this->response->redirect($this->url->link('extension/payment/beezyycashier', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['secret'])) {
            $data['error_secret'] = $this->error['secret'];
        } else {
            $data['error_secret'] = '';
        }

        if (isset($this->error['payment_method'])) {
            $data['error_payment_method'] = $this->error['payment_method'];
        } else {
            $data['error_payment_method'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/beezyycashier', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/beezyycashier', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_beezyycashier_secret_key'])) {
            $data['payment_beezyycashier_secret_key'] = $this->request->post['payment_beezyycashier_secret_key'];
        } else {
            $data['payment_beezyycashier_secret_key'] = $this->config->get('payment_beezyycashier_secret_key');
        }

        if (isset($this->request->post['payment_beezyycashier_pm'])) {
            $data['payment_beezyycashier_pm'] = $this->request->post['payment_beezyycashier_pm'];
        } else {
            $data['payment_beezyycashier_pm'] = unserialize($this->config->get('payment_beezyycashier_pm'));
        }

        if (isset($this->request->post['payment_beezyycashier_total'])) {
            $data['payment_beezyycashier_total'] = $this->request->post['payment_beezyycashier_total'];
        } else {
            $data['payment_beezyycashier_total'] = $this->config->get('payment_beezyycashier_total');
        }

        if (isset($this->request->post['payment_beezyycashier_order_status_id'])) {
            $data['payment_beezyycashier_order_status_id'] = $this->request->post['payment_beezyycashier_order_status_id'];
        } else {
            $data['payment_beezyycashier_order_status_id'] = $this->config->get('payment_beezyycashier_order_status_id');
        }

        if (isset($this->request->post['payment_beezyycashier_order_fail_status'])) {
            $data['payment_beezyycashier_order_fail_status'] = $this->request->post['payment_beezyycashier_order_fail_status'];
        } else {
            $data['payment_beezyycashier_order_fail_status'] = $this->config->get('payment_beezyycashier_order_fail_status');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_beezyycashier_geo_zone_id'])) {
            $data['payment_beezyycashier_geo_zone_id'] = $this->request->post['payment_beezyycashier_geo_zone_id'];
        } else {
            $data['payment_beezyycashier_geo_zone_id'] = $this->config->get('payment_beezyycashier_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_beezyycashier_status'])) {
            $data['payment_beezyycashier_status'] = $this->request->post['payment_beezyycashier_status'];
        } else {
            $data['payment_beezyycashier_status'] = $this->config->get('payment_beezyycashier_status');
        }

        if (isset($this->request->post['payment_beezyycashier_sort_order'])) {
            $data['payment_beezyycashier_sort_order'] = $this->request->post['payment_beezyycashier_sort_order'];
        } else {
            $data['payment_beezyycashier_sort_order'] = $this->config->get('payment_beezyycashier_sort_order');
        }

        $data['payment_methods_beezyy'] = $this->getPaymentMethods($data['payment_beezyycashier_secret_key']);

        $this->load->model('localisation/language');

        $data['languages'] = $this->model_localisation_language->getLanguages();

        $this->load->model('tool/image');
        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/beezyycashier', $data));
    }

    private function getPaymentMethods($key)
    {
        list($ch, $result) = $this->getRequest($key);
        curl_close($ch);
        $result = json_decode($result, true);
        return $result['data'];
    }

    private function checkSecretKey($key): bool
    {
        list($ch, $result) = $this->getRequest($key);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpcode == 200;
    }

    private function validate(): bool
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/beezyycashier')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_beezyycashier_secret_key']) {
            $this->error['secret'] = $this->language->get('error_secret');
        }

        $check = $this->checkSecretKey($this->request->post['payment_beezyycashier_secret_key']);

        if (!$check) {
            $this->error['secret'] = $this->language->get('error_incorrect_secret');
        }

        return !$this->error;
    }

    /**
     * @param $key
     * @return array
     */
    private function getRequest($key): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.beezyycashier.com/v1/payment-method/list");
        $headers = array();
        $headers[] = "Accept: application/json";
        $headers[] = "Cache-Control: no-cache";
        $headers[] = "Authorization: Bearer " . $key;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        $result = curl_exec($ch);
        return array($ch, $result);
    }
}
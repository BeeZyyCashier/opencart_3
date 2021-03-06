<?php

class ModelExtensionPaymentBeezyycashier extends Model
{
    public function getMethod($address, $total, $full = false)
    {
        $this->load->language('extension/payment/beezyycashier');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_beezyycashier_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('payment_beezyycashier_total') > 0 && $this->config->get('payment_beezyycashier_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('payment_beezyycashier_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            if ($full) {
                $virtual_methods = unserialize($this->config->get('payment_beezyycashier_pm'));
                if ($virtual_methods) {
                    foreach ($virtual_methods as $item) {
                        $image = '';
                        if (!empty($item['image'])){
                            $image = '<img id="beezyycashier-'.$item['id'].'" class="img-responsive" src="/image/'.$item['image'].'">';
                        }
                        $method_info = array(
                            'code' => 'beezyycashier-' . $item['id'],
                            'title' => $image.$item['pm_language'][(int)$this->config->get('config_language_id')]['name'],
                            'terms' => '',
                            'sort_order' => $item['sort']
                        );
                        $method_data[] = $method_info;
                    }
                }
            } else {
                $method_data = array(
                    'code' => 'beezyycashier',
                    'title' => $this->language->get('text_title'),
                    'terms' => '',
                    'sort_order' => $this->config->get('payment_beezyycashier_sort_order')
                );
            }
        }

        return $method_data;
    }

    public function setExtradata($order_id, $data)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET payment_custom_field='" . $data . "' WHERE order_id= '" . (int)$order_id . "'");
        return true;
    }
}
<?php

class ModelExtensionModuleNeatCountdown extends Model {
    public function getSpecial($product_id) {
        // keep it synchronized with `catalog/product` model
        return $this->db->query("SELECT date_end FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = '" . (int)$product_id . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND (ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND ps.date_end > NOW() ORDER BY ps.priority ASC, ps.price ASC LIMIT 1;");
    }

    public function getSpecials(array $product_ids) {
        // keep it synchronized with `catalog/product` model

        $result = array();

        if (!$product_ids) {
            return $result;
        }

        $product_ids_where_array = array();
        foreach ($product_ids as $product_id) {
            $product_ids_where_array[] = "ps.product_id = '" . (int)$product_id . "'";
        }
        $product_ids_where = '(' . implode(' OR ', $product_ids_where_array) . ')';

        $query = $this->db->query("SELECT product_id, date_end FROM " . DB_PREFIX . "product_special ps WHERE $product_ids_where AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND (ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND ps.date_end > NOW() ORDER BY ps.priority ASC, ps.price ASC;");

        foreach ($query->rows as $row) {
            $result[$row['product_id']] = $row['date_end'];
        }

        return $result;
    }

    public function getModules($layout_id, $eq_to_setting = null) {
        $query = $this->db->query("SELECT module_id, setting FROM " . DB_PREFIX . "module WHERE code = 'neat_countdown' ORDER BY module_id ASC;");

        $result = array();
        foreach ($query->rows as $row) {
            $decoded = json_decode($row['setting'], true);
            if (in_array($layout_id, $decoded['layouts']) && $decoded['status']) {
                if ($eq_to_setting) {
                    if ($eq_to_setting === $decoded) {
                        $result[$row['module_id']] = $decoded;
                        break;
                    }
                } else {
                    $result[$row['module_id']] = $decoded;
                }
            }
        }

        return $result;
    }
}

class ModelModuleNeatCountdown extends ModelExtensionModuleNeatCountdown {}

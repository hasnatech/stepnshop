<?php

/*
    Neat Countdown

    Module for Opencart that shows a countdown timer for special products.

    Version: 0.3.0
    Email: service@neat-countdown.chugylo.org.ua

    (c) 2017 Yury Surzhynsky
    All rights reserved.
*/


class ControllerExtensionModuleNeatCountdown extends Controller {
    // TEST: if route and product_id work well with human-readable URLs

    // NOTE: You must have a correct timezone on both your HTTP server and your database
    // for the correct work of specials. The adjusting of the timezone is not the aim of this module.
    // However, here is a hint.
    // date_default_timezone_set('Europe/Kiev');
    // $this->db->query("SET time_zone='Europe/Kiev'");

    protected $route = 'extension/module/neat_countdown';

    private $setting = null;

    private $productId = null;

    protected function getModel() {
        return $this->model_extension_module_neat_countdown;
    }

    public function index($setting) {
        // use $this->request->get, $this->request->request won't work with SEO URLs
        $route = $this->request->get['route'];
        $this->productId = @$this->request->get['product_id'];

        if ($route != 'product/product' || !$this->productId) {
            // it's not a product page, adding via layout is not possible
            return '';
        }

        $this->setting = $setting;

        return $this->product();
    }

    public function product() {
        $this->load->model($this->route);

        $model = $this->getModel();
        $special = $model->getSpecial($this->productId);

        if (!$special->num_rows) {
            return '';
        }

        $result = $this->processSpecials('product/product', array( $this->productId => $special->row['date_end'] ));

        return $result[$this->productId];
    }

    public function category(array $params) {
        return $this->processSpecials('product/category', $params['specials'], $params['category_id']);
    }

    public function compare(array $specials) {
        return $this->processSpecials('product/compare', $specials);
    }

    public function manufacturer(array $specials) {
        return $this->processSpecials('product/manufacturer', $specials);
    }

    public function search(array $specials) {
        return $this->processSpecials('product/search', $specials);
    }

    public function special(array $specials) {
        return $this->processSpecials('' /* default layout */, $specials);
    }

    public function featured(array $specials) {
        $featured_pseudo_layout_id = -1;
        return $this->composeResult($featured_pseudo_layout_id, $specials);
    }

    private function getLayoutId($route, $category_id) {
        if ($route == 'product/category') {
            $this->load->model('catalog/category');
            $layout_id = $this->model_catalog_category->getCategoryLayoutId($category_id);

            if ($layout_id) {
                return $layout_id;
            }
        }

        $this->load->model('design/layout');
        $layout_id = $this->model_design_layout->getLayout($route);

        return $layout_id ? $layout_id : null;
    }

    private function processSpecials($route, array $specials, $category_id = null) {
        $layout_id = $this->getLayoutId($route, $category_id);

        if ($layout_id) {
            return $this->composeResult($layout_id, $specials);
        } else {
            return array();
        }
    }

    private function composeResult($layout_id, array $specials) {
        $model = $this->getModel();
        $modules = $model->getModules($layout_id, $this->setting);

        $result = array();

        foreach ($specials as $id => $date) {
            $data = array();
            $end_date = date_create($date);
            $data['clock'] = array();
            $data['clock']['end_time'] = $end_date->getTimestamp();

            $result[$id] = $this->common($modules, $data, $id);
        }

        return $result;
    }

    private function common($modules, $data, $product_id) {
        $this->language->load($this->route);

        $result = '';

        $ext = substr(VERSION, 0, 3) == '2.1' ? '.tpl' : '';

        foreach ($modules as $module_id => $module) {
            switch ($module['view']) {
                case 'textual':
                    $data['lang'] = $this->language->get('textual_js');
                    break;
                case 'simple':
                    foreach ($this->language->get('simple') as $key => $string) {
                        $data['simple_' . $key] = $string;
                    }
                    break;
            }

            $data['inst'] = "$product_id-$module_id";

            $data['clock']['type'] = $module['clock'];
            if ($module['clock'] == 'server' || $module['clock'] == 'combo') {
                $data['clock']['server_time'] = function_exists('microtime') ? microtime(true) : time();
            }
            if ($module['clock'] == 'combo') {
                $data['clock']['critdiff'] = $module['clock--critdiff'];
            }

            // an extra check for the sake of security
            if (in_array($module['view'], array('textual', 'simple'))) {
                $this->document->addScript('catalog/view/javascript/neat_countdown/' . $module['view'] . '_view.js');

                // TEST whether non-default themes work

                if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . "/template/{$this->route}_{$module['view']}_view$ext")) {
                    $result .= $this->load->view($this->config->get('config_template') . "/template/{$this->route}_{$module['view']}_view$ext", $data);
                } else {
                    $result .= $this->load->view("{$this->route}_{$module['view']}_view$ext", $data);
                }
            }
        }

        return $result;
    }
}

class ControllerModuleNeatCountdown extends ControllerExtensionModuleNeatCountdown {
    protected $route = 'module/neat_countdown';

    protected function getModel() {
        return $this->model_module_neat_countdown;
    }
}

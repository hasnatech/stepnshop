<?php

/*
    Special Timer

    Module for Opencart that shows a countdown timer for special products.

    Version: 0.3.0
    Email: service@neat-countdown.chugylo.org.ua

    (c) 2017 Yury Surzhynsky
    All rights reserved.
*/

class ControllerExtensionModuleNeatCountdown extends Controller {
    const VERSION = '0.3.0';

    protected $route = 'extension/module/neat_countdown';

    private $error = array();

    private function writeLog($message) {
        $this->log->write('Module Special Timer: ' . $message);
    }

    private function loadLanguage(array $domains, array &$data) {
        foreach ($domains as $domain) {
            foreach ($this->language->get($domain) as $key => $string) {
                $data["${domain}_${key}"] = $string;
            }
        }
    }

    private function makeErrorMessages() {
        $errors = array();
        $common = $this->language->get('common');
        foreach ($this->language->get('error') as $key => $message) {
            $errors[$key] = isset($this->error[$key]) ? $common['error'] . $message : '';
        }

        return $errors;
    }

    private function foldIds($id_list) {
        assert(count($id_list) > 0);

        $id_list = array_unique($id_list);
        sort($id_list);

        $result = array( $id_list[0] );

        for ($i = 1; $i < count($id_list); $i++) {
            if ($id_list[$i] - $id_list[$i-1] > 1) {
                $result[] = $id_list[$i];
            } else {
                $pop_parts = explode('-', array_pop($result));
                $pop_parts[1] = $id_list[$i];
                $result[] = implode('-', $pop_parts);
            }
        }

        return implode(',', $result);
    }

    private function unfoldIds($id_str) {
        if (!$id_str) {
            return array();
        }

        $result = array();

        $parts = explode(',', $id_str);

        foreach ($parts as $part) {
            $parts = explode('-', $part);
            if (count($parts) == 1) {
                $result[] = (int) $parts[0];
            } else {
                for ($i = (int) $parts[0]; $i <= (int) $parts[1]; $i++) {
                    $result[] = $i;
                }
            }
        }

        $result = array_unique($result);
        sort($result);

        return $result;
    }

    /**
     * Layouts that should be checked by default.
     */
    private $defaultLayouts = array();  // cache
    private function getDefaultLayouts() {
        if (!$this->defaultLayouts) {
            $this->load->model('design/layout');
            $layouts = $this->model_design_layout->getLayouts();

            $default_routes = array(
                'product/product', 'product/category', 'product/compare',
                'product/manufacturer', 'product/search', '' );

            foreach ($layouts as $layout) {
                $layout_route = $this->model_design_layout->getLayoutRoutes($layout['layout_id']);
                if ($layout_route && in_array($layout_route[0]['route'], $default_routes)) {
                    $this->defaultLayouts[] = $layout['layout_id'];
                }
            }
        }

        return $this->defaultLayouts;
    }

    protected function getExtensionsLink() {
        return $this->url->link($this->redirectRoute, $this->token . '&type=module', 'SSL');
    }

    protected function getExtensionsText(array &$data) {
        return $data['common_extensions'];
    }

    protected $redirectRoute = null;
    private $modelModule = null;
    private $token = null;
    private $template = null;
    private function init() {
        $oc_version = substr(VERSION, 0, 3);

        if ($oc_version < '3.0') {
            $this->load->model('extension/module');
            $this->modelModule = &$this->model_extension_module;
            $this->token = "token={$this->session->data['token']}";
            $this->redirectRoute = $oc_version <= '2.2' ? 'extension/module' : 'extension/extension';
        } else {
            $this->load->model('setting/module');
            $this->modelModule = &$this->model_setting_module;
            $this->token = "user_token={$this->session->data['user_token']}";
            $this->redirectRoute = 'marketplace/extension';
        }

        $this->template = $oc_version == '2.1' ? "{$this->route}.tpl" : $this->route;
    }

    public function index() {
        $this->init();

        $this->load->language($this->route);

        $this->load->model('setting/setting');

        $this->document->setTitle($this->language->get('heading_title'));

        /***   AJAX   ***/

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['ajax'])) {
            //== Don't show timezone warning anymore
            if (isset($this->request->post['never-show-timezone-warning'])) {
                $this->model_setting_setting->editSettingValue('neat_countdown', 'neat_countdown_show_timezone_warning', false);

                header('Content-Type: text/plain');
                exit('ajax ok');
            }

            //== Add a notification to the exception list
            if (isset($this->request->post['dont-show-notification-id']) && preg_match('/^[1-9][0-9]*$/', $this->request->post['dont-show-notification-id'])) {
                $exceptions = $this->unfoldIds($this->model_setting_setting->getSettingValue('neat_countdown_notification_request'));
                $exceptions[] = (int) $this->request->post['dont-show-notification-id'];
                $this->model_setting_setting->editSettingValue('neat_countdown', 'neat_countdown_notification_request', $this->foldIds($exceptions));

                header('Content-Type: text/plain');
                exit('ajax ok');
            }
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $module_data = array(
                'name' => $this->request->post['name'],
                'view' => $this->request->post['view'],
                'status' => $this->request->post['status'],
                'clock' => $this->request->post['clock'],
                'clock--critdiff' => $this->request->post['clock--critdiff'],
            );

            $module_data['layouts'] = array();
            foreach (array_keys($this->request->post) as $key) {
                $matches = array();
                if (preg_match('/^layout-([1-9][0-9]*)$/', $key, $matches)) {
                    $module_data['layouts'][] = (int) $matches[1];
                } elseif ($key == 'featured') {
                    $module_data['layouts'][] = -1;
                }
            }

            if (!isset($this->request->get['module_id'])) {
                $this->modelModule->addModule('neat_countdown', $module_data);
            } else {
                $this->modelModule->editModule($this->request->get['module_id'], $module_data);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link($this->redirectRoute, $this->token . '&type=module', 'SSL'));
        }

        $data['heading_title'] = $this->language->get('heading_title');
       
        $this->document->addScript('view/javascript/neat_countdown/basic.js');

        $this->loadLanguage(array('common', 'form'), $data);

        $data['errors'] = $this->makeErrorMessages();

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text'      => $data['common_home'],
            'href'      => $this->url->link('common/dashboard', $this->token, 'SSL'),
            'separator' => false
        );
        $data['breadcrumbs'][] = array(
            'text'      => $this->getExtensionsText($data),
            'href'      => $this->getExtensionsLink(),
            'separator' => ' :: '
        );
        $data['breadcrumbs'][] = array(
            'text'      => $data['heading_title'],
            'href'      => $this->url->link($this->route, $this->token, 'SSL'),
            'separator' => ' :: '
        );

        $module_id_attr = isset($this->request->get['module_id']) ? '&module_id=' . $this->request->get['module_id'] : '';
        $data['action'] = $this->url->link($this->route, $this->token . $module_id_attr, 'SSL');
        $data['cancel'] = $this->url->link($this->redirectRoute, $this->token . '&type=module', 'SSL');

        // ModelSettingSetting::getSettingValue() is not defined in Opencart <= 2.1
        // Use ModelSettingSetting::getSetting() instead.
        $settings = $this->model_setting_setting->getSetting('neat_countdown');

        /***   Timezone Warning   ***/

        $data['show_timezone_warning'] = false;

        if ($settings['neat_countdown_show_timezone_warning']) {
            $data['show_timezone_warning'] = true;
        }

        /***   Service Notifications   ***/

        $data['notification_except'] = $settings['neat_countdown_notification_request'];

        if (isset($this->request->get['module_id'])) {
            $module_info = $this->modelModule->getModule($this->request->get['module_id']);
        }

        if (isset($module_info)) {
            $data['name'] = $module_info['name'];
        } else {
            $newest_index = 0;
            foreach ($this->modelModule->getModulesByCode('neat_countdown') as $module) {
                preg_match('/^Special Timer #\d+$/', $module['name'], $mathches);
                if ($mathches) {
                    preg_match('/\d+/', $module['name'], $num);
                    $newest_index = max(intval($num[0]), $newest_index);
                }
            }
            $newest_index++;
            $data['name'] = "Special Timer #$newest_index";
        }

        /***   Views   ***/

        $data['views'] = array(
            array(
                'value' => 'textual',
                'label' => $data['form_textual'],
            ),
            array(
                'value' => 'simple',
                'label' => $data['form_simple'],
            ),
        );

        if (isset($this->request->post['view'])) {
            $data['current_view'] = $this->request->post['view'];
        } elseif (isset($module_info)) {
            // in previous version it was `type`, we embrace backward compatibility
            $data['current_view'] = isset($module_info['view']) ? $module_info['view'] : $module_info['type'];
        } else {
            $data['current_view'] = 'textual';  // default
        }

        /***   Layouts  ***/

        $this->load->model('design/layout');
        $layouts = $this->model_design_layout->getLayouts();
        $default_layouts = $this->getDefaultLayouts();
        $data['layouts'] = array();
        foreach ($layouts as $layout) {
            if (isset($this->request->post['layout-' . $layout['layout_id']])) {
                $checked = true;
            } elseif (isset($module_info)) {
                $checked = in_array($layout['layout_id'], $module_info['layouts']);
            } else {
                $checked = in_array($layout['layout_id'], $default_layouts);
            }
            $data['layouts'][] = array(
                'id' => $layout['layout_id'],
                'label' => $layout['name'],
                'checked' => $checked,
                'suggested' => in_array($layout['layout_id'], $default_layouts),
            );
        }
          /***   Show in Special module   **/

        if (isset($this->request->post['special'])) {
            $data['special_checked'] = $this->request->post['special'];
        } elseif (isset($module_info)) {
            $data['special_checked'] = in_array(-1, $module_info['layouts']);
        } else {
            $data['special_checked'] = true;
        }

        /***   Show in Featured module   **/

        if (isset($this->request->post['featured'])) {
            $data['featured_checked'] = $this->request->post['featured'];
        } elseif (isset($module_info)) {
            $data['featured_checked'] = in_array(-1, $module_info['layouts']);
        } else {
            $data['featured_checked'] = true;
        }

        /***   Clock   ***/
        if (isset($this->request->post['clock'])) {
            $data['clock'] = $this->request->post['clock'];
        } elseif (isset($module_info) && isset($module_info['clock'])) {
            $data['clock'] = $module_info['clock'];
        } else {
            $data['clock'] = 'combo';
        }

        if (isset($this->request->post['clock--critdiff'])) {
            $data['clock__critdiff'] = $this->request->post['clock--critdiff'];
        } elseif (isset($module_info) && isset($module_info['clock--critdiff'])) {
            $data['clock__critdiff'] = $module_info['clock--critdiff'];
        } else {
            $data['clock__critdiff'] = '300';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (isset($module_info)) {
            $data['status'] = $module_info['status'];
        } else {
            $data['status'] = true;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view($this->template, $data));
    }

    protected function validate() {
        $lang = array();
        $this->loadLanguage(array('error'), $lang);

        // the keys of $this->error MUST be corresponding to the keys of language

        if (!$this->user->hasPermission('modify', $this->route)) {
            $this->error['permission'] = $lang['error_permission'];
        }

        // does it work in different locales?
        $name_pattern = '/^[\pL\pZs\pM\pS\pN-–—_#]{1,200}$/u';
        $name = (string) @$this->request->post['name'];
        if (!preg_match($name_pattern, $name)) {
            $this->error['name'] = $lang['error_name'];
        }

        if (!isset($this->request->post['view']) || !in_array($this->request->post['view'], array('textual', 'simple'))) {
            $this->error['view'] = $lang['error_view'];
        }

        if (!isset($this->request->post['clock']) || !in_array($this->request->post['clock'], array('browser', 'server', 'combo'))) {
            $this->error['clock'] = $lang['error_clock'];
        }

        if (!isset($this->request->post['clock--critdiff']) || !preg_match('/^\d{1,10}$/', trim($this->request->post['clock--critdiff'])) || (int)trim($this->request->post['clock--critdiff']) > 2147483647) {
            $this->error['clock--critdiff'] = $lang['error_clock--critdiff'];
        }

        if (!isset($this->request->post['status']) || !in_array($this->request->post['status'], array('0', '1'))) {
            $this->error['status'] = $lang['error_status'];
        }

        return !$this->error;
    }

    // `.install()` and `.unistall()` methods are likely don't work properly on multi-store installations

    public function install() {
        $this->load->model('setting/setting');

        $this->model_setting_setting->editSetting('neat_countdown', array(
            'neat_countdown_show_timezone_warning' => true,
            'neat_countdown_notification_request' => '',
        ));

        $this->sendToServer(@array(
            'name' => 'telemetry_install',
            'host' => $this->request->server['HTTP_HOST'],
            'ip' => $this->request->server['SERVER_ADDR'],
            'server' => $this->request->server['SERVER_SOFTWARE'],
            'ua' => $this->request->server['HTTP_USER_AGENT'],
            'scheme' => $this->request->server['REQUEST_SCHEME'],
            'module_version' => self::VERSION,
            'php_version' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
        ));

        $this->writeLog('installed');
    }

    public function uninstall() {
        $this->load->model('setting/setting');

        $this->model_setting_setting->deleteSetting('neat_countdown');

        $this->sendToServer(@array(
            'name' => 'telemetry_uninstall',
            'host' => $this->request->server['HTTP_HOST'],
            'ip' => $this->request->server['SERVER_ADDR'],
            'server' => $this->request->server['SERVER_SOFTWARE'],
            'ua' => $this->request->server['HTTP_USER_AGENT'],
            'scheme' => $this->request->server['REQUEST_SCHEME'],
            'module_version' => self::VERSION,
            'php_version' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
        ));

        $this->writeLog('uninstalled');
    }

    private function sendToServer(array $data) {
        /* I call it data gathering. If you call it spying, just uncomment
           the return statement bellow and set global variable
           `NTCDServiceOff` to `true` in the Javascript code before the
           `DOMReady` event happen, but you'll lose the ability to get
           useful notifications too. */

        // return;

        $opts = array(
            CURLOPT_URL => 'http://neat-countdown.chugylo.org.ua/ntcd_server',
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_POSTFIELDS => http_build_query($data),
        );

        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}

// make the module compatible with Opencart <= 2.2
class ControllerModuleNeatCountdown extends ControllerExtensionModuleNeatCountdown {
    protected $route = 'module/neat_countdown';

    protected function getExtensionsLink() {
        return $this->url->link($this->redirectRoute, 'token=' . $this->session->data['token'], 'SSL');
    }

    protected function getExtensionsText(array &$data) {
        return $data['common_modules'];
    }
}

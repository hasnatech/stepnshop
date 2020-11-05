<?php
class ControllerExtensionModuleQuantityincrementdecrement extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/quantityincrementdecrement');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_quantityincrementdecrement', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$data['entry_featured']        = $this->language->get('entry_featured');
		$data['entry_latest']          = $this->language->get('entry_latest');
		$data['entry_bestseller']      = $this->language->get('entry_bestseller');
		$data['entry_special']         = $this->language->get('entry_special');
		$data['entry_category']        = $this->language->get('entry_category');
		$data['entry_manufactures']    = $this->language->get('entry_manufactures');
		$data['entry_related']         = $this->language->get('entry_related');
		$data['entry_search']          = $this->language->get('entry_search');
		$data['entry_product']         = $this->language->get('entry_product');
		$data['entry_status']         = $this->language->get('entry_status');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/quantityincrementdecrement', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/quantityincrementdecrement', 'user_token=' . $this->session->data['user_token'], true);


		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);


		$data['user_token'] = $this->session->data['user_token'];


		if (isset($this->request->post['module_quantityincrementdecrement_featured_status'])) {
			$data['module_quantityincrementdecrement_featured_status'] = $this->request->post['module_quantityincrementdecrement_featured_status'];
		} else {
			$data['module_quantityincrementdecrement_featured_status'] = $this->config->get('module_quantityincrementdecrement_featured_status');
		}

		if (isset($this->request->post['module_quantityincrementdecrement_bestseller_status'])) {
			$data['module_quantityincrementdecrement_bestseller_status'] = $this->request->post['module_quantityincrementdecrement_bestseller_status'];
		} else {
			$data['module_quantityincrementdecrement_bestseller_status'] = $this->config->get('module_quantityincrementdecrement_bestseller_status');
		}

		if (isset($this->request->post['module_quantityincrementdecrement_latest_status'])) {
			$data['module_quantityincrementdecrement_latest_status'] = $this->request->post['module_quantityincrementdecrement_latest_status'];
		} else {
			$data['module_quantityincrementdecrement_latest_status'] = $this->config->get('module_quantityincrementdecrement_latest_status');
		}

		if (isset($this->request->post['module_quantityincrementdecrement_special_status'])) {
			$data['module_quantityincrementdecrement_special_status'] = $this->request->post['module_quantityincrementdecrement_special_status'];
		} else {
			$data['module_quantityincrementdecrement_special_status'] = $this->config->get('module_quantityincrementdecrement_special_status');
		}

		if (isset($this->request->post['module_quantityincrementdecrement_category_status'])) {
			$data['module_quantityincrementdecrement_category_status'] = $this->request->post['module_quantityincrementdecrement_category_status'];
		} else {
			$data['module_quantityincrementdecrement_category_status'] = $this->config->get('module_quantityincrementdecrement_category_status');
		}

		if (isset($this->request->post['module_quantityincrementdecrement_manufactures_status'])) {
			$data['module_quantityincrementdecrement_manufactures_status'] = $this->request->post['module_quantityincrementdecrement_manufactures_status'];
		} else {
			$data['module_quantityincrementdecrement_manufactures_status'] = $this->config->get('module_quantityincrementdecrement_manufactures_status');
		}

		if (isset($this->request->post['module_quantityincrementdecrement_product_status'])) {
			$data['module_quantityincrementdecrement_product_status'] = $this->request->post['module_quantityincrementdecrement_product_status'];
		} else {
			$data['module_quantityincrementdecrement_product_status'] = $this->config->get('module_quantityincrementdecrement_product_status');
		}

		if (isset($this->request->post['module_quantityincrementdecrement_related_status'])) {
			$data['module_quantityincrementdecrement_related_status'] = $this->request->post['module_quantityincrementdecrement_related_status'];
		} else {
			$data['module_quantityincrementdecrement_related_status'] = $this->config->get('module_quantityincrementdecrement_related_status');
		}

		if (isset($this->request->post['module_quantityincrementdecrement_search_status'])) {
			$data['module_quantityincrementdecrement_search_status'] = $this->request->post['module_quantityincrementdecrement_search_status'];
		} else {
			$data['module_quantityincrementdecrement_search_status'] = $this->config->get('module_quantityincrementdecrement_search_status');
		}

		if (isset($this->request->post['module_quantityincrementdecrement_status'])) {
			$data['module_quantityincrementdecrement_status'] = $this->request->post['module_quantityincrementdecrement_status'];
		} else {
			$data['module_quantityincrementdecrement_status'] = $this->config->get('module_quantityincrementdecrement_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/quantityincrementdecrement', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/quantityincrementdecrement')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}

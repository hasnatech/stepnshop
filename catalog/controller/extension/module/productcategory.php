<?php
class ControllerExtensionModuleProductcategory extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/productcategory');

		$data['lang'] = $this->language->get('code');

		$data['heading_title'] = $this->language->get('heading_title');

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		$data['categories'] = array();

		if (!$setting['limit']) {
			$setting['limit'] = 6;
		}
		$categories_data = array();

			foreach ($setting['category'] as $category_id) {
				$category_info = $this->model_catalog_category->getCategory($category_id);

				if ($category_info) {
                    $categories_data[] = $category_info;
				}
			}


		if (!empty($setting['category'])) {
			$categories = array_slice($setting['category'], 0, (int)$setting['limit']);	

		

			foreach ($categories as $category_id) {
				$category_info = $this->model_catalog_category->getCategory($category_id);

				
				if ($category_info) {

					$products = array();

					$filter_data = array(
						'filter_category_id' => $category_id,
						'filter_sub_category' => true,
						'start'              => 0,
						'limit'              => $setting['limit']
					);

					$product_total = $this->model_catalog_product->getTotalProducts($filter_data);

					$results = $this->model_catalog_product->getProducts($filter_data);


					foreach ($results as $result) {
						if($result){
						if ($result['image']) {
							$image = $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height']);
						} else {
							$image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
						}
					
						if ($result['image2']) {
							$image2 = $this->model_tool_image->resize($result['image2'], $setting['width'], $setting['height']);
						} else {
							$image2 = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
						}

						if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
							$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						} else {
							$price = false;
						}

						if ((float)$result['special']) {
							$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						} else {
							$special = false;
						}

						if ($this->config->get('config_tax')) {
							$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
						} else {
							$tax = false;
						}

						if ($this->config->get('config_review_status')) {
							$rating = (int)$result['rating'];
						} else {
							$rating = false;
						}

						if($result['special'] > 0 AND $result['special'] != NULL ){
							$tag_per = ($result['special']*100)/$result['price'];
							$tag_per = round($tag_per);
							if($tag_per == 0){
							$tag_per = 1;
							}else{
							$tag_per = 100-$tag_per;
							}
							$tag = $result['price'] - $result['special'];
							}else{
							$tag = 0;
							$tag_per = 0;
						}
						
							
						$products[] = array(
							'product_id'  => $result['product_id'],
							'image'       => $image,	
							'thumb'       => $image2,							
							'tag'         => $tag,
							'tag_per'     => $tag_per,
							'name'        => $result['name'],
							'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
							'price'       => $price,
							'special'     => $special,
							'tax'         => $tax,
							'rating'      => $rating,
							'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
							);
						}
				 	}
				 	
					$data['categories'][] = array(
						'category_id' => $category_info['category_id'],
						'products'	  => $products,					
						'name'        => $category_info['name'],
						'thumb'       => $this->model_tool_image->resize($category_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height')),
						'href'        => $this->url->link('product/category', 'path=' . $category_info['category_id'])
					);
					
				}
			}
		}
		if ($data['categories']) {

				return $this->load->view('extension/module/productcategory', $data);

		}
	}
}

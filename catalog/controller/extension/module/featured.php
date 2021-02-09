<?php
class ControllerExtensionModuleFeatured extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/featured');
		$data['bg_title'] = $this->language->get('bg_title');
		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		$data['products'] = array();

		if (!$setting['limit']) {
			$setting['limit'] = 4;
		}

		if (!empty($setting['product'])) {
			$products = array_slice($setting['product'], 0, (int)$setting['limit']);

			foreach ($products as $product_id) {
				$product_info = $this->model_catalog_product->getProduct($product_id);

				if ($product_info) {
					$images = $this->model_catalog_product->getProductImages($product_info['product_id']);

				if(isset($images[0]['image']) && !empty($images[0]['image'])){
						$images =$images[0]['image'];
					} 
					else {
						$images ="";
					}
					
					if ($product_info['image']) {
						$image = $this->model_tool_image->resize($product_info['image'], $setting['width'], $setting['height']);
					} else {
						$image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
					}
					if ($product_info['image']) {
						$thumb_image = $this->model_tool_image->resize($images, $setting['width'], $setting['height']);
					} else {
						$thumb_image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
					}


					if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
						$price1 = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					} else {
						$price1 = false;
					}

					if ((float)$product_info['special']) {
						$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					} else {
						$special = false;
					}

					if ($this->config->get('config_tax')) {
						$tax = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
					} else {
						$tax = false;
					}

					if ($this->config->get('config_review_status')) {
						$rating = $product_info['rating'];
					} else {
						$rating = false;
					}

					if($product_info['special'] > 0 AND $product_info['special'] != NULL ){
						$tag_per = ($product_info['special']*100)/$product_info['price'];
						$tag_per = round($tag_per);
						if($tag_per == 0){
						$tag_per = 1;
						}else{
						$tag_per = 100-$tag_per;
						}
						$tag = $product_info['price'] - $product_info['special'];
						}else{
						$tag = 0;
						$tag_per = 0;
					}
			
					$data['options'] = array();

					foreach ($this->model_catalog_product->getProductOptions($product_info['product_id']) as $option) {
						$product_option_value_data = array();

						foreach ($option['product_option_value'] as $option_value) {
							if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
								if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
									
 							
			                      $price = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax') ? 'P' : false), $this->session->data['currency']);
									} else {
										$price = false;
									}
								$product_option_value_data[] = array(
									'product_option_value_id' => $option_value['product_option_value_id'],
									'option_value_id'         => $option_value['option_value_id'],
									'name'                    => $option_value['name'],
									'image'                   => $this->model_tool_image->resize($option_value['image'], 50, 50),
									'price'                   => $price,
									'price_prefix'            => $option_value['price_prefix']
								);
							}
						}

						$data['options'][] = array(
							'product_option_id'    => $option['product_option_id'],
							'product_option_value' => $product_option_value_data,
							'option_id'            => $option['option_id'],
							'name'                 => $option['name'],
							'type'                 => $option['type'],
							'value'                => $option['value'],
							'required'             => $option['required']
						);
					}

					if ($product_info['minimum']) {
						$data['minimum'] = $product_info['minimum'];
					} else {
						$data['minimum'] = 1;
					}

					$data['products'][] = array(
						//'option'      => $this->model_catalog_product->getProductOptions($product_info['product_id']),
						'product_id'  => $product_info['product_id'],
						'thumb'       => $image,
						'options'     => $data['options'],
						'tag'         => $tag,
						'tag_per'     => $tag_per,						
						'thumb_swap'  => $thumb_image,
						'name'        => $product_info['name'],
						'description' => utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
						'price'       => $price1,
						'special'     => $special,
						'tax'         => $tax,
						'rating'      => $rating,
						'href'        => $this->url->link('product/product', 'product_id=' . $product_info['product_id']),
						'quick'        => $this->url->link('product/quickview', 'product_id=' . $product_info['product_id'])
					);
				}
			}
		}

		
			return $this->load->view('extension/module/featured', $data);
		
	}
}


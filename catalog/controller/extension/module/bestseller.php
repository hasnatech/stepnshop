<?php
class ControllerExtensionModuleBestSeller extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/bestseller');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		$data['products'] = array();

		$results = $this->model_catalog_product->getBestSellerProducts($setting['limit']);

		if ($results) {
			foreach ($results as $result) {
				$images = $this->model_catalog_product->getProductImages($result['product_id']);
				if(isset($images[0]['image']) && !empty($images[0]['image'])){
						$images =$images[0]['image'];
					} 
					else {
						$images ="";
				}
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height']);
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price1 = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price1 = false;
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
					$rating = $result['rating'];
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
			$data['options'] = array();

					foreach ($this->model_catalog_product->getProductOptions($result['product_id']) as $option) {
						$product_option_value_data = array();

						foreach ($option['product_option_value'] as $option_value) {
							if($this->config->get('module_live_options_show_options_type') == 0){
                        if( $option_value['price_prefix'] == '-' ){
                            $option_value['price'] = (($result['special'] ? ($result['special'] - $option_value['price']) : ($result['price']) - $option_value['price']));
                        }
                        elseif( $option_value['price_prefix'] == '+' ){
                            $option_value['price'] = (($result['special'] ? ($result['special'] + $option_value['price']) : ($result['price']) + $option_value['price']));
                        }
                    	   $option_value['price_prefix'] = '';
                    	}
							if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
								if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
									
 							
			                      $price = $this->currency->format($this->tax->calculate($option_value['price'], $result['tax_class_id'], $this->config->get('config_tax') ? 'P' : false), $this->session->data['currency']);
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

					if ($result['minimum']) {
						$data['minimum'] = $result['minimum'];
					} else {
						$data['minimum'] = 1;
					}

				$data['products'][] = array(
					//'option'      => $this->model_catalog_product->getProductOptions($result['product_id']),
					'product_id'  => $result['product_id'],
					'thumb'       => $image,					
					'tag'       => $tag,
					'tag_per' => $tag_per,
					'options'     => $data['options'],
					'thumb'       => $image,
					'thumb_swap'  => $this->model_tool_image->resize($images, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height')),
					'name'        => $result['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price1,
					'special'     => $special,
					'tax'         => $tax,
					'rating'      => $rating,
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id']),
					'quick'        => $this->url->link('product/quickview', 'product_id=' . $result['product_id'])
				);
			}

			return $this->load->view('extension/module/bestseller', $data);
		}
	}
}

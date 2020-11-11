<?php
class ControllerApiWeb extends Controller {
	
	private $debug = false;

	public function index() {
        $this->load->language('api/cart');
    }

	//get all category
    public function categories() { 
		$this->init();
		$this->load->model('catalog/category');
		$json = array('success' => true);

		# -- $_GET params ------------------------------
		
		if (isset($this->request->get['parent'])) {
			$parent = $this->request->get['parent'];
		} else {
			$parent = 0;
		}

		if (isset($this->request->get['level'])) {
			$level = $this->request->get['level'];
		} else {
			$level = 1;
		}

		# -- End $_GET params --------------------------


		$json['categories'] = $this->getCategoriesTree($parent, $level);

		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}
	
	
	//get single category
	public function category() {
		$this->init();
		$this->load->model('catalog/category');
		$this->load->model('tool/image');

		$json = array('success' => true);

		# -- $_GET params ------------------------------
		
		if (isset($this->request->get['id'])) {
			$category_id = $this->request->get['id'];
		} else {
			$category_id = 0;
		}

		# -- End $_GET params --------------------------

		$category = $this->model_catalog_category->getCategory($category_id);
		
		$json['category'] = array(
			'id'                    => $category['category_id'],
			'name'                  => $category['name'],
			'description'           => $category['description'],
			'href'                  => $this->url->link('api/web/products', 'category=' . $category['category_id'])
			
		);
		
		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}

	// get all product of a category
	// http://localhost/grocery_os/index.php?route=api/web/products&category=27

	public function products() {
		$this->init();
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$json = array('success' => true, 'products' => array());


		# -- $_GET params ------------------------------
		
		if (isset($this->request->get['category'])) {
			$category_id = $this->request->get['category'];
		} else {
			$category_id = 0;
		}

		# -- End $_GET params --------------------------

		$products = $this->model_catalog_product->getProducts(array(
			'filter_category_id'	=> $category_id
		));

		foreach ($products as $product) {

			if ($product['image']) {
				//$image = $this->model_tool_image->resize($product['image'], $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height'));
				$image = $product['image'];
			} else {
				$image = false;
			}

			if ((float)$product['special']) {
				$special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				
			} else {
				$special = false;
			}
			$price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			$json['products'][] = array(
				'id'                    => $product['product_id'],
				'name'                  => $product['name'],
				'description'           => '', //$product['description'],
				'pirce'                 => $price,
				'href'                  => $this->url->link('api/web/product', 'product_id=' . $product['product_id']),
				'thumb'                 => $image,
				'special'               => $special,
				'rating'                => $product['rating']
			);
		}

		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}

	//get single product
	public function product() {
		$this->init();
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$json = array('success' => true);

		# -- $_GET params ------------------------------
		
		if (isset($this->request->get['product_id'])) {
			$product_id = $this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		# -- End $_GET params --------------------------

		$product = $this->model_catalog_product->getProduct($product_id);

		# product image
		if ($product['image']) {
			// $image = $this->model_tool_image->resize($product['image'], $this->config->get('config_image_popup_width'), $this->config->get('config_image_popup_height'));
			$image = $product['image'];
		} else {
			$image = '';
		}

		#additional images
		$additional_images = $this->model_catalog_product->getProductImages($product['product_id']);
		$images = array();

		foreach ($additional_images as $additional_image) {
			//$images[] = $this->model_tool_image->resize($additional_image, $this->config->get('config_image_additional_width'), $this->config->get('config_image_additional_height'));
			$images[] =$additional_image;
		}

		#specal
		if ((float)$product['special']) {
			$special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
		} else {
			$special = false;
		}

		#discounts
		$discounts = array();
		$data_discounts =  $this->model_catalog_product->getProductDiscounts($product['product_id']);

		foreach ($data_discounts as $discount) {
			$discounts[] = array(
				'quantity' => $discount['quantity'],
				'price'    => $this->currency->format($this->tax->calculate($discount['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
			);
		}

		#options
		$options = array();

		foreach ($this->model_catalog_product->getProductOptions($product['product_id']) as $option) { 
			if ($option['type'] == 'select' || $option['type'] == 'radio' || $option['type'] == 'checkbox' || $option['type'] == 'image') { 
				$option_value_data = array();
				
				foreach ($option['product_option_value'] as $option_value) {
					if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
						if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
							$price = $this->currency->format($this->tax->calculate($option_value['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						} else {
							$price = false;
						}
						
						$option_value_data[] = array(
							'product_option_value_id' => $option_value['product_option_value_id'],
							'option_value_id'         => $option_value['option_value_id'],
							'name'                    => $option_value['name'],
							'image'                   => $this->model_tool_image->resize($option_value['image'], 50, 50),
							'price'                   => $price,
							'price_prefix'            => $option_value['price_prefix']
						);
					}
				}
				
				$options[] = array(
					'product_option_id' => $option['product_option_id'],
					'option_id'         => $option['option_id'],
					'name'              => $option['name'],
					'type'              => $option['type'],
					'option_value'      => $option_value_data,
					'required'          => $option['required']
				);					
			} elseif ($option['type'] == 'text' || $option['type'] == 'textarea' || $option['type'] == 'file' || $option['type'] == 'date' || $option['type'] == 'datetime' || $option['type'] == 'time') {
				$options[] = array(
					'product_option_id' => $option['product_option_id'],
					'option_id'         => $option['option_id'],
					'name'              => $option['name'],
					'type'              => $option['type'],
					'option_value'      => $option['product_option_value'],
					'required'          => $option['required']
				);						
			}
		}

		#minimum
		if ($product['minimum']) {
			$minimum = $product['minimum'];
		} else {
			$minimum = 1;
		}

		$json['product'] = array(
			'id'                            => $product['product_id'],
			'seo_h1'                        => '', //$product['seo_h1'],
			'name'                          => $product['name'],
			'manufacturer'                  => $product['manufacturer'],
			'model'                         => $product['model'],
			'reward'                        => $product['reward'],
			'points'                        => $product['points'],
			'image'                         => $image,
			'images'                        => $images,
			'price'                         => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
			'special'                       => $special,
			'discounts'                     => $discounts,
			'options'                       => $options,
			'minimum'                       => $minimum,
			'rating'                        => (int)$product['rating'],
			'description'                   => html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'),
			'attribute_groups'              => $this->model_catalog_product->getProductAttributes($product['product_id'])
		);


		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}

    /**
	 * Generation of category tree
	 * 
	 * @param  int    $parent  Prarent category id
	 * @param  int    $level   Depth level
	 * @return array           Tree
	 */
	private function getCategoriesTree($parent = 0, $level = 1) {
		$this->load->model('catalog/category');
		$this->load->model('tool/image');
		
		$result = array();

		$categories = $this->model_catalog_category->getCategories($parent);

		if ($categories && $level > 0) {
			//$level--;

			foreach ($categories as $category) {

				if ($category['image']) {
                    //$image = $this->model_tool_image->resize($category['image'], $this->config->get('config_image_category_width'), $this->config->get('config_image_category_height'));
                    $image = $category['image'];
				} else {
					$image = false;
				}

				$result[] = array(
					'category_id'   => $category['category_id'],
					'parent_id'     => $category['parent_id'],
					'name'          => $category['name'],
					'description'   => html_entity_decode($category['description'], ENT_QUOTES, 'UTF-8'),
					'image'         => $image,
					'href'          => $this->url->link('api/web/products', 'category=' . $category['category_id']),
					'categories'    => $this->getCategoriesTree($category['category_id'], $level)
				);
			}

			return $result;
		}
	}
    
    /**
	 * 
	 */
	private function init() {
 $this->response->addHeader("Access-Control-Allow-Origin: *");
		$this->response->addHeader('Content-Type: application/json');

		/*if (!$this->config->get('web_api_status')) {
			$this->error(10, 'API is disabled');
		}

		if ($this->config->get('web_api_key') && (!isset($this->request->get['key']) || $this->request->get['key'] != $this->config->get('web_api_key'))) {
			$this->error(20, 'Invalid secret key');
		}*/
    }
    
    /**
	 * Error message responser
	 *
	 * @param string $message  Error message
	 */
	private function error($code = 0, $message = '') {

		# setOutput() is not called, set headers manually
		header('Content-Type: application/json');

		$json = array(
			'success'       => false,
			'code'          => $code,
			'message'       => $message
		);

		if ($this->debug) {
			echo '<pre>';
			print_r($json);
		} else {
			echo json_encode($json);
		}
		
		exit();
	}
}
<?php
class ControllerApiWeb extends Controller {
	public function index() {
        $this->load->language('api/cart');
    }

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
			$level--;

			foreach ($categories as $category) {

				if ($category['image']) {
                    //$image = $this->model_tool_image->resize($category['image'], $this->config->get('config_image_category_width'), $this->config->get('config_image_category_height'));
                    $image =$category['image'];
				} else {
					$image = false;
				}

				$result[] = array(
					'category_id'   => $category['category_id'],
					'parent_id'     => $category['parent_id'],
					'name'          => $category['name'],
					'image'         => $image,
					'href'          => $this->url->link('product/category', 'category_id=' . $category['category_id']),
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
<?php
class ControllerSaleOrder extends Controller {
	private $error = array();
	
	private $appKey="pgyu6atqyymvu" ;                //appKey
    private $appSecret="TPuvcGzqvAU" ;             //secret
    const   SERVERAPIURL = 'http://api.cn.ronghub.com';    //IM服务地址
    const   SMSURL = 'http://api.sms.ronghub.com';          //短信服务地址
    private $format="json" ; 

	public function index() {
		$this->load->language('sale/order');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('sale/order');

		$this->getList();
	}

	public function add() {
		$this->load->language('sale/order');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('sale/order');

		$this->getForm();
	}

	public function edit() {
		$this->load->language('sale/order');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('sale/order');

		$this->getForm();
	}

	protected function getList() {
		$require = array();
		if (isset($this->request->get['filter_order_id'])) {
			$filter_order_id = $this->request->get['filter_order_id'];
			$require['filter_order_id'] = $filter_order_id;
		} else {
			$filter_order_id = null;
		}

		if (isset($this->request->get['filter_customer'])) {
			$filter_customer = $this->request->get['filter_customer'];
		} else {
			$filter_customer = null;
		}

		if (isset($this->request->get['filter_order_status'])) {
			$filter_order_status = $this->request->get['filter_order_status'];
			$require['filter_order_status'] = $filter_order_status;
		} else {
			$filter_order_status = null;
		}

		if (isset($this->request->get['filter_total'])) {
			$filter_total = $this->request->get['filter_total'];
		} else {
			$filter_total = null;
		}

		if (isset($this->request->get['filter_date_added'])) {
			$arr=explode("/",@$this->request->get["filter_date_added"]);
			$filter_date_added=@$arr[2]."-".@$arr[0]."-".@$arr[1];
			$require['filter_date_added'] = $filter_date_added;
		} else {
			$filter_date_added = null;
		}
		if (isset($this->request->get['filter_date_modified'])) {
			$arr=explode("/",@$this->request->get["filter_date_modified"]);
			$filter_date_modified=@$arr[2]."-".@$arr[0]."-".@$arr[1];
			$require['filter_date_modified'] = $filter_date_modified;
		} else {
			$filter_date_modified = null;
		}

		if (isset($this->request->get['warehouse_id'])) {
			$warehouse_id = $this->request->get['warehouse_id'];
			if($this->request->get['warehouse_id'] != 'all'){
				$require['warehouse_id'] = $warehouse_id;
			}
		} else {
			$warehouse_id = null;
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'o.order_id';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url, 'SSL')
		);

		$data['invoice'] = $this->url->link('sale/order/invoice', 'token=' . $this->session->data['token'], 'SSL');
		$data['shipping'] = $this->url->link('sale/order/shipping', 'token=' . $this->session->data['token'], 'SSL');
		$data['add'] = $this->url->link('sale/order/add', 'token=' . $this->session->data['token'], 'SSL');

		$data['orders'] = array();

		$filter_data = array(
			'filter_order_id'      => $filter_order_id,
			'filter_customer'	   => $filter_customer,
			'filter_order_status'  => $filter_order_status,
			'filter_total'         => $filter_total,
			'filter_date_added'    => $filter_date_added,
			'filter_date_modified' => $filter_date_modified,
			'sort'                 => $sort,
			'order'                => $order,
			'warehouse_id'		   => $warehouse_id,
			'start'                => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                => $this->config->get('config_limit_admin')
		);

		$order_total = $this->model_sale_order->getTotalOrders($filter_data);

		$results = $this->model_sale_order->getOrders($filter_data);
		foreach ($results as $result) {
			$data['orders'][] = array(
				'order_id'      => $result['order_id'],
				'customer'      => trim($result['customer']) ? $result['customer'] : (trim($result['email']) ? $result['email'] : $result['telephone']),
				'status'        => $result['status'],
				'total'         => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
				'date_added'    => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
				'date_modified' => date($this->language->get('datetime_format'), strtotime($result['date_modified'])),
				'shipping_code' => $result['shipping_code'],
				'view'          => $this->url->link('sale/order/info', 'token=' . $this->session->data['token'] . '&order_id=' . $result['order_id'] . $url, 'SSL'),
				'edit'          => $this->url->link('sale/order/edit', 'token=' . $this->session->data['token'] . '&order_id=' . $result['order_id'] . $url, 'SSL'),
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');
		$data['text_missing'] = $this->language->get('text_missing');
		$data['text_loading'] = $this->language->get('text_loading');

		$data['column_order_id'] = $this->language->get('column_order_id');
		$data['column_customer'] = $this->language->get('column_customer');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_total'] = $this->language->get('column_total');
		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_date_modified'] = $this->language->get('column_date_modified');
		$data['column_action'] = $this->language->get('column_action');

		$data['entry_return_id'] = $this->language->get('entry_return_id');
		$data['entry_order_id'] = $this->language->get('entry_order_id');
		$data['entry_customer'] = $this->language->get('entry_customer');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_total'] = $this->language->get('entry_total');
		$data['entry_date_added'] = $this->language->get('entry_date_added');
		$data['entry_date_modified'] = $this->language->get('entry_date_modified');

		$data['button_invoice_print'] = $this->language->get('button_invoice_print');
		$data['button_shipping_print'] = $this->language->get('button_shipping_print');
		$data['button_add'] = $this->language->get('button_add');
		$data['button_edit'] = $this->language->get('button_edit');
		$data['button_delete'] = $this->language->get('button_delete');
		$data['button_filter'] = $this->language->get('button_filter');
		$data['button_view'] = $this->language->get('button_view');
		$data['button_ip_add'] = $this->language->get('button_ip_add');

		$data['token'] = $this->session->data['token'];

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
		

		$data['sort_order'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=o.order_id' . $url, 'SSL');
		$data['sort_customer'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=customer' . $url, 'SSL');
		$data['sort_status'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=status' . $url, 'SSL');
		$data['sort_total'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=o.total' . $url, 'SSL');
		$data['sort_date_added'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=o.date_added' . $url, 'SSL');
		$data['sort_date_modified'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=o.date_modified' . $url, 'SSL');

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if(isset($this->request->get['warehouse_id'])){
			if($this->request->get['warehouse_id'] != 'all'){
				$url .= '&warehouse_id=' . $this->request->get['warehouse_id'];
			}
		}

		// $pagination = new Pagination();
		// $pagination->total = $order_total;
		// $pagination->page = $page;
		// $pagination->limit = $this->config->get('config_limit_admin');
		// $pagination->url = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url . '&page={page}', 'SSL');

		// $data['pagination'] = $pagination->render();

		// $data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_total - $this->config->get('config_limit_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_total, ceil($order_total / $this->config->get('config_limit_admin')));
		
		$id=@$this->request->get["id"];
		//新订单（未发货，待付款），未完成（待收货，已取消，已关闭），已完成
		if($id=="1"){
			//新订单
			$status="1,2";

		}else if($id=="2"){
			//未完成
			$status="3,6,7";
		}else if($id=="3"){
			//已完成
			$status="4,5";
		}else if($id=="4"){
			
		}else{
			//没有的情况下
			$status="1,2";
		}
		$this->load->model('sale/return');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}
		$arr=explode("/",@$this->request->get["filter_date_added"]);
		$year=@$arr[2]."-".@$arr[0]."-".@$arr[1];

		if($id!=4 ){
			$require['filter_order_status'] = $status;
			$require['start'] = ($page - 1) * $this->config->get('config_limit_admin');
			$require['limit'] = $this->config->get('config_limit_admin');
			$aa=$this->model_sale_order->getOrders($require);
			if(!empty($aa)){
				foreach($aa as $k=>$v){
					$bb=$this->model_sale_order->getOrderProductsByorderid($v["order_id"]);
					$is_remind=$this->model_sale_order->sel_is_order($v["order_id"]);
					if(!empty($is_remind)){
						//已经提醒发货
						$remind=1;
					}else{
						$remind=0;
					}
					$aa[$k]["is_remind"]=$remind;
					$aa[$k]["product"]=$bb["name"];
				}
			}

			$data["orders"]=$aa;
//			if(isset($this->request->get["filter_date_modified"])){
//				$aa=$this->model_sale_order->getOrders($require);
//				if(!empty($aa)){
//					foreach($aa as $k=>$v){
//						$bb=$this->model_sale_order->getOrderProductsByorderid($v["order_id"]);
//						$is_remind=$this->model_sale_order->sel_is_order($v["order_id"]);
//						if(!empty($is_remind)){
//							//已经提醒发货
//							$remind=1;
//						}else{
//							$remind=0;
//						}
//						$aa[$k]["is_remind"]=$remind;
//						$aa[$k]["product"]=$bb["name"];
//					}
//				}
//
//				$data["orders"]=$aa;
//
//			}else{
//
//				$aa=$this->model_sale_order->getOrders(array("filter_order_status"=>$status,'start'=> ($page - 1) * $this->config->get('config_limit_admin'),'limit' => $this->config->get('config_limit_admin')));
//				if(!empty($aa)){
//					foreach($aa as $k=>$v){
//						$bb=$this->model_sale_order->getOrderProductsByorderid($v["order_id"]);
//						$is_remind=$this->model_sale_order->sel_is_order($v["order_id"]);
//						if(!empty($is_remind)){
//							//已经提醒发货
//							$remind=1;
//						}else{
//							$remind=0;
//						}
//						$aa[$k]["is_remind"]=$remind;
//
//						$aa[$k]["product"]=$bb["name"];
//					}
//				}
//				$data["orders"]=$aa;
//			}
//
//			if(isset($this->request->get["filter_order_id"]) && isset($this->request->get["filter_date_modified"]) ){
//				$aa=$this->model_sale_order->getOrders(array("filter_order_status"=>$status,"filter_order_id"=>$this->request->get["filter_order_id"],"filter_date_modified"=>$year,'start'=> ($page - 1) * $this->config->get('config_limit_admin'),'limit' => $this->config->get('config_limit_admin')));
//				if(!empty($aa)){
//					foreach($aa as $k=>$v){
//						$bb=$this->model_sale_order->getOrderProductsByorderid($v["order_id"]);
//						$is_remind=$this->model_sale_order->sel_is_order($v["order_id"]);
//						if(!empty($is_remind)){
//							//已经提醒发货
//							$remind=1;
//						}else{
//							$remind=0;
//						}
//						$aa[$k]["is_remind"]=$remind;
//
//						$aa[$k]["product"]=$bb["name"];
//					}
//				}
//				$data["orders"]=$aa;
//
//			}
//			if(isset($this->request->get["filter_order_id"])){
//				$aa=$this->model_sale_order->getOrders(array("filter_order_status"=>$status,"filter_order_id"=>$this->request->get["filter_order_id"],'start'=> ($page - 1) * $this->config->get('config_limit_admin'),'limit' => $this->config->get('config_limit_admin')));
//				if(!empty($aa)){
//					foreach($aa as $k=>$v){
//						$bb=$this->model_sale_order->getOrderProductsByorderid($v["order_id"]);
//						$is_remind=$this->model_sale_order->sel_is_order($v["order_id"]);
//						if(!empty($is_remind)){
//							//已经提醒发货
//							$remind=1;
//						}else{
//							$remind=0;
//						}
//						$aa[$k]["is_remind"]=$remind;
//
//						$aa[$k]["product"]=$bb["name"];
//					}
//				}
//				$data["orders"]=$aa;
//			}
		}else{
			$require['start'] = ($page - 1) * $this->config->get('config_limit_admin');
			$require['limit'] = $this->config->get('config_limit_admin');
			$data["orders"] = $this->model_sale_return->getReturns($require);
//			if(isset($this->request->get["filter_date_modified"])){
//				$data["orders"] = $this->model_sale_return->getReturns(array("filter_date_modified"=>$year,'start'=> ($page - 1) * $this->config->get('config_limit_admin'),'limit' => $this->config->get('config_limit_admin')));
//			}else{
//				$data["orders"] = $this->model_sale_return->getReturns(array('start'=> ($page - 1) * $this->config->get('config_limit_admin'),'limit' => $this->config->get('config_limit_admin')));
//			}
//			if(isset($this->request->get["filter_order_id"]) && isset($this->request->get["filter_date_modified"])){
//				$data["orders"] = $this->model_sale_return->getReturns(array("filter_date_modified"=>$year,"filter_order_id"=>$this->request->get["filter_order_id"],'start'=> ($page - 1) * $this->config->get('config_limit_admin'),'limit' => $this->config->get('config_limit_admin')));
//			}
//			if(isset($this->request->get["filter_order_id"])){
//				$data["orders"]=$this->model_sale_return->getReturns(array("filter_order_id"=>$this->request->get["filter_order_id"],'start'=> ($page - 1) * $this->config->get('config_limit_admin'),'limit' => $this->config->get('config_limit_admin')));
//			}
		}
		// print_r($data["orders"]);
		// $count1 = ceil(count($data["orders"])/$this->config->get('config_limit_admin'));
		if($id != 4){
			$require['filter_order_status'] = $status;
		}else{
			$require['filter_order_status'] = '';
		}

		$toalss=$this->model_sale_order->getTotalOrders($require);

		$require['filter_order_status'] = "1,2";
		$data["count1"]=$this->model_sale_order->getTotalOrders($require);
		$require['filter_order_status'] = "3,6,7";
		$data["count2"]=$this->model_sale_order->getTotalOrders($require);
		$require['filter_order_status'] = "4,5";
		$data["count3"]=$this->model_sale_order->getTotalOrders($require);
		//退款总数
		$data["count4"]=$this->model_sale_return->getTotalReturns($require);
//		if(isset($this->request->get["filter_date_modified"])){
//			$toalss=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>@$status,"filter_date_modified"=>$year));
//
//			$data["count1"]=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>"1,2","filter_date_modified"=>$year));
//			$data["count2"]=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>"3,6,7","filter_date_modified"=>$year));
//			$data["count3"]=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>"4,5","filter_date_modified"=>$year));
//			//退款总数
//			$data["count4"]=$this->model_sale_return->getTotalReturns(array("filter_date_modified"=>$year));
//
//		}else{
//			$toalss=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>@$status));
//
//			$data["count1"]=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>"1,2"));
//			$data["count2"]=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>"3,6,7"));
//			$data["count3"]=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>"4,5"));
//			//退款总数
//			$data["count4"]=$this->model_sale_return->getTotalReturns();
//
//		}
//		if(isset($this->request->get["filter_order_id"])){
//			$toalss=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>@$status,"filter_order_id"=>$this->request->get["filter_order_id"]));
//
//			$data["count1"]=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>"1,2","filter_order_id"=>$this->request->get["filter_order_id"]));
//			$data["count2"]=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>"3,6,7","filter_order_id"=>$this->request->get["filter_order_id"]));
//			$data["count3"]=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>"4,5","filter_order_id"=>$this->request->get["filter_order_id"]));
//			//退款总数
//			$data["count4"]=$this->model_sale_return->getTotalReturns(array("filter_order_id"=>$this->request->get["filter_order_id"]));
//
//		}
//
//		if(isset($this->request->get["filter_date_modified"]) && isset($this->request->get["filter_order_id"])){
//			$toalss=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>@$status,"filter_date_modified"=>$year,"filter_order_id"=>$this->request->get["filter_order_id"]));
//
//			$data["count1"]=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>"1,2","filter_date_modified"=>$year,"filter_order_id"=>$this->request->get["filter_order_id"]));
//			$data["count2"]=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>"3,6,7","filter_date_modified"=>$year,"filter_order_id"=>$this->request->get["filter_order_id"]));
//			$data["count3"]=$this->model_sale_order->getTotalOrders(array("filter_order_status"=>"4,5","filter_date_modified"=>$year,"filter_order_id"=>$this->request->get["filter_order_id"]));
//			//退款总数
//			$data["count4"]=$this->model_sale_return->getTotalReturns(array("filter_date_modified"=>$year,"filter_order_id"=>$this->request->get["filter_order_id"]));
//
//		}
		//是否是退款
		if($id==4 ){
			//退
			$total1=$data["count4"];
		}else{
			$total1=$toalss;
		}

		$pagination = new Pagination();
		$pagination->total = $total1;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		if(!empty($id)){
			$url .= '&id=' . $this->request->get['id'];
			$pagination->url = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url . '&page={page}', 'SSL');
		}else{
			$pagination->url = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url . '&page={page}', 'SSL');
		}
		
		$data['pagination'] = $pagination->render();
		//这是订单的列表
		

		$data['filter_order_id'] = $filter_order_id;
		$data['filter_customer'] = $filter_customer;
		$data['filter_order_status'] = $filter_order_status;
		$data['filter_total'] = $filter_total;
		$data['filter_date_added'] = isset($this->request->get['filter_date_added'])?$this->request->get['filter_date_added']:null;
		$data['filter_date_modified'] = isset($this->request->get['filter_date_modified'])?$this->request->get['filter_date_modified']:null;
//		$data['filter_date_added'] = $filter_date_added;
//		$data['filter_date_modified'] = $filter_date_modified;
		$data['warehouse_id'] = $warehouse_id;

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['sort'] = $sort;
		$data['order'] = $order;


		$data['store'] = HTTPS_CATALOG;

		// API login
		$this->load->model('user/api');

		$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

		if ($api_info) {
			$data['api_id'] = $api_info['api_id'];
			$data['api_key'] = $api_info['key'];
			$data['api_ip'] = $this->request->server['REMOTE_ADDR'];
		} else {
			$data['api_id'] = '';
			$data['api_key'] = '';
			$data['api_ip'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		/**
		 * 物流方式
		 */
		$this->load->model("sale/shippingcompany");
		$res=$this->model_sale_shippingcompany->selectAll();
		$data["shippingcompany"]=$res;
		// print_r($data);
		//提交修改订单
		if(isset($this->request->get['filter_order_status'])){
			$linkss=$this->url->link('sale/order/sendGoods', 'token=' . $this->session->data['token'] . $url.'&filter_order_status='.$this->request->get['filter_order_status'], 'SSL');
		}else{
			$linkss=$this->url->link('sale/order/sendGoods', 'token=' . $this->session->data['token'] . $url, 'SSL');
		}
		//判断当前的id
		$data["id"]=isset($this->request->get["id"])?$this->request->get["id"]:1;

		$data["send"]=$linkss;
		//ajax异步请求数据
		$data["ajax"]=$this->url->link('sale/order', 'token=' . $this->session->data['token'], 'SSL');
		$data["ajax1"]=$this->url->link('sale/order', 'token=' . $this->session->data['token'], 'SSL');

		$data["agorrefusereturn"]=$this->url->link('sale/order/agorrefusereturn', '', 'SSL');
		$data["return_detail"]=$this->url->link('sale/order/sel_return_detail', '', 'SSL');

		$data["trade_detail"]=$this->url->link('sale/order/trade_detail', '', 'SSL');
		$data["ret_detail"]=$this->url->link('sale/order/ret_detail', '', 'SSL');
		$data["addr"]=$this->url->link('sale/order/addr', '', 'SSL');
		$data["sendgoods"]=$this->url->link('sale/order/sendGoods', '', 'SSL');

		$data["export"]=$this->url->link('sale/order/export', '', 'SSL');
		//确认收货

		$data["ok_accept"]=$this->url->link('sale/order/ok_accept', '', 'SSL');
		
		//收到货款
		$data["accept_money"]=$this->url->link('sale/order/accept_money', '', 'SSL');

		/**
		 * 所有物流公司
		 */
		$data["ship_com"]=$this->model_sale_order->selectAllCompany();

		$this->load->model('catalog/category');
		$this->load->model('user/user');

		$user_id = isset($this->session->data['user_id'])?$this->session->data['user_id']:null;
		$userinfo = $this->model_user_user->getUserinfo1($user_id);
		$data['warehouse'] = $this->model_catalog_category->warehouse($userinfo["merchant_id"]);
		// print_r($this->session->data["user_id"]);
		// print_r($data["orders"]);
		$this->response->setOutput($this->load->view('sale/order_list.tpl', $data));
	}
	//已完成和退款信息导出
	public function export(){
		$this->load->model('user/user');

		// $orders=isset($this->request->post["btSelectItem"])?$this->request->post["btSelectItem"]:null;
		$id=isset($this->request->post["id"])?$this->request->post["id"]:null;
		$user_id = $this->session->data['user_id'];
		//获取用户信息
		$userinfo = $this->model_user_user->getUserinfo1(@$user_id);
		if($userinfo){
			$merchant_id = $userinfo['merchant_id'];
		}
		require_once("PHPExcel/PHPExcel.php");
		// error_reporting(E_ALL);
	    date_default_timezone_set('Europe/London');
	    $objPHPExcel = new PHPExcel();
	    if( empty($id) || ($id!=4 && $id!=3 && $id!=1) ){
	    	echo "<script type='text/javascript'>alert('不能为空');history.back();</script>";
	    	return;
	    }else{
	    	$this->load->model("sale/order");
	    	$this->load->model("sale/return");
	    	$data=array();
	    	if($id==3 || $id==1){
	    		// $list=array();
	    		//这是新订单的
	    		if($id==1){
	    			$order_status_id="1,2";
	    		}else if($id==3){
	    			//这是已完成的
	    			$order_status_id="4,5";
	    		}
				if($merchant_id){
					$list=$this->model_sale_order->port_order(array("order_status_id"=>$order_status_id,'merchant_id'=>$merchant_id));
				}else{
					$list = array();
				}
	    		if(empty($list)){
	    			echo "<script type='text/javascript'>alert('暂无数据');history.back();</script>";
	    			return;
	    		}else{
	    			foreach($list as $k=>$v){
	    				$list[$k]["option"]=$this->model_sale_order->getOrderOptions($v["order_id"],$v["order_product_id"]);
	    			}
	    			$option="";
	    			if(!empty($list)){
	    				foreach($list as $k=>$v){
	    					if($v["order_status_id"]==4){
	    						$order_status="已收货";
	    					}else if($v["order_status_id"]==5){
	    						$order_status="已完成";
	    					}
	    					else if($v["order_status_id"]==1){
	    						$order_status="待付款";
	    					}
	    					else if($v["order_status_id"]==2){
	    						$order_status="待发货";
	    					}
	    					foreach($v["option"] as $k1=>$v1){
	    						$option=$v1["name"]."".$v1["value"];
	    					}
	    					$data1[]=array(
			    				"order_id"=>$v["order_id"],  //订单号
			    				"order_num"=>$v["order_num"], //支付单号
			    				"ship_order_no"=>$v["ship_order_no"], //物流单号
			    				"freight"=>$v["freight"],  //物流费用
			    				"nickname"=>$v["firstname"], //昵称
			    				"accept_name"=>$v["shipping_firstname"],  //收货人
			    				"address"=>$v["shipping_country"].$v["shipping_zone"].$v["shipping_city"].$v["shipping_address_1"], //地址
			    				"phone"=>$v["telephone"], //电话
			    				"quantity"=>$v["quantity"], //数量
			    				"total"=>sprintf("%.2f",$v["total"]),  //订单总价格
			    				"product_name"=>$v["name"], //产品名称
			    				"price"=>sprintf("%.2f",$v["price"]), //产品价格
			    				"option"=>$option, //规格
			    				"order_status"=>$order_status, //支付状态
			    				"order_time"=>date("Y-m-d H:i:s",$v["date_added"]) //订单时间
			    			);
			    			
	    				}
	    			}
	    			$name=date("Y_m_d",time())."日完成订单信息导出";
	    			
	    			$data=$data1;
	    		}
	    		
	    	}else if($id==4){
	    		//退款
	    		// $data=array("1"=>"2");
	    		// $list=array();
				if($merchant_id){
					$list=$this->model_sale_order->sel_retrun_order($merchant_id);
				}else{
					$list = array();
				}

				$data1 = array();
    			if(!empty($list)){
    				foreach($list as $k=>$v){
    					$list[$k]["option"]=$this->model_sale_order->getOrderOptions($v["order_id"],$v["order_product_id"]);
    				}
    				$option="";
    				if(!empty($list)){
	    				foreach($list as $k=>$v){
	    					if($v["return_status_id"]==3){
	    						$order_status="同意退款";
	    					}else if($v["return_status_id"]==1){
	    						$order_status="待处理";
	    					}else if($v["return_status_id"]==4){
	    						$order_status="拒绝退款";
	    					}
	    					
	    					foreach($v["option"] as $k1=>$v1){
	    						$option=$v1["name"]."".$v1["value"];
	    					}
	    					$data1[]=array(
			    				"order_id"=>$v["order_id"],  //订单号
			    			// 	"order_num"=>$v["order_num"], //支付单号
			    			// 	"ship_order_no"=>$v["ship_order_no"], //物流单号
			    			// 	"freight"=>$v["freight"],  //物流费用
			    				"nickname"=>$v["firstname"], //昵称
			    			// 	"accept_name"=>$v["shipping_firstname"],  //收货人
			    			// 	"address"=>$v["shipping_country"].$v["shipping_zone"].$v["shipping_city"].$v["shipping_address_1"], //地址
			    				"phone"=>$v["telephone"], //电话
			    				"quantity"=>$v["quantity"], //数量
			    				"total"=>sprintf("%.2f",$v["total"]),  //订单总价格
			    				"product_name"=>$v["name"], //产品名称
			    				"price"=>sprintf("%.2f",$v["price"]), //产品价格
			    				"option"=>$option, //规格
			    				"return_status"=>$order_status, //退货状态
			    				"return_time"=>$v["date_modified"],  //date("Y-m-d H:i:s",$v["date_added"]) //退款时间
			    				"comment"=>empty($v["comment"])?"暂无回复":$v["comment"],
			    				"returnreason"=>empty($v["returnreason"])?"暂无退款理由":$v["returnreason"]
			    			);			  
	    				}
	    			}
	    			
		    	}	
		    	$data=$data1;

	    		$name=date("Y_m_d",time())."日退款订单信息导出";
	    	}
	    }
	    if(empty($data)){
	    	echo "<script type='text/javascript'>alert('暂无数据');history.back();</script>";
	    	return;
	    }else{
	    	// die;
	    	if($id==3 || $id==1){
	    		/*以下是一些设置 ，什么作者  标题啊之类的*/
	    		$objPHPExcel->getProperties()->setCreator(date("Y-m-d",time())."订单信息导出")
		                           ->setLastModifiedBy(date("Y-m-d",time())."订单信息导出")
		                           ->setTitle(date("Y-m-d",time())."订单信息导出")
		                           ->setSubject(date("Y-m-d",time())."订单信息导出")
		                           ->setDescription(date("Y-m-d",time())."订单信息导出")
		                           ->setKeywords("excel")
		                          ->setCategory("result file");
		        $objPHPExcel->getActiveSheet()->setCellValue('H1', '嗨企货仓导出订单');
		        //设置H1字体大小
	        	$objPHPExcel->getActiveSheet()->getStyle('H1')->getFont()->setSize(20);
	        	//合并H和I
	        	$objPHPExcel->getActiveSheet()->mergeCells('H1:I1');

	    	}else if($id==4){
	    		$objPHPExcel->getProperties()->setCreator(date("Y-m-d",time())."退款订单信息导出")
		                           ->setLastModifiedBy(date("Y-m-d",time())."退款订单信息导出")
		                           ->setTitle(date("Y-m-d",time())."退款订单信息导出")
		                           ->setSubject(date("Y-m-d",time())."退款订单信息导出")
		                           ->setDescription(date("Y-m-d",time())."退款订单信息导出")
		                           ->setKeywords("excel")
		                          ->setCategory("result file");
		        $objPHPExcel->getActiveSheet()->setCellValue('F1', '嗨企货仓退款订单');
		        //设置H1字体大小
	        	$objPHPExcel->getActiveSheet()->getStyle('F1')->getFont()->setSize(20);
	        	//合并H和I
	        	$objPHPExcel->getActiveSheet()->mergeCells('F1:G1');

	    	}
	   		
		     /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
		    //填入主标题
        	$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(35);
        	
        	
        	$objPHPExcel->getActiveSheet()->getDefaultColumnDimension('A')->setWidth(16);
        	// $objPHPExcel -> getActiveSheet() -> getColumnDimension() -> setAutoSize(true);

        	$num1=3;
        
		    foreach($data as $k => $v){
		         $num=$num1++;
		    	if($id==3 || $id==1){
		    		 $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', "订单编号")
		         			 ->setCellValue('B2', "订单号")
		         			 ->setCellValue('C2', "物流单号")
		         			 ->setCellValue('D2', "物流费用")
		         			 ->setCellValue('E2', "昵称")
		         			 ->setCellValue('F2', "收货人")
		         			 ->setCellValue('G2', "收货地址")
		         			 ->setCellValue('H2', "收货人电话")
		         			 ->setCellValue('I2', "商品数量")
		         			 ->setCellValue('J2', "订单总价格")
		         			 ->setCellValue('K2', "商品名称")
		         			 ->setCellValue('L2', "商品价格")
		         			 ->setCellValue('M2', "商品规格")
		         			 ->setCellValue('N2', "订单状态")
		         			 ->setCellValue('O2', "订单时间")
		         			 ;
		         $objPHPExcel->setActiveSheetIndex(0)
		                     //Excel的第A列，uid是你查出数组的键值，下面以此类推
		                      ->setCellValue('A'.$num, $v['order_id'])    
		                      ->setCellValue('B'.$num, $v['order_num'])
		                      ->setCellValue('C'.$num, $v['ship_order_no'])
		                      ->setCellValue('D'.$num, $v['freight']."元")
		                      ->setCellValue('E'.$num, $v['nickname'])
		                      ->setCellValue('F'.$num, $v['accept_name'])
		                      ->setCellValue('G'.$num, $v['address'])
		                      ->setCellValue('H'.$num, $v['phone'])
		                      ->setCellValue('I'.$num, $v['quantity'])
		                      ->setCellValue('J'.$num, $v['total']."元")
		                      ->setCellValue('K'.$num, $v['product_name'])
		                      ->setCellValue('L'.$num, $v['price']."元")
		                      ->setCellValue('M'.$num, $v['option'])
		                      ->setCellValue('N'.$num, $v['order_status'])
		                      ->setCellValue('O'.$num, $v['order_time'])
		                      ;
		    	}else if($id==4){
		    		 $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', "订单号")
		         			 ->setCellValue('B2', "昵称")
		         			 ->setCellValue('C2', "联系方式")
		         			 ->setCellValue('D2', "商品数量")
		         			 ->setCellValue('E2', "订单总价格")
		         			 ->setCellValue('F2', "商品名称")
		         			 ->setCellValue('G2', "商品价格")
		         			 ->setCellValue('H2', "商品规格")
		         			 ->setCellValue('I2', "退款理由")
		         			 ->setCellValue('J2', "回复理由")
		         			 ->setCellValue('K2', "退款状态")
		         			 ->setCellValue('L2', "退款时间")
		         			 ;
		         $objPHPExcel->setActiveSheetIndex(0)
		                     //Excel的第A列，uid是你查出数组的键值，下面以此类推
		                      ->setCellValue('A'.$num, $v['order_id'])  
		                      ->setCellValue('B'.$num, $v['nickname'])
		                      ->setCellValue('C'.$num, $v['phone'])
		                      ->setCellValue('D'.$num, $v['quantity'])
		                      ->setCellValue('E'.$num, $v['total']."元")
		                      ->setCellValue('F'.$num, $v['product_name'])
		                      ->setCellValue('G'.$num, $v['price']."元")
		                      ->setCellValue('H'.$num, $v['option'])
		                      ->setCellValue('I'.$num, $v['returnreason'])
		                      ->setCellValue('K'.$num, $v['comment'])
		                      ->setCellValue('K'.$num, $v['return_status'])
		                      ->setCellValue('L'.$num, $v['return_time'])
		                      ;

		    	}
		    }
	        $objPHPExcel->getActiveSheet()->setTitle(date("Y-m-d",time())."订单信息导出");
	        // print_r($objPHPExcel);
	        // die;
	        // $objPHPExcel->setActiveSheetIndex(0);
	        // $objPHPExcel->createSheet();
	         header('Content-Type: application/vnd.ms-excel');
	         header('Content-Disposition: attachment;filename="'.$name.'.xls"');
	         header('Cache-Control: max-age=0');
	         $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	         $objWriter->save('php://output');                   
	    }
        exit;

	}

	//收到货款
	public function accept_money(){
		$this->load->model("sale/order");
		$pay_type=isset($this->request->post["pay_type"])?$this->request->post["pay_type"]:null;
		//支付单号
		$order_num=isset($this->request->post["order_num"])?$this->request->post["order_num"]:null;
		$order=isset($this->request->post["order"])?$this->request->post["order"]:null;
		if($pay_type==null){
			$json["error"]="请选择支付方式";	
		}
		if($order_num==null && $pay_type==1){
			$json["error"]="支付单号不能为空";	
		}
		if($order==null){
			$json["error"]="订单号不能为空";	
		}
		if($pay_type!=1 && $pay_type!=2 ){
			$json["error"]="支付方式不存在";	
		}
		if(!empty($pay_type) && !empty($order) && ($pay_type==1 || $pay_type==2) ){
			//查询订单号是否存在
			$order_list=$this->model_sale_order->getOrder($order);
			if(empty($order_list)){
				$json["error"]="该订单不存在";
			}else{
				if($pay_type==1){
					if(!empty($order_num)){
// HTTP_SERVER.'controller/sale/'
						//支付宝
						require_once("alipay.config.php");
						require_once("lib/alipay_core.function.php");
						// $url='https://openapi.alipay.com/gateway.do?timestamp='.urlencode(date("Y-m-d H:i:s",time())).'&method=alipay.trade.query&app_id=2016082601805285&sign_type=RSA&sign=MpRUDsnXilON%2F6YiF3TkpNGh2Z7s5cSCScP%2BAsqos%2BrW8AGZqOHYk3G%2FyEWOlZGVQ5popz6K%2BdAkH64Whw3Fnap70utnFg23A1v9uP%2B71ZRMZekwRoifeYp%2Fwpo8SzwHpmJYKeggj4xvlo4zf7%2BtBybTUtol7tRITrRyLhJwj34%3D&version=1.0&request.setBizContent={"out_trade_no":"ssdf20160910e1g8","trade_no":"2016091021001004000247043378"}&version=1.0&charset=utf-8';
						// // echo $url;
						// $res=file_get_contents($url);
						// echo $res;
						require_once("aop/AopClient.php");
						require_once("aop/request/AlipayTradeQueryRequest.php");

						$aop = new AopClient ();
						$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
						$aop->appId = '2016082601805285';
						$aop->rsaPrivateKeyFilePath = $alipay_config["private_key_path"];//RSA私钥
						$aop->alipayPublicKey       = $alipay_config["ali_public_key_path"];//支付宝公钥
						$aop->apiVersion = '1.0';
						$aop->postCharset='utf-8';
						$aop->format='json';
						$request = new AlipayTradeQueryRequest ();
						$out_trade_no=$order_list["order_num"];
						$trade_no=$order_num;

						//ssdf20160910e1g8    2016091021001004000247043378
						$request->setBizContent("{" .
								"    \"out_trade_no\":\"$out_trade_no\"," .
								"    \"trade_no\":\"$trade_no\"" .
								"  }");
						$result = $aop->execute ( $request,null);
						$res=$result->alipay_trade_query_response;
						// print_r($res);
						// print_r($order_list["order_num"]);
						//判断是否已经成功
						if($res->code=="10000" && $res->msg=="Success" && $res->trade_status=="TRADE_SUCCESS"){
							if($res->out_trade_no!=$order_list["order_num"]){
								$json["error"]="支付宝订单号和平台订单号不一致";
							}else{
								//成功
								// $json["error"]="该订单已经支付";
								// 修改订单状态
								$this->model_sale_order->ok_accepts(array("order_status_id"=>2,"order_id"=>$order));
								$json["code"]=0;
							}
						}else{
							$json["error"]="该订单没有支付";
						}
					}else{
						$json["error"]="没有支付单号";
					}
				}else if($pay_type==2){
					$order_out = $this->model_sale_order->sed_share($order);
					if($order_out){
						$type = 2;
					}else{
						$type = 1;
					}
					//微信
					// $json["msg"]="这是微信";
					require_once("lib/WxPay.Api.php");
					require_once("lib/WxPayPubHelper.php");
					//$type=1时是app收到货款，=2时是分享或H5的收到货款
					if($type == 1){
						$input = new \WxPayOrderQuery();

						//商户支付订单id
						$transaction_out_id = $order_list["order_num"];
						$input->SetOut_trade_no($transaction_out_id);

						$result = \WxPayApi::orderQuery($input);
					}else{
						$res = new \OrderQuery_pub();

						$transaction_out_id = $order_list["order_num"];
						$res->setParameter('out_trade_no',$transaction_out_id);
						$result = $res->getResult();
					}
		            if(array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && array_key_exists("trade_state", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS" && $result["trade_state"] == "SUCCESS")
		            {
		            	if($result["out_trade_no"]!=$order_list["order_num"]){
		            		$json["error"]="微信订单号和平台订单号不一致";
		            	}else{
		            		$this->model_sale_order->ok_accepts(array("order_status_id"=>2,"order_id"=>$order));
							$json["code"]=0;
		            	}
		            }else{
		            	$json["error"]="该订单没有支付";
		            }

				}

			}
		}


		$this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
	}
	/**
	 * 根据订单查询收货地址
	 */
	public function addr(){
		$this->load->model("sale/order");
		$order=isset($this->request->post["order"])?$this->request->post["order"]:null;
		if(!empty($order)){
			//根据订单编号查询订单信息
			$order_list=$this->model_sale_order->getOrder($order);
			$address=$order_list["shipping_country"].$order_list["shipping_zone"].$order_list["shipping_city"].$order_list["shipping_address_1"];
			$data["phone"]=$order_list["shipping_custom_field"][1];
			$data["address"]=$address;
			$data["buyer"]=$order_list["customer"];
			$data["order_id"]=$order_list["order_id"];
			$json=$data;
		}else{
			$data["phone"]='暂无信息';
			$data["address"]='暂无信息';
			$data["buyer"]='暂无信息';
			$data["order_id"]=$order;
			$json=$data;
		}
		$this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
	}
	/**
	 * 确认收货
	 */
	public function ok_accept(){
		$this->load->model("sale/order");
		$order=isset($this->request->post["order"])?$this->request->post["order"]:null;
		if(!empty($order)){
			$order_list=$this->model_sale_order->getOrder($order);
			if(empty($order_list)){
				$json["error"]="没有该订单信息";
			}else{

				//查询该订单
				if($order_list["order_status_id"]!=3){
					$json["error"]="该订单的状态错误,不能收货";
				}else{
					//判断时间
					if(date("Y-m-d",time())>date("Y-m-d",strtotime("+1week",strtotime($order_list["ship_date"])))){
						$json["error"]="发货时间一周后才可以操作";
					}else{
						//查询是否进行过退款
						$is_return_money=$this->model_sale_order->is_check_return_money($order);
						if($is_return_money){
							//已经申请过退款
							$json["error"]="有申请退款的订单，不能操作";
						}else{
							//没有申请过  或者同意退款
							//修改订单状态
							$this->model_sale_order->ok_accepts(array("order_status_id"=>4,"order_id"=>$order));
							$json["code"]=0;
						}

					}

					// //第三方分享，增加商户价格
					// if(!empty($order_list)){
					// 	foreach($order_list as $k=>$v){
					// 		$sed[]=$this->model_sale_order->sed_share($v["order_id"]);
					// 		// $sed[]=$sed1->row;
					// 	}
					// }
					// if(!empty($sed)){
					// 	foreach($sed as $k=>$v){
					// 		//增加第三方的价格
					// 		//增加用户资金记录
					// 		$this->model_sale_order->money_record(array("differenceprice"=>$v["differenceprice"],"customer_id"=>$v["customer_id"],));
					// 	}
					// }
					
				}
			}
		}else{
			$json["error"]="订单号不能为空";
		}

		$this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
	}


	/**
	 * 修改订单信息   立即发货
	 */
	public function sendGoods(){
		$res=isset($this->request->post)?$this->request->post:null;
		// print_r($res);
		$this->load->model("sale/order");
		if(empty($res["order"])){
			$json["error"]="该订单不能为空";
		}
		if(empty($res["ship_ids"])){
			$json["error"]="该物流编号不能为空";
		}
		if(empty($res["order_num"])){
			$json["error"]="物流单号不能为空";
		}else{
			//查询是否有该订单号
			$order_list=$this->model_sale_order->getOrder($res["order"]);
			if(empty($order_list)){
				$json["error"]="没有该订单信息";
			}
			//查询是否有该物流公司
			$com=$this->model_sale_order->selectCompany($res["ship_ids"]);
			if(empty($com)){
				$json["error"]="没有该物流编号";
			}
			if(!empty($order_list) || !empty($com)){
				$data=array(
					"order_id"=>$res["order"],
					"ship_id"=>$res["ship_ids"],
					"num"=>$res["order_num"],
					"date"=>date("Y-m-d H:i:s",time())
				);
				$data1=array(
					"order_id"=>$res["order"],
					"customer_id"=>$order_list["customer_id"]
				);
				//查询是否进行过退款
				$is_return_money=$this->model_sale_order->is_check_return_money($res["order"]);
				if($is_return_money){
					//已经申请过退款
					$json["error"]="有申请退款的订单，不能操作";
				}else{
					//没有申请过  或者同意退款
					//修改订单状态
					//给买家发送发货通知
					$info = $this->model_sale_order->getNameByOrderID($res["order"]);
					$this->sendSingleMt($info['telephone'],"【嗨企货仓】"."尊敬的".$info['firstname'].",您购买的商品已搭乘".@$com[0]['com']."快递列车，班次(运单号)".$res["order_num"]."出发，请前往“嗨企货仓”客户端查看物流详情");
					$this->model_sale_order->insert_trans($data1);
					$this->load->model("sale/order");
					$this->model_sale_order->sendRightNow($data);
					$json["msg"]="发货成功";
				}
				
			}
			
		}
		$this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

	}

	function sendSingleMt($mobile,$msg) {//下发 短信
        // 在这里拦截转发到新接口 6-1 by lcb
        include rtrim(DIR_APPLICATION, '/').'/lib/sms.php';
        if(class_exists('sms')){
            return (new sms())->send($mobile, $msg, true, 'iwantcdm');
        }

        //预定义参数，参数说明见文档
        $host = "si.800617.com:4400";
        $mobile=$mobile;
        $message=urlencode(iconv("UTF-8","GB2312","$msg"));

        $request = "/SendLenSms.aspx?un=hzssdf-1&pwd=5ffcc9&mobile=".$mobile."&msg=".$message;
        $content = $this->_dopostrequest($host,80,$request);
        $data = array("mobile"=>$mobile,"msg"=>$message,"results"=>$content);
        // $this->DBW->insert('lee_sms_log', $data);
        $res=json_encode(simplexml_load_string($content));
        
        if(@$res["Result"]){
            //成功
            return json_encode(array("retcode"=>0));
        }else{
            //失败
            return json_encode(array("retcode"=>1000));
        }
        
        // $this->response->addHeader('Content-Type: application/json');
        // $this->response->setOutput(json_encode($json));
    }
    function _dopostrequest($host,$port,$request) {
        $httpGet  = "GET ". $request. " HTTP/1.1\r\n";
        $httpGet .= "Host: $host\r\n";
        $httpGet .= "Connection: Close\r\n";
        $httpGet .= "Content-type: text/plain\r\n";
        $httpGet .= "Content-length: " . strlen($request) . "\r\n";
        $httpGet .= "\r\n";
        $httpGet .= $request;
        $httpGet .= "\r\n\r\n";
        return $this->_httpsend($host,$port,$httpGet);
    }
    function _httpsend($host,$port,$request) {
        $result = "";
        $fp = @fsockopen($host, $port,$errno,$errstr,5);
        if ( $fp ) {
            fwrite($fp, $request);
            while(! feof($fp)) {
                $result .= fread($fp, 1024);
            }
            fclose($fp);
        }
        else
        {
            //超时了
            return json_encode(array("retcode"=>1207));
            // return ErrorInfo::$smsGateWayTimeout;//超时标志
        }
        list($header, $foo)  = explode("\r\n\r\n", $result);
        list($foo, $content) = explode($header, $result);
        $content=str_replace("\r\n","",$content);
        return $content;
    }



	/**
	 * 查看交易详情
	 */
	public function trade_detail(){
		$this->load->model("sale/return");
		$this->load->model("sale/order");
		$this->load->model("catalog/product");
		$order=isset($this->request->post["order"])?$this->request->post["order"]:null;
		if(!empty($order)){

			//根据订单编号查询订单信息
			$order_list=$this->model_sale_order->getOrder($order);

			//获取产品规格
			$product=$this->model_sale_order->getOrderProducts($order);
			$option=$this->model_sale_order->getOrderOptions($order,$product[0]["order_product_id"]);
			$pro_img=$this->model_catalog_product->getProduct($product[0]["product_id"]);
			$pro_option=array();
			if(!empty($option)){
				foreach($option as $k=>$v){
					$pro_option[$k]["op_name"]=$v["name"];
					$pro_option[$k]["op_value"]=$v["value"];
				}
			}
			$address=$order_list["shipping_country"].$order_list["shipping_zone"].$order_list["shipping_city"].$order_list["shipping_address_1"];
			$data["price"]=sprintf("%.2f",empty($pro_img["price"])?"暂无信息":$pro_img["price"]);
			$data["old_price"]=sprintf("%.2f",empty($pro_img["proxyprice"])?"暂无信息":$pro_img["proxyprice"]);
			$data["name"]=empty($pro_img["name"])?"":$pro_img["name"];
			$data["buyer"]=$order_list["customer"];
			$data["photo"]=empty($pro_img["image"])?"暂无信息":TEST_IP.'/image/'.$pro_img["image"];
			$data["option"]=$pro_option;
			$data["phone"]=$order_list["telephone"];
			$data["address"]=$address;
			$data["accept_name"]=$order_list["shipping_firstname"];
			$data["go"]=empty($pro_img["freight"])?"0.00":$pro_img["freight"];
			
			//引用物流信息
			
			$ships=$this->sel_admin_ship($order_list["order_id"],$order_list["customer_id"]);
			if(!empty($ships["Traces"])){
				$data["ship"]=$ships["Traces"];
			}else{
				if(isset($ships["Reason"])){
					$data["reason"]=$ships["Reason"];
				}else{
					$data["reason"]="该订单暂无物流信息";
				}
				$data["ship"]=array();
			}
			$data["com"]=@$ships["company"];
			// print_r($data);
			$json=$data;

		}else{
			$data["price"]="暂无信息";
			$data["old_price"]="暂无信息";
			$data["name"]="暂无信息";
			$data["buyer"]="暂无信息";
			$data["photo"]=TEST_IP.'/image/placeholder_com.9.png';
			$data["option"]=array();
			$data["phone"]="暂无信息";
			$data["address"]="暂无信息";
			$data["accept_name"]="暂无信息";
			$data["go"]="暂无信息";
			
			$data["reason"]="该订单暂无物流信息";
			$json=$data;
		}
		$this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
	}
	/**
     * 提供给后台查询信息
     */
    public function sel_admin_ship($typeNu,$check){
        //根据快递订单号和用户编号查询
        $this->load->model("sale/order");
        $res11=$this->model_sale_order->selectBypersonAndNum(array("customer_id"=>$check,"num"=>$typeNu));
        
        //根据快递公司编号查询快递公司信息    229973531902
        $shippingcompany=$this->model_sale_order->selectCompany(@$res11[0]["ship_id"]);
        
        $typeCom=@$shippingcompany[0]["cod"];
        $typeNu=@$res11[0]["ship_order_no"];
        // $typeCom="STO";
        // $typeNu ='229973531902';
        if(!isset($typeCom) || !isset($typeNu)){
        	return array();
        }
        $requestData= "{'OrderCode':'','ShipperCode':'$typeCom','LogisticCode':'$typeNu'}";

        // print_r($shippingcompany);

        $id="1263768";
        $key="36278c0d-8168-4f05-9ef6-b0d749cfc47a";
        $url="http://api.kdniao.cc/api/dist";

        $datas = array(
            'EBusinessID' => $id,
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, $key);
        $result=$this->sendPost($url, $datas);   
        
        //根据公司业务处理返回的信息......
        $res=json_decode($result,"json");
        // $res["retcode"]=0;
        // print_r($res["Traces"]);
        $sort = array(  
        'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
        'field'     => 'AcceptTime',       //排序字段  
        );
        $arrSort = array();
        if(!empty($res["Traces"])){
            foreach($res["Traces"] AS $uniqid => $row){  
                foreach($row AS $key=>$value){  
                    $arrSort[$key][$uniqid] = $value;  
                }  
            }  
            if($sort['direction']){  
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $res["Traces"]);  
            }
        }
        // //快递公司信息
        $res["company"]=!empty($shippingcompany[0]["com"])?@$shippingcompany[0]["com"]:"";
        // $res["phone"]=!empty($shippingcompany[0]["ship_phone"])?@$shippingcompany[0]["ship_phone"]:"";
        // $res["img"]=!empty($shippingcompany[0]["ship_img"])?HTTP_SERVER.'image/'.$shippingcompany[0]["ship_img"]:"";
        return $res;
    }

    /**
     * 发送物流的信息
     */
    /**
     * 电商Sign签名生成
     * @param data 内容   
     * @param appkey Appkey
     * @return DataSign签名
     */
    function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }
    /**
     *  post提交数据 
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据 
     * @return url响应返回的html
     */
    function sendPost($url, $datas) {
        $temps = array();   
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);      
        }   
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(!isset($url_info['port']))
        {
            $url_info['port']=80;   
        }
        // echo $url_info['port'];
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }

        return $gets;
        fclose($fd);  
        
    }




	/**
	 * 查看退款退货详情
	 */
	public function ret_detail(){
		$this->load->model("sale/return");
		$this->load->model("sale/order");
		$return_id=isset($this->request->post["order"])?$this->request->post["order"]:null;
		if(!empty($return_id)){
			$return=$this->model_sale_return->getReturn($return_id);
			if(!empty($return)){
				// $json=$data;
				$productinfo = $this->model_sale_return->getProductinfo($return['product_id']);//订单商品详情
				$seller = $this->model_sale_return->getMerchant($productinfo['merchant_id']);
				$returnhistory=$this->model_sale_return->getreturnhis($return["return_id"]);
				if(!empty($returnhistory)){
					foreach($returnhistory as $key=>$val){
                        if($val['return_status_id'] == 1){
                            $returnprocess[$key]['processdate'] = $val['date_added'];
                            $returnprocess[$key]['info'] = "买家（".$return['firstname']."）创建了退款申请，".$val['comment'];
                        }else if($val['return_status_id'] == 3){
                            $returnprocess[$key]['processdate'] = $val['date_added'];
                            $returnprocess[$key]['info'] = "卖家（".$seller['merchant_name']."）已经同意了申请，交易款项已归还至".$return['firstname']."账户";
                        }else if($val['return_status_id'] == 4){
                            $returnprocess[$key]['processdate'] = $val['date_added'];
                            $returnprocess[$key]['info'] = "卖家（".$return['firstname']."）已经拒绝了退款申请，".$val['comment'];
                        }
                    }
                }
                $json=$returnprocess;

			}else{
				$json=array();
			}

		}else{
			$json=array();
		}
		$this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

	}


	/**
	 * 查看退款详情
	 */
	public function sel_return_detail(){
		$this->load->model("sale/return");
		$return_id=isset($this->request->post["order"])?$this->request->post["order"]:null;
		if(!empty($return_id)){
			$data=$this->model_sale_return->getReturn($return_id);
			if(!empty($data)){
				$json["reason"]=$data["returnreason"];
				$json["return"]=$data["return_id"];
				$json["person"]=$data["firstname"];
			}else{
				$json=array();
			}

		}else{
			$json=array();
		}
		$this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
	}

	/**
	 * 同意退款，还是拒绝退款
	 */
	public function agorrefusereturn(){
		$this->load->model("sale/return");
		$this->load->model("sale/order");
		$this->load->model('customer/customer_merchant');
		$this->load->model('customer/customer');

		$id=isset($this->request->post["id"])?$this->request->post["id"]:null;
		$content=isset($this->request->post["content"])?$this->request->post["content"]:null;
		$button=isset($this->request->post["button"])?$this->request->post["button"]:null;
		if(!empty($id) && !empty($button) ){
			$return_money=0;
			//根据退款编号查询
			$data=$this->model_sale_return->getReturn($id);
			if(!empty($data)){
				$customer_id=$data["customer_id"];
				$order_id=$data["order_id"];
				$order = $this->model_sale_order->Order($order_id);
				$merchant_id = $order['merchant_id'];
				$return_money = $order['total'] - $order['freight']-$order['invoicefee'];
			}
			if($button=="agree"){

				if($order['payment_method'] == 3 || $order['payment_method'] == ''){
					//同意
					$return = array(
							'return_id'=>$id,
							'return_status_id'=>3
					);
					$this->model_sale_return->updateReturn($return);
					$history = array(
							'returnid'=>$id,
							'status'=>3,
							'comment'=>'卖家已同意了退款申请'.",".$content
					);
					//修改订单为关闭状态
					$this->model_sale_order->ok_accepts(array("order_status_id"=>7,"order_id"=>$order["order_id"]));


					// //第三方分享，增加商户价格
					$sed=$this->model_sale_order->sed_share($order["order_id"]);
					if(!empty($sed)){
						//增加第三方的价格
						//增加用户资金记录
						$this->model_sale_order->money_record11(array("differenceprice"=>$sed["differenceprice"],"customer_id"=>$sed["customer_id"],"order_id"=>$sed["order_id"]));

						//将商户余额中的该订单金额退还
						$money = $return_money - $sed["differenceprice"];
						$this->model_customer_customer_merchant->edit_merchant_money($merchant_id,$money);

					}else{
						//将商户余额中的该订单金额退还
						$money = $return_money;
						$this->model_customer_customer_merchant->edit_merchant_money($merchant_id,$money);
					}


					$this->model_sale_return->addreturnhistory11($history);
					$this->model_sale_return->updateBalanceNow($return_money,$customer_id,2);
				}else if($order['payment_method'] == 2){
					if($data['return_status_id']  == 1){
						//微信原路退款
						$order_out = $this->model_sale_order->sed_share($order["order_id"]);
						if($order_out){
							$type = 2;
						}else{
							$type = 1;
						}
						//微信
						require_once("lib/WxPay.Api.php");
						require_once("lib/WxPay.Data.php");
						require_once("lib/WxPay.Config.php");
						require_once("lib/WxPayPubHelper.php");
						//$type=1时是app收到货款，=2时是分享或H5的收到货款

						if($type == 1){
							$input = new \WxPayRefund();
							$total_fee = 1;
							$refund_fee = 1;
							//商户支付订单id
							$transaction_out_id = $order["order_num"];

							$input->SetOut_trade_no($transaction_out_id);
							$input->SetOut_refund_no($id);
							$input->SetTotal_fee($total_fee);
							$input->SetRefund_fee($refund_fee);
							$input->SetOp_user_id($id);
							$input->SetAppid(\WxPayConfig::APPID);//公众账号ID
							$input->SetMch_id(\WxPayConfig::MCHID);//商户号
							$input->SetNonce_str($this->getNonceStr());//随机字符串

							$input->SetSign();//签名

							$xml = $input->ToXml();
							$url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
							$result = $this->curl_post_ssl($url, $xml, $second=30,$aHeader=array());

						}else{
							$res = new \Refund_pub();

							$transaction_out_id = $order["order_num"];
							$total_fee = 1;
							$refund_fee = 1;

							$res->setParameter('out_trade_no',$transaction_out_id);
							$res->setParameter('out_refund_no',$id);
							$res->setParameter('total_fee',$total_fee);
							$res->setParameter('refund_fee',$refund_fee);
							$res->setParameter('op_user_id',$id);

							$result = $res->getResult();
						}
						if(array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && array_key_exists("trade_state", $result) && $result["return_code"] == "SUCCESS"){
							$return = array(
									'return_id'=>$id,
									'return_status_id'=>3
							);
							$this->model_sale_return->updateReturn($return);
							$history = array(
									'returnid'=>$id,
									'status'=>3,
									'comment'=>'卖家已同意了退款申请'.",".$content
							);
							//修改订单为关闭状态
							$this->model_sale_order->ok_accepts(array("order_status_id"=>7,"order_id"=>$order["order_id"]));
							$this->model_sale_return->addreturnhistory11($history);
						}else{
							$json["ret"]=1;
						}
					}else{
						$json["ret"]=1;
					}

				}

			}else if($button=="refuse"){
				//拒绝
				$return = array(
	                'return_id'=>$id,
	                'return_status_id'=>4
	            );
	            $this->model_sale_return->updateReturn($return);

	            $history = array(
	                'returnid'=>$id,
	                'status'=>4,
	                'comment'=>'拒绝理由：'.$content
	            );
	            $this->model_sale_return->addreturnhistory11($history);
			}
			$json["ret"]=0;
		}else{
			//失败
			$json["ret"]=1;
		}
		$this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

	}

	public static function getNonceStr($length = 32){
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
		}
		return $str;
	}

	function curl_post_ssl($url, $vars, $second=30,$aHeader=array())
	{
		$ch = curl_init();
		//超时时间
		curl_setopt($ch,CURLOPT_TIMEOUT,$second);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		//这里设置代理，如果有的话
		//curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
		//curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

		//以下两种方式需选择一种

		//第一种方法，cert 与 key 分别属于两个.pem文件
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLCERT,'\www\web\iwant\public_html\highup\admin\controller\sale\cert\apiclient_cert.pem');
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLKEY,'\www\web\iwant\public_html\highup\admin\controller\sale\cert\apiclient_key.pem');

		//第二种方式，两个文件合成一个.pem文件
//		curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');

		if( count($aHeader) >= 1 ){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
		}

		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
		$data = curl_exec($ch);
		if($data){
			curl_close($ch);
			return $data;
		}
		else {
			$error = curl_errno($ch);
			echo "call faild, errorCode:$error\n";
			curl_close($ch);
			return false;
		}
	}

	/**
	 *  ajax请求数据 
	 */
	public function ajax_json(){
		$this->load->model('sale/order');
		if(!empty($this->request->post)){
			$json["code"]=0;
			$id=$this->request->post["id"];
			//新订单（未发货，待付款），未完成（待收货，已取消，已关闭），已完成
			if($id=="1"){
				//新订单
				$status="1,2";

			}else if($id=="2"){
				//未完成
				$status="3,6,7";
			}else if($id=="3"){
				//已完成
				$status="4,5";
			}else if($id=="4"){
				//退货
			}
			if (isset($this->request->get['page'])) {
				$page = $this->request->get['page'];
			} else {
				$page = 1;
			}
			$orders=$this->model_sale_order->getOrders(array("filter_order_status"=>$status,'start'=> ($page - 1) * $this->config->get('config_limit_admin'),'limit' => $this->config->get('config_limit_admin')));

			$json['count'] = ceil(count($orders)/10);
			// $json["page"]
			
			$json["orders"]=$orders;

		}

		$this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));


	}
	
	/**
	 * 账户资金
	 */
	public function sale_god_money(){
		$this->load->model("user/user");
		//查询个人信息
		$id=$this->session->data["user_id"];
		$user=$this->model_user_user->getUser($id);
		$status = isset($this->request->get['status'])?$this->request->get['status']:1;
		if($status == 1){
			$merchant=array();
			$name="";
			$firstname="";
			$firstname=@$user["firstname"];
			$name=@$user["username"];
			$merchant=$this->model_user_user->sel_gods_msg(@$user["merchant_id"]);
			$data["user"]=$merchant;
			$data["user"]["name"]=$name;
			$data["user"]["firstname"]=$firstname;

			if(!empty($data["user"]["mer_headurl"])){
				$data["user"]["mer_headurl"]=TEST_IP.'/image/'.$data["user"]["mer_headurl"];
			}
			//查询月销售和年销售
			$this->load->model("sale/order");

			$year=$this->model_sale_order->monthOryear(array("date_added"=>date("Y",time()),"merchant_id"=>$user["merchant_id"],"year"=>1) );
			$month=$this->model_sale_order->monthOryear(array("date_added"=>date("Y-m",time()),"merchant_id"=>$user["merchant_id"],"month"=>1));
			$data["month"]=sprintf("%.2f",$month);
			$data["year"]=sprintf("%.2f",$year);
			$data['status'] = 1;
			$data['sale_money_url'] = $this->url->link('sale/order/sale_god_money','token='.$this->session->data['token'],'SSL');
			$data['sale_list_url'] = $this->url->link('sale/order/sale_god_money','token='.$this->session->data['token'],'SSL').'&status=2';
			$data['withdraw_list_url'] = $this->url->link('sale/order/sale_god_money','token='.$this->session->data['token'],'SSL').'&status=3';
			$data['yearSale'] = $this->url->link('sale/order/yearSale','token='.$this->session->data['token'],'SSL');
			$data['monthSale'] = $this->url->link('sale/order/monthSale','token='.$this->session->data['token'],'SSL');

			$this->response->setOutput($this->load->view('sale/sale_god_money.tpl',$data));
		}else if($status == 2){
			$this->saleList();
		}else{
			$this->withdrawList();
		}
	}
	/*
	 * 销售记录
	 */
	public function saleList(){
		$this->load->model('user/user');
		$this->load->model('sale/order');

		$user_id = $this->session->data['user_id'];
		$userinfo = $this->model_user_user->getUser($user_id);
		$merchant_id = $userinfo['merchant_id'];

		$page = isset($this->request->get['page'])?$this->request->get['page']:null;
		$time_start = isset($this->request->get['start'])?$this->request->get['start']:null;
		$time_end = isset($this->request->get['end'])?$this->request->get['end']:null;
		$search = isset($this->request->get['search'])?$this->request->get['search']:null;

		if(!$page){
			$page = 1;
		}
		$require = array();
		$url = '&status=2';
		if($time_start){
			$require['time_start'] = strtotime($time_start);
			$url .= "&start=".$time_start;
		}

		if($time_end){
			$require['time_end'] = strtotime($time_end);
			$url .= "&end=".$time_end;
		}

		if($search){
			$require['search'] = $search;
			$url .= "search=".$search;
		}

		//数据数量
		$total = $this->model_sale_order->getMerchantSaleTotal($merchant_id,$require);
		//分页
		$pagination = new Pagination();
		$pagination->total = $total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('sale/order/saleList','token='.$this->session->data['token'].$url.'&page={page}','SSL');
		$pagelist = $pagination->render();

		$start = ($page-1)*10;
		$data = array(
			'time_start'=>strtotime($time_start),
			'time_end'=>strtotime($time_end),
			'search'=>$search,
			'start'=>$start,
			'limit'=>10
		);

		$salelist = $this->model_sale_order->getMerchantSalelist($merchant_id,$data);
		foreach($salelist as $key=>$val){
			if(!preg_match("/[\x7f-\xff]/", $val['price'])){
				$salelist[$key]['price'] = sprintf("%.2f",$val['price']-$val['differenceprice']);
			}
		}
		$data = array(
			'salelist'=>$salelist,
			'status'=>2,
			'pagelist'=>$pagelist,
			'start'=>$time_start,
			'search'=>$search,
			'end'=>$time_end,
		);
		$data['sale_money_url'] = $this->url->link('sale/order/sale_god_money','token='.$this->session->data['token'],'SSL');
		$data['sale_list_url'] = $this->url->link('sale/order/sale_god_money','token='.$this->session->data['token'],'SSL').'&status=2';
		$data['withdraw_list_url'] = $this->url->link('sale/order/sale_god_money','token='.$this->session->data['token'],'SSL').'&status=3';
		$data['close_url'] = $this->url->link('sale/order/sale_god_money','token='.$this->session->data['token'],'SSL').'&status=2';
		$this->response->setOutput($this->load->view('sale/sale_god_money.tpl',$data));
	}
	/*
	 * 提现记录
	 */
	public function withdrawList(){
		$this->load->model('sale/order');
		$this->load->model('user/user');

		$user_id = $this->session->data['user_id'];
		$userinfo = $this->model_user_user->getUser($user_id);
		$merchant_id = $userinfo['merchant_id'];
		$limit = $this->config->get('config_limit_admin');

		$page = isset($this->request->get['page'])?$this->request->get['page']:1;
		$time_start = isset($this->request->get['start'])?$this->request->get['start']:null;
		$time_end = isset($this->request->get['end'])?$this->request->get['end']:null;
		$url = '&status=3';
		$require = array();
		if($time_start){
			$url .= "&start=".$time_start;
			$require['time_start'] = strtotime($time_start);
		}
		if($time_end){
			$url .= "&end=".$time_end;
			$require['time_end'] = strtotime($time_end);
		}
		//1为平台，其他为商户
		if($merchant_id == 1){
			//平台的所有商户提现记录
			$total = $this->model_sale_order->getMerchantWithdrawCashTotal($require);
			//分页
			$pagination = new Pagination();
			$pagination->total = $total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('sale/order/sale_god_money','token='.$this->session->data['token'].$url.'&page={page}','SSL');
			$pagelist = $pagination->render();

			$result_require = array(
					'time_start'=>strtotime($time_start),
					'time_end'=>strtotime($time_end),
					'start'=>($page-1)*$limit,
					'limit'=>$limit
			);
			//记录
			$result = $this->model_sale_order->getMerchantWithdrawCashList($result_require);

			foreach($result as $key=>$val){
				if($val['type'] == 1){
					$result[$key]['type'] = '银行卡';
				}else if($val['type'] == 2){
					$result[$key]['type'] = '支付宝';
				}
				$result[$key]['date_added'] = date('Y-m-d H:i:s',$val['date_added']);
			}
			$data = array(
					'merchant_id'=>$merchant_id,
					'result'=>$result,
					'status'=>3,
					'pagelist'=>$pagelist
			);

		}else{

			//商户本人的提现记录
			$total = $this->model_sale_order->getMerchantWithdrawTotal($merchant_id,$require);

			//分页
			$pagination = new Pagination();
			$pagination->total = $total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('sale/order/sale_god_money','token='.$this->session->data['token'].$url.'&page={page}','SSL');
			$pagelist = $pagination->render();

			$result_require = array(
				'time_start'=>strtotime($time_start),
				'time_end'=>strtotime($time_end),
				'start'=>($page-1)*$limit,
				'limit'=>$limit
			);
			$result = $this->model_sale_order->getMerchantWithdrawCash($merchant_id,$result_require);

			foreach($result as $key=>$val){
				if($val['type'] == 1){
					$result[$key]['type'] = '银行卡';
				}else if($val['type'] == 2){
					$result[$key]['type'] = '支付宝';
				}
				$result[$key]['date_added'] = date('Y-m-d H:i:s',$val['date_added']);
			}
			$data = array(
					'merchant_id'=>$merchant_id,
					'result'=>$result,
					'status'=>3,
					'pagelist'=>$pagelist
			);
		}

		$data['sale_money_url'] = $this->url->link('sale/order/sale_god_money','token='.$this->session->data['token'],'SSL');
		$data['sale_list_url'] = $this->url->link('sale/order/sale_god_money','token='.$this->session->data['token'],'SSL').'&status=2';
		$data['withdraw_list_url'] = $this->url->link('sale/order/sale_god_money','token='.$this->session->data['token'],'SSL').'&status=3';
		$data['start'] = $time_start;
		$data['end'] = $time_end;
		$data['close_url'] = $this->url->link('sale/order/sale_god_money','token='.$this->session->data['token'],'SSL').'&status=3';
		$this->response->setOutput($this->load->view('sale/sale_god_money.tpl',$data));
	}
	/*
	 * 年销售额统计
	 */
	public function yearSale(){
		$this->load->model('sale/order');
		$this->load->model('user/user');

		$time = $this->request->post['time'];
		$user_id = $this->session->data['user_id'];
		$userinfo = $this->model_user_user->getUser($user_id);
		$merchant_id = $userinfo['merchant_id'];

		$total = $this->model_sale_order->OrderMonthOryear(array("date_added"=>$time,"merchant_id"=>$merchant_id,"year"=>1));
		$order_total = array();
		for($i=1;$i<=12;$i++){
			foreach($total as $key=>$val){
				if($i == $val['months']){
					$order_total[$i-1] = (float)sprintf("%.2f", $val['total']);
					continue 2;
				}else{
					$order_total[$i-1] = (float)0.00;
				}
			}
		}

		$total_money = $this->model_sale_order->saleMonthOryear(array("date_added"=>$time,"merchant_id"=>$merchant_id,"year"=>1));
		$money = array();
		for($i=1;$i<=12;$i++){
			foreach($total_money as $key=>$val){
				if($i == $val['months']){
					$money[$i-1] = (float)sprintf("%.2f", $val['total']);
					continue 2;
				}else{
					$money[$i-1] = (float)0.00;
				}
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(array('order_total'=>$order_total,'money'=>$money)));
	}
	/*
	 * 月销售额统计
	 */
	public function monthSale(){
		$this->load->model('sale/order');
		$this->load->model('user/user');

		$time = $this->request->post['time'];
		$day = date("t",strtotime($time));
		$user_id = $this->session->data['user_id'];
		$userinfo = $this->model_user_user->getUser($user_id);
		$merchant_id = $userinfo['merchant_id'];

		$total = $this->model_sale_order->OrderMonthOryear(array("date_added"=>$time,"merchant_id"=>$merchant_id,"month"=>1));
		$order_total = array();
		for($i=1;$i<=$day;$i++){
			foreach($total as $key=>$val){
				if($i == $val['days']){
					$order_total[$i-1] = (float)sprintf("%.2f", $val['total']);
					continue 2;
				}else{
					$order_total[$i-1] = (float)0.00;
				}
			}
		}

		$total_money = $this->model_sale_order->saleMonthOryear(array("date_added"=>$time,"merchant_id"=>$merchant_id,"month"=>1));
		$money = array();
		for($i=1;$i<=$day;$i++){
			foreach($total_money as $key=>$val){
				if($i == $val['days']){
					$money[$i-1] = (float)sprintf("%.2f", $val['total']);
					continue 2;
				}else{
					$money[$i-1] = (float)0.00;
				}
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(array('order_total'=>$order_total,'money'=>$money)));
	}

	public function getForm() {
		$this->load->model('customer/customer');

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_form'] = !isset($this->request->get['order_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_default'] = $this->language->get('text_default');
		$data['text_select'] = $this->language->get('text_select');
		$data['text_none'] = $this->language->get('text_none');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['text_ip_add'] = sprintf($this->language->get('text_ip_add'), $this->request->server['REMOTE_ADDR']);
		$data['text_product'] = $this->language->get('text_product');
		$data['text_voucher'] = $this->language->get('text_voucher');
		$data['text_order_detail'] = $this->language->get('text_order_detail');

		$data['entry_store'] = $this->language->get('entry_store');
		$data['entry_customer'] = $this->language->get('entry_customer');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$data['entry_firstname'] = $this->language->get('entry_firstname');
		$data['entry_email'] = $this->language->get('entry_email');
		$data['entry_telephone'] = $this->language->get('entry_telephone');
		$data['entry_fax'] = $this->language->get('entry_fax');
		$data['entry_comment'] = $this->language->get('entry_comment');
		$data['entry_affiliate'] = $this->language->get('entry_affiliate');
		$data['entry_address'] = $this->language->get('entry_address');
		$data['entry_company'] = $this->language->get('entry_company');
		$data['entry_address_1'] = $this->language->get('entry_address_1');
		$data['entry_city'] = $this->language->get('entry_city');
		$data['entry_postcode'] = $this->language->get('entry_postcode');
		$data['entry_zone'] = $this->language->get('entry_zone');
		$data['entry_zone_code'] = $this->language->get('entry_zone_code');
		$data['entry_country'] = $this->language->get('entry_country');
		$data['entry_product'] = $this->language->get('entry_product');
		$data['entry_option'] = $this->language->get('entry_option');
		$data['entry_quantity'] = $this->language->get('entry_quantity');
		$data['entry_to_name'] = $this->language->get('entry_to_name');
		$data['entry_to_email'] = $this->language->get('entry_to_email');
		$data['entry_from_name'] = $this->language->get('entry_from_name');
		$data['entry_from_email'] = $this->language->get('entry_from_email');
		$data['entry_theme'] = $this->language->get('entry_theme');
		$data['entry_message'] = $this->language->get('entry_message');
		$data['entry_amount'] = $this->language->get('entry_amount');
		$data['entry_currency'] = $this->language->get('entry_currency');
		$data['entry_shipping_method'] = $this->language->get('entry_shipping_method');
		$data['entry_payment_method'] = $this->language->get('entry_payment_method');
		$data['entry_coupon'] = $this->language->get('entry_coupon');
		$data['entry_voucher'] = $this->language->get('entry_voucher');
		$data['entry_reward'] = $this->language->get('entry_reward');
		$data['entry_order_status'] = $this->language->get('entry_order_status');

		$data['column_product'] = $this->language->get('column_product');
		$data['column_model'] = $this->language->get('column_model');
		$data['column_quantity'] = $this->language->get('column_quantity');
		$data['column_price'] = $this->language->get('column_price');
		$data['column_total'] = $this->language->get('column_total');
		$data['column_action'] = $this->language->get('column_action');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_continue'] = $this->language->get('button_continue');
		$data['button_back'] = $this->language->get('button_back');
		$data['button_refresh'] = $this->language->get('button_refresh');
		$data['button_product_add'] = $this->language->get('button_product_add');
		$data['button_voucher_add'] = $this->language->get('button_voucher_add');
		$data['button_apply'] = $this->language->get('button_apply');
		$data['button_upload'] = $this->language->get('button_upload');
		$data['button_remove'] = $this->language->get('button_remove');
		$data['button_ip_add'] = $this->language->get('button_ip_add');

		$data['tab_order'] = $this->language->get('tab_order');
		$data['tab_customer'] = $this->language->get('tab_customer');
		$data['tab_payment'] = $this->language->get('tab_payment');
		$data['tab_shipping'] = $this->language->get('tab_shipping');
		$data['tab_product'] = $this->language->get('tab_product');
		$data['tab_voucher'] = $this->language->get('tab_voucher');
		$data['tab_total'] = $this->language->get('tab_total');

		$data['token'] = $this->session->data['token'];

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			

			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url, 'SSL')
		);

		$data['cancel'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url, 'SSL');

		if (isset($this->request->get['order_id'])) {
			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);
		}

		if (!empty($order_info)) {
			$data['order_id'] = $this->request->get['order_id'];
			$data['store_id'] = $order_info['store_id'];

			$data['customer'] = $order_info['customer'];
			$data['customer_id'] = $order_info['customer_id'];
			$data['customer_group_id'] = $order_info['customer_group_id'];
			$data['firstname'] = $order_info['firstname'];
			$data['email'] = $order_info['email'];
			$data['telephone'] = $order_info['telephone'];
			$data['fax'] = $order_info['fax'];
			$data['account_custom_field'] = $order_info['custom_field'];

			$this->load->model('customer/customer');

			$data['addresses'] = $this->model_customer_customer->getAddresses($order_info['customer_id']);

			$data['payment_firstname'] = $order_info['payment_firstname'];
			$data['payment_company'] = $order_info['payment_company'];
			$data['payment_address_1'] = $order_info['payment_address_1'];
			$data['payment_city'] = $order_info['payment_city'];
			$data['payment_postcode'] = $order_info['payment_postcode'];
			$data['payment_country_id'] = $order_info['payment_country_id'];
			$data['payment_zone_id'] = $order_info['payment_zone_id'];
			$data['payment_custom_field'] = $order_info['payment_custom_field'];
			$data['payment_method'] = $order_info['payment_method'];
			$data['payment_code'] = $order_info['payment_code'];

			$data['shipping_firstname'] = $order_info['shipping_firstname'];
			$data['shipping_company'] = $order_info['shipping_company'];
			$data['shipping_address_1'] = $order_info['shipping_address_1'];
			$data['shipping_city'] = $order_info['shipping_city'];
			$data['shipping_postcode'] = $order_info['shipping_postcode'];
			$data['shipping_country_id'] = $order_info['shipping_country_id'];
			$data['shipping_zone_id'] = $order_info['shipping_zone_id'];
			$data['shipping_custom_field'] = $order_info['shipping_custom_field'];
			$data['shipping_method'] = $order_info['shipping_method'];
			$data['shipping_code'] = $order_info['shipping_code'];

			// Products
			$data['order_products'] = array();

			$products = $this->model_sale_order->getOrderProducts($this->request->get['order_id']);

			foreach ($products as $product) {
				$data['order_products'][] = array(
					'product_id' => $product['product_id'],
					'name'       => $product['name'],
					'model'      => $product['model'],
					'option'     => $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']),
					'quantity'   => $product['quantity'],
					'price'      => $product['price'],
					'total'      => $product['total'],
					'reward'     => $product['reward']
				);
			}

			// Vouchers
			$data['order_vouchers'] = $this->model_sale_order->getOrderVouchers($this->request->get['order_id']);

			$data['coupon'] = '';
			$data['voucher'] = '';
			$data['reward'] = '';

			$data['order_totals'] = array();

			$order_totals = $this->model_sale_order->getOrderTotals($this->request->get['order_id']);

			foreach ($order_totals as $order_total) {
				// If coupon, voucher or reward points
				$start = strpos($order_total['title'], '(') + 1;
				$end = strrpos($order_total['title'], ')');

				if ($start && $end) {
					$data[$order_total['code']] = substr($order_total['title'], $start, $end - $start);
				}
			}

			$data['order_status_id'] = $order_info['order_status_id'];
			$data['comment'] = $order_info['comment'];
			$data['affiliate_id'] = $order_info['affiliate_id'];
			$data['affiliate'] = $order_info['affiliate_firstname'];
			$data['currency_code'] = $order_info['currency_code'];
		} else {
			$data['order_id'] = 0;
			$data['store_id'] = '';
			$data['customer'] = '';
			$data['customer_id'] = '';
			$data['customer_group_id'] = $this->config->get('config_customer_group_id');
			$data['firstname'] = '';
			$data['email'] = '';
			$data['telephone'] = '';
			$data['fax'] = '';
			$data['customer_custom_field'] = array();

			$data['addresses'] = array();

			$data['payment_firstname'] = '';
			$data['payment_company'] = '';
			$data['payment_address_1'] = '';
			$data['payment_city'] = '';
			$data['payment_postcode'] = '';
			$data['payment_country_id'] = '';
			$data['payment_zone_id'] = '';
			$data['payment_custom_field'] = array();
			$data['payment_method'] = '';
			$data['payment_code'] = '';

			$data['shipping_firstname'] = '';
			$data['shipping_company'] = '';
			$data['shipping_address_1'] = '';
			$data['shipping_city'] = '';
			$data['shipping_postcode'] = '';
			$data['shipping_country_id'] = '';
			$data['shipping_zone_id'] = '';
			$data['shipping_custom_field'] = array();
			$data['shipping_method'] = '';
			$data['shipping_code'] = '';

			$data['order_products'] = array();
			$data['order_vouchers'] = array();
			$data['order_totals'] = array();

			$data['order_status_id'] = $this->config->get('config_order_status_id');
			$data['comment'] = '';
			$data['affiliate_id'] = '';
			$data['affiliate'] = '';
			$data['currency_code'] = $this->config->get('config_currency');

			$data['coupon'] = '';
			$data['voucher'] = '';
			$data['reward'] = '';
		}

		// Stores
		$this->load->model('setting/store');

		$data['stores'] = array();

		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default'),
			'href'     => HTTP_CATALOG
		);

		$results = $this->model_setting_store->getStores();

		foreach ($results as $result) {
			$data['stores'][] = array(
				'store_id' => $result['store_id'],
				'name'     => $result['name'],
				'href'     => $result['url']
			);
		}

		// Customer Groups
		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		// Custom Fields
		$this->load->model('customer/custom_field');

		$data['custom_fields'] = array();

		$filter_data = array(
			'sort'  => 'cf.sort_order',
			'order' => 'ASC'
		);

		$custom_fields = $this->model_customer_custom_field->getCustomFields($filter_data);

		foreach ($custom_fields as $custom_field) {
			$data['custom_fields'][] = array(
				'custom_field_id'    => $custom_field['custom_field_id'],
				'custom_field_value' => $this->model_customer_custom_field->getCustomFieldValues($custom_field['custom_field_id']),
				'name'               => $custom_field['name'],
				'value'              => $custom_field['value'],
				'type'               => $custom_field['type'],
				'location'           => $custom_field['location'],
				'sort_order'         => $custom_field['sort_order']
			);
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		$this->load->model('localisation/currency');

		$data['currencies'] = $this->model_localisation_currency->getCurrencies();

		$data['voucher_min'] = $this->config->get('config_voucher_min');

		$this->load->model('sale/voucher_theme');

		$data['voucher_themes'] = $this->model_sale_voucher_theme->getVoucherThemes();

		// API login
		$this->load->model('user/api');

		$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

		if ($api_info) {
			$data['api_id'] = $api_info['api_id'];
			$data['api_key'] = $api_info['key'];
			$data['api_ip'] = $this->request->server['REMOTE_ADDR'];
		} else {
			$data['api_id'] = '';
			$data['api_key'] = '';
			$data['api_ip'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('sale/order_form.tpl', $data));
	}

	public function info() {
		$this->load->model('sale/order');

		if (isset($this->request->get['order_id'])) {
			$order_id = $this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		$order_info = $this->model_sale_order->getOrder($order_id);

		if ($order_info) {
			$this->load->language('sale/order');

			$this->document->setTitle($this->language->get('heading_title'));

			$data['heading_title'] = $this->language->get('heading_title');

			$data['text_ip_add'] = sprintf($this->language->get('text_ip_add'), $this->request->server['REMOTE_ADDR']);
			$data['text_order_detail'] = $this->language->get('text_order_detail');
			$data['text_customer_detail'] = $this->language->get('text_customer_detail');
			$data['text_option'] = $this->language->get('text_option');
			$data['text_store'] = $this->language->get('text_store');
			$data['text_date_added'] = $this->language->get('text_date_added');
			$data['text_payment_method'] = $this->language->get('text_payment_method');
			$data['text_shipping_method'] = $this->language->get('text_shipping_method');
			$data['text_customer'] = $this->language->get('text_customer');
			$data['text_customer_group'] = $this->language->get('text_customer_group');
			$data['text_email'] = $this->language->get('text_email');
			$data['text_telephone'] = $this->language->get('text_telephone');
			$data['text_invoice'] = $this->language->get('text_invoice');
			$data['text_reward'] = $this->language->get('text_reward');
			$data['text_affiliate'] = $this->language->get('text_affiliate');
			$data['text_order'] = sprintf($this->language->get('text_order'), $this->request->get['order_id']);
			$data['text_payment_address'] = $this->language->get('text_payment_address');
			$data['text_shipping_address'] = $this->language->get('text_shipping_address');
			$data['text_comment'] = $this->language->get('text_comment');

			$data['text_account_custom_field'] = $this->language->get('text_account_custom_field');
			$data['text_payment_custom_field'] = $this->language->get('text_payment_custom_field');
			$data['text_shipping_custom_field'] = $this->language->get('text_shipping_custom_field');
			$data['text_browser'] = $this->language->get('text_browser');
			$data['text_ip'] = $this->language->get('text_ip');
			$data['text_forwarded_ip'] = $this->language->get('text_forwarded_ip');
			$data['text_user_agent'] = $this->language->get('text_user_agent');
			$data['text_accept_language'] = $this->language->get('text_accept_language');
			$data['text_history'] = $this->language->get('text_history');
			$data['text_history_add'] = $this->language->get('text_history_add');
			$data['text_loading'] = $this->language->get('text_loading');

			$data['column_product'] = $this->language->get('column_product');
			$data['column_model'] = $this->language->get('column_model');
			$data['column_quantity'] = $this->language->get('column_quantity');
			$data['column_price'] = $this->language->get('column_price');
			$data['column_total'] = $this->language->get('column_total');

			$data['entry_order_status'] = $this->language->get('entry_order_status');
			$data['entry_notify'] = $this->language->get('entry_notify');
			$data['entry_override'] = $this->language->get('entry_override');
			$data['entry_comment'] = $this->language->get('entry_comment');

			$data['help_override'] = $this->language->get('help_override');

			$data['button_invoice_print'] = $this->language->get('button_invoice_print');
			$data['button_shipping_print'] = $this->language->get('button_shipping_print');
			$data['button_edit'] = $this->language->get('button_edit');
			$data['button_cancel'] = $this->language->get('button_cancel');
			$data['button_generate'] = $this->language->get('button_generate');
			$data['button_reward_add'] = $this->language->get('button_reward_add');
			$data['button_reward_remove'] = $this->language->get('button_reward_remove');
			$data['button_commission_add'] = $this->language->get('button_commission_add');
			$data['button_commission_remove'] = $this->language->get('button_commission_remove');
			$data['button_history_add'] = $this->language->get('button_history_add');
			$data['button_ip_add'] = $this->language->get('button_ip_add');

			$data['tab_history'] = $this->language->get('tab_history');
			$data['tab_additional'] = $this->language->get('tab_additional');

			$data['token'] = $this->session->data['token'];

			$url = '';

			if (isset($this->request->get['filter_order_id'])) {
				$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
			}

			if (isset($this->request->get['filter_customer'])) {
				$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_order_status'])) {
				$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
			}

			if (isset($this->request->get['filter_total'])) {
				$url .= '&filter_total=' . $this->request->get['filter_total'];
			}

			if (isset($this->request->get['filter_date_added'])) {
				$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
			}

			if (isset($this->request->get['filter_date_modified'])) {
				$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url, 'SSL')
			);

			$data['shipping'] = $this->url->link('sale/order/shipping', 'token=' . $this->session->data['token'] . '&order_id=' . (int)$this->request->get['order_id'], 'SSL');
			$data['invoice'] = $this->url->link('sale/order/invoice', 'token=' . $this->session->data['token'] . '&order_id=' . (int)$this->request->get['order_id'], 'SSL');
			$data['edit'] = $this->url->link('sale/order/edit', 'token=' . $this->session->data['token'] . '&order_id=' . (int)$this->request->get['order_id'], 'SSL');
			$data['cancel'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url, 'SSL');

			$data['order_id'] = $this->request->get['order_id'];

			$data['store_name'] = $order_info['store_name'];
			$data['store_url'] = $order_info['store_url'];

			if ($order_info['invoice_no']) {
				$data['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
			} else {
				$data['invoice_no'] = '';
			}

			$data['date_added'] = date($this->language->get('datetime_format'), strtotime($order_info['date_added']));

			$data['firstname'] = $order_info['firstname'];
			if(!trim($data['firstname'])){
				$data['firstname'] = trim($order_info['email']) ? $order_info['email'] : $order_info['telephone'];
			}

			if ($order_info['customer_id']) {
				$data['customer'] = $this->url->link('customer/customer/edit', 'token=' . $this->session->data['token'] . '&customer_id=' . $order_info['customer_id'], 'SSL');
			} else {
				$data['customer'] = '';
			}

			$this->load->model('customer/customer_group');

			$customer_group_info = $this->model_customer_customer_group->getCustomerGroup($order_info['customer_group_id']);

			if ($customer_group_info) {
				$data['customer_group'] = $customer_group_info['name'];
			} else {
				$data['customer_group'] = '';
			}

			$data['email'] = $order_info['email'];
			$data['telephone'] = $order_info['telephone'];

			$data['shipping_method'] = $order_info['shipping_method'];
			$data['payment_method'] = $order_info['payment_method'];

			// Payment Address
			if ($order_info['payment_address_format']) {
				$format = $order_info['payment_address_format'];
			} else {
				$format = '{firstname}' . "\n" . '{company}' . "\n" . '{country}' . "\n" . '{zone}' . "\n" . '{city} ' . "\n" . '{address_1}' . '{postcode}';
			}

			$find = array(
				'{firstname}',
				'{company}',
				'{address_1}',
				'{city}',
				'{postcode}',
				'{zone}',
				'{zone_code}',
				'{country}'
			);

			$replace = array(
				'firstname' => $order_info['payment_firstname'],
				'company'   => $order_info['payment_company'],
				'address_1' => $order_info['payment_address_1'],
				'city'      => $order_info['payment_city'],
				'postcode'  => $order_info['payment_postcode'],
				'zone'      => $order_info['payment_zone'],
				'zone_code' => $order_info['payment_zone_code'],
				'country'   => $order_info['payment_country']
			);

			$data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

			// Shipping Address
			if ($order_info['shipping_address_format']) {
				$format = $order_info['shipping_address_format'];
			} else {
				$format = '{firstname}' . "\n" . '{company}' . "\n" . '{country}' . "\n" . '{zone}' . "\n" . '{city} ' . "\n" . '{address_1}' . '{postcode}';
			}

			$find = array(
				'{firstname}',
				'{company}',
				'{address_1}',
				'{city}',
				'{postcode}',
				'{zone}',
				'{zone_code}',
				'{country}'
			);

			$replace = array(
				'firstname' => $order_info['shipping_firstname'],
				'company'   => $order_info['shipping_company'],
				'address_1' => $order_info['shipping_address_1'],
				'city'      => $order_info['shipping_city'],
				'postcode'  => $order_info['shipping_postcode'],
				'zone'      => $order_info['shipping_zone'],
				'zone_code' => $order_info['shipping_zone_code'],
				'country'   => $order_info['shipping_country']
			);

			$data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

			// Uploaded files
			$this->load->model('tool/upload');

			$data['products'] = array();

			$products = $this->model_sale_order->getOrderProducts($this->request->get['order_id']);

			foreach ($products as $product) {
				$option_data = array();

				$options = $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']);

				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $option['value'],
							'type'  => $option['type']
						);
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($upload_info) {
							$option_data[] = array(
								'name'  => $option['name'],
								'value' => $upload_info['name'],
								'type'  => $option['type'],
								'href'  => $this->url->link('tool/upload/download', 'token=' . $this->session->data['token'] . '&code=' . $upload_info['code'], 'SSL')
							);
						}
					}
				}

				$data['products'][] = array(
					'order_product_id' => $product['order_product_id'],
					'product_id'       => $product['product_id'],
					'name'    	 	   => $product['name'],
					'model'    		   => $product['model'],
					'option'   		   => $option_data,
					'quantity'		   => $product['quantity'],
					'price'    		   => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
					'total'    		   => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
					'href'     		   => $this->url->link('catalog/product/edit', 'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'], 'SSL')
				);
			}

			$data['vouchers'] = array();

			$vouchers = $this->model_sale_order->getOrderVouchers($this->request->get['order_id']);

			foreach ($vouchers as $voucher) {
				$data['vouchers'][] = array(
					'description' => $voucher['description'],
					'amount'      => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value']),
					'href'        => $this->url->link('sale/voucher/edit', 'token=' . $this->session->data['token'] . '&voucher_id=' . $voucher['voucher_id'], 'SSL')
				);
			}

			$data['totals'] = array();

			$totals = $this->model_sale_order->getOrderTotals($this->request->get['order_id']);

			foreach ($totals as $total) {
				$data['totals'][] = array(
					'title' => $total['title'],
					'text'  => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']),
				);
			}

			$data['comment'] = nl2br($order_info['comment']);

			$this->load->model('customer/customer');

			$data['reward'] = $order_info['reward'];

			$data['reward_total'] = $this->model_customer_customer->getTotalCustomerRewardsByOrderId($this->request->get['order_id']);

			$data['affiliate_firstname'] = $order_info['affiliate_firstname'];

			if ($order_info['affiliate_id']) {
				$data['affiliate'] = $this->url->link('marketing/affiliate/edit', 'token=' . $this->session->data['token'] . '&affiliate_id=' . $order_info['affiliate_id'], 'SSL');
			} else {
				$data['affiliate'] = '';
			}

			$data['commission'] = $this->currency->format($order_info['commission'], $order_info['currency_code'], $order_info['currency_value']);

			$this->load->model('marketing/affiliate');

			$data['commission_total'] = $this->model_marketing_affiliate->getTotalTransactionsByOrderId($this->request->get['order_id']);

			$this->load->model('localisation/order_status');

			$order_status_info = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

			if ($order_status_info) {
				$data['order_status'] = $order_status_info['name'];
			} else {
				$data['order_status'] = '';
			}

			$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

			$data['order_status_id'] = $order_info['order_status_id'];

			$data['account_custom_field'] = $order_info['custom_field'];

			// Custom Fields
			$this->load->model('customer/custom_field');

			$data['account_custom_fields'] = array();

			$filter_data = array(
				'sort'  => 'cf.sort_order',
				'order' => 'ASC',
			);

			$custom_fields = $this->model_customer_custom_field->getCustomFields($filter_data);

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'account' && isset($order_info['custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($order_info['custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['account_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $custom_field_value_info['name']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['account_custom_fields'][] = array(
									'name'  => $custom_field['name'],
									'value' => $custom_field_value_info['name']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['account_custom_fields'][] = array(
							'name'  => $custom_field['name'],
							'value' => $order_info['custom_field'][$custom_field['custom_field_id']]
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['account_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $upload_info['name']
							);
						}
					}
				}
			}

			// Custom fields
			$data['payment_custom_fields'] = array();

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'address' && isset($order_info['payment_custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($order_info['payment_custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['payment_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $custom_field_value_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['payment_custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['payment_custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['payment_custom_fields'][] = array(
									'name'  => $custom_field['name'],
									'value' => $custom_field_value_info['name'],
									'sort_order' => $custom_field['sort_order']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['payment_custom_fields'][] = array(
							'name'  => $custom_field['name'],
							'value' => $order_info['payment_custom_field'][$custom_field['custom_field_id']],
							'sort_order' => $custom_field['sort_order']
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['payment_custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['payment_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $upload_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}
				}
			}

			// Shipping
			$data['shipping_custom_fields'] = array();

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'address' && isset($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($order_info['shipping_custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['shipping_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $custom_field_value_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['shipping_custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['shipping_custom_fields'][] = array(
									'name'  => $custom_field['name'],
									'value' => $custom_field_value_info['name'],
									'sort_order' => $custom_field['sort_order']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['shipping_custom_fields'][] = array(
							'name'  => $custom_field['name'],
							'value' => $order_info['shipping_custom_field'][$custom_field['custom_field_id']],
							'sort_order' => $custom_field['sort_order']
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['shipping_custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['shipping_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $upload_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}
				}
			}

			$data['ip'] = $order_info['ip'];
			$data['forwarded_ip'] = $order_info['forwarded_ip'];
			$data['user_agent'] = $order_info['user_agent'];
			$data['accept_language'] = $order_info['accept_language'];

			// Additional Tabs
			$data['tabs'] = array();

			$this->load->model('extension/extension');

			$content = $this->load->controller('payment/' . $order_info['payment_code'] . '/order');

			if ($content) {
				$this->load->language('payment/' . $order_info['payment_code']);

				$data['tabs'][] = array(
					'code'    => $order_info['payment_code'],
					'title'   => $this->language->get('heading_title'),
					'content' => $content
				);
			}

			$extensions = $this->model_extension_extension->getInstalled('fraud');

			foreach ($extensions as $extension) {
				if ($this->config->get($extension . '_status')) {
					$this->load->language('fraud/' . $extension);

					$content = $this->load->controller('fraud/' . $extension . '/order');

					if ($content) {
						$data['tabs'][] = array(
							'code'    => $extension,
							'title'   => $this->language->get('heading_title'),
							'content' => $content
						);
					}
				}
			}

			// API login
			$this->load->model('user/api');

			$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

			if ($api_info) {
				$data['api_id'] = $api_info['api_id'];
				$data['api_key'] = $api_info['key'];
				$data['api_ip'] = $this->request->server['REMOTE_ADDR'];
			} else {
				$data['api_id'] = '';
				$data['api_key'] = '';
				$data['api_ip'] = '';
			}

			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');
			
			$this->response->setOutput($this->load->view('sale/order_info.tpl', $data));
		} else {
			$this->load->language('error/not_found');

			$this->document->setTitle($this->language->get('heading_title'));

			$data['heading_title'] = $this->language->get('heading_title');

			$data['text_not_found'] = $this->language->get('text_not_found');

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('error/not_found', 'token=' . $this->session->data['token'], 'SSL')
			);

			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view('error/not_found.tpl', $data));
		}
	}

	public function createInvoiceNo() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (isset($this->request->get['order_id'])) {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$invoice_no = $this->model_sale_order->createInvoiceNo($order_id);

			if ($invoice_no) {
				$json['invoice_no'] = $invoice_no;
			} else {
				$json['error'] = $this->language->get('error_action');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function addReward() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info && $order_info['customer_id'] && ($order_info['reward'] > 0)) {
				$this->load->model('customer/customer');

				$reward_total = $this->model_customer_customer->getTotalCustomerRewardsByOrderId($order_id);

				if (!$reward_total) {
					$this->model_customer_customer->addReward($order_info['customer_id'], $this->language->get('text_order_id') . ' #' . $order_id, $order_info['reward'], $order_id);
				}
			}

			$json['success'] = $this->language->get('text_reward_added');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function removeReward() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info) {
				$this->load->model('customer/customer');

				$this->model_customer_customer->deleteReward($order_id);
			}

			$json['success'] = $this->language->get('text_reward_removed');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function addCommission() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info) {
				$this->load->model('marketing/affiliate');

				$affiliate_total = $this->model_marketing_affiliate->getTotalTransactionsByOrderId($order_id);

				if (!$affiliate_total) {
					$this->model_marketing_affiliate->addTransaction($order_info['affiliate_id'], $this->language->get('text_order_id') . ' #' . $order_id, $order_info['commission'], $order_id);
				}
			}

			$json['success'] = $this->language->get('text_commission_added');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function removeCommission() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info) {
				$this->load->model('marketing/affiliate');

				$this->model_marketing_affiliate->deleteTransaction($order_id);
			}

			$json['success'] = $this->language->get('text_commission_removed');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function history() {
		$this->load->language('sale/order');

		$data['text_no_results'] = $this->language->get('text_no_results');

		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_notify'] = $this->language->get('column_notify');
		$data['column_comment'] = $this->language->get('column_comment');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['histories'] = array();

		$this->load->model('sale/order');

		$results = $this->model_sale_order->getOrderHistories($this->request->get['order_id'], ($page - 1) * 10, 10);

		foreach ($results as $result) {
			$data['histories'][] = array(
				'notify'     => $result['notify'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
				'status'     => $result['status'],
				'comment'    => nl2br($result['comment']),
				'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added']))
			);
		}

		$history_total = $this->model_sale_order->getTotalOrderHistories($this->request->get['order_id']);

		$pagination = new Pagination();
		$pagination->total = $history_total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('sale/order/history', 'token=' . $this->session->data['token'] . '&order_id=' . $this->request->get['order_id'] . '&page={page}', 'SSL');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($history_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($history_total - 10)) ? $history_total : ((($page - 1) * 10) + 10), $history_total, ceil($history_total / 10));

		$this->response->setOutput($this->load->view('sale/order_history.tpl', $data));
	}

	public function invoice() {
		$this->load->language('sale/order');

		$data['title'] = $this->language->get('text_invoice');

		if ($this->request->server['HTTPS']) {
			$data['base'] = HTTPS_SERVER;
		} else {
			$data['base'] = HTTP_SERVER;
		}

		$data['direction'] = $this->language->get('direction');
		$data['lang'] = $this->language->get('code');

		$data['text_invoice'] = $this->language->get('text_invoice');
		$data['text_order_detail'] = $this->language->get('text_order_detail');
		$data['text_order_id'] = $this->language->get('text_order_id');
		$data['text_invoice_no'] = $this->language->get('text_invoice_no');
		$data['text_invoice_date'] = $this->language->get('text_invoice_date');
		$data['text_date_added'] = $this->language->get('text_date_added');
		$data['text_telephone'] = $this->language->get('text_telephone');
		$data['text_fax'] = $this->language->get('text_fax');
		$data['text_email'] = $this->language->get('text_email');
		$data['text_website'] = $this->language->get('text_website');
		$data['text_payment_address'] = $this->language->get('text_payment_address');
		$data['text_shipping_address'] = $this->language->get('text_shipping_address');
		$data['text_payment_method'] = $this->language->get('text_payment_method');
		$data['text_shipping_method'] = $this->language->get('text_shipping_method');
		$data['text_comment'] = $this->language->get('text_comment');

		$data['column_product'] = $this->language->get('column_product');
		$data['column_model'] = $this->language->get('column_model');
		$data['column_quantity'] = $this->language->get('column_quantity');
		$data['column_price'] = $this->language->get('column_price');
		$data['column_total'] = $this->language->get('column_total');

		$this->load->model('sale/order');

		$this->load->model('setting/setting');

		$data['orders'] = array();

		$orders = array();

		if (isset($this->request->post['selected'])) {
			$orders = $this->request->post['selected'];
		} elseif (isset($this->request->get['order_id'])) {
			$orders[] = $this->request->get['order_id'];
		}

		foreach ($orders as $order_id) {
			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info) {
				$store_info = $this->model_setting_setting->getSetting('config', $order_info['store_id']);

				if ($store_info) {
					$store_address = $store_info['config_address'];
					$store_email = $store_info['config_email'];
					$store_telephone = $store_info['config_telephone'];
					$store_fax = $store_info['config_fax'];
				} else {
					$store_address = $this->config->get('config_address');
					$store_email = $this->config->get('config_email');
					$store_telephone = $this->config->get('config_telephone');
					$store_fax = $this->config->get('config_fax');
				}

				if ($order_info['invoice_no']) {
					$invoice_no = $order_info['invoice_prefix'] . $order_info['invoice_no'];
				} else {
					$invoice_no = '';
				}

				if ($order_info['payment_address_format']) {
					$format = $order_info['payment_address_format'];
				} else {
					$format = '{firstname}' . "\n" . '{company}' . "\n" . '{country}' . "\n" . '{zone}' . "\n" . '{city} ' . "\n" . '{address_1}' . '{postcode}';
				}

				$find = array(
					'{firstname}',
					'{company}',
					'{address_1}',
					'{city}',
					'{postcode}',
					'{zone}',
					'{zone_code}',
					'{country}'
				);

				$replace = array(
					'firstname' => $order_info['payment_firstname'],
					'company'   => $order_info['payment_company'],
					'address_1' => $order_info['payment_address_1'],
					'city'      => $order_info['payment_city'],
					'postcode'  => $order_info['payment_postcode'],
					'zone'      => $order_info['payment_zone'],
					'zone_code' => $order_info['payment_zone_code'],
					'country'   => $order_info['payment_country']
				);

				$payment_address = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

				if ($order_info['shipping_address_format']) {
					$format = $order_info['shipping_address_format'];
				} else {
					$format = '{firstname}' . "\n" . '{company}' . "\n" . '{country}' . "\n" . '{zone}' . "\n" . '{city} ' . "\n" . '{address_1}' . '{postcode}';
				}

				$find = array(
					'{firstname}',
					'{company}',
					'{address_1}',
					'{city}',
					'{postcode}',
					'{zone}',
					'{zone_code}',
					'{country}'
				);

				$replace = array(
					'firstname' => $order_info['shipping_firstname'],
					'company'   => $order_info['shipping_company'],
					'address_1' => $order_info['shipping_address_1'],
					'city'      => $order_info['shipping_city'],
					'postcode'  => $order_info['shipping_postcode'],
					'zone'      => $order_info['shipping_zone'],
					'zone_code' => $order_info['shipping_zone_code'],
					'country'   => $order_info['shipping_country']
				);

				$shipping_address = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

				$this->load->model('tool/upload');

				$product_data = array();

				$products = $this->model_sale_order->getOrderProducts($order_id);

				foreach ($products as $product) {
					$option_data = array();

					$options = $this->model_sale_order->getOrderOptions($order_id, $product['order_product_id']);

					foreach ($options as $option) {
						if ($option['type'] != 'file') {
							$value = $option['value'];
						} else {
							$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

							if ($upload_info) {
								$value = $upload_info['name'];
							} else {
								$value = '';
							}
						}

						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $value
						);
					}

					$product_data[] = array(
						'name'     => $product['name'],
						'model'    => $product['model'],
						'option'   => $option_data,
						'quantity' => $product['quantity'],
						'price'    => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
						'total'    => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value'])
					);
				}

				$voucher_data = array();

				$vouchers = $this->model_sale_order->getOrderVouchers($order_id);

				foreach ($vouchers as $voucher) {
					$voucher_data[] = array(
						'description' => $voucher['description'],
						'amount'      => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value'])
					);
				}

				$total_data = array();

				$totals = $this->model_sale_order->getOrderTotals($order_id);

				foreach ($totals as $total) {
					$total_data[] = array(
						'title' => $total['title'],
						'text'  => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']),
					);
				}

				$data['orders'][] = array(
					'order_id'	         => $order_id,
					'invoice_no'         => $invoice_no,
					'date_added'         => date($this->language->get('date_format_short'), strtotime($order_info['date_added'])),
					'store_name'         => $order_info['store_name'],
					'store_url'          => rtrim($order_info['store_url'], '/'),
					'store_address'      => nl2br($store_address),
					'store_email'        => $store_email,
					'store_telephone'    => $store_telephone,
					'store_fax'          => $store_fax,
					'email'              => $order_info['email'],
					'telephone'          => $order_info['telephone'],
					'shipping_address'   => $shipping_address,
					'shipping_method'    => $order_info['shipping_method'],
					'payment_address'    => $payment_address,
					'payment_method'     => $order_info['payment_method'],
					'product'            => $product_data,
					'voucher'            => $voucher_data,
					'total'              => $total_data,
					'comment'            => nl2br($order_info['comment'])
				);
			}
		}

		$this->response->setOutput($this->load->view('sale/order_invoice.tpl', $data));
	}

	public function shipping() {
		$this->load->language('sale/order');

		$data['title'] = $this->language->get('text_shipping');

		if ($this->request->server['HTTPS']) {
			$data['base'] = HTTPS_SERVER;
		} else {
			$data['base'] = HTTP_SERVER;
		}

		$data['direction'] = $this->language->get('direction');
		$data['lang'] = $this->language->get('code');

		$data['text_shipping'] = $this->language->get('text_shipping');
		$data['text_picklist'] = $this->language->get('text_picklist');
		$data['text_order_detail'] = $this->language->get('text_order_detail');
		$data['text_order_id'] = $this->language->get('text_order_id');
		$data['text_invoice_no'] = $this->language->get('text_invoice_no');
		$data['text_invoice_date'] = $this->language->get('text_invoice_date');
		$data['text_date_added'] = $this->language->get('text_date_added');
		$data['text_telephone'] = $this->language->get('text_telephone');
		$data['text_fax'] = $this->language->get('text_fax');
		$data['text_email'] = $this->language->get('text_email');
		$data['text_website'] = $this->language->get('text_website');
		$data['text_contact'] = $this->language->get('text_contact');
		$data['text_payment_address'] = $this->language->get('text_payment_address');
		$data['text_shipping_method'] = $this->language->get('text_shipping_method');
		$data['text_sku'] = $this->language->get('text_sku');
		$data['text_upc'] = $this->language->get('text_upc');
		$data['text_ean'] = $this->language->get('text_ean');
		$data['text_jan'] = $this->language->get('text_jan');
		$data['text_isbn'] = $this->language->get('text_isbn');
		$data['text_mpn'] = $this->language->get('text_mpn');
		$data['text_comment'] = $this->language->get('text_comment');

		$data['column_location'] = $this->language->get('column_location');
		$data['column_reference'] = $this->language->get('column_reference');
		$data['column_product'] = $this->language->get('column_product');
		$data['column_weight'] = $this->language->get('column_weight');
		$data['column_model'] = $this->language->get('column_model');
		$data['column_quantity'] = $this->language->get('column_quantity');

		$this->load->model('sale/order');

		$this->load->model('catalog/product');

		$this->load->model('setting/setting');

		$data['orders'] = array();

		$orders = array();

		if (isset($this->request->post['selected'])) {
			$orders = $this->request->post['selected'];
		} elseif (isset($this->request->get['order_id'])) {
			$orders[] = $this->request->get['order_id'];
		}

		foreach ($orders as $order_id) {
			$order_info = $this->model_sale_order->getOrder($order_id);

			// Make sure there is a shipping method
			if ($order_info && $order_info['shipping_code']) {
				$store_info = $this->model_setting_setting->getSetting('config', $order_info['store_id']);

				if ($store_info) {
					$store_address = $store_info['config_address'];
					$store_email = $store_info['config_email'];
					$store_telephone = $store_info['config_telephone'];
					$store_fax = $store_info['config_fax'];
				} else {
					$store_address = $this->config->get('config_address');
					$store_email = $this->config->get('config_email');
					$store_telephone = $this->config->get('config_telephone');
					$store_fax = $this->config->get('config_fax');
				}

				if ($order_info['invoice_no']) {
					$invoice_no = $order_info['invoice_prefix'] . $order_info['invoice_no'];
				} else {
					$invoice_no = '';
				}

				if ($order_info['shipping_address_format']) {
					$format = $order_info['shipping_address_format'];
				} else {
					$format = '{firstname}' . "\n" . '{company}' . "\n" . '{country}' . "\n" . '{zone}' . "\n" . '{city} ' . "\n" . '{address_1}' . '{postcode}';
				}

				$find = array(
					'{firstname}',
					'{company}',
					'{address_1}',
					'{city}',
					'{postcode}',
					'{zone}',
					'{zone_code}',
					'{country}'
				);

				$replace = array(
					'firstname' => $order_info['shipping_firstname'],
					'company'   => $order_info['shipping_company'],
					'address_1' => $order_info['shipping_address_1'],
					'city'      => $order_info['shipping_city'],
					'postcode'  => $order_info['shipping_postcode'],
					'zone'      => $order_info['shipping_zone'],
					'zone_code' => $order_info['shipping_zone_code'],
					'country'   => $order_info['shipping_country']
				);

				$shipping_address = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

				$this->load->model('tool/upload');

				$product_data = array();

				$products = $this->model_sale_order->getOrderProducts($order_id);

				foreach ($products as $product) {
					$option_weight = '';

					$product_info = $this->model_catalog_product->getProduct($product['product_id']);

					if ($product_info) {
						$option_data = array();

						$options = $this->model_sale_order->getOrderOptions($order_id, $product['order_product_id']);

						foreach ($options as $option) {
							$option_value_info = $this->model_catalog_product->getProductOptionValue($order_id, $product['order_product_id']);

							if ($option['type'] != 'file') {
								$value = $option['value'];
							} else {
								$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

								if ($upload_info) {
									$value = $upload_info['name'];
								} else {
									$value = '';
								}
							}

							$option_data[] = array(
								'name'  => $option['name'],
								'value' => $value
							);

							$product_option_value_info = $this->model_catalog_product->getProductOptionValue($product['product_id'], $option['product_option_value_id']);

							if ($product_option_value_info) {
								if ($product_option_value_info['weight_prefix'] == '+') {
									$option_weight += $product_option_value_info['weight'];
								} elseif ($product_option_value_info['weight_prefix'] == '-') {
									$option_weight -= $product_option_value_info['weight'];
								}
							}
						}

						$product_data[] = array(
							'name'     => $product_info['name'],
							'model'    => $product_info['model'],
							'option'   => $option_data,
							'quantity' => $product['quantity'],
							'location' => $product_info['location'],
							'sku'      => $product_info['sku'],
							'upc'      => $product_info['upc'],
							'ean'      => $product_info['ean'],
							'jan'      => $product_info['jan'],
							'isbn'     => $product_info['isbn'],
							'mpn'      => $product_info['mpn'],
							'weight'   => $this->weight->format(($product_info['weight'] + $option_weight) * $product['quantity'], $product_info['weight_class_id'], $this->language->get('decimal_point'), $this->language->get('thousand_point'))
						);
					}
				}

				$data['orders'][] = array(
					'order_id'	       => $order_id,
					'invoice_no'       => $invoice_no,
					'date_added'       => date($this->language->get('date_format_short'), strtotime($order_info['date_added'])),
					'store_name'       => $order_info['store_name'],
					'store_url'        => rtrim($order_info['store_url'], '/'),
					'store_address'    => nl2br($store_address),
					'store_email'      => $store_email,
					'store_telephone'  => $store_telephone,
					'store_fax'        => $store_fax,
					'email'            => $order_info['email'],
					'telephone'        => $order_info['telephone'],
					'shipping_address' => $shipping_address,
					'shipping_method'  => $order_info['shipping_method'],
					'product'          => $product_data,
					'comment'          => nl2br($order_info['comment'])
				);
			}
		}

		$this->response->setOutput($this->load->view('sale/order_shipping.tpl', $data));
	}
}

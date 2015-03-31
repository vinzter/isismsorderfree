<?php
	//////////////////////////////////////////////////////////
	//       Isismorderfree                         	    //
	//       Envoi de sms via free mobile           	    //
	//		 au vendeur quand une commande est validée	    //
	//       Voir fichier CHANGELOG.txt			     	    //
	//////////////////////////////////////////////////////////

class ISISmsOrderFree extends Module
{
	public $multishop = false;
	public $context = null;
	public $_html;

	function __construct()
	{
		global $css_files;
		$this->name = 'isismsorderfree';
		$this->tab = 'front_office_features';
		$this->version = 1.1;
		$this->author = 'Communauté';

		parent::__construct();

		$this->page = basename(__FILE__, '.php');
		$this->extension = sprintf("%010X", crc32("ISISmsOrderFree"))."_";
		$this->displayName = $this->l('ISISmsOrderFree');
		$this->description = $this->l('Send an SMS on your "Free Mobile" for each confirmed order');
		$this->multishop = Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE');
		$this->context = Context::getContext();
		
		$this->doInitialization();
		
		$this->usedHooks = Array('actionValidateOrder' => array() );
		$this->installHook();
	}

	function installHook() {
		$thisfile = _PS_ROOT_DIR_.'/modules/'.$this->name.'/'.$this->name.'.php';
		$content = file_get_contents($thisfile);
		$out = "";
		foreach($this->usedHooks as $hook => $cfgHook) {
			$exists = false;
			if(substr(_PS_VERSION_,0,3)>="1.5"){
				$exists = Hook::getIdByName($hook);
			}else{
				$exists = Hook::get($hook);
			}
			if(!$exists) {
				$xhook = new Hook();
				$xhook->name = $hook;
				$xhook->add();
			}
			$funcname = 'hook'.ucfirst($hook);
			
			if(!method_exists($this,$funcname) && isset($cfgHook["mapTo"]) && $cfgHook["mapTo"]!="") {
				$out .= "\r\n	function {$funcname}(\$params){ return \$this->{$cfgHook["mapTo"]}(\$params); }\r\n";
			}
		}
		
		if($out!="" && ($pos = strpos($content, '/** Auto'.' '.'Extentions **/'))!==false) {
			unlink($thisfile);
			$file = fopen($thisfile, "w+");
			fwrite($file, substr($content, 0, $pos+strlen('/** Auto'.' '.'Extentions **/\r\n')));
			fwrite($file, $out);
			fwrite($file, substr($content, $pos+strlen('/** Auto'.' '.'Extentions **/\r\n')));
			fclose($file);
		}
		return true;
	}

	function install(){
		parent::install();
		foreach($this->usedHooks as $hook => $cfgHook) {
			$this->registerHook($hook);
		}

		$xdata = array(
			"code_1" => '',
			"cle_1" => '',
			"code_2" => '',
			"cle_2" => '',
			"send_first_phone" => '',
			"send_second_phone" => '',
			"show_order_num" => '',
			"show_order_ref" => '',
			"show_customer_details" => '',
			"show_total_amount" => '',
			"show_payment_mode" => '',
			"show_carrier_name" => '',
			"show_country_delivery" => '',
			"show_items" => '',
		);

		Configuration::updateValue($this->extension.'CFG', base64_encode(gzdeflate(serialize($xdata))));
		return true;
	}

	function uninstall(){
		Configuration::deleteByName($this->extension.'CFG');
		parent::uninstall();
		return true;
	}

	public function getContent()
	{
		$output = '<h2>'.$this->displayName.'</h2>';
		if (Tools::isSubmit('submit'.$this->extension)){
			$this->setConfig();
			$output .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />'.$this->l('Settings updated').'</div>';
		}elseif(Tools::isSubmit('submit'.$this->extension."Ajax")){
			ob_clean();
			$uid = (int)$_POST["uid"];
			$xdata = $this->getConfig();
			echo json_encode($xdata["content"][$uid]);
			die();
		}
		return '<div style="max-width:900px;">'.$output.$this->displayForm().'</div>';
	}

	function doInitialization(){
		return true;
  }

	function getMyConfig(){
		$xdata = unserialize(gzinflate(base64_decode(Configuration::get($this->extension.'CFG'))));
		$xdata = (($xdata===false)?array():$xdata);
		return $xdata;
	}

	public function displayForm()
	{
		$this->_html .= '
		<script type="text/javascript">
			function ISIjQueryLoaded(){
				// loadISILib(link, callback);
			}
		</script>
		<script type="text/javascript" src="'._MODULE_DIR_.$this->name.'/js/include-jquery.js'.'"></script>
		<style>
			img.edit	{	cursor: pointer;		}
			img.remove	{	cursor: not-allowed;	}
			.drophover	{	background: #c0c0c0;	}
			.menu-zone-container	{	float: left; padding: 0 5px;	}
			.menu-zone	{	border: 1px solid #000;	}
			.hide 		{ display: none; }
			.block-config {
				border: 1px solid #000;
				padding: 10px;
			}
			#isitabmenu li {
				border: 1px solid #000;
				float: left;
				padding: 5px;
				cursor: pointer;
				list-style-type: none;
			}
			#isitabmenu li:hover, .isitabmenu-current {
				/*background: #D6A;*/
			}			
			#isitabmenu li:first-child {
				border-left: 1px solid #000;;
			}	
			#menu-style {
				padding: 10px;
			}
		</style>
		<form  id="main-form" action="'.$_SERVER['REQUEST_URI'].'" method="post" >
			<fieldset>
				<legend><img src="'.$this->_path.'logo.gif" alt="" title="" />'.$this->l('Settings').'</legend>	
		';

		$this->getForm();

		$this->_html .= '
				<!-- center><input type="submit" name="submit'.$this->extension.'" value="'.$this->l('Save').'" class="button" /></center -->
			</fieldset>
		</form>
		';
		return $this->_html;
	}

	function setConfig() {
				isset($_POST['send_first_phone']) ? $send_first_phone = $_POST['send_first_phone'] : $send_first_phone = '';
				isset($_POST['send_second_phone']) ? $send_second_phone = $_POST['send_second_phone'] : $send_second_phone = '';
				isset($_POST['show_order_num']) ? $show_order_num = $_POST['show_order_num'] : $show_order_num = '';
				isset($_POST['show_order_ref']) ? $show_order_ref = $_POST['show_order_ref'] : $show_order_ref = '';
				isset($_POST['show_customer_details']) ? $show_customer_details = $_POST['show_customer_details'] : $show_customer_details = '';
				isset($_POST['show_total_amount']) ? $show_total_amount = $_POST['show_total_amount'] : $show_total_amount = '';
				isset($_POST['show_payment_mode']) ? $show_payment_mode = $_POST['show_payment_mode'] : $show_payment_mode = '';
				isset($_POST['show_carrier_name']) ? $show_carrier_name = $_POST['show_carrier_name'] : $show_carrier_name = '';
				isset($_POST['show_country_delivery']) ? $show_country_delivery = $_POST['show_country_delivery'] : $show_country_delivery = '';
				isset($_POST['show_items']) ? $show_items = $_POST['show_items'] : $show_items = '';

				$oldxdata = $this->getConfig();
				$xdata = array(
					"code_1" => $_REQUEST["code_1"],
					"cle_1" => $_REQUEST["cle_1"],
					"code_2" => $_REQUEST["code_2"],
					"cle_2" => $_REQUEST["cle_2"],
					"send_first_phone" => $send_first_phone,
					"send_second_phone" => $send_second_phone,
					"show_order_num" => $show_order_num,
					"show_order_ref" => $show_order_ref,
					"show_customer_details" => $show_customer_details,
					"show_total_amount" => $show_total_amount,
					"show_payment_mode" => $show_payment_mode,
					"show_carrier_name" => $show_carrier_name,
					"show_country_delivery" => $show_country_delivery,
					"show_items" => $show_items,
				);
				Configuration::updateValue($this->extension.'CFG', base64_encode(gzdeflate(serialize($xdata))));
	}

	function getConfig() {
		// to be replaced
		return $this->getMyConfig();
	}
	
	function getForm() {
		global $cookie;
		$xdata = $this->getConfig();
		($xdata["send_first_phone"]=="on") ? $send_first_phone_Checked = 'checked' : $send_first_phone_Checked = '';
		($xdata["send_second_phone"]=="on") ? $send_second_phone_Checked = 'checked' : $send_second_phone_Checked = '';
		($xdata["show_order_num"]=="on") ? $show_order_num_Checked = 'checked' : $show_order_num_Checked = '';
		($xdata["show_order_ref"]=="on") ? $show_order_ref_Checked = 'checked' : $show_order_ref_Checked = '';
		($xdata["show_customer_details"]=="on") ? $show_customer_details_Checked = 'checked' : $show_customer_details_Checked = '';
		($xdata["show_total_amount"]=="on") ? $show_total_amount_Checked = 'checked' : $show_total_amount_Checked = '';
		($xdata["show_payment_mode"]=="on") ? $show_payment_mode_Checked = 'checked' : $show_payment_mode_Checked = '';
		($xdata["show_carrier_name"]=="on") ? $show_carrier_name_Checked = 'checked' : $show_carrier_name_Checked = '';
		($xdata["show_country_delivery"]=="on") ? $show_country_delivery_Checked = 'checked' : $show_country_delivery_Checked = '';
		($xdata["show_items"]=="on") ? $show_items_Checked = 'checked' : $show_items_Checked = '';

		$this->_html .= '
					<p>
						'.$this->l('Before going any further, you need to activate the SMS notification on your "Free Mobile" account to generate the Key ID needed for configuration.').'
					</p>
					<p>
						'.$this->l('The received SMS will look like this but you can select what elements to display : ').'<br />
						<div style="font-style:italic;padding-left:20px;">'.$this->l('New order : #num.').'<br />
						'.$this->l('Order ref. : #ref.').'<br />
						'.$this->l('Client : #firstname #lastname.').'<br />
						'.$this->l('Total : #total.').'<br />
						'.$this->l('Payment : #payment mode.').'<br />
						'.$this->l('Shipping mode : #shipping mode.').'<br />
						'.$this->l('Shipping to : #country delivery.').'<br />
						'.$this->l('Products details :.').'<br />
						'.$this->l('#qty X #ref #name #attributes #price.').'</div>
					</p>
					<br style="clear: both"/>
					'.$this->l('Choose your SMS options').'
					<div id="tab-config" class="block-config">
						<label>'.$this->l('Show order number').'</label>
						<div class="margin-form">
							<input type="checkbox" name="show_order_num" '.$show_order_num_Checked.' />
						</div>
						<label>'.$this->l('Show order reference').'</label>
						<div class="margin-form">
							<input type="checkbox" name="show_order_ref" '.$show_order_ref_Checked.' />
						</div>
						<label>'.$this->l('Show customer details').'</label>
						<div class="margin-form">
							<input type="checkbox" name="show_customer_details" '.$show_customer_details_Checked.' />
						</div>
						<label>'.$this->l('Show total amount').'</label>
						<div class="margin-form">
							<input type="checkbox" name="show_total_amount" '.$show_total_amount_Checked.' />
						</div>
						<label>'.$this->l('Show payment mode').'</label>
						<div class="margin-form">
							<input type="checkbox" name="show_payment_mode" '.$show_payment_mode_Checked.' />
						</div>
						<label>'.$this->l('Show carrier name').'</label>
						<div class="margin-form">
							<input type="checkbox" name="show_carrier_name" '.$show_carrier_name_Checked.' />
						</div>
						<label>'.$this->l('Show country delivery').'</label>
						<div class="margin-form">
							<input type="checkbox" name="show_country_delivery" '.$show_country_delivery_Checked.' />
						</div>
						<label>'.$this->l('Show items').'</label>
						<div class="margin-form">
							<input type="checkbox" name="show_items" '.$show_items_Checked.' />
						</div>
					</div>
					<br style="clear: both"/>
					'.$this->l('Config 1st Phone').'
					<div id="tab-config" class="block-config">
						<label>'.$this->l('Send on first phone').'</label>
						<div class="margin-form">
							<input type="checkbox" name="send_first_phone" '.$send_first_phone_Checked.' />
						</div>
						<label>'.$this->l('Free Mobile Client ID').'</label>
						<div class="margin-form">
							<input type="text" name="code_1" value="'.$xdata["code_1"].'" />
							<p class="clear">'.$this->l('Set your "Free Mobile" client number').'</p>
						</div>
						<label>'.$this->l('Key ID').'</label>
						<div class="margin-form">
							<input type="text" name="cle_1" value="'.$xdata["cle_1"].'" />
							<p class="clear">'.$this->l('Set the key number').' ('.$this->l('check the parameters in your "Free Mobile" account').')</p>
						</div>
					</div>
					<br style="clear: both"/>
					'.$this->l('Config 2nd Phone').'
					<div id="tab-config" class="block-config">
						<label>'.$this->l('Send on second phone').'</label>
						<div class="margin-form">
							<input type="checkbox" name="send_second_phone" '.$send_second_phone_Checked.' />
						</div>
						<label>'.$this->l('Free Mobile Client ID').'</label>
						<div class="margin-form">
							<input type="text" name="code_2" value="'.$xdata["code_2"].'" />
							<p class="clear">'.$this->l('Set your "Free Mobile" client number').'</p>
						</div>
						<label>'.$this->l('Key ID').'</label>
						<div class="margin-form">
							<input type="text" name="cle_2" value="'.$xdata["cle_2"].'" />
							<p class="clear">'.$this->l('Set the key number').' ('.$this->l('check the parameters in your "Free Mobile" account').')</p>
						</div>
					</div>
					<br style="clear: both"/>
					<div>
						<div style="float: right"><input type="submit" id="btnSaveModule" name="submit'.$this->extension.'" value="'.$this->l('Save').'" class="button" /></div>
					</div>
					<script type="text/javascript">
						function ISIjQueryLoaded(){
							loadISILib("'._MODULE_DIR_.$this->name.'/js/jquery-ui-1.8.21.js", function(){
							});
							
							$("#isitabmenu li").each(function(i,o){
								$(o).click(function(){
									$("#isitabmenu li").removeClass("isitabmenu-current");
									$(o).addClass("isitabmenu-current");
									$("div.block-config").hide();
									$("#"+$(o).attr("target")).fadeIn();
								});
							});
						}
					</script>
		';
	}

	function getData() {
		global $smarty, $cookie, $cart;
		$smarty->assign('modulehash', $this->name.' - '.$this->version.' - '.$this->author);
		$smarty->assign('modulename', $this->name);
		$this->display = false;
		$xdata = $this->getConfig();
		if($xdata['code_1']!='' && $xdata['cle_1']!='') $this->display = true;
	}

	function hookActionValidateOrder($params){
		$this->getData();
		$xdata = $this->getConfig();
		$currency = $params['currency'];
		if($this->display){
			($xdata["show_order_num"]!='') ? $order_num = $this->l('New order') . " : ".sprintf("%06d",$params['order']->id)."\r\n" : $order_num = '';
            ($xdata["show_order_ref"]!='') ? $order_ref = $this->l('Order ref.') . " : ".$params['order']->reference."\r\n" : $order_ref = '';
            ($xdata["show_payment_mode"]!='') ? $payment_mode = $this->l('Payment') . " : ".$params['order']->payment."\r\n" : $payment_mode = '';

			if ($xdata["show_customer_details"]!='') {
				$the_customer = $params['customer'];
				$customer_details = $this->l('Client') . " : ".$the_customer->firstname." ".$the_customer->lastname."\r\n";
			} else {
				$customer_details = '';
			}
            if ($xdata["show_total_amount"]!='') {
				$total_amount = $this->l('Total') . " : ".$params['order']->total_paid." ".$currency->sign."\r\n";
			} else {
				$total_amount = '';
			}
			if ($xdata["show_carrier_name"]!='') {
				$carrier = new Carrier((int)$params['order']->id_carrier);
				$carrier_name = $this->l('Shipping mode') . " : ". $carrier->name."\r\n";
			} else {
				$carrier_name = '';
			}
            if ($xdata["show_country_delivery"]!='') {
				$address_delivery = new Address((int)$params['order']->id_address_delivery);
				$country_delivery = $this->l('Shipping to') . " : ". Country::getNameById((int)$this->context->cookie->id_lang, (int)$address_delivery->id_country)."\r\n\r\n";
			} else {
				$country_delivery = '';
			}
			if ($xdata["show_items"]!='') {
				$items = $this->l('Products details') . " :\r\n";
				$products = $params['order']->getProducts();

				foreach ($products as $key => $product)
				{
					$unit_price = $product['product_price_wt'];
					$items .= (int)$product['product_quantity']." X ".$product['product_reference']." ".$product['product_name']." ".(isset($product['attributes_small']) ? ' '.$product['attributes_small'] : '')." ".Tools::displayPrice($unit_price, $currency, false)."\r\n";
				}
			} else {
				$items = '';
			}

			$msg =  urlencode($order_num.$order_ref.$customer_details.$total_amount.$payment_mode.$carrier_name.$country_delivery.$items);

			if($xdata['send_first_phone']!='') { @file_get_contents("https://smsapi.free-mobile.fr/sendmsg?user=".$xdata['code_1']."&pass=".$xdata['cle_1']."&msg=".$msg); }
			if($xdata['send_second_phone']!='') { @file_get_contents("https://smsapi.free-mobile.fr/sendmsg?user=".$xdata['code_2']."&pass=".$xdata['cle_2']."&msg=".$msg); }
		}
	}
}

	/** Auto Extentions **/

	/** Fin Auto Extentions **/
?>

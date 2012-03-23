<?php
/*
  Plugin Name: 3dcart Online Store
  Plugin URI: http://www.3dcart.com
  Description: This is an official plugin of 3DCart, to fetch products from your shop and display it in widget.
  Author: 3dcart
  Version: V.1.0
  Author URI: http://www.3dcart.com
 */
class W_3dCartProduct_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'3dcart_widget', // Base ID
			'3dcart Online Store', // Name
			array('description' => __('Display list of products from your shop', 'text_domain'),) // Args
		);
	}
	public function xml2array($contents, $get_attributes=1, $priority = 'tag') {
		if (!$contents)
			return array();

		if (!function_exists('xml_parser_create')) {
			//print "'xml_parser_create()' function not found!";
			return array();
		}

		//Get the XML parser of PHP - PHP must have this module for the parser to work
		$parser = xml_parser_create('');
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # 
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, trim($contents), $xml_values);
		xml_parser_free($parser);

		if (!$xml_values)
			return; //Hmm...





			
//Initializations
		$xml_array = array();
		$parents = array();
		$opened_tags = array();
		$arr = array();

		$current = &$xml_array; //Refference
		//Go through the tags.
		$repeated_tag_index = array(); //Multiple tags with same name will be turned into an array
		foreach ($xml_values as $data) {
			unset($attributes, $value); //Remove existing values, or there will be trouble
			//This command will extract these variables into the foreach scope
			// tag(string), type(string), level(int), attributes(array).
			extract($data); //We could use the array by itself, but this cooler.

			$result = array();
			$attributes_data = array();

			if (isset($value)) {
				if ($priority == 'tag')
					$result = $value;
				else
					$result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
			}

			//Set the attributes too.
			if (isset($attributes) and $get_attributes) {
				foreach ($attributes as $attr => $val) {
					if ($priority == 'tag')
						$attributes_data[$attr] = $val;
					else
						$result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
				}
			}

			//See tag status and do the needed.
			if ($type == "open") {//The starting of the tag '<tag>'
				$parent[$level - 1] = &$current;
				if (!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
					$current[$tag] = $result;
					if ($attributes_data)
						$current[$tag . '_attr'] = $attributes_data;
					$repeated_tag_index[$tag . '_' . $level] = 1;

					$current = &$current[$tag];
				} else { //There was another element with the same tag name
					if (isset($current[$tag][0])) {//If there is a 0th element it is already an array
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
						$repeated_tag_index[$tag . '_' . $level]++;
					} else {//This section will make the value an array if multiple tags with the same name appear together
						$current[$tag] = array($current[$tag], $result); //This will combine the existing item and the new item together to make an array
						$repeated_tag_index[$tag . '_' . $level] = 2;

						if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
							$current[$tag]['0_attr'] = $current[$tag . '_attr'];
							unset($current[$tag . '_attr']);
						}
					}
					$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
					$current = &$current[$tag][$last_item_index];
				}
			} elseif ($type == "complete") { //Tags that ends in 1 line '<tag />'
				//See if the key is already taken.
				if (!isset($current[$tag])) { //New Key
					$current[$tag] = $result;
					$repeated_tag_index[$tag . '_' . $level] = 1;
					if ($priority == 'tag' and $attributes_data)
						$current[$tag . '_attr'] = $attributes_data;
				} else { //If taken, put all things inside a list(array)
					if (isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...
						// ...push the new element into that array.
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;

						if ($priority == 'tag' and $get_attributes and $attributes_data) {
							$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
						}
						$repeated_tag_index[$tag . '_' . $level]++;
					} else { //If it is not an array...
						$current[$tag] = array($current[$tag], $result); //...Make it an array using using the existing value and the new value
						$repeated_tag_index[$tag . '_' . $level] = 1;
						if ($priority == 'tag' and $get_attributes) {
							if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
								$current[$tag]['0_attr'] = $current[$tag . '_attr'];
								unset($current[$tag . '_attr']);
							}

							if ($attributes_data) {
								$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
							}
						}
						$repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
					}
				}
			} elseif ($type == 'close') { //End of tag '</tag>'
				$current = &$parent[$level - 1];
			}
		}

		return($xml_array);
	}
	public function form($instance) {
		if (isset($instance['title'])) {
			$title = $instance['title'];
			$url = $instance['url'];
			$item_sel = $instance['item_sel'];
			$style = $instance['style'];
			$product = $instance['product'];
			$affiliate = $instance['affiliate'];
			$catid = $instance['catid'];
			$cssstyle = $instance['cssstyle'];
		} else {
			$title = __('3d Cart Products', 'text_domain');
			$url = '';
			$item_sel = 'home';
			$style = "0";
			$cssstyle = "simple";
			$product = '';
			$affiliate = '';
			$catid = '';
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('url'); ?>"><?php _e('URL:'); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('url'); ?>" name="<?php echo $this->get_field_name('url'); ?>" type="text" value="<?php echo esc_attr($url); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('product'); ?>"><?php _e('No. of Products'); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('product'); ?>" name="<?php echo $this->get_field_name('product'); ?>" type="text" value="<?php if($product>0) echo esc_attr($product); else echo 5; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('affiliate'); ?>"><?php _e('Affilate ID:'); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('affiliate'); ?>" name="<?php echo $this->get_field_name('affiliate'); ?>" type="text" value="<?php echo esc_attr($affiliate); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('item_sel'); ?>"><?php _e('Item Selection:'); ?></label> 
			<select class="widefat" id="<?php echo $this->get_field_id('item_sel'); ?>" name="<?php echo $this->get_field_name('item_sel'); ?>">			
				<option value="home" <?php
		if ($item_sel == "home") {
			echo 'selected';
		}
		?>>Home Specials</option>
				<option value="onsale" <?php
		if ($item_sel == "onsale") {
			echo 'selected';
		}
		?>>On Sale</option>
				<option value="category" <?php
		if ($item_sel == "category") {
			echo 'selected';
		}
		?>>Specific Category</option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('catid'); ?>"><?php _e('Category ID:'); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('catid'); ?>" name="<?php echo $this->get_field_name('catid'); ?>" type="text" value="<?php echo esc_attr($catid); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('style'); ?>"><?php _e('Template Style:'); ?></label> 
			<select class="widefat" id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>">			
				<option value="0" <?php
		if ($style == "0") {
			echo 'selected';
		}
		?>>Default</option>
				<option value="1" <?php
		if ($style == "1") {
			echo 'selected';
		}
		?>>Template 1</option>
				<option value="2" <?php
		if ($style == "2") {
			echo 'selected';
		}
		?>>Template 2</option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('cssstyle'); ?>"><?php _e('Style:'); ?></label> 
			<select class="widefat" id="<?php echo $this->get_field_id('cssstyle'); ?>" name="<?php echo $this->get_field_name('cssstyle'); ?>">			
				<option value="simple" <?php if ($cssstyle == "simple") echo 'selected';	?>>Simple</option>
				<option value="fancy-black" <?php if ($cssstyle == "fancy-black") echo 'selected';	?>>Fancy Black</option>
				<option value="custom" <?php if ($cssstyle == "custom") echo 'selected'; ?>> No Styling</option>
			</select>
		</p>
		<?php
	}
	public function getXMLFromURL($rss_url) {
			
		$ch = curl_init($rss_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$data = curl_exec($ch);
		curl_close($ch);
		//print_r($data);
		
		return $data;
	}
	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['url'] = strip_tags($new_instance['url']);
		$instance['item_sel'] = strip_tags($new_instance['item_sel']);
		$instance['style'] = strip_tags($new_instance['style']);
		$instance['cssstyle'] = strip_tags($new_instance['cssstyle']);
		$instance['product'] = strip_tags($new_instance['product']);
		$instance['affiliate'] = strip_tags($new_instance['affiliate']);
		$instance['catid'] = strip_tags($new_instance['catid']);

		return $instance;
	}
	public function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$url = $instance['url'];
		$item_sel = $instance['item_sel'];
		$style = $instance['style'];
		$cssstyle = $instance['cssstyle'];
		$product = $instance['product'];
		$affiliate = $instance['affiliate'];
		$catid = $instance['catid'];

		echo $before_widget;
		if (!empty($title)) {
			echo $before_title . $title . $after_title;
		}
		if (isset($url) && !empty($url)) {
			$url .= '/wordpress.asp?a=1&style=' . $style;
			if (!empty($item_sel)) {
				$url .= '&type=' . $item_sel;
			}
			if (!empty($product)) {
				$url .= '&batchSize=' . $product;
			}
			if (!empty($affiliate)) {
				$url .= '&affid=' . $affiliate;
			}
			if (!empty($catid)) {
				$url .= '&catid=' . $catid;
			}			
			
			$xmlData = $this->getXMLFromURL($url);
			
			$arrayXML = $this->xml2array($xmlData);

			$array = $arrayXML['rss']['channel']['item'];
			echo "<div class='w3dcart-product-".$cssstyle."'>";
			foreach ($array as $key => $value) {
				echo "<div class='w3dcart-products-item'>";	
				echo $value['description'];
				echo "</div>";
			}
			echo "</div>";
		} else {
			echo 'Store settings mismatch';
		}

		echo $after_widget;
	}
}
add_action('widgets_init', create_function('', 'register_widget( "W_3dCartProduct_Widget" );'));
add_action('wp_enqueue_scripts', 'W_3dCartProduct_Widget_stylesheet');
function W_3dCartProduct_Widget_stylesheet(){
 $myStyleUrl = plugins_url('style.css', __FILE__); // Respects SSL, Style.css is relative to the current file
        $myStyleFile = WP_PLUGIN_DIR . '/3dcart/style.css';
        if ( file_exists($myStyleFile) ) {
            wp_register_style('myStyleSheets', $myStyleUrl);
            wp_enqueue_style( 'myStyleSheets');
        }	
}
?>
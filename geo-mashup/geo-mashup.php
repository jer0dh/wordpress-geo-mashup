<?php /*
Plugin Name: Geo Mashup
Plugin URI: http://code.google.com/p/wordpress-geo-mashup/ 
Description: Tools for adding maps to your blog, and plotting posts on a master map. Configure in <a href="options-general.php?page=geo-mashup/geo-mashup.php">Options->Geo Mashup</a> after the plugin is activated.
Version: 1.2beta1
Author: Dylan Kuhn
Author URI: http://www.cyberhobo.net/
Minimum WordPress Version Required: 2.5.1
*/

/*
Copyright (c) 2005-2007 Dylan Kuhn

This program is free software; you can redistribute it
and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation;
either version 2 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be
useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE. See the GNU General Public License for more
details.
*/

/**
 * The Geo Mashup static class.
 */
class GeoMashup {
	public static $url_path;
	public static $kml_load_script = null;
	public static $options;
	public static $default_options = array(
		'using_defaults' => true, 
		'category_link_separator' => '::',
		'category_link_text' => 'map',
		'category_zoom' => '7',
		'add_category_links' => 'false',
		'theme_stylesheet_with_maps' => 'false',
		'map_width' => '400',
		'map_height' => '500',
		'map_type' => 'G_NORMAL_MAP',
		'zoom_level' => '7',
		'excerpt_format' => 'text',
		'excerpt_length' => '250',
		'category_color' => array(),
		'category_line_zoom' => array(),
		'marker_min_zoom' => '',
		'map_control' => 'GSmallMapControl',
		'add_map_type_control' => 'true',
		'add_overview_control' => 'false',
		'show_post' => 'false',
		'show_future' => 'false',
		'marker_min_zoom' => '',
		'max_posts' => '',
		'auto_info_open' => 'true',
		'click_to_load' => 'false',
		'in_post_map_control' => 'GSmallMapControl',
		'in_post_map_width' => '400',
		'in_post_map_height' => '500',
		'in_post_map_type' => 'G_NORMAL_MAP',
		'in_post_zoom_level' => '11',
		'in_post_add_overview_control' => 'false',
		'in_post_add_map_type_control' => 'true',
		'in_post_click_to_load' => 'false');

	public static function load() {
		load_plugin_textdomain('GeoMashup', 'wp-content/plugins/geo-mashup/languages');

		self::load_members();
		self::load_dependencies();
		self::load_hooks();
		self::load_styles();
		self::load_scripts();
	}

	private static function load_hooks() {
		if (is_admin()) {
			add_filter('upload_mimes', array('GeoMashup', 'upload_mimes'));

			add_action('admin_menu', array('GeoMashup', 'admin_menu'));
			add_action('save_post', array('GeoMashup', 'save_post'));
			add_action('wp_handle_upload', array('GeoMashup', 'wp_handle_upload'));
			add_action('admin_print_scripts', array('GeoMashup', 'admin_print_scripts'));
		} else {
			if (self::$options['add_category_links'] == 'true') {
				add_filter('list_cats', array('GeoMashup', 'list_cats'), 10, 2);
			}

			add_action('wp_head', array('GeoMashup', 'wp_head'));
			add_action('rss_ns', array('GeoMashup', 'rss_ns'));
			add_action('rss2_ns', array('GeoMashup', 'rss_ns'));
			add_action('atom_ns', array('GeoMashup', 'rss_ns'));
			add_action('rss_item', array('GeoMashup', 'rss_item'));
			add_action('rss2_item', array('GeoMashup', 'rss_item'));
			add_action('atom_entry', array('GeoMashup', 'rss_item'));
		}
	}

	private static function load_members() {
		self::$options = get_settings('geo_mashup_options');
		if (!is_array(self::$options)) {
			$failed_options = self::$options;
			self::$options = self::$default_options;
			if (is_string(self::$options) && !empty(self::$options)) {
				self::$options['failed_options'] = $failed_options;
			}
		}

		$plugin_name = plugin_basename(__FILE__);
		$plugin_name = substr($plugin_name, 0, strpos($plugin_name, '/'));
		self::$url_path = WP_CONTENT_URL . '/plugins/' . $plugin_name;
	}

	private static function load_dependencies() {
		if (!is_admin()) {
			require_once(dirname(__FILE__) . '/shortcodes.php');
		}
	}

	private static function load_scripts() {
		if (is_admin()) {
			if ($_GET['page'] == 'geo-mashup/geo-mashup.php') {

				wp_enqueue_script('jquery-ui-tabs');

			} else if (self::$options['google_key'] && preg_match('/(post|page)(-new|).php/',$_SERVER['REQUEST_URI'])) {

				wp_enqueue_script('geo-mashup-google-api', 'http://maps.google.com/maps?file=api&amp;v=2&amp;key='.self::$options['google_key']);
				wp_enqueue_script('geo-mashup-admin', self::$url_path.'/geo-mashup-admin.js', false, '1.0.0');
				wp_enqueue_script('json-script-request', self::$url_path.'/JSONscriptRequest.js', false, '1.0.0');

			}
		} else {
			wp_enqueue_script('geo-mashup-loader',self::$url_path.'/geo-mashup-loader.js',false,'1.0.0');
		}
	}

	private static function load_styles() {
		if (is_admin()) {
			if ($_GET['page'] == 'geo-mashup/geo-mashup.php') {

				wp_enqueue_style('geo-mashup-tabs', self::$url_path.'/jquery.tabs.css', false, '2.5.0', 'screen');

			} else if (preg_match('/(post|page)(-new|).php/', $_SERVER['REQUEST_URI'])) {

				wp_enqueue_style('geo-mashup-edit-form', self::$url_path.'/edit-form.css', false, '1.0.0', 'screen');

			}
		}
	}

	public static function explode_assoc($glue1, $glue2, $array) {
		$array2=explode($glue2, $array);
		foreach($array2 as  $val) {
			$pos=strpos($val,$glue1);
			$key=substr($val,0,$pos);
			$array3[$key] =substr($val,$pos+1,strlen($val));
		}
		return $array3;
	}

	public static function implode_assoc($inner_glue, $outer_glue, $array, $skip_empty=false, $urlencoded=false) {
	$output = array();
		foreach($array as $key=>$item) {
			if (!$skip_empty || isset($item)) {
				if ($urlencoded)
					$output[] = $key.$inner_glue.urlencode($item);
				else
					$output[] = $key.$inner_glue.$item;
			}
		}
		return implode($outer_glue, $output);
	}

	public static function get_kml_attachment_urls($post_id)
	{
		if (!isset($post_id) || is_null($post_id))
		{
			return array();
		}
		$args = array(
			'post_type' => 'attachment',
			'numberposts' => null,
			'post_status' => null,
			'post_parent' => $post_id
			); 
		$attachments = get_posts($args);
		$urls = array();
		if ($attachments) {
			foreach ($attachments as $attachment) {
				$attachment_url = $attachment->guid;
				if (stripos($attachment_url,'.kml') == strlen($attachment_url)-4)
				{
					array_push($urls,$attachment_url);
				}
			}
		}
		return $urls;
	}

	public static function upload_mimes($mimes) {
		$mimes['kml'] = 'application/vnd.google-earth.kml+xml';
		return $mimes;
	}

	public static function wp_head() {
		global $wp_query;

		if (is_single())
		{
			list($lat, $lon) = split(',', get_post_meta($wp_query->post->ID, '_geo_location', true));
			if (($lat != '') && ($lon != '')) {
				$title = htmlspecialchars(convert_chars(strip_tags(get_bloginfo('name'))." - ".$wp_query->post->post_title));
				echo "<meta name=\"ICBM\" content=\"{$lat}, {$lon}\" />\n";
				echo "<meta name=\"DC.title\" content=\"{$title}\" />\n";
				echo "<meta name=\"geo.position\" content=\"{$lat};{$lon}\" />\n";
			}
		}
		else
		{
			$geo_locations = get_settings('geo_locations');
			if (is_array($geo_locations) && $geo_locations['default'])
			{
				list($lat, $lon) = split(',', $geo_locations['default']);
				$title = htmlspecialchars(convert_chars(strip_tags(get_bloginfo('name'))));
				echo "<meta name=\"ICBM\" content=\"{$lat}, {$lon}\" />\n";
				echo "<meta name=\"DC.title\" content=\"{$title}\" />\n";
				echo "<meta name=\"geo.position\" content=\"{$lat};{$lon}\" />\n";
			}
		}
	}

	public static function admin_print_scripts($not_used)
	{
		if ($_GET['page'] == 'geo-mashup/geo-mashup.php') {

			echo '
				<script type="text/javascript"> 
					addLoadEvent(function() { jQuery("#geo-mashup-settings-form > ul").tabs(); }); 
				</script>';

		} else if (preg_match('/(post|page)(-new|).php/', $_SERVER['REQUEST_URI'])) {

			$kml_url = get_option('geo_mashup_temp_kml_url');
			if (strlen($kml_url) > 0)
			{
				echo '
					<script type="text/javascript"> 
						if (parent.GeoMashupAdmin) parent.GeoMashupAdmin.loadKml(\''.$kml_url.'\');
					</script>';
				update_option('geo_mashup_temp_kml_url','');
			}
		}
	}

	public static function wp_handle_upload($args)
	{
		update_option('geo_mashup_temp_kml_url','');
		if (is_array($args) && isset($args->file)) {
			if (stripos($args['file'],'.kml') == strlen($args['file'])-4) {
				update_option('geo_mashup_temp_kml_url',$args['url']);
			}
		}
		return $args;
	}

	public static function edit_form_advanced()
	{
		include_once(dirname(__FILE__) . '/edit-form.php');
		geo_mashup_edit_form();
	}

	public static function save_post($post_id) {
		if (!wp_verify_nonce($_POST['geo_mashup_edit_nonce'], 'geo-mashup-edit-post')) {
			return $post_id;
		}
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id )) return $post_id;
		} else {
			if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
		}

		delete_post_meta($post_id, '_geo_location');
		update_option('geo_mashup_temp_kml_url','');
		if (isset($_POST['geo_mashup_location'])) {
			add_post_meta($post_id, '_geo_location', $_POST['geo_mashup_location']);

			if (isset($_POST['geo_mashup_location_name']) && $_POST['geo_mashup_location_name'] != '') {
				$geo_locations = get_settings('geo_locations');
				$geo_locations[$_POST['geo_mashup_location_name']] = $_POST['geo_mashup_location'];
				update_option('geo_locations',$geo_locations);
			}
		}
	}

	public static function getLocations($query_args)
	{
		global $wpdb;
		$query_args = wp_parse_args($query_args);
		
		// Construct the query 
		$fields = 'ID, post_title, meta_value';
		$tables = "$wpdb->postmeta pm
			INNER JOIN $wpdb->posts p
			ON pm.post_id = p.ID";
		$where = 'meta_key=\'_geo_location\''.
			' AND length(meta_value)>1';

		if ($query_args['show_future'] == 'true') {
			$where .= ' AND post_status in (\'publish\',\'future\')';
		} else if ($query_args['show_future'] == 'only') {
			$where .= ' AND post_status=\'future\'';
		} else {
			$where .= ' AND post_status=\'publish\'';
		}

		// Ignore nonsense bounds
		if ($query_args['minlat'] && $query_args['maxlat'] && $query_args['minlat']>$query_args['maxlat']) {
			$query_args['minlat'] = $query_args['maxlat'] = null;
		}
		if ($query_args['minlon'] && $query_args['maxlon'] && $query_args['minlon']>$query_args['maxlon']) {
			$query_args['minlon'] = $query_args['maxlon'] = null;
		}
		// Build bounding where clause
		if (is_numeric($query_args['minlat'])) $where .= " AND substring_index(meta_value,',',1)>{$query_args['minlat']}";
		if (is_numeric($query_args['minlon'])) $where .= " AND substring_index(meta_value,',',-1)>{$query_args['minlon']}";
		if (is_numeric($query_args['maxlat'])) $where .= " AND substring_index(meta_value,',',1)<{$query_args['maxlat']}";
		if (is_numeric($query_args['maxlon'])) $where .= " AND substring_index(meta_value,',',-1)<{$query_args['maxlon']}";

		if (is_numeric($query_args['map_cat'])) {
			$tables .= " JOIN $wpdb->term_relationships tr ON tr.object_id = p.ID " .
				"JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id " .
				"AND tt.taxonomy='category'";
			$cat = $wpdb->escape($query_args['map_cat']);
			$where .= " AND tt.term_id=$cat";
		} 

		if (isset($query_args['post_id'])) {
			$where .= ' AND p.ID = ' . $wpdb->escape($query_args['post_id']);
		} else if (isset($query_args['post_ids'])) {
			$where .= ' AND p.ID in (' . $wpdb->escape($query_args['post_ids']) .')';
		}

		$query_string = "SELECT $fields FROM $tables WHERE $where ORDER BY post_status ASC, post_date DESC";

		if (!($query_args['minlat'] && $query_args['maxlat'] && $query_args['minlon'] && $query_args['maxlon']) && !$query_args['limit']) {
			// result should contain all posts (possibly for a category)
		} else if (is_numeric($query_args['limit']) && $query_args['limit']>0) {
			$query_string .= " LIMIT 0,{$query_args['limit']}";
		}

		$wpdb->query($query_string);
		
		return $wpdb->last_result;
	}

	public static function getLocationsJson($query_args)
	{
		global $wpdb;

		$json = '{ posts : [';
		$posts = self::getLocations($query_args);
		if ($posts) {
			$comma = '';
			foreach ($posts as $post) {
				list($lat,$lng) = split(',',$post->meta_value);
				$json .= $comma.'{"post_id":"'.$post->ID.'","title":"'.addslashes($post->post_title).
					'","lat":"'.$lat.'","lng":"'.$lng.'","categories":[';
				$categories_sql = "SELECT name 
					FROM {$wpdb->term_relationships} tr
					JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id 
					JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
					WHERE tt.taxonomy='category' 
					AND tr.object_id = {$post->ID}";
				$categories = $wpdb->get_col($categories_sql);
				$categories_comma = '';
				foreach ($categories as $category) {
					$json .= $categories_comma.'"'.addslashes($category).'"';
					$categories_comma = ',';
				}
				$json .= ']}';
				$comma = ',';
			}
		}
		$json .= ']}';

		return $json;
	}

	public static function map($option_args = null)
	{
		return geo_mashup_map(wp_parse_args($option_args));
	}

	public static function full_post($option_args = null)
	{
		return '<div id="geoPost"></div>';
	}

	public static function category_name($option_args = null)
	{
		$category_name = '';
		if (is_string($option_args)) {
			$option_args = wp_parse_args($option_args);
		}
		if (is_page() && isset($_SERVER['QUERY_STRING'])) {
			$option_args = $option_args + self::explode_assoc('=','&amp;',$_SERVER['QUERY_STRING']);
		}
		if (isset($option_args['map_cat'])) {
			$category_name = get_cat_name($option_args['map_cat']);
		}
		return $category_name;
	}

	public static function category_legend($option_args = null)
	{
		return '<div id="geoMashupCategoryLegend"></div>';
	}

	public static function list_cats($content, $category = null) {
		global $wpdb;
		if ($category) {
			$query = "SELECT count(*) FROM {$wpdb->posts} p 
				INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID 
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID 
				WHERE tt.term_id={$category->cat_ID} 
				AND pm.meta_key='_geo_location'
				AND length(pm.meta_value)>1
				AND p.post_status='publish'";
			$count = $wpdb->get_var($query);
			if ($count) {
				// Add map link only if there are geo-located posts to see
				$link = '';
				$url = get_page_link(self::$options['mashup_page']);
				if (strstr($url,'?')) {
					$url .= '&amp;';
				} else {
					$url .= '?';
				}
				$link = '<a href="'.$url.'map_cat='.$category->cat_ID.'&amp;zoom='.self::$options['category_zoom'].
					'" title="'.self::$options['category_link_text'].'">';
				return $content.'</a>'.self::$options['category_link_separator'].$link.self::$options['category_link_text'];
			}
		}
		return $content;
	}

	public static function admin_menu() {
		if (function_exists('add_options_page')) {
			add_options_page(__('Geo Mashup Options','GeoMashup'), __('Geo Mashup','GeoMashup'), 8, __FILE__, array('GeoMashup', 'options_page'));
		}
		if (function_exists('add_meta_box')) {
			add_meta_box('geo_mashup_admin_edit',__('Location','GeoMashup'),array('GeoMashup','edit_form_advanced'),'post','advanced');
			add_meta_box('geo_mashup_admin_edit',__('Location','GeoMashup'),array('GeoMashup','edit_form_advanced'),'page','advanced');
		}
	}

	public static function options_page() {
		include_once(dirname(__FILE__) . '/options.php');
		geo_mashup_options_page();
	}

	/**
	 * A tag to insert the the onload call needed by IE 
	 * (and works in Firefox) in the body tag to load the 
	 * Google map. DEPRECATED
	 */
	public static function body_attribute() {
	}

	/**
	 * A tag to insert a link to a post on the mashup.
	 */
	public static function post_link($option_args = null) {
		global $post;
		$options = array('text' => __('Show on map','GeoMashup'),
										 'display' => false,
										 'show_icon' => true);
		if (is_array($option_args)) {
			$options = $option_args + $options;
		} if (is_string($option_args)) {
			$options = wp_parse_args($option_args, $options);
		}
		$coords = self::post_coordinates();
		$lat = $coords['lat'];
		$lng = $coords['lng'];
		if ($lat && $lng) {
			$icon = '';
			if ($options['show_icon'] && strcmp($options['show_icon'],'false') != 0) {
				$icon = '<img src="'.get_bloginfo('wpurl') .
					'/wp-content/plugins/geo-mashup/images/geotag_16.png" alt="'.__('Geotag Icon','GeoMashup').'"/>';
			}
			$link = '';
			$url = get_page_link(self::$options['mashup_page']);
			if (strstr($url,'?')) {
				$url .= '&amp;';
			} else {
				$url .= '?';
			}
			$link = '<a class="geo_mashup_link" href="'.$url.htmlentities("lat=$lat&lng=$lng&openPostId={$post->ID}").'">'.$icon.' '.$options['text'].'</a>';
			if ($options['display']) {
				echo $link;
				return true;
			} else {
				return $link;
			}
		}
	}

	/**
	* A better name for the post_link tag.
	*/
	public static function show_on_map_link($option_args = null) {
		return self::post_link($option_args);
	}

	/**
	 * List all located posts.
	 */
	public static function list_located_posts($option_args = null)
	{
		global $wpdb;
		$query = "SELECT * 
			FROM {$wpdb->posts} JOIN
				{$wpdb->postmeta} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
			WHERE meta_key='_geo_location' AND
				length(meta_value) > 1";
		if (self::$options['show_future'] == 'false') {
			$query .= ' AND post_status=\'publish\'';
		} else if (self::$options['show_future'] == 'only') {
			$query .= ' AND post_status=\'future\'';
		} else {
			$query .= ' AND post_status IN (\'publish\',\'future\')';
		}

		$query .= ' ORDER BY post_date DESC';
		$list_html = '<ul id="geo_mashup_located_post_list">';
		$posts = $wpdb->get_results($query);
		if ($posts)
		{
			foreach ($posts as $post) {
				$list_html .= '<li><a href="'.get_permalink($post->ID).'">'.
					$post->post_title."</a></li>\n";
			}
		}
		$list_html .= '</ul>';
		return $list_html;
	}

	/**
	* Fetch post coordinates.
	*/
	public static function post_coordinates($places = 10) {
		global $post;

		$meta = trim(get_post_meta($post->ID, '_geo_location', true));
		$coordinates = array();
		if (strlen($meta)>1) {
			list($lat, $lng) = split(',', $meta);
			$lat_dec_pos = strpos($lat,'.');
			if ($lat_dec_pos !== false) {
				$lat = substr($lat, 0, $lat_dec_pos+$places+1);
			}
			$lng_dec_pos = strpos($lng,'.');
			if ($lng_dec_pos !== false) {
				$lng = substr($lng, 0, $lng_dec_pos+$places+1);
			}
			$coordinates['lat'] = $lat;
			$coordinates['lng'] = $lng;
		}
		return $coordinates;
	}

	/**
	 * Emit GeoRSS namespace
	 */
	public static function rss_ns() {
		echo 'xmlns:georss="http://www.georss.org/georss" ';
	}

	/**
	* Emit GeoRSS tags.
	*/
	public static function rss_item() {
		global $wp_query;

		// Using Simple GeoRSS for now
		$coordinates = trim(get_post_meta($wp_query->post->ID, '_geo_location', true));
		if (strlen($coordinates) > 1) {
			$coordinates = str_replace(',',' ',$coordinates);
			echo '<georss:point>' . $coordinates . '</georss:point>';
		}
	}
} // class GeoMashup

GeoMashup::load();

?>

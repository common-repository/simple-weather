<?php
/**
 * Plugin Name: Simple Weather
 * Plugin URI: http://code.google.com/p/wp-simple-weather/
 * Description: Shows current weather for your specified location. Makes use of "secret" Google Weather API. Uses cURL, so works even on hosts with security restricions, like url-disabled file_get_contents.
 * Version: 0.39
 * Author: Nordvind
 * Author URI: http://www.recon-by-fire.eu
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

 add_action('wp_head', 'weather_css');
 
 function weather_css(){
	echo '<style type="text/css">
	#loc-name{
	font-weight:bold;
	font-size:16pt;
	}
	#s-weather{
	background:#FFF;
	}
	#s-weather-data{
	width:150px;
	margin:0 auto;
	}
	#weather-text-data{
	margin-left:15px;
	color:#101010;
	}
	#curr-t{
	font-size:12pt;
	padding-left:5px;
	}
	</style>';
}
 
 add_action( 'widgets_init', 'load_sweather' );

 function load_sweather(){
	register_widget('simple_weather');
 }
 
 /* Core functions and classes */

class g_weather{
	private $w_xml;
	public function get_weather($cnt){
		libxml_use_internal_errors(true);
		$this->w_xml = new domDocument();
		$this->w_xml->loadXML($cnt);
		$test = $this->w_xml->getElementsByTagName('problem_cause');
		if ($test->length !== 0) return false;
	}
	public function get_loc(){
		if (empty($this->w_xml)) return -1;
		$cname = $this->w_xml->getElementsByTagName('city')->item(0)->getAttribute('data');	
		return $cname;
	}
	public function get_condition(){
		if (empty($this->w_xml)) return -1;
		$cnd = $this->w_xml->getElementsByTagName('condition')->item(0)->getAttribute('data');
		return $cnd;
	}
	public function get_icon(){
		if (empty($this->w_xml)) return -1;
		$icon = $this->w_xml->getElementsByTagName('icon')->item(0)->getAttribute('data');
		if (strpos($icon,'chance_of_snow') !== false) $path = 'cos.jpg';
		elseif (strpos($icon,'snow') !== false || strpos($icon,'flurries') !== false) $path = 'snow.jpg';
		elseif (strpos($icon,'mostly_sunny') !== false) $path = 'm_sunny.jpg';
		elseif (strpos($icon,'sunny') !== false) $path = 'sunny.jpg';
		elseif (strpos($icon,'mostly_cloudy') !== false || strpos($icon,'partly_cloudy') !== false) $path = 'm_cloudy.jpg';
		elseif (strpos($icon,'cloudy') !== false) $path = 'cloudy.jpg';
		elseif (strpos($icon,'haze') !== false) $path = 'haze.jpg';
		elseif (strpos($icon,'storm') !== false) $path = 'storm.jpg';
		elseif (strpos($icon, 'rain') !== false) $path = 'rain.jpg';
		elseif (strpos($icon, 'showers') !== false || strpos($icon, 'chance_of_rain') !== false) $path = 'cor.jpg';
		else $path = 'na.jpg';
		return $path;
	}
	public function get_hum(){
		if (empty($this->w_xml)) return -1;
		$hmd = $this->w_xml->getElementsByTagName('humidity')->item(0)->getAttribute('data');
		return $hmd;
	}
	public function get_temper($tm){
		if (empty($this->w_xml)) return -1;
		if ($tm == 'C') $t = $this->w_xml->getElementsByTagName('temp_c')->item(0)->getAttribute('data');
		elseif ($tm == 'F') $t = $this->w_xml->getElementsByTagName('temp_f')->item(0)->getAttribute('data');
		else $t = 'ivalid parameter';
		return $t;
	}
	public function get_wind(){
		if (empty($this->w_xml)) return -1;
		$wind = $this->w_xml->getElementsByTagName('wind_condition')->item(0)->getAttribute('data');
		return $wind;
	}
}

function get_url_cnt($url){
        $crl = curl_init();
        $timeout = 5;
        curl_setopt ($crl, CURLOPT_URL,$url);
        curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        $ret = curl_exec($crl);
        curl_close($crl);
        return $ret;
 }
 /* Core end */
 
 class simple_weather extends WP_Widget{
		public $url = 'http://www.meteo.lv/public/index.html';
		function simple_weather() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'weather-wdg', 'description' => 'Weather widget.' );

		/* Widget control settings. */
		$control_ops = array( 'width' => 200, 'height' => 300, 'id_base' => 'simple_weather' );

		/* Create the widget. */
		$this->WP_Widget( 'simple_weather', 'Simple weather', $widget_ops, $control_ops );
	}
	function widget($args,$instance){
		$loc = $instance['location'];
		$tm = $instance['measure'];
		if (empty($loc)) $loc = 'Riga';
		
		echo '<div id="s-weather"><div id="s-weather-data">';
		
		$w_url = 'http://www.google.com/ig/api?weather='.$loc;
		$g = new g_weather();
		$wg = get_url_cnt($w_url);
		$x = $g->get_weather($wg);
		if ($x !== false){
		echo '<img src="'.get_bloginfo('wpurl').'/wp-content/plugins/simple-weather/img/'.$g->get_icon().'" alt="" />';
		echo '<div id="weather-text-data">';
		echo '<span id="loc-name">'.$g->get_loc().'</span>';
		echo '<span id="curr-t">'.$g->get_temper($tm).$tm.'</span><br />';
		echo '<p>'.$g->get_condition().'</p>';
		echo '<p id="w-humid">'.$g->get_hum().'</p>';
		echo '<p id="w-wind">'.$g->get_wind().'</p>';
		echo '</div>';
		}
		else{
		echo '<img src="'.get_bloginfo('wpurl').'/wp-content/plugins/simple-weather/img/na.jpg" alt="" /><br />';
		echo 'Error getting data, try another location';
		}
		echo '</div></div>';
	}
	function update($new_instance,$old_instance){
	$instance = $old_instance;
	$instance['location'] = strip_tags( $new_instance['location'] );
	$instance['measure'] = $new_instance['measure'];
	return $instance;
	}
	function form($instance){
	$def = array('measure' => 'C','location' => 'Riga');
	$instance = wp_parse_args((array)$instance,$def);
	?>
	<p>
	<label for="<?php echo $this->get_field_id('location'); ?>">Location:</label>
	<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php 
	echo $this->get_field_name( 'location' ); ?>" value="<?php echo $instance['location']; ?>" 
	style="width:100%;" />
	</p>
	<p style="color:#606060;">You can fill in location field like this:<br />
	<span>[city]</span><br />
	<span>or</span><br />
	<span>[city],[country]</span>
	</p>
	<p style="color:#FF0000;">Warning: no whitespace allowed in location field</p>
		<p>
	<label for="<?php echo $this->get_field_id('measure'); ?>">Temperature measure:</label><br />
	<input id="<?php echo $this->get_field_id( 'title' ); ?>" type="radio" name="<?php 
	echo $this->get_field_name( 'measure' ); ?>" value="C" <?php if($instance['measure']=='C') echo 'checked="checked"' ?> /> Celsius<br />
	<input type="radio" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php 
	echo $this->get_field_name( 'measure' ); ?>" value="F" <?php if($instance['measure']=='F') echo 'checked="checked"' ?> /> Fahrenheit
	</p>
	<?php
	}
 }
 ?>
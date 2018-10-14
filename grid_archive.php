<?php
/*
Plugin Name: Grid Archive
Text Domain: rt-grid-archive
Plugin URI: http://roytang.net
Description: Display archive links in a grid format of months and years.
Version: 0.1.0
Author: Roy Tang
Author URI: https://www.roytang.net/
License: GPL2
*/

/**
 * Class Grid_Archive
 * @package WP_plugin
 * @category WordPress Plugins
 */

class Grid_Archive {

	/**
	 * Plugin vars
	 * @var string
	 */
	var $plugin_name = 'Grid Archive';
	var $version = '0.1.0';
	var $domain = 'rt-grid-archive';

	/**
	 * PHP5 constructor
	 */
	function __construct() {
		// add shortcode
		add_shortcode('grid_archives', array($this, 'shortcode'));

	}

	/**
	* Advaned wp_get_archives
	* adds the ability to order alpha and postbypost Archives
	* adds archive by decade
	**/
	static function wp_get_archives() {
		global $wpdb, $wp_locale;
		$order = 'ASC';

		$sql_where = $wpdb->prepare( "WHERE post_type = %s AND post_status = 'publish'", 'post' );

		$output = '';

		$last_changed = wp_cache_get_last_changed( 'posts' );

		$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $sql_where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date $order";
		$key   = md5( $query );
		$key   = "wp_get_archives_advanced:$key:$last_changed";
		if ( ! $results = wp_cache_get( $key, 'posts' ) ) {
			$results = $wpdb->get_results( $query );
			wp_cache_set( $key, $results, 'posts' );
		}
		if ( $results ) {
			$current_year = '';
			$current_month = 1;
			$after = $r['after'];
			$output .= "<tr><th></th>";
			// header row
			for($i = 1; $i<=12; $i++) {
				$text = sprintf( __( '%1$s' ), $wp_locale->get_month_abbrev($wp_locale->get_month( $i )));
				$output .= "<th>" . $text . "</th>";
			}		
			foreach ( (array) $results as $result ) {
				if ($current_year != $result->year) {
					// end the previous row
					$output .= "</tr>";
					// start a new row
					$current_year = $result->year;
					$current_month = 1;
					$output .= "<tr><th>$current_year</th>";
				}
				while ($current_month < $result->month) {
					// generate empty cells until we get to the correct month
					$output .= "<td></td>";
					$current_month++;
				}
				$url = get_month_link( $result->year, $result->month );
				if ( 'post' !== $r['post_type'] ) {
					$url = add_query_arg( 'post_type', $r['post_type'], $url );
				}
				/* translators: 1: month name, 2: 4-digit year */
				$text = $result->posts;
				$output .= "<td class='arc'>";
				$output .= get_archives_link( $url, $text, 'custom', $r['before'], $r['after'] );
				$output .= "</td>";
				$current_month++;
			}
			$output .= "</tr>";
		}

		if ( $r['echo'] ) {
			echo $output;
		} else {
			return $output;
		}
	}

	/**
	 * Callback shortcode
	 */
	function shortcode($atts, $content = null){
		$tag = 'table';
		$arc = '<'.$tag.' class="grid_archive">';
		$arc .= Grid_Archive::wp_get_archives();
		$arc .= '</'.$tag.'>';
		return $arc;
	}

} // end class WP_Plugin_Template

/**
 * Create instance
 */
$Grid_Archive = new Grid_Archive;

function enqueue_related_pages_scripts_and_styles(){
	wp_enqueue_style('related-styles', plugins_url('/css/grid_archive.css', __FILE__));
}
add_action('wp_enqueue_scripts','enqueue_related_pages_scripts_and_styles');

?>

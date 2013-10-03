<?php
/*
 * Plugin Name: Custom Meta Boxes for Taxonomies
 * Plugin URI: http://wordpress.lowtone.nl/wp-taxonomies-meta_boxes
 * Plugin Type: lib
 * Description: Adds an option to use custom meta boxes for term selection.
 * Version: 1.0
 * Author: Lowtone <info@lowtone.nl>
 * Author URI: http://lowtone.nl
 * License: http://wordpress.lowtone.nl/license
 */

namespace lowtone\wp\taxonomies\meta_boxes {

	use lowtone\content\packages\Package;

	// Includes
	
	if (!include_once WP_PLUGIN_DIR . "/lowtone-content/lowtone-content.php") 
		return trigger_error("Lowtone Content plugin is required", E_USER_ERROR) && false;

	$__i = Package::init(array(
			Package::INIT_PACKAGES => array("lowtone", "lowtone\\wp"),
			Package::INIT_MERGED_PATH => __NAMESPACE__,
			Package::INIT_SUCCESS => function() {

				add_action("add_meta_boxes", function($postType, $post) {
						$taxonomies = array_filter(get_object_taxonomies($post, "objects"), function($taxonomy) {
								return $taxonomy->show_ui && isset($taxonomy->meta_box);
							});

						global $wp_meta_boxes;

						$screen = get_current_screen();

						foreach ($taxonomies as $name => $taxonomy) {
							if (!($metaBox = MetaBoxes::get($taxonomy->meta_box)))
								continue;

							$id = $taxonomy->hierarchical ? $name . "div" : "tagsdiv-" . $name;

							if (!isset($wp_meta_boxes[$screen->id]["side"]["core"][$id]))
								continue;

							$replace = array(
									"callback" => $metaBox["callback"]
								);

							if (isset($metaBox["title"])) {
								$title = $metaBox["title"];

								if (is_callable($title))
									$title = call_user_func($title, $wp_meta_boxes[$screen->id]["side"]["core"][$id]["title"]);

								$replace["title"] = $title;
							}

							$wp_meta_boxes[$screen->id]["side"]["core"][$id] = array_merge($wp_meta_boxes[$screen->id]["side"]["core"][$id], $replace);
						}
					}, 9999, 2);

			}
		));

	/**
	 * A handler for registered meta boxes.
	 */
	abstract class MetaBoxes {

		private static $metaBoxes = array();

		public static function register($id, $options) {
			if (is_callable($options))
				$options = array("callback" => $options);

			$options = array_merge(array("title" => NULL, "callback" => NULL), (array) $options);

			if (!is_callable($options["callback"]))
				throw new \ErrorException("A meta box requires a valid callback");

			return (self::$metaBoxes[$id] = $options);
		}

		public static function get($id) {
			return isset(self::$metaBoxes[$id]) ? self::$metaBoxes[$id] : NULL;
		}

	}

	function register($id, $options) {
		return MetaBoxes::register($id, $options);
	}
	
}
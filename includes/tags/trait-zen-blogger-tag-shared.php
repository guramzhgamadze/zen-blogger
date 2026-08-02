<?php
/**
 * Dynamic tag: Shared
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shared plumbing for every Zen Blogger tag.
 *
 * A trait, not a base class: the image tag must extend Data_Tag while the text
 * tags extend Tag, and those two parents are incompatible.
 */
trait Zen_Blogger_Tag_Shared {

	/**
	 * Group this tag appears under.
	 *
	 * @return string[]
	 */
	public function get_group() {
		return array( Zen_Blogger_Tags::GROUP );
	}

	/**
	 * The post the tag should read.
	 *
	 * Inside a Zen Blogger loop this is the current loop post, because the widget
	 * renders the template while that post is set up.
	 *
	 * @return int
	 */
	protected function target_id() {
		return (int) get_the_ID();
	}
}

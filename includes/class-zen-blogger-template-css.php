<?php
/**
 * Per-card dynamic CSS for template cards.
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Elementor's dynamic CSS, re-scoped so it can differ per post.
 *
 * A control whose value is a dynamic tag AND whose output is CSS — a container
 * background image bound to the featured image is the usual case — is left out
 * of a document's cached stylesheet on purpose. Elementor supplies it separately
 * through Dynamic_CSS, resolved against whichever post is current.
 *
 * That works for a page, which is one post. It cannot work for a loop:
 *
 *   - Base::enqueue() records the file handle in a static and returns early
 *     ever after, so the dynamic CSS is emitted at most once per request;
 *   - the selector it emits is `.elementor-<template> .elementor-element-<id>`,
 *     which is the same for every card, so even if it were emitted nine times
 *     the last one would win for all nine.
 *
 * Prefixing every selector with a per-card class fixes both: each card gets its
 * own rule, at one class higher specificity than the shared stylesheet it is
 * overriding. This is the same shape as the `.e-loop-item-<id>` scoping
 * Elementor Pro's loop grid uses.
 */
final class Zen_Blogger_Template_CSS extends \Elementor\Core\DynamicTags\Dynamic_CSS {

	/**
	 * Selector every rule is nested under.
	 *
	 * @var string
	 */
	private $zenblog_scope = '';

	/**
	 * Set the per-card scope selector.
	 *
	 * @param string $selector Leading selector, e.g. ".zenblog-tpl-42".
	 * @return void
	 */
	public function zenblog_set_scope( $selector ) {
		$this->zenblog_scope = (string) $selector;
	}

	/**
	 * Prefix each element's selector with the card scope.
	 *
	 * This is what fills {{WRAPPER}}, so it covers every selector the controls
	 * declare, not just the ones this plugin knows about.
	 *
	 * @param \Elementor\Element_Base $element Element being styled.
	 * @return string
	 */
	public function get_element_unique_selector( \Elementor\Element_Base $element ) {
		$selector = parent::get_element_unique_selector( $element );

		if ( '' === $this->zenblog_scope ) {
			return $selector;
		}

		return $this->zenblog_scope . ' ' . $selector;
	}
}

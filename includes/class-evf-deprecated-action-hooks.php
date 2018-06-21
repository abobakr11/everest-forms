<?php
/**
 * Deprecated action hooks
 *
 * @package EverestForms\Abstracts
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles deprecation notices and triggering of legacy action hooks.
 */
class EVF_Deprecated_Action_Hooks extends EVF_Deprecated_Hooks {

	/**
	 * Array of deprecated hooks we need to handle. Format of 'new' => 'old'.
	 *
	 * @var array
	 */
	protected $deprecated_hooks = array(
		'everest_forms_builder_page_init' => array(
			'everest_forms_page_init',
			'everest_forms_builder_init',
		),
		'admin_enqueue_scripts'           => array(
			'everest_forms_page_init',
			'everest_forms_builder_scripts',
			'everest_forms_builder_enqueues_before',
		),
		'everest_forms_builder_tabs'      => array(
			'everest_forms_builder_panel_buttons',
		),
		'everest_forms_builder_output'    => array(
			'everest_forms_builder_panels',
		)
	);

	/**
	 * Array of versions on each hook has been deprecated.
	 *
	 * @var array
	 */
	protected $deprecated_version = array(
		'everest_forms_page_init'               => '1.2.0',
		'everest_forms_builder_init'            => '1.2.0',
		'everest_forms_builder_scripts'         => '1.2.0',
		'everest_forms_builder_enqueues_before' => '1.2.0',
		'everest_forms_builder_panel_buttons'   => '1.2.0',
		'everest_forms_builder_panels'          => '1.2.0',
	);

	/**
	 * Hook into the new hook so we can handle deprecated hooks once fired.
	 *
	 * @param string $hook_name Hook name.
	 */
	public function hook_in( $hook_name ) {
		add_action( $hook_name, array( $this, 'maybe_handle_deprecated_hook' ), -1000, 8 );
	}

	/**
	 * If the old hook is in-use, trigger it.
	 *
	 * @param  string $new_hook          New hook name.
	 * @param  string $old_hook          Old hook name.
	 * @param  array  $new_callback_args New callback args.
	 * @param  mixed  $return_value      Returned value.
	 * @return mixed
	 */
	public function handle_deprecated_hook( $new_hook, $old_hook, $new_callback_args, $return_value ) {
		if ( has_action( $old_hook ) ) {
			$this->display_notice( $old_hook, $new_hook );
			$return_value = $this->trigger_hook( $old_hook, $new_callback_args );
		}
		return $return_value;
	}

	/**
	 * Fire off a legacy hook with it's args.
	 *
	 * @param  string $old_hook          Old hook name.
	 * @param  array  $new_callback_args New callback args.
	 * @return mixed
	 */
	protected function trigger_hook( $old_hook, $new_callback_args ) {
		do_action_ref_array( $old_hook, $new_callback_args );
	}
}
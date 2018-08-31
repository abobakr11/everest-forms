<?php
/**
 * EverestForms Admin Entries Class
 *
 * @package EverestForms\Admin
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Entries class.
 */
class EVF_Admin_Entries {

	/**
	 * Initialize the entries admin actions.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
	}

	/**
	 * Check if is entries page.
	 *
	 * @return bool
	 */
	private function is_entries_page() {
		return isset( $_GET['page'] ) && 'evf-entries' === $_GET['page']; // WPCS: input var okay, CSRF ok.
	}

	/**
	 * Page output.
	 */
	public static function page_output() {
		if ( isset( $_GET['view-entry'] ) ) {
			$form_id  = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // WPCS: input var okay, CSRF ok.
			$entry_id = isset( $_GET['view-entry'] ) ? absint( $_GET['view-entry'] ) : 0; // WPCS: input var okay, CSRF ok.
			$entry    = evf_get_entry( $entry_id );

			include 'views/html-admin-page-entries-view.php';
		} else {
			self::table_list_output();
		}
	}

	/**
	 * Table list output.
	 */
	private static function table_list_output() {
		global $entries_table_list;

		// Get the entries count.
		$count = count( evf_get_entries_ids( $entries_table_list->form_id ) );

		$entries_table_list->process_bulk_action();
		$entries_table_list->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Entries', 'everest-forms' ); ?></h1>
			<hr class="wp-header-end">

			<?php settings_errors(); ?>

			<?php if ( 0 < $count ) : ?>
				<form id="entries-list" method="post">
					<input type="hidden" name="page" value="evf-entries" />
					<?php
						$entries_table_list->views();
						$entries_table_list->search_box( __( 'Search Entries', 'everest-forms' ), 'everest-forms' );
						$entries_table_list->display();
					?>
				</form>
			<?php else : ?>
				<div class="everest-forms-BlankState">
					<svg aria-hidden="true" class="octicon octicon-graph everest-forms-BlankState-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M16 14v1H0V0h1v14h15zM5 13H3V8h2v5zm4 0H7V3h2v10zm4 0h-2V6h2v7z"/></svg>
					<h2 class="everest-forms-BlankState-message"><?php esc_html_e( 'Whoops, it appears you do not have any form entries yet.', 'everest-forms' ); ?></h2>
					<?php if ( ! empty( $entries_table_list->forms ) ) : ?>
						<form id="entries-list" method="get">
							<input type="hidden" name="page" value="evf-entries" />
							<?php
								ob_start();
								$entries_table_list->forms_dropdown();
								$output = ob_get_clean();

								if ( ! empty( $output ) ) {
									echo $output;
									submit_button( __( 'Filter', 'everest-forms' ), '', '', false, array( 'id' => 'post-query-submit' ) );
								}
							?>
						</form>
					<?php else : ?>
						<a class="everest-forms-BlankState-cta button-primary button" target="_blank" href="https://docs.wpeverest.com/docs/everest-forms/entry-management/?utm_source=blankslate&utm_medium=entry&utm_content=entriesdoc&utm_campaign=everestformplugin"><?php esc_html_e( 'Learn more about entries', 'everest-forms' ); ?></a>
						<a class="everest-forms-BlankState-cta button" href="<?php echo esc_url( admin_url( 'admin.php?page=evf-builder&create-form=1' ) ); ?>"><?php esc_html_e( 'Create your first form!', 'everest-forms' ); ?></a>
					<?php endif; ?>
					<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions, .wrap .subsubsub { display: none; }</style>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Entries admin actions.
	 */
	public function actions() {
		if ( $this->is_entries_page() ) {
			// Trash entry.
			if ( isset( $_GET['trash'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->trash_entry();
			}

			// Untrash entry.
			if ( isset( $_GET['untrash'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->untrash_entry();
			}

			// Delete entry.
			if ( isset( $_GET['delete'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->delete_entry();
			}

			// Empty Trash.
			if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->empty_trash();
			}

			// Export CSV.
			if ( isset( $_REQUEST['export_action'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->export_csv();
			}
		}
	}

	/**
	 * Trash entry.
	 */
	private function trash_entry() {
		check_admin_referer( 'trash-entry' );

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : '';

		if ( isset( $_GET['trash'] ) ) { // WPCS: input var okay, CSRF ok.
			$entry_id = absint( $_GET['trash'] ); // WPCS: input var okay, CSRF ok.

			if ( $entry_id ) {
				self::update_status( $entry_id, 'trash' );
			}
		}

		wp_redirect( esc_url_raw( add_query_arg( array( 'form_id' => $form_id, 'trashed' => 1 ), admin_url( 'admin.php?page=evf-entries' ) ) ) );
		exit();
	}

	/**
	 * Trash entry.
	 */
	private function untrash_entry() {
		check_admin_referer( 'untrash-entry' );

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : '';

		if ( isset( $_GET['untrash'] ) ) { // WPCS: input var okay, CSRF ok.
			$entry_id = absint( $_GET['untrash'] ); // WPCS: input var okay, CSRF ok.

			if ( $entry_id ) {
				self::update_status( $entry_id, 'publish' );
			}
		}

		wp_redirect( esc_url_raw( add_query_arg( array( 'form_id' => $form_id, 'untrashed' => 1 ), admin_url( 'admin.php?page=evf-entries' ) ) ) );
		exit();
	}

	/**
	 * Delete entry.
	 */
	private function delete_entry() {
		check_admin_referer( 'delete-entry' );

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : '';

		if ( isset( $_GET['delete'] ) ) { // WPCS: input var okay, CSRF ok.
			$entry_id = absint( $_GET['delete'] ); // WPCS: input var okay, CSRF ok.

			if ( $entry_id ) {
				self::remove_entry( $entry_id );
			}
		}

		wp_redirect( esc_url_raw( add_query_arg( array( 'form_id' => $form_id, 'deleted' => 1 ), admin_url( 'admin.php?page=evf-entries' ) ) ) );
		exit();
	}

	/**
	 * Empty Trash.
	 */
	public function empty_trash() {
		global $wpdb;

		check_admin_referer( 'bulk-entries' );

		if ( isset( $_GET['form_id'] ) ) { // WPCS: input var okay, CSRF ok.
			$form_id = absint( $_GET['form_id'] ); // WPCS: input var okay, CSRF ok.

			if ( $form_id ) {
				$count     = 0;
				$results   = $wpdb->get_results( $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}evf_entries WHERE `status` = 'trash' AND form_id = %d", $form_id ) ); // WPCS: cache ok, DB call ok.
				$entry_ids = array_map( 'intval', wp_list_pluck( $results, 'entry_id' ) );

				foreach ( $entry_ids as $entry_id ) {
					if ( self::remove_entry( $entry_id ) ) {
						$count ++;
					}
				}

				add_settings_error(
					'empty_trash',
					'empty_trash',
					/* translators: %d: number of entries */
					sprintf( _n( '%d entry permanently deleted.', '%d entries permanently deleted.', $count, 'everest-forms' ), $count ),
					'updated'
				);
			}
		}
	}

	/**
	 * Remove entry.
	 *
	 * @param  int $entry_id Entry ID.
	 * @return bool
	 */
	public static function remove_entry( $entry_id ) {
		global $wpdb;

		$delete = $wpdb->delete( $wpdb->prefix . 'evf_entries', array( 'entry_id' => $entry_id ), array( '%d' ) );

		if ( apply_filters( 'everest_forms_delete_entrymeta', true ) ) {
			$wpdb->delete( $wpdb->prefix . 'evf_entrymeta', array( 'entry_id' => $entry_id ), array( '%d' ) );
		}

		return $delete;
	}

	/**
	 * Set entry status.
	 *
	 * @param int    $entry_id Entry ID.
	 * @param string $status   Entry status.
	 */
	public static function update_status( $entry_id, $status = 'publish' ) {
		global $wpdb;

		$update = $wpdb->update(
			$wpdb->prefix . 'evf_entries',
			array( 'status' => $status ),
			array( 'entry_id' => $entry_id ),
			array( '%s' ),
			array( '%d' )
		);

		return $update;
	}

	/**
	 * Export entries in CSV format.
	 *
	 * @since 1.2.5
	 */
	private function export_csv() {
		if ( ! current_user_can( 'export' ) ) {
			return;
		}

		$form_id = isset( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : 0;

		if ( ! isset( $form_id ) ) {
			return;
		}

		$entries   = array();
		$entry_ids = evf_get_entries_ids( $form_id );

		if ( empty( $entry_ids ) ) {
			return;
		}

		$default_columns = apply_filters( 'everest_forms_entries_default_columns', array(
			'user_device'     => __( 'User Device', 'everest-forms' ),
			'user_ip_address' => __( 'User IP Address', 'everest-forms' ),
			'date_created'    => __( 'Date Created', 'everest-forms' ),
		) );

		$exclude_columns = apply_filters( 'everest_froms_entries_exclude_columns', array( 'form_id', 'user_id', 'status', 'referer', ) );
		$entry_column    = array( 'entry_id' => __( 'Entry ID', 'everest-forms' ) );
		$extra_columns   = array_merge( $entry_column, get_all_form_fields_by_form_id( $form_id ) );
		$columns         = array_merge( $extra_columns, $default_columns );

		foreach( $entry_ids as $entry_id ) {
			$entries[] = evf_get_entry( $entry_id );
		}

		$rows = array();

		foreach( $entries as $entry ) {
			$entry = (array) $entry;

			$entry['meta'] = ! empty ( $entry['meta'] ) ? $entry['meta'] : array();

			foreach( $entry['meta'] as $key => $meta ) {
				if ( is_serialized( $meta ) ) {
					$array_values = unserialize( $meta );
					$meta 		  = implode( ',', $array_values );
				}

				$entry[ $key ] = $meta;
				unset( $entry[ 'meta' ] );

				foreach( $exclude_columns as $exclude_column ) {
					unset( $entry[ $exclude_column ]);
				}
			}

			// Order the row depending on columns meta key.
			$ordered_rows = array_merge( array_fill_keys ( array_keys( $columns ), '' ), $entry );
			$rows[]       = $ordered_rows;
		}

		$form_name = strtolower( str_replace( " ", "-", get_the_title( $form_id ) ) );
		$file_name = $form_name . "-" . current_time( 'Y-m-d_H:i:s' ) . '.csv';

		// Set the CSV headers.
		$this->send_headers( $file_name );

		$handle = fopen( "php://output", 'w' );

		// Handle UTF-8 chars conversion for CSV.
		fprintf( $handle, chr(0xEF).chr(0xBB).chr(0xBF) );

		// Put the column headers.
		fputcsv( $handle, array_values( $columns ) );

		// Put the entry values.
		foreach ( $rows as $row ) {
			fputcsv( $handle, $row );
		}

		fclose( $handle );
		exit;
	}

	/**
	 * Set the export headers.
	 *
	 * @since 1.2.5
	 * @param string $file_name File name.
	 */
	private function send_headers( $file_name = '' ) {
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable(); // phpcs:ignore PHPCompatibility.PHP.NewFunctions.gc_enableFound
		}
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 ); // @codingStandardsIgnoreLine
		}
		@ini_set( 'zlib.output_compression', 'Off' ); // @codingStandardsIgnoreLine
		@ini_set( 'output_buffering', 'Off' ); // @codingStandardsIgnoreLine
		@ini_set( 'output_handler', '' ); // @codingStandardsIgnoreLine
		ignore_user_abort( true );
		evf_set_time_limit( 0 );
		evf_nocache_headers();
		header( "Content-Type: application/force-download" );
		header( "Content-Type: application/octet-stream" );
		header( "Content-Type: application/download" );
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $file_name );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
	}
}

new EVF_Admin_Entries();

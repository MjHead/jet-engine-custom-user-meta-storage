<?php
/**
 * Plugin Name: JetEngine - custom user meta storage
 * Plugin URI:  
 * Description: 
 * Version:     1.0.0
 * Author:      Crocoblock
 * Author URI:  https://crocoblock.com/
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

class Jet_Engine_Custom_User_Meta_Storage {

	public function __construct() {
		
		add_action( 'jet-engine/post-types/registered', [ $this, 'register_storage' ], 9 );
		add_action( 'jet-engine/custom-meta-tables/query/handle-object-type-query', [ $this, 'prepare_query' ] );
		add_action( 'pre_user_query', [ $this, 'handle_query' ] );
		add_filter( 'jet-engine/custom-meta-tables/storage/is-object-of-type', [ $this, 'object_of_type' ], 0, 2 );

	}

	public function register_storage() {

		if ( ! defined( 'JET_ENGINE_CUSTOM_USER_META_FIELDS' ) ) {
			return;
		}

		\Jet_Engine\CPT\Custom_Tables\Manager::instance()->register_storage(
			'user',
			'user',
			JET_ENGINE_CUSTOM_USER_META_FIELDS
		);
	}

	public function handle_query( $query ) {

		$custom_table_query = $query->get( 'custom_table_query' );

		if ( $custom_table_query ) {

			global $wpdb;

			$custom_query = new \Jet_Engine\CPT\Custom_Tables\Meta_Query( $custom_table_query['query'] );
			$custom_query->set_custom_table( $custom_table_query['table'] );
			$custom_clauses = $custom_query->get_sql( 'user', $wpdb->users, 'ID', $query );

			if ( ! empty( $custom_clauses['join'] ) ) {
				$query->query_from .= $custom_clauses['join'];
			}

			if ( ! empty( $custom_clauses['where'] ) ) {
				$query->query_where .= $custom_clauses['where'];
			}

			$query->query_fields .= ', ' . $custom_table_query['table'] . '.*';

			if ( ! empty( $custom_table_query['order'] ) ) {
				foreach( $custom_table_query['order'] as $order_by => $order ) {
					
					$query->query_orderby .= sprintf( 
						', %1$s.%2$s %3$s', 
						$custom_table_query['table'], 
						$order_by, 
						$order 
					);

					$query->query_orderby = ltrim( $clauses['orderby'], ',' );

				}
			}

		}
	}

	public function prepare_query( $query_controller ) {
		if ( 'user' === $query_controller->object_type ) {
		
			add_action( 'pre_get_users', function( $query ) use ( $query_controller ) {

				$meta_query    = $query->get( 'meta_query' );
				$meta_partials = $query_controller->exctract_meta_query_partials( $meta_query );
				$custom_order  = [];

				$query_order_by = $query->get( 'orderby' );
				$query_order    = $query->get( 'order' );

				if ( $query_order_by ) {

					if ( ! is_array( $query_order_by ) ) {
						$query_order_by = [ $query_order_by => $query_order ];
					}

					$unset_orders = [];

					foreach ( $query_order_by as $order_by => $order ) {
						if ( in_array( $order_by, [ 'meta_value_num', 'meta_value' ] ) ) {
							
							$meta_key = $query->get( 'meta_key' );
							$order    = ! empty( $order ) ? $order : 'DESC';
							$suffix   = ( 'meta_value_num' === $order_by ) ? '+0' : '';

							if ( in_array( $meta_key, $this->fields ) ) {
								$unset_orders[] = $order_by;
								$query->set( 'meta_key', null );
								$custom_order[ $meta_key . $suffix ] = $order;
							}
						}

						if ( ! empty( $meta_partials['custom_query'] ) 
							&& isset( $meta_partials['custom_query'][ $order_by ] ) 
						) {
							$clause = $meta_partials['custom_query'][ $order_by ];
							$meta_key = $clause['key'];
							$unset_orders[] = $order_by;
							$order = ! empty( $order ) ? $order : 'DESC';
							$type = $clause['type'] ?? '';
							$numeric_types = [ 'TIMESTAMP', 'NUMERIC', 'DECIMAL', 'SIGNED' ];
							$suffix = in_array( $type, $numeric_types ) ? '+0' : '';
							$custom_order[ $meta_key . $suffix ] = $order;
						}

					}

					if ( ! empty( $unset_orders ) ) {

						foreach ( $unset_orders as $order ) {
							unset( $query_order_by[ $order ] );
						}

						$query->set( 'orderby', $query_order_by );

					}

				}

				if ( ! empty( $meta_partials['custom_query'] ) || ! empty( $custom_order ) ) {

					$custom_query = $meta_partials['custom_query'] ?? [];
					
					$query->set( 'custom_table_query', [
						'table' => $query_controller->db->table(),
						'query' => $custom_query,
						'order' => $custom_order,
					] );

				}

				$query->set( 'meta_query', $meta_partials['meta_query'] );

			} );
		}
	}

	public function object_of_type( $result = false, $object_type = '' ) {

		if ( 'user' === $object_type ) {
			$result = true;
		}

		return $result;

	}

}

new Jet_Engine_Custom_User_Meta_Storage();

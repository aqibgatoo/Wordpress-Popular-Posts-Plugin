<?php
/*
Plugin Name: Popular Posts Plugin
Description: This plugin tracks the popular posts on a site based on the number of views and shows them in a widget
Author: Aqib Gatoo
Version: 1.0
Author URI: http://aqibgatoo.com
*/


//count post views

function popular_post_views( $postID ) {

	$total_key = 'views';
	$total     = get_post_meta( $postID, $total_key, true );
	if ( $total == '' ) {
		$total = 0;
		delete_post_meta( $postID, $total_key );
		add_post_meta( $postID, $total_key, '0' );
	} else {
		$total ++;
		update_post_meta( $postID, $total_key, $total );
	}

}


// Inject counter into posts

function counter_popular_posts( $post_id ) {

	if ( ! is_single() ) {
		return;
	}
	if ( ! is_user_logged_in() ) {
		if ( empty( $post_id ) ) {
			global $post;
			$post_id = $post->ID;

			popular_post_views( $post_id );
		}
	}
}

add_action( 'wp_head', 'counter_popular_posts' );

function add_views_column( $defaults ) {
	$defaults['post_views'] = 'View Count';

	return $defaults;

}

add_filter( 'manage_posts_columns', 'add_views_column' );

function display_view_count( $column_name ) {
	if ( $column_name === 'post_views' ) {
		echo (int) get_post_meta( get_the_ID(), 'views', true );
	}

}

add_action( 'manage_posts_custom_column', 'display_view_count' );


/**
 * Adds Popular posts widget.
 */
class popular_posts extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'popular_posts', // Base ID
			__( 'Popular Posts', 'text_domain' ), // Name
			array( 'description' => __( 'Shows the 5 top most popular posts', 'text_domain' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {

		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		$query_args = array(
			'post_type'           => 'post',
			'posts_per_page'      => 5,
			'meta_key'            => 'views',
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true
		);

		// The Query
		$the_query = new WP_Query( $query_args );

		// The Loop
		if ( $the_query->have_posts() ) {
			echo '<ul>';
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				echo '<li>';
				echo '<a href="' . get_post_permalink() . '" rel="bookmark">';
				echo get_the_title() . '(' . get_post_meta( get_the_ID(), 'views', true ) . ')';
				echo '</a>';
				echo '</li>';
			}
			echo '</ul>';
		} else {
			// no posts found
		}
		/* Restore original Post Data */
		wp_reset_postdata();


		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Popular Posts', 'text_domain' );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
			       name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
			       value="<?php echo esc_attr( $title ); ?>">
		</p>
	<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

} // class Foo_Widget


// register Foo_Widget widget
function register_popular_posts_widget() {
	register_widget( 'popular_posts' );
}

add_action( 'widgets_init', 'register_popular_posts_widget' );
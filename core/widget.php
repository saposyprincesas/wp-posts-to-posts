<?php

class P2P_Widget extends scbWidget {

	protected $defaults = array(
		'ctype' => false,
		'listing' => 'connected'
	);

	static function init( $file ) {
		parent::init( __CLASS__, $file, 'p2p' );
	}

	function __construct() {
		parent::__construct( 'p2p', __( 'Posts 2 Posts', P2P_TEXTDOMAIN ), array(
			'description' => __( 'A list of posts connected to the current post', P2P_TEXTDOMAIN )
		) );
	}

	function form( $instance ) {
		if ( empty( $instance ) )
			$instance = $this->defaults;

		$ctypes = array();

		foreach ( P2P_Connection_Type_Factory::get_all_instances() as $p2p_type => $ctype ) {
			if ( ! $ctype instanceof P2P_Connection_Type )
				continue;

			$ctypes[ $p2p_type ] = self::ctype_label( $ctype );
		}

		echo html( 'p', $this->input( array(
			'type' => 'select',
			'name' => 'ctype',
			'values' => $ctypes,
			'desc' => __( 'Connection type:', P2P_TEXTDOMAIN )
		), $instance ) );

		echo html( 'p',
			__( 'Connection listing:', P2P_TEXTDOMAIN ),
			'<br>',
			$this->input( array(
				'type' => 'radio',
				'name' => 'listing',
				'values' => array(
					'connected' => __( 'connected', P2P_TEXTDOMAIN ),
					'related' => __( 'related', P2P_TEXTDOMAIN )
				),
			), $instance )
		);
	}

	function widget( $args, $instance ) {
		if ( !is_singular() )
			return;

		$instance = array_merge( $this->defaults, $instance );

		$post_id = get_queried_object_id();

		$ctype = p2p_type( $instance['ctype'] );
		if ( !$ctype )
			return;

		$directed = $ctype->find_direction( $post_id );
		if ( !$directed )
			return;

		if ( 'related' == $instance['listing'] ) {
			$connected = $ctype->get_related( $post_id );
			$title = sprintf(
				__( 'Related %s', P2P_TEXTDOMAIN ),
				$directed->get_current( 'side' )->get_title()
			);
		} else {
			$connected = $directed->get_connected( $post_id );
			$title = $directed->get_current( 'title' );
		}

		if ( !$connected->have_posts() )
			return;

		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		extract( $args );

		echo $before_widget;

		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;

		p2p_list_posts( $connected );

		echo $after_widget;
	}

	private static function ctype_label( $ctype ) {
		foreach ( array( 'from', 'to' ) as $key ) {
			$$key = implode( ', ', array_map( array( __CLASS__, 'post_type_label' ), $ctype->$key ) );
		}

		if ( $ctype->indeterminate )
			$arrow = '&harr;';
		else
			$arrow = '&rarr;';

		$label = "$from $arrow $to";

		$title = $ctype->title[ 'from' ];

		if ( $title )
			$label .= " ($title)";

		return $label;
	}

	private static function post_type_label( $post_type ) {
		$cpt = get_post_type_object( $post_type );
		return $cpt ? $cpt->label : $post_type;
	}
}


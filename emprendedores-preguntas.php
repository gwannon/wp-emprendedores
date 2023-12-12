<?php

// Aids ----------------------------------------
// ------------------------------------------------
add_action( 'init', 'emprendedores_preguntas_create_post_type' );
function emprendedores_preguntas_create_post_type() {
	$labels = array(
		'name'               => __( 'Preguntas', 'wp-emprendedores' ),
		'singular_name'      => __( 'Pregunta', 'wp-emprendedores' ),
		'add_new'            => __( 'Añadir nueva', 'wp-emprendedores' ),
		'add_new_item'       => __( 'Añadir nueva pregunta', 'wp-emprendedores' ),
		'edit_item'          => __( 'Editar pregunta', 'wp-emprendedores' ),
		'new_item'           => __( 'Nueva pregunta', 'wp-emprendedores' ),
		'all_items'          => __( 'Todas las preguntas', 'wp-emprendedores' ),
		'view_item'          => __( 'Ver pregunta', 'wp-emprendedores' ),
		'search_items'       => __( 'Buscar preguntass', 'wp-emprendedores' ),
		'not_found'          => __( 'Pregunta no encontrada', 'wp-emprendedores' ),
		'not_found_in_trash' => __( 'Pregunta no encontrada en la papelera', 'wp-emprendedores' ),
		'menu_name'          => __( 'Preguntas Autodiagnóstico', 'wp-emprendedores' ),
	);
	$args = array(
		'labels'        => $labels,
		'description'   => __( 'Añadir nueva pregunta', 'wp-emprendedores' ),
		//'menu_position' => 7,
		'taxonomies' 		=> array( 'test'),
		'supports'      => array( 
      'title', 
      'editor',
      //'thumbnail', 
      'page-attributes' 
    ),
		'rewrite'	      => false,
		'query_var'	    => false,
		'has_archive' 	=> false,
		'hierarchical'	=> true,
  	'exclude_from_search' => true,
		'publicly_queryable' => false,
		'show_in_nav_menus' => false,
		'public'             => true
	);
	register_post_type( 'emprendedor-pregunta', $args );
}

//Location -------------------------
add_action( 'init', 'emprendedores_preguntas_test_create_type' );
function emprendedores_preguntas_test_create_type() {
	$labels = array(
		'name'              => __( 'Localizaciones', 'wp-emprendedores' ),
		'singular_name'     => __( 'Localización', 'wp-emprendedores' ),
		'search_items'      => __( 'Search localizaciones', 'wp-emprendedores' ),
		'all_items'         => __( 'Todas las localizaciones', 'wp-emprendedores' ),
		'parent_item'       => __( 'Pariente localización', 'wp-emprendedores' ),
		'parent_item_colon' => __( 'Pariente localización', 'wp-emprendedores' ).":",
		'edit_item'         => __( 'Editar localización', 'wp-emprendedores' ),
		'update_item'       => __( 'Actualziar localización', 'wp-emprendedores' ),
		'add_new_item'      => __( 'Añadir localización', 'wp-emprendedores' ),
		'new_item_name'     => __( 'Nueva localización', 'wp-emprendedores' ),
		'menu_name'         => __( 'Localizaciones', 'wp-emprendedores' ),
	);
	$args = array(
		'labels' 		        => $labels,
		'hierarchical' 	    => true,
		'public'		        => false,
		'query_var'		      => true,
		'show_in_nav_menus' => false,
		'has_archive'       => false,
    'rewrite'           =>  false,
    'publicly_queryable' => false
	);
  register_taxonomy( 'test', array('emprendedor-pregunta'), $args );
}

//CAMPOS personalizados ---------------------------
// ------------------------------------------------
function get_emprendedores_preguntas_custom_fields () {
	$fields = array(
		'respuesta-a' => array ('titulo' => __( 'Respuesta A', 'wp-emprendedores' ), 'tipo' => 'textarea'),
		'respuesta-b' => array ('titulo' => __( 'Respuesta B', 'wp-emprendedores' ), 'tipo' => 'textarea'),
		'respuesta-c' => array ('titulo' => __( 'Respuesta C', 'wp-emprendedores' ), 'tipo' => 'textarea')
	);
	return $fields;
}

function emprendedores_preguntas_add_custom_fields() {
  add_meta_box(
    'box_activities', // $id
    __('Respuestas', 'wp-emprendedores'), // $title 
    'wp_emprendedores_show_custom_fields', // $callback
    'emprendedor-pregunta', // $page
    'normal', // $context
    'high'); // $priority
}
add_action('add_meta_boxes', 'emprendedores_preguntas_add_custom_fields');
add_action('save_post', 'wp_emprendedores_save_custom_fields' );

//Columnas , filtros y ordenaciones ------------------------------------------------
function emprendedores_preguntas_set_custom_edit_columns($columns) {
  $columns['test'] = __( 'Sección', 'wp-emprendedores');
  unset($columns['date']);
	return $columns;
}

function emprendedores_preguntas_custom_column( $column ) {
  global $post;
  if ($column == 'test') {
    $terms = get_the_terms( $post->ID, 'test'); 
		$sorted_terms = sort_terms_hierarchically( $terms );
    $string = array();
    foreach($sorted_terms as $term) {
      $string[] = $term->name;
    }
    if(count($string) > 0) echo implode (", ", $string);
  } /*else if ($column == 'centro_estudios-emprendedor-pregunta') {
		echo get_post_meta( $post->ID, '_emprendedor-pregunta_centro_estudios', true );
  }*/
}

function emprendedores_preguntas_test_post_by_taxonomy() {
	global $typenow;
	$post_type = 'emprendedor-pregunta'; // change to your post type
	$taxonomy  = 'test'; // change to your taxonomy
	if ($typenow == $post_type) {
		$selected      = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
		$info_taxonomy = get_taxonomy($taxonomy);
		wp_dropdown_categories(array(
			'hierarchical' 		=> 1,
			'show_option_all' => __( 'Mostrar todas las secciones', 'wp-emprendedores' ),
			'taxonomy'        => $taxonomy,
			'name'            => $taxonomy,
			'orderby'         => 'name',
			'selected'        => $selected,
			'show_count'      => true,
			'hide_empty'      => true,
		));
	};
}

function emprendedores_preguntas_test_id_to_term_in_query($query) {
	global $pagenow;
	$post_type = 'emprendedor-pregunta'; // change to your post type
	$taxonomy  = 'test'; // change to your taxonomy
	$q_vars    = &$query->query_vars;
	if ( $pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $post_type && isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0 ) {
		$term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
		$q_vars[$taxonomy] = $term->slug;
	}
}

//Los hooks si estamos en el admin 
if ( is_admin() && 'edit.php' == $pagenow && isset($_GET['post_type']) && 'emprendedor-pregunta' == $_GET['post_type'] ) {
  add_filter( 'manage_edit-emprendedor-pregunta_columns', 'emprendedores_preguntas_set_custom_edit_columns' ); //Metemos columnas
  add_action( 'manage_emprendedor-pregunta_posts_custom_column' , 'emprendedores_preguntas_custom_column', 'category' ); //Metemos columnas
  
  add_action( 'restrict_manage_posts', 'emprendedores_preguntas_test_post_by_taxonomy' ); //Añadimos filtro sección
  add_filter( 'parse_query', 'emprendedores_preguntas_test_id_to_term_in_query' ); //Añadimos filtro sección
  
  add_filter( 'months_dropdown_results', '__return_empty_array' ); //Quitamos el filtro de fechas en el admin
}
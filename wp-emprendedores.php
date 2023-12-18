<?php

/**
 * Plugin Name: Autodiagnóstico en competencias emprendedoras
 * Description: Shortcode para montar un formulario de autodiagnóstico en competencias emprendedoras [wp-emprendedores]
 * Version:     1.0
 * Author:      jorge@enutt.net
 * Author URI:  https://enutt.net/
 * License:     GNU General Public License v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-emprendedores
 *
 * PHP 8.2
 * WordPress 6.4.2
 */

/* ----------- Multi-idioma ------------------ */
function wp_emprendedores_plugins_loaded() {
	load_plugin_textdomain('wp-emprendedores', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
}
add_action('plugins_loaded', 'wp_emprendedores_plugins_loaded', 0 );

/* ----------- Includes ------------------ */
include_once(plugin_dir_path(__FILE__).'custom_posts.php');
include_once(plugin_dir_path(__FILE__).'emprendedores-preguntas.php');
include_once(plugin_dir_path(__FILE__).'admin.php');

/* ----------- Shortcode ------------------ */
function wp_emprendedores_shortcode($params = array(), $content = null) {
  global $post;
  ob_start(); 
  $control = 0;
	$currentstep = 0;
	$responses = [];

  if(defined('ICL_LANGUAGE_CODE')) $current_lang = ICL_LANGUAGE_CODE;
  else $current_lang = get_bloginfo("language"); ?>
  <div id="emprendedores-preguntas">
		<?php if(isset($params['titulo'])) { ?>
			<h2>
				<?php echo apply_filters("the_title", $params['titulo']); ?>
			</h2>
		<?php } ?>
		<?php if(isset($_POST['enviar'])) {
			if(isset($_POST['responses'])) $responses = json_decode(stripslashes($_POST['responses']), true);
			if(isset($_POST['preguntas'])) foreach($_POST['preguntas'] as $id_pregunta => $pregunta) $responses[$id_pregunta] = $pregunta;
			$currentstep = $_POST['nextstep'];
			if($currentstep == 0) {
				$control = 1;
				?><h4 class="emprendedores-preguntas-mensaje"><?php _e("Eskerrik asko! Gracias por completar el formulario.", "wp-emprendedores"); ?></h4><?php
				
				//Guardar datos en CSV
				$f=fopen(__DIR__."/csv/autodiagnostico.csv", "a+");
				$csv[] = date("Y-m-d H:i:s");
				$csv[] = $current_lang;
				foreach($responses as $response) $csv[] = $response;
				fputcsv($f, $csv);
				fclose($f);
				
				//Enviamos email de aviso a los admin
				$headers = array('Content-Type: text/html; charset=UTF-8');
				$emails = explode(",", get_option("_wp_emprendedores_emails"));
				foreach($emails as $email) {
					wp_mail(chop($email), 
						"Aviso de cuestionario de \"Autodiagnóstico en competencias emprendedoras\" rellenado", 
						"<b>Cuestionario de \"Autodiagnóstico en competencias emprendedoras\" rellenado</b><br><br/>Respuestas: ".implode(", ", $csv), 
						$headers);
				}

				//Generamos el PDF
				$filename = wp_emprendedores_generate_pdf($responses);
				?><a class="download" href="<?=plugin_dir_url(__FILE__).'pdf/'.$filename;?>" target="_blank" rel="noopener"><?php _e("Descargar informe", 'wp-emprendedores'); ?></a><?php

				$message = __('<table border="0" width="600" cellpadding="10" align="center" bgcolor="ffffff">
				<tbody>
				<tr><td><img src="http://www.autoevalua.es/wp-content/uploads/2023/12/asle-300x98.jpg" alt=""></td><td><img src="http://www.autoevalua.es/wp-content/uploads/2023/12/lanbide-300x98.jpg" alt=""></td></tr>
				<tr>
				<td colspan="2"><span style="font-family: Arial; font-size: medium;">Hola,</span></td>
				</tr>
				<tr>
				<td colspan="2"><span style="font-family: Arial; font-size: medium;">Aquí tienes tu informe de "Autodiagnóstico en competencias emprendedoras".</span></td>
				</tr>
				<tr>
				<td colspan="2"><span style="font-family: Arial; font-size: medium;">Muchas gracias.</span></td>
				</tr>
				<tr>
				<td colspan="2"><span style="font-family: Arial; font-size: medium;">Un saludo</span></td>
				</tr>
				<tr>
				<td align="center" colspan="2"><span style="font-family: Arial; font-size: medium;"><a style="color: #000;" href="http://www.autoevalua.es/">www.autoevalua.es</a></span></td>
				</tr>
				</tbody>
				</table>', 'wp-emprendedores');


				//Enviamos email de aviso al usuario
				if(isset($_POST['email']) && is_email($_POST['email'])) {
					wp_mail($_POST['email'], __("Aquí tienes tu informe de \"Autodiagnóstico en competencias emprendedoras\"", 'wp-emprendedores'), $message, $headers, plugin_dir_path(__FILE__).'pdf/'.$filename);
				}
			}
		} ?>
		<?php if($control == 0) { ?> 
			<?php if(isset($content)) { ?>
    		<div>
      		<?php echo apply_filters("the_content", $content); ?>
    		</div>
			<?php } ?>
			<form id="emprendedores-preguntas-form" method="post" action="<?php echo get_the_permalink(); ?>#emprendedores-preguntas">
				<?php
					$sections = get_terms( array(
						'taxonomy'   => 'test',
						'hide_empty' => true,
						'orderby' => 'slug',
						'order' => 'ASC'
					));
					$steps = '<ul class="steps">';
					foreach($sections as $index => $section) {
						if($currentstep == $index) $steps .= "<li class='current'></li>";
						else $steps .= "<li></li>";
					}
					$steps .= "</ul>";
					echo $steps;

					foreach($sections as $index => $section) {
						if($currentstep == $index) { $nextstep = $index + 1;
							echo "<h3>".$section->name."</h3>";
							
							$args = array(
								'post_type' => 'emprendedor-pregunta',
								'posts_per_page' => -1,
								'post_status' => 'publish',
								'tax_query' => array(
									array (
											'taxonomy' => 'test',
											'field' => 'term_id',
											'terms' => $section->term_id,
									)
								),
								'orderby' => 'menu_order',
								'order' => 'ASC'
							);
						
							$the_query = new WP_Query( $args);
							if ($the_query->have_posts()) {
								while ($the_query->have_posts()) { $the_query->the_post(); ?>
								<h4><?=get_the_title();?></h4>
								<?php foreach(array('a', 'b', 'c') as $letra) { ?>
									<label><input type="radio" name="preguntas[<?=get_the_id();?>]" value="<?=$letra;?>"<?=($letra == 'a' ? " required" : "");?>> <?=get_post_meta(get_the_id(), '_emprendedor-pregunta_respuesta-'.$letra, true );?></label>
								<?php } ?>
							<?php } } wp_reset_query(); ?>
						<?php if(!isset($sections[$nextstep])) { ?>
							<h4><?php _e("Si quieres que te enviemos el informe por email, rellena este campo con tu email.", 'wp-emprendedores'); ?></h4>
							<input type="email" name="email" placeholder='email@dominio.com' value=''>
							<div class="legal">
								<?php echo get_option("_wp_emprendedores_aviso_legal_".$current_lang); ?>
							</div>
						<?php } ?>
						<input type="hidden" name="responses" value='<?=json_encode($responses);?>'>
						<input type="hidden" name="currentstep" value="<?=$index;?>">
						<input type="hidden" name="nextstep" value="<?php  echo (isset($sections[$nextstep]) ? $nextstep : "0"); ?>"> 

						<input  type="submit" name="enviar" value="<?php echo (isset($sections[$nextstep]) ? __("Continuar", "wp-emprendedores") : __("Enviar", "wp-emprendedores")); ?>">
					<?php break; } ?> 	
				<?php } ?>
			</form>
		<?php } ?>
	</div>
  <style>
  
  
  	#emprendedores-preguntas-form label {
  		display: block;
  		position: relative;
  		padding-left: 30px;
  	}
  	
  	#emprendedores-preguntas-form label input[type=radio] {
  		position: absolute;
  		left: 0px;
  		top: 3px;
  	
  	}
  	
  	
  	#emprendedores-preguntas-form .legal {
  		padding: 10px;
  		height: 50px;
  		border: 1px solid #cecece;
  		background-color: #dfdfdf;
  		overflow: auto;
  	}
  	
  	#emprendedores-preguntas-form label + h4 {
  		margin-top: 30px;
  	}
  	
  	
  	#emprendedores-preguntas-form input[type=submit] {
  		margin-top: 30px;
  	
  	}
		#emprendedores-preguntas a.download {
			display: inline-block;
			margin: 10px auto;
			padding: 20px;
			background-color: red;
			color: white;
			font-weight: bold;
			text-decoration: none;
		}

		/* Steps */
		#emprendedores-preguntas ul.steps {
			--black-color: #000;
			--size: 25px;
			margin: 0px;
			list-style-type: none;
			display: flex;
			flex-wrap: wrap;
			counter-reset: my-counter;
			flex-direction: column;
			align-items: stretch;
			justify-content: center;
			align-content: center;
		}

		@media (max-width: 599px) {
			#emprendedores-preguntas ul.steps li br {
				display: none;
			}
		}

		@media (min-width: 600px) {
			#emprendedores-preguntas ul.steps {
				flex-direction: row;
			}
		}

		#emprendedores-preguntas ul.steps li {
			margin: 0;
			list-style: none;
			display: flex;
			align-items: end;
			color: var(--black-color);
			padding: 0px 20px 5px 0px;
			position: relative;
			font-size: 12px;
			line-height: 12px;
			min-width: 80px;
			margin-bottom: 40px;
			counter-increment: my-counter;
			padding-left: 50px;
		}

		@media (min-width: 600px) {
			#emprendedores-preguntas ul.steps li {
				padding-left: 0px;
				margin-bottom: 60px;
			}
		}

		#emprendedores-preguntas ul.steps li:last-child {
			min-width: 0px;
			padding: 0px 0px 5px 50px;
		}

		@media (min-width: 600px) {
			#emprendedores-preguntas ul.steps li:last-child {
				padding: 0px 0px 5px 0px;
				margin-bottom: 60px;
			}
		}

		#emprendedores-preguntas ul.steps li:before {
			position: absolute;
			content: counter(my-counter);
			color: var(--the7-accent-bg-color);
			background-color: var(--the7-accent-bg-2);
			display: flex;
			width: 40px;
			height: 40px;
			border-radius: 50%;
			bottom: -8px;
			left: 0px;
			font-size: 20px;
			font-weight: 700;
			justify-content: center;
			align-items: center;
		}

		@media (min-width: 600px) {
			#emprendedores-preguntas ul.steps li:before {
				bottom: -40px;
			}
		}

		#emprendedores-preguntas ul.steps li.current:before {
			background-color: var(--black-color);
		}

		#emprendedores-preguntas ul.steps li:after {
			position: absolute;
			content: "";
			background-color: var(--the7-accent-bg-2);
			display: none;
		}

		@media (min-width: 600px) {
			#emprendedores-preguntas ul.steps li:after {
				display: block;
				width: calc(100% - 40px);
				height: 2px;
				bottom: -20px;
				left: 40px;
				font-size: 20px;
				font-weight: 700;
			}
		}

		#emprendedores-preguntas ul.steps li:last-of-type:after {
			display: none;
		}

		#emprendedores-preguntas ul.steps li.current:after {
			background-color: var(--black-color);
		}
	</style>
  <?php return ob_get_clean();
}
add_shortcode('emprendedores-preguntas', 'wp_emprendedores_shortcode');

function wp_emprendedores_generate_pdf($responses) {
	require_once __DIR__ . '/vendor/autoload.php';

	$filename = "cuestionario-autodiagnostico-competencias-emprendedoras-".hash("md5", implode("", $responses).date("YmdHis")).".pdf";

	$mpdf = new \Mpdf\Mpdf([
		'format' => 'A4',
		//'margin_header' => 30,     // 30mm not pixel
		//'margin_footer' => 30,     // 10mm
		'setAutoBottomMargin' => 'pad',
		'setAutoTopMargin' => 'pad',
		'fontDir' => __DIR__ . '/fonts/',
		'fontdata' => [
			'dosis' => [
					'R' => 'Dosis-Regular.ttf'
			],
			'roboto' => [
				'R' => 'Roboto-Light.ttf',
				'I' => 'Roboto-LightItalic.ttf'
			]
		],
		'default_font' => 'dosis'
	]);

	$mpdf->SetHeader("");
	$mpdf->SetFooter("");
	$mpdf->AddPage();

	//Generamos el HTML
	$html = "<table border='0' width='100%' cellpadding='5'><tr><td><img src='http://www.autoevalua.es/wp-content/uploads/2023/12/asle-300x98.jpg' alt=''></td><td><img src='http://www.autoevalua.es/wp-content/uploads/2023/12/lanbide-300x98.jpg' alt=''></td></tr></table>";
	$html .= "<h1>".__("Cuestionario de \"Autodiagnóstico en competencias emprendedoras\"", 'wp-emprendedores')."</h1>";
	$html .= "<p>".__("Una vez cumplimentado el test de autodiagnóstico de competencias para emprender basado en el marco europeo de competencias de emprendimiento (EntreComp), mostramos las respuestas que has elegido y un pequeño resumen de la información basada en el ámbito del emprendimiento en Europa.", 'wp-emprendedores')."</h1>";
	$sections = get_terms( array(
		'taxonomy'   => 'test',
		'hide_empty' => true,
		'orderby' => 'slug',
		'order' => 'ASC'
	));
	foreach($sections as $index => $section) {
		$html .= "<h2>".$section->name."</h2>";
		$args = array(
			'post_type' => 'emprendedor-pregunta',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'tax_query' => array(
				array (
						'taxonomy' => 'test',
						'field' => 'term_id',
						'terms' => $section->term_id,
				)
			),
			'orderby' => 'menu_order',
			'order' => 'ASC'
		);
		$the_query = new WP_Query( $args);
		if ($the_query->have_posts()) {
			while ($the_query->have_posts()) { $the_query->the_post(); $post_id = get_the_id();
				$html .= "<hr><h3>".get_the_title()."</h3>";
				foreach(array('a', 'b', 'c') as $letra) { 
					if($letra != 'a') $html .= "<tr>";
					$html .= "<p style='padding: 5px; ".($letra == $responses[$post_id] ? " color: white; background-color: black;" : "")."'>".$letra.") ".get_post_meta(get_the_id(), '_emprendedor-pregunta_respuesta-'.$letra, true )."</p>";
				}
				$html .= "<table cellpadding='10' style='background-color: #cecece;'><tr><td>".get_the_content()."</td><td><img src='".plugin_dir_url( __FILE__ )."images/".$responses[$post_id].".png' width='150'></td></tr></table>";
			} 
		} wp_reset_query();
	}

	//Guardamos el PDF
	$mpdf->WriteHTML($html);
	$mpdf->SetTitle(__("Cuestionario de \"Autodiagnóstico en competencias emprendedoras\"", 'wp-emprendedores'));
	$mpdf->SetAuthor("ASLE · Asociación empresarial sin ánimo de lucro");	
	$mpdf->Output(plugin_dir_path(__FILE__).'pdf/'.$filename,'F');
	return $filename;

}

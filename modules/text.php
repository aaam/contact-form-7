<?php
/**
** A base module for [text], [text*], [email], and [email*]
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'text', 'wpcf7_text_shortcode_handler', true );
wpcf7_add_shortcode( 'text*', 'wpcf7_text_shortcode_handler', true );
wpcf7_add_shortcode( 'email', 'wpcf7_text_shortcode_handler', true );
wpcf7_add_shortcode( 'email*', 'wpcf7_text_shortcode_handler', true );

function wpcf7_text_shortcode_handler( $tag ) {
	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

	if ( empty( $name ) )
		return '';

	$validation_error = wpcf7_get_validation_error( $name );

	$atts = $id_att = $size_att = $maxlength_att = '';
	$tabindex_att = $placeholder_att = '';

	$class_att = wpcf7_form_controls_class( $type, 'wpcf7-text' );

	if ( 'email' == $type || 'email*' == $type )
		$class_att .= ' wpcf7-validates-as-email';

	if ( $validation_error )
		$class_att .= ' wpcf7-not-valid';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '%^([0-9]*)[/x]([0-9]*)$%', $option, $matches ) ) {
			$size_att = (int) $matches[1];
			$maxlength_att = (int) $matches[2];

		} elseif ( preg_match( '%^tabindex:(\d+)$%', $option, $matches ) ) {
			$tabindex_att = (int) $matches[1];

		}
	}

	$value = (string) reset( $values );

	if ( preg_grep( '%^placeholder|watermark$%', $options ) ) {
		$placeholder_att = $value;
		$value = '';
	} elseif ( empty( $value ) && is_user_logged_in() ) {
		$user = wp_get_current_user();

		$user_options = array(
			'default:user_login' => 'user_login',
			'default:user_email' => 'user_email',
			'default:user_url' => 'user_url',
			'default:user_first_name' => 'first_name',
			'default:user_last_name' => 'last_name',
			'default:user_nickname' => 'nickname',
			'default:user_display_name' => 'display_name' );

		foreach ( $user_options as $option => $prop ) {
			if ( preg_grep( '%^' . $option . '$%', $options ) ) {
				$value = $user->{$prop};
				break;
			}
		}
	}

	if ( wpcf7_is_posted() && isset( $_POST[$name] ) )
		$value = stripslashes_deep( $_POST[$name] );

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	if ( $size_att )
		$atts .= ' size="' . $size_att . '"';
	else
		$atts .= ' size="40"'; // default size

	if ( $maxlength_att )
		$atts .= ' maxlength="' . $maxlength_att . '"';

	if ( '' !== $tabindex_att )
		$atts .= sprintf( ' tabindex="%d"', $tabindex_att );

	if ( $placeholder_att )
		$atts .= sprintf( ' placeholder="%s"', esc_attr( $placeholder_att ) );

	if ( wpcf7_support_html5() ) {
		$type_att = trim( $type, '*' );
	} else {
		$type_att = 'text';
	}

	$html = '<input type="' . $type_att . '" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $atts . ' />';

	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}


/* Validation filter */

add_filter( 'wpcf7_validate_text', 'wpcf7_text_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_text*', 'wpcf7_text_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_email', 'wpcf7_text_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_email*', 'wpcf7_text_validation_filter', 10, 2 );

function wpcf7_text_validation_filter( $result, $tag ) {
	$type = $tag['type'];
	$name = $tag['name'];

	$value = isset( $_POST[$name] )
		? trim( strtr( (string) $_POST[$name], "\n", " " ) )
		: '';

	if ( 'text*' == $type ) {
		if ( '' == $value ) {
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message( 'invalid_required' );
		}
	}

	if ( 'email' == $type || 'email*' == $type ) {
		if ( 'email*' == $type && '' == $value ) {
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message( 'invalid_required' );
		} elseif ( '' != $value && ! wpcf7_is_email( $value ) ) {
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message( 'invalid_email' );
		}
	}

	return $result;
}


/* Tag generator */

add_action( 'admin_init', 'wpcf7_add_tag_generator_text_and_email', 15 );

function wpcf7_add_tag_generator_text_and_email() {
	if ( ! function_exists( 'wpcf7_add_tag_generator' ) )
		return;

	wpcf7_add_tag_generator( 'text', __( 'Text field', 'wpcf7' ),
		'wpcf7-tg-pane-text', 'wpcf7_tg_pane_text' );

	wpcf7_add_tag_generator( 'email', __( 'Email field', 'wpcf7' ),
		'wpcf7-tg-pane-email', 'wpcf7_tg_pane_email' );
}

function wpcf7_tg_pane_text( &$contact_form ) {
	wpcf7_tg_pane_text_and_email( 'text' );
}

function wpcf7_tg_pane_email( &$contact_form ) {
	wpcf7_tg_pane_text_and_email( 'email' );
}

function wpcf7_tg_pane_text_and_email( $type = 'text' ) {
	if ( 'email' != $type )
		$type = 'text';

?>
<div id="wpcf7-tg-pane-<?php echo $type; ?>" class="hidden">
<form action="">
<table>
<tr><td><input type="checkbox" name="required" />&nbsp;<?php echo esc_html( __( 'Required field?', 'wpcf7' ) ); ?></td></tr>
<tr><td><?php echo esc_html( __( 'Name', 'wpcf7' ) ); ?><br /><input type="text" name="name" class="tg-name oneline" /></td><td></td></tr>
</table>

<table>
<tr>
<td><code>id</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="id" class="idvalue oneline option" /></td>

<td><code>class</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="class" class="classvalue oneline option" /></td>
</tr>

<tr>
<td><code>size</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="size" class="numeric oneline option" /></td>

<td><code>maxlength</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="maxlength" class="numeric oneline option" /></td>
</tr>

<tr>
<td colspan="2"><?php echo esc_html( __( 'Akismet', 'wpcf7' ) ); ?> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<?php if ( 'text' == $type ) : ?>
<input type="checkbox" name="akismet:author" class="exclusive option" />&nbsp;<?php echo esc_html( __( "This field requires author's name", 'wpcf7' ) ); ?><br />
<input type="checkbox" name="akismet:author_url" class="exclusive option" />&nbsp;<?php echo esc_html( __( "This field requires author's URL", 'wpcf7' ) ); ?>
<?php else : ?>
<input type="checkbox" name="akismet:author_email" class="option" />&nbsp;<?php echo esc_html( __( "This field requires author's email address", 'wpcf7' ) ); ?>
<?php endif; ?>
</td>
</tr>

<tr>
<td><?php echo esc_html( __( 'Default value', 'wpcf7' ) ); ?> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br /><input type="text" name="values" class="oneline" /></td>

<td>
<br /><input type="checkbox" name="placeholder" class="option" />&nbsp;<?php echo esc_html( __( 'Use this text as placeholder?', 'wpcf7' ) ); ?>
</td>
</tr>
</table>

<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'wpcf7' ) ); ?><br /><input type="text" name="<?php echo $type; ?>" class="tag" readonly="readonly" onfocus="this.select()" /></div>

<div class="tg-mail-tag"><?php echo esc_html( __( "And, put this code into the Mail fields below.", 'wpcf7' ) ); ?><br /><span class="arrow">&#11015;</span>&nbsp;<input type="text" class="mail-tag" readonly="readonly" onfocus="this.select()" /></div>
</form>
</div>
<?php
}

?>
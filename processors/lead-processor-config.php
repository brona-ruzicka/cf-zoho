<?php
/**
 * Lead Processor Config.
 *
 * @package cf_zoho/processors.
 */

namespace cf_zoho\processors;
use cf_zoho\includes;

$module = new includes\CF_Processor_Render( 'leads' );

$errors = $module->get_errors();

$template = ( ! empty( $errors ) ) ? 'zoho-errors.php' : 'contact-based-processor.php';

include CFZ_TEMPLATE_PATH . $template;

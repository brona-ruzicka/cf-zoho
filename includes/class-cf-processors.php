<?php
/**
 * Defines processors for Caldera Forms.
 *
 * @package cf_zoho/includes.
 */

namespace cf_zoho\includes;

use cf_zoho\includes\zohoapi;

/**
 * Processors Class.
 */
class CF_Processors {

	/**
	 * Processor config.
	 *
	 * @var array.
	 */
	private $config = [];

	/**
	 * Form config.
	 *
	 * @var array.
	 */
	private $form = [];

	/**
	 * Module name.
	 *
	 * @var string.
	 */
	private $module = '';

	/**
	 * Registers our processors with Caldera Forms.
	 *
	 * @param  array $processors Array of current processors.
	 * @return array                Current processors with our added processors
	 */
	public function register_processors( $processors ) {

		$processors['zoho_lead'] = [
			'name'          => __( 'Zoho CRM - Create Lead', 'cf-zoho-2' ),
			'description'   => __( 'Create or Update a lead on form submission', 'cf-zoho-2' ),
			'author'        => 'Matt Bush',
			'author_url'    => 'https://haycroftmedia.com/',
			'pre_processor' => [ $this, 'process_lead_submission' ],
			'template'      => CFZ_PROCESSORS_PATH . 'lead-processor-config.php',
			'icon'          => CFZ_URL . 'assets/images/icon.png',
			'magic_tags'    => [
				'id' => [ 'text', 'zoho_task' ],
			],
		];

		$processors['zoho_contact'] = [
			'name'          => __( 'Zoho CRM - Create Contact', 'cf-zoho-2' ),
			'description'   => __( 'Create or Update a contact on form submission', 'cf-zoho-2' ),
			'author'        => 'Matt Bush',
			'author_url'    => 'https://haycroftmedia.com/',
			'pre_processor' => [ $this, 'process_contact_submission' ],
			'template'      => CFZ_PROCESSORS_PATH . 'contact-processor-config.php',
			'icon'          => CFZ_URL . 'assets/images/icon.png',
			'magic_tags'    => [
				'id' => [ 'text', 'zoho_task' ],
			],
		];

		$processors['zoho_task'] = [
			'name'          => __( 'Zoho CRM - Create Task', 'cf-zoho-2' ),
			'description'   => __( 'Create or Update a task on form submission', 'cf-zoho-2' ),
			'author'        => 'Matt Bush',
			'author_url'    => 'https://haycroftmedia.com/',
			'pre_processor' => [ $this, 'process_task_submission' ],
			'template'      => CFZ_PROCESSORS_PATH . 'task-processor-config.php',
			'icon'          => CFZ_URL . 'assets/images/icon.png',
			'magic_tags'    => [ 'id' ],
		];

		return $processors;
	}

	/**
	 * Callback for Lead form submissions.
	 *
	 * @param  array  $config Processor config
	 * @param  array  $form Form config
	 * @param  string $process_id Unique process ID for this submission
	 * @return void|array.
	 */
	public function process_lead_submission( $config, $form, $process_id ) {

		$this->config = $config;
		$this->form   = $form;
		$this->module = 'leads';

		return $this->do_submission();
	}

	/**
	 * Callback for Contact form submissions.
	 *
	 * @param  array  $config Processor config
	 * @param  array  $form Form config
	 * @param  string $process_id Unique process ID for this submission
	 * @return void|array.
	 */
	public function process_contact_submission( $config, $form, $process_id ) {

		$this->config = $config;
		$this->form   = $form;
		$this->module = 'contacts';

		return $this->do_submission();
	}

	/**
	 * Callback for Task form submissions.
	 *
	 * @param  array  $config Processor config
	 * @param  array  $form Form config
	 * @param  string $process_id Unique process ID for this submission
	 * @return void|array.
	 */
	public function process_task_submission( $config, $form, $process_id ) {

		$this->config = $config;
		$this->form   = $form;
		$this->module = 'tasks';

		return $this->do_submission();
	}

	/**
	 * Process form submissions.
	 *
	 * @return null|array Array containining id if successfull|null response on fail.
	 */
	public function do_submission() {

		$object = $this->build_object();

		$trigger = [];

		if ( ! empty( $this->config['_approval_mode'] ) ) {
			$trigger[] = 'approval';
		}

		if ( ! empty( $this->config['_workflow_mode'] ) ) {
			$trigger[] = 'workflow';
		}

		$body = [
			'data'    => [ $object ],
			'trigger' => $trigger,
		];

		// Filter hook.
		$object = apply_filters( 'process_zoho_submission', $body, $this->config, $this->form );

		$post = new zohoapi\Post();
		$path = '/crm/v2/' . ucfirst( $this->module );

		$response = $post->request( $path, $body );

		if ( is_wp_error( $response ) ) {

			return [
				'note' => $response->get_error_message(),
				'type' => 'error',
			];
		}

		if ( ! isset( $response['data'][0]['code'] ) || 'SUCCESS' !== $response['data'][0]['code'] ) {

			return [
				'note' => $response['message'],
				'type' => 'error',
			];
		}

		$object_id = $response['data'][0]['details']['id'];

		do_action( 'cf_zoho_create_entry_complete', $object_id, $this->config, $this->form );
	}

	/**
	 * Build object for the module.
	 *
	 * @return array Object for the module.
	 */
	public function build_object() {

		$object = $this->get_default_object( $this->module, $this->config );

		$cache  = new Cache();
		$fields = $cache->get_plugin_cache_item( $this->module );

		// If fields aren't cached, fetch them.
		if ( false === $fields ) {
			$fields = $this->get_module_fields();
		}

		foreach ( $fields as $section ) {

			foreach ( $section['fields'] as $field ) {

				$label            = str_replace( ' ', '_', $field['field_label'] );
				$object[ $label ] = $this->get_form_value( $field );
			}
		}

		return $object;
	}

	/**
	 * Default object for the module.
	 *
	 * @return array Default object for the module.
	 */
	public function get_default_object() {

		switch ( $this->module ) {

			case 'leads':
			case 'contacts':
				$object = [
					'Email_Opt_Out' => ! empty( $this->config['_email_opt_out'] ) ? 'true' : 'false',
					'Description'   => '',
				];

				$unset_if_empty = [
					'leadowner'  => 'SMOWNERID',
					'leadsource' => 'Lead_Source',
					'leadstatus' => 'Lead_Status',
					'rating'     => 'Rating',
				];

				foreach ( $unset_if_empty as $key => $label ) {

					if ( ! empty( $this->config[ $key ] ) ) {
						$object[ $label ] = $this->config[ $key ];
						continue;
					}

					if ( ! isset( $this->config[ $key ] ) ) {
						continue;
					}

					unset( $this->config[ $key ] );
				}

				return $object;

			case 'tasks':
				$object = [
					'Due_Date'  => '',
					'Subject'   => '',
					'Status'    => '',
					'SMOWNERID' => '',
				];

				// No parent? Then return.
				if ( empty( $this->config['parent'] ) ) {
					return $object;
				}

				// Otherwise, get parent.
				$parent = explode( ':', trim( $this->config['parent'], '{}' ) );

				if ( empty( $this->form['processors'][ $parent[0] ] ) ) {
					$this->config['whoid'] = '%' . $this->form['fields'][ $this->config['parent'] ]['slug'] . '%';
					return $object;
				}

				$parent_value = \Caldera_Forms::do_magic_tags( $this->config['parent'] );

				/* Add lead or contact ID. */
				if ( 'zoho_contact' === $this->form['processors'][ $parent[0] ]['type'] ) {
					$object['CONTACTID'] = $parent_value;
					return $object;
				}

				if ( 'zoho_lead' === $this->form['processors'][ $parent[0] ]['type'] ) {
					$object['SEID']     = $parent_value;
					$object['SEMODULE'] = 'Leads';
				}

				return $object;
		}
	}

	/**
	 * Fetches module fields.
	 * Called when cached fields have expired.
	 *
	 * @return array Module fields.
	 */
	public function get_module_fields() {
		$cf_processor_render = new CF_Processor_Render( $this->module );
		return $cf_processor_render->get_module_data();
	}

	/**
	 * Get the submitted value for a form element.
	 *
	 * @param  array $field Form field data.
	 * @return string        Form field value.
	 */
	public function get_form_value( $field ) {

		$key = sanitize_key( $field['field_label'] );

		if ( ! isset( $this->config[ $key ] ) ) {
			return;
		}

		$value = \Caldera_Forms::do_magic_tags( $this->config[ $key ] );

		if ( 'boolean' !== strtolower( $field['data_type'] ) ) {
			return $value;
		}

		$true_options = [ 'Yes', 'yes', 'True', '1' ];

		foreach ( $true_options as $true_option ) {
			$value = str_replace( $true_option, 'true', $value );
		}

		$false_options = [ 'No', 'no', 'False', '0' ];

		foreach ( $false_options as $false_option ) {
			$value = str_replace( $false_option, 'false', $value );
		}

		return $value;
	}

}

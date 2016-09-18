<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

abstract class Element_Base {
	const RESPONSIVE_DESKTOP = 'desktop';
	const RESPONSIVE_TABLET = 'tablet';
	const RESPONSIVE_MOBILE = 'mobile';

	private $_id;

	private $_settings;

	private $_data;

	/**
	 * @var Element_Base[]
	 */
	private $_children;

	private $_render_attributes = [];

	abstract public function before_render();

	abstract public function after_render();

	/**
	 * @param array $element_data
	 *
	 * @return Element_Base class name
	 */
	abstract protected function _get_child_class( array $element_data );

	public final static function get_controls() {
		$stack = Plugin::instance()->controls_manager->get_element_stack( get_called_class() );

		if ( null === $stack ) {
			self::_init_controls();

			return self::get_controls();
		}

		return $stack['controls'];
	}

	public final static function add_control( $id, $args ) {
		return Plugin::instance()->controls_manager->add_control_to_stack( get_called_class(), $id, $args );
	}

	public final static function add_group_control( $group_name, $args = [] ) {
		do_action_ref_array( 'elementor/elements/add_group_control/' . $group_name, [ get_called_class(), $args ] );
	}

	public final static function get_tabs_controls() {
		$stack = Plugin::instance()->controls_manager->get_element_stack( get_called_class() );

		return $stack['tabs'];
	}

	public final static function get_scheme_controls() {
		$enabled_schemes = Schemes_Manager::get_enabled_schemes();

		return array_filter( self::get_controls(), function( $control ) use ( $enabled_schemes ) {
			return ( ! empty( $control['scheme'] ) && in_array( $control['scheme']['type'], $enabled_schemes ) );
		} );
	}

	public final static function get_style_controls() {
		return array_filter( self::get_controls(), function( $control ) {
			return ( ! empty( $control['selectors'] ) );
		} );
	}

	public final static function get_class_controls() {
		return array_filter( self::get_controls(), function( $control ) {
			return ( isset( $control['prefix_class'] ) );
		} );
	}

	public final static function add_responsive_control( $id, $args = [] ) {
		// Desktop
		$control_args = $args;

		if ( ! empty( $args['prefix_class'] ) ) {
			$control_args['prefix_class'] = sprintf( $args['prefix_class'], '' );
		}

		$control_args['responsive'] = self::RESPONSIVE_DESKTOP;

		self::add_control(
			$id,
			$control_args
		);

		// Tablet
		$control_args = $args;

		if ( ! empty( $args['prefix_class'] ) ) {
			$control_args['prefix_class'] = sprintf( $args['prefix_class'], '-' . self::RESPONSIVE_TABLET );
		}

		$control_args['responsive'] = self::RESPONSIVE_TABLET;

		self::add_control(
			$id . '_tablet',
			$control_args
		);

		// Mobile
		$control_args = $args;

		if ( ! empty( $args['prefix_class'] ) ) {
			$control_args['prefix_class'] = sprintf( $args['prefix_class'], '-' . self::RESPONSIVE_MOBILE );
		}

		$control_args['responsive'] = self::RESPONSIVE_MOBILE;

		self::add_control(
			$id . '_mobile',
			$control_args
		);
	}

	private static function _init_controls() {
		static::_before_register_controls();

		Plugin::instance()->controls_manager->open_stack( get_called_class() );

		static::_register_controls();

		static::_after_register_controls();
	}

	public final static function get_class_name() {
		return get_called_class();
	}

	public static function get_name() {
		return '';
	}

	public static function get_title() {
		return '';
	}

	public static function get_keywords() {
		return [];
	}

	public static function get_categories() {
		return [ 'basic' ];
	}

	public static function get_type() {
		return 'element';
	}

	public static function get_icon() {
		return 'columns';
	}

	public static function get_config( $item = null ) {
		$config = [
			'elType' => static::get_type(),
			'title' => static::get_title(),
			'controls' => array_values( self::get_controls() ),
			'tabs_controls' => self::get_tabs_controls(),
			'categories' => static::get_categories(),
			'keywords' => static::get_keywords(),
			'icon' => static::get_icon(),
		];

		if ( $item ) {
			return isset( $config[ $item ] ) ? $config[ $item ] : null;
		}

		return $config;
	}

	public static function print_template() {
		ob_start();

		static::_content_template();

		$content_template = ob_get_clean();

		if ( empty( $content_template ) ) {
			return;
		}
		?>
		<script type="text/html" id="tmpl-elementor-<?php echo static::get_type(); ?>-<?php echo esc_attr( static::get_name() ); ?>-content">
			<?php static::_render_settings(); ?>
			<?php echo $content_template; ?>
		</script>
		<?php
	}

	protected static function _before_register_controls() {}

	protected static function _register_controls() {}

	protected static function _after_register_controls() {}

	protected static function _content_template() {}

	protected static function _render_settings() {
		?>
		<div class="elementor-element-overlay">
			<div class="elementor-editor-element-settings elementor-editor-<?php echo esc_attr( static::get_type() ); ?>-settings elementor-editor-<?php echo esc_attr( static::get_name() ); ?>-settings">
				<ul class="elementor-editor-element-settings-list">
					<li class="elementor-editor-element-setting elementor-editor-element-add">
						<a href="#" title="<?php _e( 'Add Widget', 'elementor' ); ?>">
							<span class="elementor-screen-only"><?php _e( 'Add', 'elementor' ); ?></span>
							<i class="fa fa-plus"></i>
						</a>
					</li>
					<?php /* Temp removing for better UI
					<li class="elementor-editor-element-setting elementor-editor-element-edit">
						<a href="#" title="<?php _e( 'Edit Widget', 'elementor' ); ?>">
							<span class="elementor-screen-only"><?php _e( 'Edit', 'elementor' ); ?></span>
							<i class="fa fa-pencil"></i>
						</a>
					</li>
					*/ ?>
					<li class="elementor-editor-element-setting elementor-editor-element-duplicate">
						<a href="#" title="<?php _e( 'Duplicate Widget', 'elementor' ); ?>">
							<span class="elementor-screen-only"><?php _e( 'Duplicate', 'elementor' ); ?></span>
							<i class="fa fa-files-o"></i>
						</a>
					</li>
					<li class="elementor-editor-element-setting elementor-editor-element-remove">
						<a href="#" title="<?php _e( 'Remove Widget', 'elementor' ); ?>">
							<span class="elementor-screen-only"><?php _e( 'Remove', 'elementor' ); ?></span>
							<i class="fa fa-trash-o"></i>
						</a>
					</li>
				</ul>
			</div>
		</div>
		<?php
	}

	public function __construct( $data, $args = [] ) {
		$this->_data = array_merge( $this->get_default_data(), $data );
		$this->_id = $data['id'];
		$this->_settings = $this->_get_parsed_settings();
	}

	public final function get_id() {
		return $this->_id;
	}

	public function get_data( $item = null ) {
		if ( $item ) {
			return isset( $this->_data[ $item ] ) ? $this->_data[ $item ] : null;
		}

		return $this->_data;
	}

	public function get_settings( $setting = null ) {
		if ( $setting ) {
			return isset( $this->_settings[ $setting ] ) ? $this->_settings[ $setting ] : null;
		}

		return $this->_settings;
	}

	public function get_children_data() {
		return ! empty( $this->_data['elements'] ) ? $this->_data['elements'] : [];
	}

	public function get_children() {
		if ( null === $this->_children ) {
			$this->_init_children();
		}

		return $this->_children;
	}

	public function is_control_visible( $control ) {
		if ( empty( $control['condition'] ) ) {
			return true;
		}

		foreach ( $control['condition'] as $condition_key => $condition_value ) {
			preg_match( '/([a-z_0-9]+)(?:\[([a-z_]+)])?(!?)$/i', $condition_key, $condition_key_parts );

			$pure_condition_key = $condition_key_parts[1];
			$condition_sub_key = $condition_key_parts[2];
			$is_negative_condition = ! ! $condition_key_parts[3];

			$instance_value = $this->get_settings()[ $pure_condition_key ];

			if ( $condition_sub_key ) {
				if ( ! isset( $instance_value[ $condition_sub_key ] ) ) {
					return false;
				}

				$instance_value = $instance_value[ $condition_sub_key ];
			}

			$is_contains = is_array( $condition_value ) ? in_array( $instance_value, $condition_value ) : $instance_value === $condition_value;

			if ( $is_negative_condition && $is_contains || ! $is_negative_condition && ! $is_contains ) {
				return false;
			}
		}

		return true;
	}

	public function add_render_attribute( $element, $key, $value ) {
		if ( empty( $this->_render_attributes[ $element ][ $key ] ) ) {
			$this->_render_attributes[ $element ][ $key ] = [];
		}

		$this->_render_attributes[ $element ][ $key ] = array_merge( $this->_render_attributes[ $element ][ $key ], (array) $value );
	}

	public function get_render_attribute_string( $element ) {
		if ( empty( $this->_render_attributes[ $element ] ) ) {
			return '';
		}

		$render_attributes = $this->_render_attributes[ $element ];

		$attributes = [];

		foreach ( $render_attributes as $attribute_key => $attribute_values ) {
			$attributes[] = sprintf( '%s="%s"', $attribute_key, esc_attr( implode( ' ', $attribute_values ) ) );
		}

		return implode( ' ', $attributes );
	}

	public function print_element() {
		$this->before_render();

		foreach ( $this->get_children() as $child ) {
			$child->print_element();
		}

		$this->after_render();
	}

	public function get_raw_data( $with_html_content = false ) {
		$data = $this->get_data();

		$elements = [];

		foreach ( $this->get_children() as $child ) {
			$elements[] = $child->get_raw_data( $with_html_content );
		}

		return [
			'id' => $this->_id,
			'elType' => $data['elType'],
			'settings' => $data['settings'],
			'elements' => $elements,
			'isInner' => $data['isInner'],
		];
	}

	protected function render() {}

	protected function get_default_data() {
		return [
			'id' => 0,
			'elType' => 'element',
			'settings' => [],
			'elements' => '',
			'isInner' => false,
		];
	}

	private function _get_parsed_settings() {
		$settings = $this->_data['settings'];

		foreach ( self::get_controls() as $control ) {
			$control_obj = Plugin::instance()->controls_manager->get_control( $control['type'] );

			if ( $control_obj ) {
				continue;
			}

			$settings[ $control['name'] ] = $control_obj->get_value( $control, $settings );
		}

		return $settings;
	}

	private function _init_children() {
		$this->_children = [];

		$elements = $this->get_data( 'elements' );

		if ( ! $elements ) {
			return;
		}

		foreach ( $elements as $element ) {
			$child_class = $this->_get_child_class( $element );

			$this->_children[] = new $child_class( $element );
		}
	}
}

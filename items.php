<?php class GhostPool_Elementor_Items extends Elementor\Widget_Base
{

	public function get_style_depends()
	{
		return ['ghostpool-fontello'];
	}

	public function get_script_depends()
	{
		return ['jquery', 'ghostpool-items'];
	}

	public function get_name()
	{
		return 'ghostpool_items';
	}

	public function get_title()
	{
		return esc_html__('Items', 'ghostpool-core');
	}

	public function get_icon()
	{
		return 'eicon-post-list';
	}

	public function get_categories()
	{
		return ['ghostpool_general'];
	}

	protected function register_controls()
	{

		/*--------------------------------------------------------------
			  CONTENT TAB
			  --------------------------------------------------------------*/

		$this->start_controls_section(
			'_ghostpool_section_content_items',
			[
				'label' => esc_html__('Items', 'ghostpool-core'),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'format',
			[
				'label' => esc_html__('Format', 'ghostpool-core'),
				'label_block' => true,
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'list' => esc_html__('List', 'ghostpool-core'),
					'grid' => esc_html__('Grid', 'ghostpool-core'),
					'columns' => esc_html__('Columns', 'ghostpool-core'),
					'masonry' => esc_html__('Masonry', 'ghostpool-core'),
				),
				'default' => 'list',
			]
		);

		$this->add_control(
			'item_template_primary',
			[
				'label' => esc_html__('Primary Item Template', 'ghostpool-core'),
				'label_block' => true,
				'description' => sprintf(wp_kses(__('For more info on creating an item loop template click <a href="%s" target="_blank">here</a>.', 'ghostpool-core'), array('a' => array('href' => array(), 'target' => array()))), 'https://docs.ghostpool.com/ghostpool-core/website-builder-basics/item-loop-builder/'),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => ghostpool_data_type(
					array(
						'data' => array('template' => 'item'),
						'data_defaults' => array(
							'gp-none' => esc_html__('None', 'ghostpool-core'),
						),
					)),
				'default' => 'gp-none',
				'separator' => 'before',
				'classes' => 'gp-template-control',
			]
		);

		$this->add_control(
			'item_template_secondary',
			[
				'label' => esc_html__('Secondary Item Template', 'ghostpool-core'),
				'label_block' => true,
				'description' => sprintf(wp_kses(__('For more info on creating an item loop template click <a href="%s" target="_blank">here</a>.', 'ghostpool-core'), array('a' => array('href' => array(), 'target' => array()))), 'https://docs.ghostpool.com/ghostpool-core/website-builder-basics/item-loop-builder/'),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => ghostpool_data_type(
					array(
						'data' => array('template' => 'item'),
						'data_defaults' => array(
							'gp-none' => esc_html__('None', 'ghostpool-core'),
						),
					)),
				'default' => 'gp-none',
				'classes' => 'gp-template-control',
			]
		);

		$individual_items_repeater = new \Elementor\Repeater();

		$individual_items_repeater->add_control(
			'item_number',
			[
				'label' => esc_html__('Number', 'ghostpool-core'),
				'description' => esc_html__('The number of the item you want to target e.g. if you want to target the third item you would enter 3.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 1,
			]
		);

		$individual_items_repeater->add_control(
			'item_template',
			[
				'label' => esc_html__('Template', 'ghostpool-core'),
				'label_block' => true,
				'description' => sprintf(wp_kses(__('For more info on creating an item loop template click <a href="%s" target="_blank">here</a>.', 'ghostpool-core'), array('a' => array('href' => array(), 'target' => array()))), 'https://docs.ghostpool.com/ghostpool-core/website-builder-basics/item-loop-builder/'),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => ghostpool_data_type(
					array(
						'data' => array('template' => 'item'),
						'data_defaults' => array(
							'gp-none' => esc_html__('None', 'ghostpool-core'),
						),
					)),
				'default' => 'gp-none',
				'classes' => 'gp-template-control',
			]
		);

		$this->add_control(
			'individual_items',
			[
				'label' => esc_html__('Individual Item Templates', 'ghostpool-core'),
				'description' => esc_html__('Add different templates for individual items.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $individual_items_repeater->get_controls(),
				'title_field' => esc_html__('Item ', 'ghostpool-core') . '{{{ item_number }}}',
				'prevent_empty' => false,
			]
		);

		$this->add_control(
			'pagination_format',
			[
				'label' => esc_html__('Pagination Format', 'ghostpool-core'),
				'label_block' => true,
				'options' => array(
					'numbers' => esc_html__('Numbers', 'ghostpool-core'),
					'arrows' => esc_html__('Arrows', 'ghostpool-core'),
					'load-more-button' => esc_html__('Load More Button', 'ghostpool-core'),
					//'load-more-scrolling' => esc_html__( 'Load More Scrolling', 'ghostpool-core' ),
					'disabled' => esc_html__('Disabled', 'ghostpool-core'),
				),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'numbers',
				'separator' => 'before',
			]
		);


		$this->add_control(
			'pagination_format_notice',
			[
				'label' => '',
				'show_label' => false,
				'raw' => esc_html__('Do not use the Load More Button pagination format when using the Grid Format as it will not work correctly.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition' => [
					'format' => 'grid',
				]
			]
		);

		$this->add_control(
			'disable_ajax_pagination',
			[
				'label' => esc_html__('Disable Ajax Pagination', 'ghostpool-core'),
				'description' => esc_html__('When clicking pagination links a new page will be loaded rather than dynamically loading new items within the page.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Yes', 'ghostpool-core'),
				'label_off' => esc_html__('No', 'ghostpool-core'),
				'return_value' => 'yes',
				'condition' => [
					'pagination_format' => ['numbers', 'arrows'],
				],
			]
		);

		$this->end_controls_section();

		// Load query options
		require (plugin_dir_path(__FILE__) . 'inc/query-options.php');

		$this->start_controls_section(
			'_ghostpool_section_content_labels',
			[
				'label' => esc_html__('Labels', 'ghostpool-core'),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'no_items_found_message',
			[
				'label' => esc_html__('No Items Found Message', 'ghostpool-core'),
				'label_block' => true,
				'type' => \Elementor\Controls_Manager::TEXTAREA,
			]
		);

		$this->add_control(
			'load_more_button_label',
			[
				'label' => esc_html__('Load More Button Label', 'ghostpool-core'),
				'label_block' => true,
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__('Load More', 'ghostpool-core'),
				'condition' => [
					'pagination_format' => 'load-more-button',
				],
			]
		);

		$this->end_controls_section();

		/*--------------------------------------------------------------
			  STYLE TAB
			  --------------------------------------------------------------*/

		// Style - List
		$this->start_controls_section(
			'_ghostpool_section_style_list',
			[
				'label' => esc_html__('List', 'ghostpool-core'),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'format' => 'list',
				],
			]
		);

		$this->add_responsive_control(
			'list_gap',
			[
				'label' => esc_html__('Gap', 'ghostpool-core'),
				'description' => esc_html__('The gap between each list item.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
					'em' => [
						'min' => 0,
						'max' => 10,
						'step' => 0.1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 20,
				],
				'selectors' => [
					'{{WRAPPER}} .gp-items-list .gp-item:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style - Columns
		$this->start_controls_section(
			'_ghostpool_section_style_columns',
			[
				'label' => esc_html__('Columns', 'ghostpool-core'),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'format' => 'columns',
				],
			]
		);

		$this->add_responsive_control(
			'columns_number',
			[
				'label' => esc_html__('Column Number', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 10,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 3,
				],
				'tablet_default' => [
					'unit' => 'px',
					'size' => 2,
				],
				'mobile_default' => [
					'unit' => 'px',
					'size' => 1,
				],
			]
		);

		$this->add_responsive_control(
			'columns_h_gap',
			[
				'label' => esc_html__('Horizontal Gap', 'ghostpool-core'),
				'description' => esc_html__('Gap between each item.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
					'em' => [
						'min' => 0,
						'max' => 10,
						'step' => 0.1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => '%',
					'size' => 2,
				],
			]
		);

		$this->add_responsive_control(
			'columns_v_gap',
			[
				'label' => esc_html__('Vertical Gap', 'ghostpool-core'),
				'description' => esc_html__('Gap below each item.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
					'em' => [
						'min' => 0,
						'max' => 10,
						'step' => 0.1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 30,
				],
			]
		);

		$columns_individual_items_repeater = new \Elementor\Repeater();

		$columns_individual_items_repeater->add_responsive_control(
			'item_number',
			[
				'label' => esc_html__('Number', 'ghostpool-core'),
				'description' => esc_html__('The number of the item you want to target e.g. if you want to target the third item you would enter 3.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 1,
			]
		);

		$columns_individual_items_repeater->add_responsive_control(
			'item_custom_width',
			[
				'label' => esc_html__('Custom Width', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 500,
						'step' => 1,
					],
				],
			]
		);

		$this->add_control(
			'columns_individual_items',
			[
				'label' => esc_html__('Individual Items', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $columns_individual_items_repeater->get_controls(),
				'title_field' => esc_html__('Item ', 'ghostpool-core') . '{{{ item_number }}}',
				'prevent_empty' => false,
			]
		);

		$this->end_controls_section();

		// Style - Masonry
		$this->start_controls_section(
			'_ghostpool_section_style_masonry',
			[
				'label' => esc_html__('Masonry', 'ghostpool-core'),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'format' => 'masonry',
				],
			]
		);

		$this->add_responsive_control(
			'masonry_column_number',
			[
				'label' => esc_html__('Column Number', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 10,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 3,
				],
				/*'tablet_default' => [
								   'unit' => 'px',
								   'size' => 2,	
							   ],
							   'mobile_default' => [
								   'unit' => 'px',
								   'size' => 1,	
							   ],*/
			]
		);

		$this->add_responsive_control(
			'masonry_h_gap',
			[
				'label' => esc_html__('Horizontal Gap', 'ghostpool-core'),
				'description' => esc_html__('Gap between each item.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
					'em' => [
						'min' => 0,
						'max' => 10,
						'step' => 0.1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 20,
				],
				/*'mobile_default' => [
								   'unit' => 'px',
								   'size' => 0,	
							   ],*/
				'selectors' => [
					'{{WRAPPER}} .gp-item-gutter-size' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'masonry_v_gap',
			[
				'label' => esc_html__('Vertical Gap', 'ghostpool-core'),
				'description' => esc_html__('Gap between each item.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
					'em' => [
						'min' => 0,
						'max' => 10,
						'step' => 0.1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 20,
				],
				'selectors' => [
					'{{WRAPPER}} .gp-items-masonry .gp-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style - Grid
		$this->start_controls_section(
			'_ghostpool_section_style_grid',
			[
				'label' => esc_html__('Grid', 'ghostpool-core'),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'format' => 'grid',
				],
			]
		);

		$this->add_responsive_control(
			'grid_gap',
			[
				'label' => esc_html__('Grid Gap', 'ghostpool-core'),
				'description' => esc_html__('Gap between each grid item.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
					'em' => [
						'min' => 0,
						'max' => 10,
						'step' => 0.1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 10,
				],
				'selectors' => [
					'{{WRAPPER}} .gp-items-grid' => 'grid-column-gap: {{SIZE}}{{UNIT}}; grid-row-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'grid_columns',
			[
				'label' => esc_html__('Columns', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 100,
				'step' => 1,
				'default' => 3,
				'selectors' => [
					'{{WRAPPER}} .gp-items-grid' => '-ms-grid-columns: repeat({{VALUE}}, 1fr);grid-template-columns: repeat({{VALUE}}, 1fr);',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'grid_custom_column_widths',
			[
				'label' => esc_html__('Custom Column Widths', 'ghostpool-core'),
				'label_block' => true,
				'description' => esc_html__('You can specify custom widths for each column, separating each width with a space e.g. "50% 25% 25%".', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::TEXT,
				'selectors' => [
					'{{WRAPPER}} .gp-items-grid' => '-ms-grid-columns: {{VALUE}};grid-template-columns: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'grid_custom_column_widths_example_1',
			[
				'label' => esc_html__('Examples', 'ghostpool-core'),
				'show_label' => false,
				'raw' => esc_html__('Example 1: If you have 3 columns you could use "50% 25% 25%" where the first column takes up half the total width and the other 2 columns take up a quarter of the total width.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);
		$this->add_control(
			'grid_custom_column_widths_example_2',
			[
				'label' => esc_html__('Examples', 'ghostpool-core'),
				'show_label' => false,
				'raw' => esc_html__('Example 2: If you have 2 columns you could use "2fr 1fr" where the first column is 2 parts and the second column is 1 part of the whole width.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->add_responsive_control(
			'grid_rows',
			[
				'label' => esc_html__('Rows', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 100,
				'step' => 1,
				'default' => 2,
				'selectors' => [
					'{{WRAPPER}} .gp-items-grid' => '-ms-grid-rows: repeat({{VALUE}}, 1fr);grid-template-rows: repeat({{VALUE}}, 1fr);',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'grid_row_height_type',
			[
				'label' => esc_html__('Height Type', 'ghostpool-core'),
				'label_block' => true,
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'row_height' => esc_html__('Row Height', 'ghostpool-core'),
					'grid_height' => esc_html__('Grid Height', 'ghostpool-core'),
				],
				'default' => 'row_height',
			]
		);

		$this->add_responsive_control(
			'grid_row_height',
			[
				'label' => esc_html__('Row Height', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1000,
						'step' => 1,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
						'step' => 0.1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
				],
				'condition' => [
					'grid_row_height_type' => 'row_height',
				],
			]
		);

		$this->add_responsive_control(
			'grid_height',
			[
				'label' => esc_html__('Grid Height', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1000,
						'step' => 1,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
						'step' => 0.1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .gp-items-grid' => 'height: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'grid_row_height_type' => 'grid_height',
				],
			]
		);

		$this->add_responsive_control(
			'grid_custom_row_heights',
			[
				'label' => esc_html__('Custom Row Heights', 'ghostpool-core'),
				'label_block' => true,
				'description' => esc_html__('You can specify custom heights for each row, separating each height with a space e.g. "50% 25% 50%".', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::TEXT,
				'selectors' => [
					'{{WRAPPER}} .gp-items-grid' => '-ms-grid-rows: {{VALUE}};grid-template-rows: {{VALUE}};',
				],
				'condition' => [
					'grid_row_height_type' => 'grid_height',
				],
			]
		);

		$this->add_control(
			'grid_custom_row_heights_example_1',
			[
				'label' => esc_html__('Examples', 'ghostpool-core'),
				'show_label' => false,
				'raw' => esc_html__('Example 1: If you have 3 rows you could use "50% 25% 25%" where the first row takes up half the total height and the other 2 rows take up a quarter of the total height.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'condition' => [
					'grid_row_height_type' => 'grid_height',
				],
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);
		$this->add_control(
			'grid_custom_row_heights_example_2',
			[
				'label' => esc_html__('Examples', 'ghostpool-core'),
				'show_label' => false,
				'raw' => esc_html__('Example 2: If you have 2 rows you could use "2fr 1fr" where the first row is 2 parts and the second row is 1 part of the whole height.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'condition' => [
					'grid_row_height_type' => 'grid_height',
				],
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$grid_individual_items_repeater = new \Elementor\Repeater();

		$grid_individual_items_repeater->add_responsive_control(
			'item_number',
			[
				'label' => esc_html__('Number', 'ghostpool-core'),
				'description' => esc_html__('The number of the item you want to target e.g. if you want to target the third item you would enter 3.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 1,
			]
		);

		$grid_individual_items_repeater->add_responsive_control(
			'item_colspan',
			[
				'label' => esc_html__('Columns', 'ghostpool-core'),
				'description' => esc_html__('The number of columns this item should take up.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 1,
			]
		);

		$grid_individual_items_repeater->add_responsive_control(
			'item_rowspan',
			[
				'label' => esc_html__('Rows', 'ghostpool-core'),
				'description' => esc_html__('The number of rows this item should take up.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 1,
			]
		);

		$grid_individual_items_repeater->add_responsive_control(
			'item_order',
			[
				'label' => esc_html__('Order', 'ghostpool-core'),
				'description' => esc_html__('The order of this item relative to the other items e.g. if you want to move the second item in place of the first item you would enter -1.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::NUMBER,
			]
		);

		$this->add_control(
			'grid_individual_items',
			[
				'label' => esc_html__('Individual Items', 'ghostpool-core'),
				'description' => esc_html__('Control the size and position of individual items in the grid.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $grid_individual_items_repeater->get_controls(),
				'title_field' => esc_html__('Item ', 'ghostpool-core') . '{{{ item_number }}}',
				'prevent_empty' => false,
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		// Style - Pagination
		$this->start_controls_section(
			'_ghostpool_section_style_pagination',
			[
				'label' => esc_html__('Pagination', 'ghostpool-core'),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'pagination_format!' => array('disabled'),
				],
			]
		);

		$this->add_control(
			'pagination_style',
			[
				'label' => esc_html__('Style', 'ghostpool-core'),
				'label_block' => true,
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => ghostpool_data_type(array('data' => 'heading_style')),
				'default' => 'none',
				'condition' => [
					'pagination_format' => 'load-more-button',
				],
			]
		);

		$this->add_control(
			'pagination_full_width',
			[
				'label' => esc_html__('Full Width', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Yes', 'ghostpool-core'),
				'label_off' => esc_html__('No', 'ghostpool-core'),
				'return_value' => 'yes',
				'condition' => [
					'pagination_format' => 'load-more-button',
				],
				'selectors' => [
					'{{WRAPPER}} .gp-pagination-load-more-button a' => 'width: 100%;',
				],
			]
		);

		$this->add_control(
			'prev_arrow_icon',
			[
				'label' => esc_html__('Previous Icon', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::ICONS,
				'condition' => [
					'pagination_format' => 'arrows',
				],
			]
		);

		$this->add_control(
			'next_arrow_icon',
			[
				'label' => esc_html__('Next Icon', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::ICONS,
				'condition' => [
					'pagination_format' => 'arrows',
				],
			]
		);

		$this->add_control(
			'_ghostpool_divider_pagination_text_color',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
				'conditions' => [
					'relation' => 'or',
					'terms' => [
						[
							'name' => 'pagination_format',
							'operator' => '=',
							'value' => 'arrows',
						],
						[
							'name' => 'pagination_format',
							'operator' => '=',
							'value' => 'load-more-button',
						],
					]
				],
			]
		);

		$this->start_controls_tabs('_ghostpool_tabs_pagination');

		$this->start_controls_tab(
			'_ghostpool_tab_pagination_normal',
			[
				'label' => esc_html__('Normal', 'ghostpool-core'),
			]
		);

		$this->add_control(
			'pagination_text_color',
			[
				'label' => esc_html__('Text Color', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gp-pagination a.page-numbers, {{WRAPPER}} .gp-pagination span.page-numbers, {{WRAPPER}} .gp-pagination-load-more-button a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'pagination_typography',
				'label' => esc_html__('Typography', 'ghostpool-core'),
				'selector' => '{{WRAPPER}} .gp-pagination, {{WRAPPER}} .gp-pagination-load-more-button a, {{WRAPPER}} .gp-pagination-arrows .gp-icons-default:before',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'pagination_shadow',
				'label' => esc_html__('Text Shadow', 'ghostpool-core'),
				'selector' => '{{WRAPPER}} .gp-pagination a.page-numbers, {{WRAPPER}} .gp-pagination span.page-numbers, {{WRAPPER}} .gp-pagination-load-more-button a',
			]
		);

		$this->add_control(
			'pagination_background_color',
			[
				'label' => esc_html__('Background Color', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gp-pagination a.page-numbers, 
								{{WRAPPER}} .gp-pagination span.page-numbers, {{WRAPPER}} .gp-pagination-load-more-button a, {{WRAPPER}} .gp-pagination-load-more-button a:before, {{WRAPPER}} .gp-pagination-load-more-button a:after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name' => 'pagination_border',
				'label' => esc_html__('Border', 'ghostpool-core'),
				'selector' => '{{WRAPPER}} .gp-pagination a.page-numbers, {{WRAPPER}} .gp-pagination-load-more-button a:not(.gp-style-horizontal-line-through), {{WRAPPER}} .gp-style-horizontal-line-through:after, {{WRAPPER}} .gp-style-horizontal-line-through:before',
				'condition' => [
					'pagination_style' => ['none', 'horizontal-line-through'],
				],
			]
		);

		$this->add_responsive_control(
			'pagination_border_radius',
			[
				'label' => esc_html__('Border Radius', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors' => [
					'{{WRAPPER}} .gp-pagination a.page-numbers, {{WRAPPER}} .gp-pagination span.page-numbers, {{WRAPPER}} .gp-pagination-load-more-button a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'pagination_style' => ['none', 'horizontal-line-through'],
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'pagination_box_shadow',
				'label' => esc_html__('Box Shadow', 'ghostpool-core'),
				'selector' => '{{WRAPPER}} .gp-pagination a.page-numbers, {{WRAPPER}} .gp-pagination span.page-numbers, {{WRAPPER}} .gp-pagination-load-more-button a, {{WRAPPER}} .gp-pagination-load-more-button a:before, {{WRAPPER}} .gp-pagination-load-more-button a:after'
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'_ghostpool_tab_pagination_hover',
			[
				'label' => esc_html__('Hover', 'ghostpool-core'),
			]
		);
		$this->add_control(
			'pagination_text_hover_color',
			[
				'label' => esc_html__('Text Color', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gp-pagination a.page-numbers:hover, {{WRAPPER}} .gp-pagination .current.page-numbers, {{WRAPPER}} .gp-pagination-load-more-button a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'pagination_typography_hover',
				'label' => esc_html__('Typography', 'ghostpool-core'),
				'selector' => '{{WRAPPER}} .gp-pagination a.page-numbers:hover, {{WRAPPER}} .gp-pagination .current.page-numbers, {{WRAPPER}} .gp-pagination-load-more-button a:hover',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'pagination_shadow_hover',
				'label' => esc_html__('Text Shadow', 'ghostpool-core'),
				'selector' => '{{WRAPPER}} .gp-pagination a.page-numbers:hover, {{WRAPPER}} .gp-pagination .current.page-numbers, {{WRAPPER}} .gp-pagination-load-more-button a:hover',
			]
		);

		$this->add_control(
			'pagination_background_hover_color',
			[
				'label' => esc_html__('Background Color', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gp-pagination a.page-numbers:hover, {{WRAPPER}} .gp-pagination .current.page-numbers, {{WRAPPER}} .gp-pagination-load-more-button a:hover, {{WRAPPER}} .gp-pagination-load-more-button a:hover:before, {{WRAPPER}} .gp-pagination-load-more-button a:hover:after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_border_hover_color',
			[
				'label' => esc_html__('Border Color', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gp-pagination a.page-numbers:hover, {{WRAPPER}} .gp-pagination .current.page-numbers, {{WRAPPER}} .gp-pagination-load-more-button a:hover' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'pagination_style' => ['none', 'horizontal-line-through'],
				],
			]
		);

		$this->add_responsive_control(
			'pagination_border_radius_hover',
			[
				'label' => esc_html__('Border Radius', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors' => [
					'{{WRAPPER}} .gp-pagination a.page-numbers:hover, {{WRAPPER}} .gp-pagination .current.page-numbers, {{WRAPPER}} .gp-pagination-load-more-button a:hover, {{WRAPPER}} .gp-pagination-load-more-button a:hover:before, {{WRAPPER}} .gp-pagination-load-more-button a:hover:after' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'pagination_style' => ['none', 'horizontal-line-through'],
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'pagination_box_shadow_hover',
				'label' => esc_html__('Box Shadow', 'ghostpool-core'),
				'selector' => '{{WRAPPER}} .gp-pagination a.page-numbers:hover, {{WRAPPER}} .gp-pagination .current.page-numbers, {{WRAPPER}} .gp-pagination-load-more-button a:hover, {{WRAPPER}} .gp-pagination-load-more-button a:hover:before, {{WRAPPER}} .gp-pagination-load-more-button a:hover:after'
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'pagination_button_alignment',
			[
				'label' => esc_html__('Text Alignment', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__('Left', 'ghostpool-core'),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'ghostpool-core'),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__('Right', 'ghostpool-core'),
						'icon' => 'eicon-text-align-right',
					],
				],
				'toggle' => true,
				'selectors_dictionary' => [
					'left' => 'text-align: start;',
					'center' => 'text-align: center;',
					'right' => 'text-align: end;',
				],
				'selectors' => [
					'{{WRAPPER}} .gp-pagination-load-more-button' => '{{VALUE}}',
				],
				'condition' => [
					'pagination_format' => 'load-more-button',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'pagination_arrow_alignment',
			[
				'label' => esc_html__('Alignment', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::CHOOSE,
				'options' => [
					'flex-start' => [
						'title' => esc_html__('Left', 'ghostpool-core'),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'ghostpool-core'),
						'icon' => 'eicon-text-align-center',
					],
					'flex-end' => [
						'title' => esc_html__('Right', 'ghostpool-core'),
						'icon' => 'eicon-text-align-right',
					],
					'space-between' => [
						'title' => esc_html__('Space Between', 'ghostpool-core'),
						'icon' => 'eicon-text-align-justify',
					],
				],
				'default' => 'flex-start',
				'toggle' => true,
				'selectors' => [
					'{{WRAPPER}} .gp-pagination-arrows ul, {{WRAPPER}} .gp-pagination-numbers ul' => 'justify-content: {{VALUE}};',
				],
				'condition' => [
					'pagination_format' => ['arrows', 'numbers'],
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'_ghostpool_divider_pagination_width',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->add_responsive_control(
			'pagination_width',
			[
				'label' => esc_html__('Width', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 200,
						'step' => 1,
					],
					'%' => [
						'min' => 1,
						'max' => 100,
						'step' => 1,
					],
				],
				/*REMOVE'default' => [
								   'unit' => 'px',
								   'size' => 42,
							   ],*/
				'selectors' => [
					'{{WRAPPER}} .gp-pagination li .page-numbers' => 'width: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'pagination_format' => ['arrows', 'numbers'],
				],
			]
		);

		$this->add_responsive_control(
			'pagination_height',
			[
				'label' => esc_html__('Height', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 200,
						'step' => 1,
					],
					'%' => [
						'min' => 1,
						'max' => 100,
						'step' => 1,
					],
				],
				/*REMOVE'default' => [
								   'unit' => 'px',
								   'size' => 42,
							   ],*/
				'selectors' => [
					'{{WRAPPER}} .gp-pagination li .page-numbers' => 'height: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'pagination_format' => ['arrows', 'numbers'],
				],
			]
		);

		$this->add_responsive_control(
			'pagination_padding',
			[
				'label' => esc_html__('Button Padding', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				/*REMOVE'default' => [
								   'top' => 0,
								   'right' => 5,							
								   'bottom' => 0,
								   'left' => 0,
								   'unit' => 'px',
							   ],*/
				'selectors' => [
					'body:not(.rtl) {{WRAPPER}} .gp-load-more-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'body.rtl {{WRAPPER}} .gp-load-more-button' => 'padding: {{TOP}}{{UNIT}} {{LEFT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{RIGHT}}{{UNIT}};',
				],
				'condition' => [
					'pagination_format' => 'load-more-button',
				],
			]
		);

		$this->add_responsive_control(
			'pagination_margin',
			[
				'label' => esc_html__('Margin', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				/*REMOVE'default' => [
								   'top' => 0,
								   'right' => 5,							
								   'bottom' => 0,
								   'left' => 0,
								   'unit' => 'px',
							   ],*/
				'selectors' => [
					'body:not(.rtl) {{WRAPPER}} .gp-pagination a.page-numbers, body:not(.rtl) {{WRAPPER}} .gp-pagination span.page-numbers, body:not(.rtl) {{WRAPPER}} .gp-pagination-load-more-button a' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'body.rtl {{WRAPPER}} .gp-pagination a.page-numbers, body.rtl {{WRAPPER}} .gp-pagination span.page-numbers, body.rtl {{WRAPPER}} .gp-pagination-load-more-button a' => 'margin: {{TOP}}{{UNIT}} {{LEFT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{RIGHT}}{{UNIT}};',
				],
				'condition' => [
					'pagination_format' => ['arrows', 'numbers'],
				],
			]
		);

		$this->add_control(
			'pagination_position',
			[
				'label' => esc_html__('Position', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'relative' => esc_html__('Default', 'ghostpool-core'),
					'absolute' => esc_html__('Absolute', 'ghostpool-core'),
				],
				'default' => 'relative',
				'selectors' => [
					'{{WRAPPER}} .gp-pagination-arrows' => 'position: {{VALUE}}',
				],
				'condition' => [
					'pagination_format' => 'arrows',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'pagination_gap',
			[
				'label' => esc_html__('Gap', 'ghostpool-core'),
				'description' => esc_html__('The gap between the items and pagination.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 200,
						'step' => 1,
					],
					'em' => [
						'min' => 0,
						'max' => 10,
						'step' => 0.1,
					],
				],
				/*REMOVE'default' => [
								   'unit' => 'px',
								   'size' => 40,	
							   ],*/
				'selectors' => [
					'{{WRAPPER}} .gp-pagination' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'pagination_position' => 'relative',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'pagination_horizontal_position',
			[
				'label' => esc_html__('Horizontal Position', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => -500,
						'max' => 500,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .gp-pagination' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'pagination_position' => 'absolute',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		// Style - No Items Found Message
		$this->start_controls_section(
			'_ghostpool_section_style_no_items_found_message',
			[
				'label' => esc_html__('No Items Found Message', 'ghostpool-core'),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'no_items_found_message!' => '',
				],
			]
		);

		$this->add_control(
			'no_items_found_message_text_color',
			[
				'label' => esc_html__('Text Color', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gp-no-items-found-message' => 'color: {{VALUE}};',
				]
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'no_items_found_message_typography',
				'label' => esc_html__('Typography', 'ghostpool-core'),
				'selector' => '{{WRAPPER}} .gp-no-items-found-message',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'no_items_found_message_text_shadow',
				'label' => esc_html__('Text Shadow', 'ghostpool-core'),
				'selector' => '{{WRAPPER}} .gp-no-items-found-message',
			]
		);

		$this->add_responsive_control(
			'no_items_found_message_text_alignment',
			[
				'label' => esc_html__('Text Alignment', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__('Left', 'ghostpool-core'),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'ghostpool-core'),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__('Right', 'ghostpool-core'),
						'icon' => 'eicon-text-align-right',
					],
				],
				'selectors_dictionary' => [
					'left' => 'text-align: start;',
					'center' => 'text-align: center;',
					'right' => 'text-align: end;',
				],
				'selectors' => [
					'{{WRAPPER}} .gp-no-items-found-message' => '{{VALUE}}',
				],
				'toggle' => false,
			]
		);

		$this->add_control(
			'no_items_found_message_background_color',
			[
				'label' => esc_html__('Background Color', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .gp-no-items-found-message' => 'background-color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name' => 'no_items_found_message_border',
				'label' => esc_html__('Border', 'ghostpool-core'),
				'selector' => '{{WRAPPER}} .gp-no-items-found-message',
			]
		);

		$this->add_responsive_control(
			'no_items_found_message_border_radius',
			[
				'label' => esc_html__('Border Radius', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors' => [
					'{{WRAPPER}} .gp-no-items-found-message' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'no_items_found_message_padding',
			[
				'label' => esc_html__('Padding', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors' => [
					'body:not(.rtl) {{WRAPPER}} .gp-no-items-found-message' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'body.rtl {{WRAPPER}} .gp-no-items-found-message' => 'padding: {{TOP}}{{UNIT}} {{LEFT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{RIGHT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'no_items_found_message_margin',
			[
				'label' => esc_html__('Margin', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors' => [
					'body:not(.rtl) {{WRAPPER}} .gp-no-items-found-message' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'body.rtl {{WRAPPER}} .gp-no-items-found-message' => 'margin: {{TOP}}{{UNIT}} {{LEFT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{RIGHT}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();

		// Style - Loader
		$this->start_controls_section(
			'_ghostpool_section_style_loader',
			[
				'label' => esc_html__('Items Loader', 'ghostpool-core'),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'loader_icon_type',
			[
				'label' => esc_html__('Icon Type', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'none' => esc_html__('None', 'ghostpool-core'),
					'icon' => esc_html__('Icon', 'ghostpool-core'),
					'image' => esc_html__('Image', 'ghostpool-core'),
				],
				'default' => 'none',
				'selector' => '{{WRAPPER}} .gp-loader-icon',
				'condition' => [
				],
			]
		);

		$this->add_control(
			'loader_icon',
			[
				'label' => esc_html__('Icon', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::ICONS,
				'default' => [
					'value' => 'fas fa-star',
					'library' => 'solid',
				],
				'condition' => [
					'loader_icon_type' => 'icon',
				],
			]
		);

		$this->add_control(
			'loader_icon_color',
			[
				'label' => esc_html__('Icon Color', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'condition' => [
					'loader_icon_type' => 'icon',
				],
				'selectors' => [
					'{{WRAPPER}} .gp-loader-icon' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'loader_icon_size',
			[
				'label' => esc_html__('Size', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 200,
						'step' => 1,
					],
				],
				'condition' => [
					'loader_icon_type' => 'icon',
				],
				'selectors' => [
					'{{WRAPPER}} .gp-loader-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'loader_image',
			[
				'label' => esc_html__('Image', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'condition' => [
					'loader_icon_type' => 'image',
				],
			]
		);

		$this->add_responsive_control(
			'loader_image_width',
			[
				'label' => esc_html__('Image Width', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 2000,
						'step' => 1,
					],
					'%' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'condition' => [
					'loader_icon_type' => 'image',
				],
				'selectors' => [
					'{{WRAPPER}} .gp-loader img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'loader_image_height',
			[
				'label' => esc_html__('Image Height', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em'],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 2000,
						'step' => 1,
					],
					'%' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'condition' => [
					'loader_icon_type' => 'image',
				],
				'selectors' => [
					'{{WRAPPER}} .gp-loader img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'loader_icon_animation',
			[
				'label' => esc_html__('Icon Animation', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'horizontal-vertical-flip' => esc_html__('Flip', 'ghostpool-core'),
					'spin' => esc_html__('Spin', 'ghostpool-core'),
					'none' => esc_html__('None', 'ghostpool-core'),
				],
				'default' => 'horizontal-vertical-flip',
				'condition' => [
					'loader_icon_type' => ['image', 'icon'],
				],
			]
		);

		$this->add_responsive_control(
			'loader_vertical_alignment',
			[
				'label' => esc_html__('Vertical Alignment', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::CHOOSE,
				'options' => [
					'flex-start' => [
						'title' => esc_html__('Left', 'ghostpool-core'),
						'icon' => 'eicon-v-align-top',
					],
					'center' => [
						'title' => esc_html__('Center', 'ghostpool-core'),
						'icon' => 'eicon-v-align-middle',
					],
					'flex-end' => [
						'title' => esc_html__('Right', 'ghostpool-core'),
						'icon' => 'eicon-v-align-bottom',
					],
				],
				'toggle' => true,
				'selectors' => [
					'{{WRAPPER}} .gp-loader' => 'align-items: {{VALUE}};',
				],
				'condition' => [
					'loader_icon_type' => ['image', 'icon'],
				],
			]
		);

		$this->add_responsive_control(
			'loader_padding',
			[
				'label' => esc_html__('Padding', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors' => [
					'body:not(.rtl) {{WRAPPER}} .gp-loader' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'body.rtl {{WRAPPER}} .gp-loader' => 'padding: {{TOP}}{{UNIT}} {{LEFT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{RIGHT}}{{UNIT}};',
				],
				'condition' => [
					'loader_icon_type' => ['image', 'icon'],
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'_ghostpool_section_style_other',
			[
				'label' => esc_html__('Other', 'ghostpool-core'),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'hover_fix',
			[
				'label' => esc_html__('Hover Fix', 'ghostpool-core'),
				'description' => esc_html__('Enable this option if you experience issues with images flickering when hovering over them.', 'ghostpool-core'),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Yes', 'ghostpool-core'),
				'label_off' => esc_html__('No', 'ghostpool-core'),
				'return_value' => 'yes',
				'selectors' => [
					'{{WRAPPER}} .gp-item' => '-webkit-mask-image: -webkit-radial-gradient(white, black);',
				],
			]
		);

		$this->end_controls_section();

	}

	public function title_filter($where, $wp_query)
	{
		global $wpdb;
		if ($search_term = $wp_query->get('search_title')) {
			$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql(like_escape($search_term)) . '%\'';
		}
		return $where;
	}

	protected function render()
	{

		// Is item loop
		$GLOBALS['ghostpool_item_loop'] = true;

		// Get settings
		$settings = $this->get_settings_for_display();

		// Load query args
		require (plugin_dir_path(__FILE__) . 'inc/query-args.php');

		if ('yes' === $settings['current_query_items'] && !wp_doing_ajax()) {

			global $wp_query;
			$query = $wp_query;

		} else {
			// Query						
			$query_args = array(
				'post_status' => $settings['post_statuses'],
				'post_type' => $settings['post_types'],
				'post__in' => $posts_pages_array,
				'post__not_in' => $excluded_post_id,
				'offset' => $settings['offset'],
				'tax_query' => $tax_query,
				'author' => $author_query,
				'date_query' => $date_query,
				's' => $search_query,
				'meta_query' => $meta_query,
				'orderby' => isset($sorting['order_by']) ? $sorting['order_by'] : '',
				'order' => isset($sorting['order']) ? $sorting['order'] : '',
				'meta_key' => isset($sorting['meta_key']) ? $sorting['meta_key'] : '',
				'posts_per_page' => $settings['number'],
				'no_found_rows' => ('disabled' === $settings['pagination_format']) ? true : false,
				'paged' => ('disabled' === $settings['pagination_format']) ? 1 : $current_page,
				'ignore_sticky_posts' => is_home() ? false : true,
			);

			// Allows search via title only
			if ($search_term) {
				$query_args['search_title'] = $search_term;
				add_filter('posts_where', array($this, 'title_filter'), 10, 2);
			}

			$query = new WP_Query(apply_filters('ghostpool_items_query', $query_args, $settings, $sorting));

			// Removes search via title filter
			if ($search_term) {
				remove_filter('posts_where', array($this, 'title_filter'), 10);
			}
		}

		// Generate classes
		$unique_selector = uniqid('gp-element-items-');
		$this->add_render_attribute(
			'attributes',
			'class',
			[
				'gp-element-items',
				$unique_selector,
				'gp-items-' . $settings['format'],
			]
		);

		ob_start();

		$custom_css = '';

		if (isset($settings['prev_arrow_icon']['value']) && !empty($settings['prev_arrow_icon']['value'])) {
			$prev_icon = $settings['prev_arrow_icon']['value'];
		} else {
			$prev_icon = 'gp-icons-default';
		}

		if (isset($settings['next_arrow_icon']['value']) && '' !== $settings['next_arrow_icon']['value']) {
			$next_icon = $settings['next_arrow_icon']['value'];
		} else {
			$next_icon = 'gp-icons-default';
		}

		echo '<div class="gp-element-items-wrapper">';

		echo '<div class="gp-items-gap-filler">';

		// Item loader					
		echo ghostpool_loader($settings);

		echo '
			<div ' . $this->get_render_attribute_string('attributes') . '
			data-post-id="' . $post_id . '" 
			data-term-id="' . $term_id . '"
			data-post-types="' . $post_types . '"
			data-post-subtypes="' . $post_subtypes . '"
			data-posts-pages="' . $posts_pages . '"
			data-post-statuses="' . $post_statuses . '"
			data-offset="' . $settings['offset'] . '"
			data-sort="' . $settings['sort'] . '"
			data-current-query-sort="' . $sort . '"
			data-limit-sort-values="' . $settings['limit_sort_values'] . '"
			data-limit-release-dates="' . $settings['limit_release_dates'] . '"
			data-number="' . $settings['number'] . '"
			data-tax-query="' . esc_attr( $tax_query_json ) . '"
			data-tax-query-relationship="' . $settings['tax_query_relationship'] . '"
			data-meta-query="' . esc_attr( $meta_query_json ) . '"
			data-meta-query-relationship="' . $settings['meta_query_relationship'] . '"
			data-current-query-items="' . $settings['current_query_items'] . '"
			data-current-query="' . $current_query . '"
			data-user-id="' . $author_query . '"
			data-current-hub-associated-items="' . $settings['current_hub_associated_items'] . '"
			data-followed-items="' . $settings['followed_items'] . '"
			data-matching-terms-items="' . $settings['matching_terms_items'] . '"
			data-matching-terms-taxonomies="' . $matching_terms_taxonomies . '"
			data-matching-terms-relationship="' . $settings['matching_terms_relationship'] . '"
			data-exclude-current-item="' . $settings['exclude_current_item'] . '"
			data-format="' . $settings['format'] . '"
			data-item-template-primary="' . $settings['item_template_primary'] . '"
			data-item-template-secondary="' . $settings['item_template_secondary'] . '"
			data-pagination-format="' . $settings['pagination_format'] . '"
			data-disable-ajax-pagination="' . $settings['disable_ajax_pagination'] . '"
			data-pagination-style="' . $settings['pagination_style'] . '"
			data-prev-arrow-icon="' . $prev_icon . '"
			data-next-arrow-icon="' . $next_icon . '"
			data-no-items-found-message="' . esc_attr($settings['no_items_found_message']) . '"
			data-max-pages="' . $query->max_num_pages . '"
			data-search="' . get_search_query() . '">
		';

		// Debug
		/*if ( current_user_can( 'edit_posts' ) ) { 
						   print( '<pre>' . print_r( $query_args, true ) . '</pre>' );
					   }*/
		/*if ( current_user_can( 'edit_posts' ) ) { 
						   if ( isset( $query ) ) { print( '<pre>' . print_r( $query, true ) . '</pre>' ); }
					   }*/

		if ($query->have_posts() && true === $run_query):

			$counter = 1;

			if ('masonry' === $settings['format'] && !\Elementor\Plugin::$instance->editor->is_edit_mode() && !\Elementor\Plugin::$instance->preview->is_preview_mode()) {
				echo '<div class="gp-item-gutter-size"></div>';
			}

			while ($query->have_posts()):
				$query->the_post();

				if ('grid' === $settings['format'] or 'columns' === $settings['format']) {
					$custom_css .= '.' . $unique_selector . ' .gp-item-' . $counter . '{-ms-flex-order: ' . $counter . ';order: ' . $counter . ';}';
				}

				$classes = array('gp-item', 'gp-item-' . $counter);
				if ('gp-none' === $settings['item_template_primary'] && defined('GHOSTPOOL_THEME_VERSION')) {
					$classes[] = 'gp-basic-post-item';
				}

				echo '<div class="' . implode(' ', get_post_class($classes)) . '">';

				$individual_template = array_search($counter, array_column($settings['individual_items'], 'item_number'));

				if (false !== $individual_template) {

					echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display(apply_filters('wpml_object_id', (int) $settings['individual_items'][$individual_template]['item_template'], 'gp_theme_template', TRUE), $is_editor);

				} elseif ('gp-none' !== $settings['item_template_secondary'] && $counter > 1) {

					echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display(apply_filters('wpml_object_id', (int) $settings['item_template_secondary'], 'gp_theme_template', TRUE), $is_editor);

				} elseif ('gp-none' !== $settings['item_template_primary']) {

					echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display(apply_filters('wpml_object_id', (int) $settings['item_template_primary'], 'gp_theme_template', TRUE), $is_editor);

				} else {

					// Link
					if ('link' === get_post_format(get_the_ID()) && get_post_meta(get_the_ID(), 'gp_link', true)) {
						$link = get_post_meta(get_the_ID(), 'gp_link', true);
						$this->add_link_attributes('url', $link, true);
					} else {
						$link = $this->add_render_attribute('url', 'href', get_permalink(get_the_ID()), true);
					}

					if (has_post_thumbnail()) {
						echo '<a ' . $this->get_render_attribute_string('url') . ' class="gp-basic-image-link">' . get_the_post_thumbnail(get_the_ID(), 'thumbnail') . '</a>';
					}

					echo '<div class="gp-basic-post-item-text">';
					echo '<h2><a ' . $this->get_render_attribute_string('url') . '>' . get_the_title() . '</a></h2>';
					echo '<div class="gp-basic-post-item-excerpt">' . get_the_excerpt() . '</div>';
					echo '<div class="gp-basic-post-item-metas">';
					echo '<div class="gp-basic-post-item-meta">' . get_the_author_meta('display_name') . '</div>';
					echo '<div class="gp-basic-post-item-meta"><time datetime="' . get_the_date('c') . '">' . get_the_time(get_option('date_format')) . '</time></div>';
					echo '</div>';
					echo '</div>';

				}

				echo '</div>';

				$counter++;

			endwhile;

		else:

			if ($settings['no_items_found_message']) {
				echo '<div class="gp-no-items-found-message">' . wp_kses_post(stripslashes($settings['no_items_found_message'])) . '</div>';
			} elseif (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
				echo '<div class="gp-elemetor-template-notice">' . esc_html__('Items Element: No items found, please check on the frontend to confirm your query is working correctly.', 'ghostpool-core') . '</div>';
			}

		endif;

		echo '</div>';

		echo '</div>';

		if (true === $run_query) {
			if (wp_doing_ajax() or 'yes' === $settings['current_query_items']) {
				$max_pages = $query->max_num_pages;
			} else {
				$offset = ('' !== $settings['offset'] && NULL !== $settings['offset']) ? $settings['offset'] : 0;
				$number = ('' !== $settings['number'] && NULL !== $settings['number']) ? $settings['number'] : get_option('posts_per_page');
				if ('' !== $query->found_posts) {
					$max_pages = ceil(($query->found_posts - (int) $offset) / (int) $number);
				} else {
					$max_pages = 1;
				}
			}
			echo ghostpool_pagination($max_pages, $current_page, $settings['pagination_format'], $settings['disable_ajax_pagination'], $settings['pagination_style'], $prev_icon, $next_icon, $settings['load_more_button_label']);
		}

		echo '</div>';

		// Custom CSS
		$align = is_rtl() ? 'right' : 'left';

		$active_breakpoints = \Elementor\Plugin::$instance->breakpoints->get_breakpoints_config();

		$active_breakpoints['original'] = [
			'value' => '',
			'is_enabled' => true,
		];

		$active_breakpoints = array_reverse($active_breakpoints);

		//$column_number = null;
		$h_gap_size = null;
		$h_gap_unit = null;
		$v_gap_size = null;
		$v_gap_unit = null;

		foreach ($active_breakpoints as $key => $data) {

			if (false === $data['is_enabled']) {
				continue;
			}

			if ('original' === $key) {
				$name = '';
				$data_attr_key = 'desktop';
			} else {
				$name = '_' . $key;
				$data_attr_key = $key;
			}

			$data_attr = '[data-elementor-device-mode="' . $data_attr_key . '"] ';

			if (isset($data['value']) && '' !== $data['value']) {
				$custom_css .= '@media only screen and (' . $data['direction'] . '-width: ' . $data['value'] . 'px) {';
			}

			// Inline CSS - Grid Rows
			if (isset($settings['grid_row_height' . $name]['size']) && '' != $settings['grid_row_height' . $name]['size']) {
				
				$grid_rows = isset( $settings['grid_rows' . $name] ) ? $settings['grid_rows' . $name] : 2; 
				$custom_css .= '.elementor-element.elementor-element-' . $this->get_id() . ' .' . $unique_selector . '.gp-items-grid{grid-template-rows: repeat(' . $grid_rows . ',' . $settings['grid_row_height' . $name]['size'] . $settings['grid_row_height' . $name]['unit'] . ') !important;}';
			}

			// Inline CSS - Columns
			/*if ( isset( $settings['columns_number' . $name ]['size'] ) && '' !== $settings['columns_number' . $name ]['size'] ) {
							echo '$column_number ' . $settings['columns_number' . $name ]['size'];
							$column_number = $settings['columns_number' . $name ]['size'];
							
							echo '$column_number ' . $column_number;
						}*/

			// Readd default column number as removed by Elementor 
			if ('original' === $key && !isset($settings['columns_number'])) {
				$column_number = 3;
			} elseif ('tablet' === $key && !isset($settings['columns_number_tablet']['size'])) {
				$column_number = 2;
			} elseif ('mobile' === $key && !isset($settings['columns_number_mobile']['size'])) {
				$column_number = 1;
			} else {
				$column_number = isset($settings['columns_number' . $name]['size']) ? $settings['columns_number' . $name]['size'] : 1;
			}

			if (isset($settings['columns_h_gap' . $name]['size']) && '' !== $settings['columns_h_gap' . $name]['size']) {
				$h_gap_size = $settings['columns_h_gap' . $name]['size'];
				$h_gap_unit = $settings['columns_h_gap' . $name]['unit'];
			}

			if (isset($settings['columns_v_gap' . $name]['size']) && '' !== $settings['columns_v_gap' . $name]['size']) {
				$v_gap_size = $settings['columns_v_gap' . $name]['size'];
				$v_gap_unit = $settings['columns_v_gap' . $name]['unit'];
			}

			$custom_css .= $data_attr . '.elementor-element.elementor-element-' . $this->get_id() . ' .' . $unique_selector . '.gp-items-columns .gp-item {width: calc((100% - ((' . $column_number . ' - 1) * ' . $h_gap_size . $h_gap_unit . ')) / ' . $column_number . ');
				' . 'margin-' . $align . ': ' . $h_gap_size . $h_gap_unit . ';}';

			$custom_css .= $data_attr . '.elementor-element.elementor-element-' . $this->get_id() . ' .' . $unique_selector . '.gp-items-columns .gp-item:nth-of-type(' . $column_number . 'n+1) {
				' . 'margin-' . $align . ': ' . $h_gap_size . $h_gap_unit . ';}';

			$custom_css .= $data_attr . '.elementor-element.elementor-element-' . $this->get_id() . ' .' . $unique_selector . '.gp-items-columns .gp-item:nth-of-type(' . $column_number . 'n+1) {
				' . 'margin-' . $align . ': 0; clear: ' . $align . ' !important;}';

			$custom_css .= $data_attr . '.elementor-element.elementor-element-' . $this->get_id() . ' .' . $unique_selector . '.gp-items-columns .gp-item:nth-last-child(-n+' . $column_number . ') {margin-bottom: ' . $v_gap_size . $v_gap_unit . '}';

			$custom_css .= $data_attr . '.elementor-element.elementor-element-' . $this->get_id() . ' .' . $unique_selector . '.gp-items-columns .gp-item {margin-bottom: ' . $v_gap_size . $v_gap_unit . '}';

			//$custom_css .= $data_attr . '.elementor-element.elementor-element-' . $this->get_id() . ' .' . $unique_selector . '.gp-items-columns .gp-item:nth-last-child(-n+' . $column_number . ') { margin-bottom: 0; }';	

			if (isset($settings['columns_individual_items']) && !empty($settings['columns_individual_items'])) {
				foreach ($settings['columns_individual_items'] as $ind_item) {
					if (isset($ind_item['item_number' . $name]) && '' !== $ind_item['item_number' . $name]) {
						$custom_css .= '.elementor-element.elementor-element-' . $this->get_id() . ' .' . $unique_selector . '.gp-items-columns .gp-item-' . $ind_item['item_number' . $name] . '{width: ' . $ind_item['item_custom_width' . $name]['size'] . $ind_item['item_custom_width' . $name]['unit'] . '}';
					}
				}
			}

			// Inline CSS - Masonry
			if (isset($settings['masonry_column_number' . $name]['size']) && '' !== ($settings['masonry_column_number' . $name]['size'])) {
				$column_number = $settings['masonry_column_number' . $name]['size'];
			}

			if (isset($settings['masonry_h_gap' . $name]['size']) && '' !== $settings['masonry_h_gap' . $name]['size']) {
				$h_gap_size = $settings['masonry_h_gap' . $name]['size'];
			}

			if (isset($settings['masonry_h_gap' . $name]['unit']) && '' !== $settings['masonry_h_gap' . $name]['unit']) {
				$h_gap_unit = $settings['masonry_h_gap' . $name]['unit'];
			}

			$custom_css .= '.elementor-element.elementor-element-' . $this->get_id() . ' .' . $unique_selector . '.gp-items-masonry .gp-item { width: calc((100% / ' . $column_number . ') - ((' . $h_gap_size . $h_gap_unit . ' * (' . $column_number . ' - 1)) / ' . $column_number . '));}';


			// Inline CSS - Individual items CSS
			if (!empty($settings['grid_individual_items'])) {

				$ind_items = $settings['grid_individual_items'];

				foreach ($ind_items as $ind_item) {

					if (isset($ind_item['item_number' . $name]) && '' !== $ind_item['item_number' . $name]) {

						$custom_css .= '.' . $unique_selector . ' .gp-item-' . $ind_item['item_number' . $name] . '{';

						if ($ind_item['item_colspan' . $name]) {
							$custom_css .= '-ms-grid-column-end: span ' . $ind_item['item_colspan' . $name] . ';grid-column-end: span ' . $ind_item['item_colspan' . $name] . ';';
						}

						if ($ind_item['item_rowspan' . $name]) {
							$custom_css .= '-ms-grid-row-end: span ' . $ind_item['item_rowspan' . $name] . ';grid-row-end: span ' . $ind_item['item_rowspan' . $name] . ';';
						}

						if ($ind_item['item_order' . $name]) {
							$custom_css .= '-ms-flex-order: ' . $ind_item['item_order' . $name] . ' !important;order: ' . $ind_item['item_order' . $name] . ' !important;';
						}

						$custom_css .= '}';

					}

				}

			}

			if (isset($data['value']) && '' !== $data['value']) {
				$custom_css .= '}';
			}

		}

		if ($custom_css) {
			echo '<style>' . preg_replace('/\s\s+/', ' ', $custom_css) . '</style>';
		}

		wp_reset_postdata();

		$output = ob_get_contents();
		ob_end_clean();

		$GLOBALS['ghostpool_item_loop'] = null;

		echo apply_filters('ghostpool_render_items', $output, $settings, $this);

	}

}
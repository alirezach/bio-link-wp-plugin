<?php
/**
 * Bio Link - Elementor Widget
 *
 * Instagram bio-link widget for Elementor (free version compatible):
 * - import the Instagram bio theme into any page
 * - edit bio in the editor, set links
 * - toggle follower count
 * - social links with icons (telegram, x, linkedin, instagram, whatsapp, bluesky)
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bio_Link_Elementor {

	public function __construct() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'add_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
		// Legacy hook for Elementor < 3.5.
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widget_legacy' ) );
	}

	public function add_category( $elements_manager ) {
		if ( method_exists( $elements_manager, 'add_category' ) ) {
			$elements_manager->add_category( 'bio-link', array(
				'title' => __( 'Bio Link', 'bio-link' ),
				'icon'  => 'eicon-instagram-embed',
			) );
		}
	}

	public function register_widget( $widgets_manager ) {
		if ( class_exists( 'Bio_Link_Elementor_Widget' ) && method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( new Bio_Link_Elementor_Widget() );
		}
	}

	public function register_widget_legacy( $widgets_manager ) {
		if ( class_exists( 'Bio_Link_Elementor_Widget' ) && method_exists( $widgets_manager, 'register_widget_type' ) ) {
			$widgets_manager->register_widget_type( new Bio_Link_Elementor_Widget() );
		}
	}
}

// The widget class extends Elementor's base — only define it when Elementor is
// actually loaded. Without this guard, PHP fatals on sites without Elementor.
if ( class_exists( '\Elementor\Plugin' ) || did_action( 'elementor/loaded' ) ) {
	class Bio_Link_Elementor_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'bio_link_insta';
	}

	public function get_title() {
		return __( 'Bio Link — Instagram', 'bio-link' );
	}

	public function get_icon() {
		return 'eicon-instagram-embed';
	}

	public function get_categories() {
		return array( 'bio-link' );
	}

	public function get_keywords() {
		return array( 'instagram', 'bio', 'bio link', 'link in bio' );
	}

	protected function register_controls() {
		$this->register_profile_controls();
		$this->register_photos_controls();
		$this->register_social_controls();
		$this->register_style_controls();
	}

	private function register_profile_controls() {
		$this->start_controls_section(
			'bio_profile_section',
			array(
				'label' => __( 'Profile', 'bio-link' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'profile_photo',
			array(
				'label'   => __( 'Profile Photo', 'bio-link' ),
				'type'    => \Elementor\Controls_Manager::MEDIA,
				'default' => array(
					'url' => get_option( 'bio_link_profile_photo', '' ),
				),
			)
		);

		$this->add_control(
			'bio_text',
			array(
				'label'   => __( 'Bio Text', 'bio-link' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => get_option( 'bio_link_bio_text', '' ),
				'rows'    => 4,
			)
		);

		$this->add_control(
			'username',
			array(
				'label'   => __( 'Instagram Username (optional, for DM automation)', 'bio-link' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => get_option( 'bio_link_ig_username', '' ),
			)
		);

		$this->add_control(
			'show_followers',
			array(
				'label'        => __( 'Show Follower Count', 'bio-link' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'bio-link' ),
				'label_off'    => __( 'Hide', 'bio-link' ),
				'return_value' => 'yes',
				'default'      => get_option( 'bio_link_show_followers', 1 ) ? 'yes' : '',
			)
		);

		$this->add_control(
			'followers_label',
			array(
				'label'       => __( 'Followers Label', 'bio-link' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'followers', 'bio-link' ),
				'condition'   => array( 'show_followers' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_photos_controls() {
		$this->start_controls_section(
			'bio_photos_section',
			array(
				'label' => __( 'Photos Grid', 'bio-link' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_photos',
			array(
				'label'        => __( 'Show Photo Grid', 'bio-link' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'bio-link' ),
				'label_off'    => __( 'Hide', 'bio-link' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'photo_count',
			array(
				'label'     => __( 'Number of Photos', 'bio-link' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 50,
				'step'      => 1,
				'default'   => 12,
				'condition' => array( 'show_photos' => 'yes' ),
			)
		);

		$this->add_control(
			'photo_note',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => __( 'Photos are imported from Instagram via the Bio Link dashboard (Import from Instagram). This widget shows the imported grid.', 'bio-link' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->end_controls_section();
	}

	private function register_social_controls() {
		$this->start_controls_section(
			'bio_social_section',
			array(
				'label' => __( 'Social Links', 'bio-link' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'platform',
			array(
				'label'   => __( 'Platform', 'bio-link' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'telegram',
				'options' => array(
					'telegram'  => __( 'Telegram', 'bio-link' ),
					'x'         => __( 'X (Twitter)', 'bio-link' ),
					'linkedin'  => __( 'LinkedIn', 'bio-link' ),
					'instagram' => __( 'Instagram', 'bio-link' ),
					'whatsapp'  => __( 'WhatsApp', 'bio-link' ),
					'bluesky'   => __( 'Bluesky', 'bio-link' ),
					'custom'    => __( 'Custom / Other', 'bio-link' ),
				),
			)
		);

		$repeater->add_control(
			'url',
			array(
				'label'       => __( 'URL', 'bio-link' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => 'https://t.me/username',
				'show_external' => true,
				'default'     => array( 'url' => '', 'is_external' => true, 'nofollow' => false ),
			)
		);

		$this->add_control(
			'social_links',
			array(
				'label'       => __( 'Social Links', 'bio-link' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ platform }}}',
				'default'     => array(),
			)
		);

		$this->end_controls_section();
	}

	private function register_style_controls() {
		$this->start_controls_section(
			'bio_style_section',
			array(
				'label' => __( 'Profile Style', 'bio-link' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'avatar_size',
			array(
				'label'     => __( 'Avatar Size (px)', 'bio-link' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 48, 'max' => 200 ) ),
				'default'   => array( 'size' => 96 ),
				'selectors' => array(
					'{{WRAPPER}} .bio-link-avatar' => 'width: {{SIZE}}px; height: {{SIZE}}px;',
				),
			)
		);

		$this->add_control(
			'bio_color',
			array(
				'label'     => __( 'Bio Text Color', 'bio-link' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#333333',
				'selectors' => array(
					'{{WRAPPER}} .bio-link-bio' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'followers_color',
			array(
				'label'     => __( 'Followers Color', 'bio-link' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#666666',
				'selectors' => array(
					'{{WRAPPER}} .bio-link-followers' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'social_icon_color',
			array(
				'label'     => __( 'Social Icon Color', 'bio-link' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#262626',
				'selectors' => array(
					'{{WRAPPER}} .bio-link-social' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'social_icon_size',
			array(
				'label'     => __( 'Social Icon Size (px)', 'bio-link' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 12, 'max' => 48 ) ),
				'default'   => array( 'size' => 20 ),
				'selectors' => array(
					'{{WRAPPER}} .bio-link-social svg' => 'width: {{SIZE}}px; height: {{SIZE}}px;',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$profile_photo = '';
		if ( ! empty( $settings['profile_photo']['url'] ) ) {
			$profile_photo = $settings['profile_photo']['url'];
		} elseif ( ! empty( $settings['profile_photo']['id'] ) ) {
			$profile_photo = wp_get_attachment_image_url( $settings['profile_photo']['id'], 'full' );
		}

		$bio_text      = isset( $settings['bio_text'] ) ? $settings['bio_text'] : '';
		$show_followers = 'yes' === ( $settings['show_followers'] ?? '' );
		$followers     = intval( get_option( 'bio_link_followers', 0 ) );
		$followers_label = ! empty( $settings['followers_label'] ) ? $settings['followers_label'] : __( 'followers', 'bio-link' );

		// Social links from repeater.
		$social_links = array();
		if ( ! empty( $settings['social_links'] ) && is_array( $settings['social_links'] ) ) {
			foreach ( $settings['social_links'] as $item ) {
				$url = ! empty( $item['url']['url'] ) ? $item['url']['url'] : '';
				if ( empty( $url ) ) {
					continue;
				}
				$social_links[] = array(
					'platform' => isset( $item['platform'] ) ? sanitize_key( $item['platform'] ) : 'custom',
					'url'      => $url,
				);
			}
		}

		// Photos.
		$show_photos = 'yes' === ( $settings['show_photos'] ?? 'yes' );
		$photo_count = ! empty( $settings['photo_count'] ) ? intval( $settings['photo_count'] ) : 12;

		$photos = array();
		if ( $show_photos ) {
			$query = get_posts( array(
				'post_type'      => 'bio_link_photo',
				'posts_per_page' => $photo_count,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			) );
			foreach ( $query as $photo ) {
				$photos[] = array(
					'ID'         => $photo->ID,
					'title'      => $photo->post_title,
					'image_url'  => get_post_meta( $photo->ID, '_bio_link_image_url', true ),
					'post_url'   => get_post_meta( $photo->ID, '_bio_link_post_url', true ),
					'has_link'   => ! empty( get_post_meta( $photo->ID, '_bio_link_post_url', true ) ),
				);
			}
		}

		// Avatar.
		$avatar_html = $profile_photo
			? '<img src="' . esc_url( $profile_photo ) . '" alt="' . esc_attr__( 'Profile Photo', 'bio-link' ) . '" class="bio-link-avatar" />'
			: '';

		$bio_html = $bio_text
			? '<p class="bio-link-bio">' . esc_html( $bio_text ) . '</p>'
			: '';

		$followers_html = '';
		if ( $show_followers && $followers > 0 ) {
			$followers_html = '<p class="bio-link-followers">'
				. '<span class="bio-link-followers-icon" aria-hidden="true">👥</span>'
				. '<span class="bio-link-followers-count">' . esc_html( number_format_i18n( $followers ) ) . '</span>'
				. '<span class="bio-link-followers-label">' . esc_html( $followers_label ) . '</span>'
				. '</p>';
		}

		$social_html = Bio_Link_Frontend::render_social_links( $social_links );

		echo '<div class="bio-link-container">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="bio-link-profile">' . $avatar_html . $bio_html . $followers_html . $social_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $show_photos ) {
			echo '<div class="bio-link-grid">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( empty( $photos ) ) {
				echo '<p>' . esc_html__( 'No photos imported yet — use the Bio Link dashboard to import from Instagram.', 'bio-link' ) . '</p>';
			}
			foreach ( $photos as $photo ) {
				$classes = array( 'bio-link-item' );
				if ( ! $photo['has_link'] ) {
					$classes[] = 'no-link';
				}
				echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
				if ( $photo['has_link'] ) {
					echo '<a href="' . esc_url( $photo['post_url'] ) . '" target="_blank" rel="noopener noreferrer" class="bio-link-photo-link">';
				}
				if ( $photo['image_url'] ) {
					echo '<img src="' . esc_url( $photo['image_url'] ) . '" alt="' . esc_attr( $photo['title'] ) . '" class="bio-link-photo" loading="lazy" />';
				} else {
					echo '<div class="bio-link-photo bio-link-photo-placeholder"></div>';
				}
				if ( $photo['has_link'] ) {
					echo '</a>';
				}
				echo '</div>';
			}
			echo '</div>';
		}

		echo '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
} // end guard: class_exists( '\Elementor\Plugin' )

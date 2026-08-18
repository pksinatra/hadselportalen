<?php
if (!defined('ABSPATH')) exit;

class HP_Widget extends WP_Widget {

  public function __construct() {
    parent::__construct(
      'hp_widget',
      'HadselPortalen RSS',
      ['description' => 'Viser RSS-nyheter fra flere kilder (HadselPortalen).']
    );
  }

  public function widget($args, $instance) {
    echo $args['before_widget'];

    $title = !empty($instance['title']) ? $instance['title'] : 'Nyheter';
    echo $args['before_title'] . esc_html($title) . $args['after_title'];

    if (function_exists('hp_render_block')) {
      echo hp_render_block([
        'limit'       => intval($instance['limit'] ?? 8),
        'include'     => (string)($instance['include'] ?? ''),
        'exclude'     => (string)($instance['exclude'] ?? ''),
        'max_age_days'=> intval($instance['max_age_days'] ?? 14),
        'show_source' => !empty($instance['show_source']) ? '1' : '0',
        'show_date'   => !empty($instance['show_date']) ? '1' : '0',
      ]);
    } else {
      // Fail-safe: hvis hovedfila ikke er lastet av en eller annen grunn
      echo '<div style="font-size:13px;opacity:.75;">RSS-modulen er ikke tilgjengelig.</div>';
    }

    echo $args['after_widget'];
  }

  public function form($instance) {
    $title = $instance['title'] ?? 'Nyheter';
    $limit = $instance['limit'] ?? 8;
    $include = $instance['include'] ?? '';
    $exclude = $instance['exclude'] ?? '';
    $max_age_days = $instance['max_age_days'] ?? 14;
    $show_source = !empty($instance['show_source']);
    $show_date = !empty($instance['show_date']);
    ?>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Tittel</label>
      <input class="widefat"
        id="<?php echo esc_attr($this->get_field_id('title')); ?>"
        name="<?php echo esc_attr($this->get_field_name('title')); ?>"
        type="text"
        value="<?php echo esc_attr($title); ?>" />
    </p>

    <p>
      <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>">Antall saker</label>
      <input class="widefat"
        id="<?php echo esc_attr($this->get_field_id('limit')); ?>"
        name="<?php echo esc_attr($this->get_field_name('limit')); ?>"
        type="number" min="1" max="50"
        value="<?php echo esc_attr($limit); ?>" />
    </p>

    <p>
      <label for="<?php echo esc_attr($this->get_field_id('include')); ?>">Inkluder nøkkelord (komma)</label>
      <input class="widefat"
        id="<?php echo esc_attr($this->get_field_id('include')); ?>"
        name="<?php echo esc_attr($this->get_field_name('include')); ?>"
        type="text"
        value="<?php echo esc_attr($include); ?>" />
      <small>La stå tomt for å vise alt.</small>
    </p>

    <p>
      <label for="<?php echo esc_attr($this->get_field_id('exclude')); ?>">Ekskluder nøkkelord (komma)</label>
      <input class="widefat"
        id="<?php echo esc_attr($this->get_field_id('exclude')); ?>"
        name="<?php echo esc_attr($this->get_field_name('exclude')); ?>"
        type="text"
        value="<?php echo esc_attr($exclude); ?>" />
    </p>

    <p>
      <label for="<?php echo esc_attr($this->get_field_id('max_age_days')); ?>">Maks alder (dager)</label>
      <input class="widefat"
        id="<?php echo esc_attr($this->get_field_id('max_age_days')); ?>"
        name="<?php echo esc_attr($this->get_field_name('max_age_days')); ?>"
        type="number" min="0" max="365"
        value="<?php echo esc_attr($max_age_days); ?>" />
    </p>

    <p>
      <label>
        <input type="checkbox"
          name="<?php echo esc_attr($this->get_field_name('show_date')); ?>"
          <?php checked($show_date); ?> />
        Vis dato
      </label>
      <br/>
      <label>
        <input type="checkbox"
          name="<?php echo esc_attr($this->get_field_name('show_source')); ?>"
          <?php checked($show_source); ?> />
        Vis kilde
      </label>
    </p>
    <?php
  }

  public function update($new_instance, $old_instance) {
    $instance = [];
    $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
    $instance['limit'] = max(1, min(50, intval($new_instance['limit'] ?? 8)));
    $instance['include'] = sanitize_text_field($new_instance['include'] ?? '');
    $instance['exclude'] = sanitize_text_field($new_instance['exclude'] ?? '');
    $instance['max_age_days'] = max(0, intval($new_instance['max_age_days'] ?? 14));
    $instance['show_source'] = !empty($new_instance['show_source']) ? 1 : 0;
    $instance['show_date'] = !empty($new_instance['show_date']) ? 1 : 0;
    return $instance;
  }
}

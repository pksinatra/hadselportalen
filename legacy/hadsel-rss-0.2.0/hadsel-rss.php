<?php
/**
 * Plugin Name: HadselPortalen RSS
 * Description: Samler og viser RSS-nyheter fra flere kilder med filtre (keyword, alder). Shortcode + widget.
 * Version: 0.2.0
 * Author: HadselPortalen
 */

if (!defined('ABSPATH')) exit;

define('HP_OPT_KEY', 'hp_settings');
define('HP_TRANSIENT_PREFIX', 'hp_feed_');

require_once __DIR__ . '/includes/class-hp-widget.php';

/**
 * Admin: options page
 */
add_action('admin_menu', function () {
  add_options_page(
    'HadselPortalen RSS',
    'HadselPortalen RSS',
    'manage_options',
    'hadselportalen-rss',
    'hp_render_settings_page'
  );
});

add_action('admin_init', function () {
  register_setting('hp_settings_group', HP_OPT_KEY, [
    'type' => 'array',
    'sanitize_callback' => 'hp_sanitize_settings',
    'default' => [
      'feeds' => [],
      'include_keywords' => '', // tomt som standard = ikke filtrer bort alt
      'exclude_keywords' => '',
      'max_age_days' => 14,
      'cache_minutes' => 15,
      'default_total_items' => 8,
    ],
  ]);
});

function hp_get_settings() {
  $s = get_option(HP_OPT_KEY, []);
  if (!is_array($s)) $s = [];
  return wp_parse_args($s, [
    'feeds' => [],
    'include_keywords' => '',
    'exclude_keywords' => '',
    'max_age_days' => 14,
    'cache_minutes' => 15,
    'default_total_items' => 8,
  ]);
}

function hp_sanitize_settings($input) {
  $out = [
    'feeds' => [],
    'include_keywords' => sanitize_text_field($input['include_keywords'] ?? ''),
    'exclude_keywords' => sanitize_text_field($input['exclude_keywords'] ?? ''),
    'max_age_days' => max(0, intval($input['max_age_days'] ?? 0)),
    'cache_minutes' => max(5, intval($input['cache_minutes'] ?? 15)),
    'default_total_items' => max(1, min(50, intval($input['default_total_items'] ?? 8))),
  ];

  if (!empty($input['feeds']) && is_array($input['feeds'])) {
    foreach ($input['feeds'] as $feed) {
      $url = esc_url_raw($feed['url'] ?? '');
      if (!$url) continue;

      $out['feeds'][] = [
        'name' => sanitize_text_field($feed['name'] ?? $url),
        'url'  => $url,
        'max_items' => max(1, min(30, intval($feed['max_items'] ?? 10))),
      ];
    }
  }

  return $out;
}

function hp_render_settings_page() {
  if (!current_user_can('manage_options')) return;

  $s = hp_get_settings();
  ?>
  <div class="wrap">
    <h1>HadselPortalen RSS</h1>

    <form method="post" action="options.php">
      <?php settings_fields('hp_settings_group'); ?>

      <h2>Feeder</h2>
      <p>Legg inn RSS-URL-er. Eksempel: <code>https://www.vol.no/rss</code>, <code>https://vny.no/feed/</code></p>

      <table class="widefat striped" id="hp-feeds-table">
        <thead>
          <tr>
            <th>Navn</th>
            <th>RSS-URL</th>
            <th>Maks per feed</th>
            <th>Fjern</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($s['feeds'] ?? []) as $i => $feed): ?>
            <tr>
              <td><input type="text" name="<?php echo HP_OPT_KEY; ?>[feeds][<?php echo $i; ?>][name]" value="<?php echo esc_attr($feed['name']); ?>" style="width: 100%;" /></td>
              <td><input type="url" name="<?php echo HP_OPT_KEY; ?>[feeds][<?php echo $i; ?>][url]" value="<?php echo esc_attr($feed['url']); ?>" style="width: 100%;" /></td>
              <td><input type="number" min="1" max="30" name="<?php echo HP_OPT_KEY; ?>[feeds][<?php echo $i; ?>][max_items]" value="<?php echo esc_attr($feed['max_items']); ?>" /></td>
              <td><button type="button" class="button hp-remove-row">X</button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <p><button type="button" class="button" id="hp-add-feed">+ Legg til feed</button></p>

      <h2>Filtre</h2>
      <table class="form-table">
        <tr>
          <th scope="row">Inkluder nøkkelord</th>
          <td>
            <input type="text" name="<?php echo HP_OPT_KEY; ?>[include_keywords]" value="<?php echo esc_attr($s['include_keywords']); ?>" style="width: 100%;" />
            <p class="description">Komma-separert. Treffer i tittel + utdrag. La stå tomt for å vise alt.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">Ekskluder nøkkelord</th>
          <td>
            <input type="text" name="<?php echo HP_OPT_KEY; ?>[exclude_keywords]" value="<?php echo esc_attr($s['exclude_keywords']); ?>" style="width: 100%;" />
            <p class="description">Komma-separert. Hvis ordet finnes i tittel/utdrag blir saken skjult.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">Maks alder (dager)</th>
          <td><input type="number" min="0" max="365" name="<?php echo HP_OPT_KEY; ?>[max_age_days]" value="<?php echo esc_attr($s['max_age_days']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row">Cache (minutter)</th>
          <td><input type="number" min="5" max="1440" name="<?php echo HP_OPT_KEY; ?>[cache_minutes]" value="<?php echo esc_attr($s['cache_minutes']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row">Standard: totalt antall saker</th>
          <td><input type="number" min="1" max="50" name="<?php echo HP_OPT_KEY; ?>[default_total_items]" value="<?php echo esc_attr($s['default_total_items']); ?>" /></td>
        </tr>
      </table>

      <?php submit_button(); ?>
    </form>
  </div>

  <script>
  (function(){
    const tbody = document.getElementById('hp-feeds-table').querySelector('tbody');
    const addBtn = document.getElementById('hp-add-feed');

    function bindRemove(btn){
      btn.addEventListener('click', () => {
        btn.closest('tr').remove();
        renumber();
      });
    }

    function renumber(){
      const rows = tbody.querySelectorAll('tr');
      rows.forEach((row, idx) => {
        row.querySelectorAll('input').forEach(input => {
          input.name = input.name.replace(/\[feeds\]\[\d+\]/, '[feeds]['+idx+']');
        });
      });
    }

    addBtn.addEventListener('click', () => {
      const idx = tbody.querySelectorAll('tr').length;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><input type="text" name="<?php echo HP_OPT_KEY; ?>[feeds][${idx}][name]" value="" style="width:100%;" /></td>
        <td><input type="url" name="<?php echo HP_OPT_KEY; ?>[feeds][${idx}][url]" value="" style="width:100%;" /></td>
        <td><input type="number" min="1" max="30" name="<?php echo HP_OPT_KEY; ?>[feeds][${idx}][max_items]" value="10" /></td>
        <td><button type="button" class="button hp-remove-row">X</button></td>
      `;
      tbody.appendChild(tr);
      bindRemove(tr.querySelector('.hp-remove-row'));
    });

    document.querySelectorAll('.hp-remove-row').forEach(bindRemove);
  })();
  </script>
  <?php
}

/**
 * Helpers
 */
function hp_parse_keywords($csv) {
  $parts = array_filter(array_map('trim', explode(',', (string)$csv)));
  return array_values(array_unique($parts));
}

function hp_item_passes_filters($item, $include, $exclude, $max_age_days) {
  $title = mb_strtolower((string)($item['title'] ?? ''));
  $desc  = mb_strtolower((string)($item['desc'] ?? ''));

  // exclude
  if (!empty($exclude)) {
    foreach ($exclude as $kw) {
      $kwl = mb_strtolower((string)$kw);
      if ($kwl !== '' && (str_contains($title, $kwl) || str_contains($desc, $kwl))) return false;
    }
  }

  // include (hvis tom = slipp gjennom alt)
  if (!empty($include)) {
    $hit = false;
    foreach ($include as $kw) {
      $kwl = mb_strtolower((string)$kw);
      if ($kwl !== '' && (str_contains($title, $kwl) || str_contains($desc, $kwl))) {
        $hit = true;
        break;
      }
    }
    if (!$hit) return false;
  }

  // age filter
  if ($max_age_days > 0 && !empty($item['date_ts'])) {
    $cutoff = time() - ($max_age_days * 86400);
    if (intval($item['date_ts']) < $cutoff) return false;
  }

  return true;
}

/**
 * Fetch feed items (server-side)
 */
function hp_fetch_feed_items($feed_url, $max_items, $cache_minutes) {
  $key = HP_TRANSIENT_PREFIX . md5($feed_url . '|' . $max_items);
  $cached = get_transient($key);
  if (is_array($cached)) return $cached;

  // WordPress feed functions
  include_once ABSPATH . WPINC . '/feed.php';

  $feed = fetch_feed($feed_url);

  if (is_wp_error($feed)) {
    $payload = ['error' => $feed->get_error_message(), 'items' => []];
    set_transient($key, $payload, max(300, $cache_minutes * 60)); // cache feilmelding litt
    return $payload;
  }

  // Cache varighet (SimplePie)
  $feed->set_cache_duration($cache_minutes * 60);

  $count = min($max_items, $feed->get_item_quantity($max_items));
  $raw_items = $feed->get_items(0, $count);

  $items = [];
  foreach ($raw_items as $it) {
    $date = $it->get_date('U');
    $date_ts = $date ? intval($date) : 0;

    $items[] = [
      'title' => wp_strip_all_tags($it->get_title()),
      'link'  => esc_url_raw($it->get_link()),
      'date_ts' => $date_ts,
      'date_iso' => $date_ts ? gmdate('c', $date_ts) : '',
      'desc'  => wp_strip_all_tags($it->get_description() ?: $it->get_content()),
      'source' => parse_url($feed_url, PHP_URL_HOST),
    ];
  }

  $payload = ['error' => '', 'items' => $items];
  set_transient($key, $payload, $cache_minutes * 60);
  return $payload;
}

/**
 * Render (shortcode + widget uses this)
 */
function hp_render_block($atts = []) {
  $s = hp_get_settings();

  $atts = shortcode_atts([
    'limit' => $s['default_total_items'],
    'include' => $s['include_keywords'],
    'exclude' => $s['exclude_keywords'],
    'max_age_days' => $s['max_age_days'],
    'show_source' => '1',
    'show_date' => '1',
    'title' => '', // optional
  ], $atts, 'hp_rss');

  $limit = max(1, min(50, intval($atts['limit'])));
  $include = hp_parse_keywords($atts['include']);
  $exclude = hp_parse_keywords($atts['exclude']);
  $max_age_days = max(0, intval($atts['max_age_days']));
  $cache_minutes = max(5, intval($s['cache_minutes']));

  $all = [];
  $errors = [];

  foreach (($s['feeds'] ?? []) as $feed) {
    $url = $feed['url'] ?? '';
    if (!$url) continue;

    $res = hp_fetch_feed_items($url, intval($feed['max_items'] ?? 10), $cache_minutes);

    if (!empty($res['error'])) {
      $errors[] = ($feed['name'] ?? $url) . ': ' . $res['error'];
    }

    foreach (($res['items'] ?? []) as $item) {
      if (hp_item_passes_filters($item, $include, $exclude, $max_age_days)) {
        $item['source_name'] = $feed['name'] ?? ($item['source'] ?? 'Kilde');
        $all[] = $item;
      }
    }
  }

  usort($all, function($a, $b){
    return (intval($b['date_ts'] ?? 0)) <=> (intval($a['date_ts'] ?? 0));
  });

  $all = array_slice($all, 0, $limit);

  ob_start();

  // Admin-only debug (hvis det feiler å hente en feed)
  if (!empty($errors) && current_user_can('manage_options')) {
    echo '<div class="hp-debug" style="padding:10px;border:1px solid rgba(0,0,0,.15);margin:10px 0;font-size:13px;">';
    echo '<strong>HadselPortalen RSS (debug):</strong><br>';
    foreach ($errors as $e) echo esc_html($e) . '<br>';
    echo '</div>';
  }

  ?>
  <div class="hp-rss-block">
    <?php if (!empty($atts['title'])): ?>
      <div class="hp-rss-title"><?php echo esc_html($atts['title']); ?></div>
    <?php endif; ?>

    <ul class="hp-rss-list">
      <?php if (empty($all)): ?>
        <li class="hp-rss-empty">Ingen treff akkurat nå.</li>
      <?php else: ?>
        <?php foreach ($all as $it): ?>
          <li class="hp-rss-item">
            <a class="hp-rss-link" href="<?php echo esc_url($it['link']); ?>" target="_blank" rel="noopener">
              <?php echo esc_html($it['title']); ?>
            </a>

            <?php if ($atts['show_date'] === '1' || $atts['show_source'] === '1'): ?>
              <div class="hp-rss-meta">
                <?php if ($atts['show_date'] === '1' && !empty($it['date_ts'])): ?>
                  <time datetime="<?php echo esc_attr($it['date_iso']); ?>">
                    <?php echo esc_html(date_i18n('d.m.Y H:i', intval($it['date_ts']))); ?>
                  </time>
                <?php endif; ?>

                <?php if ($atts['show_source'] === '1'): ?>
                  <span class="hp-rss-source">
                    <?php echo ($atts['show_date'] === '1' && !empty($it['date_ts'])) ? ' · ' : ''; ?>
                    <?php echo esc_html($it['source_name']); ?>
                  </span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
  </div>
  <?php

  return ob_get_clean();
}

/**
 * Shortcode
 */
add_shortcode('hp_rss', 'hp_render_block');

// valgfritt: behold gammel shortcode hvis du vil
add_shortcode('hadsel_rss', 'hp_render_block');

/**
 * Widget registration
 */
add_action('widgets_init', function () {
  if (class_exists('HP_Widget')) {
    register_widget('HP_Widget');
  }
});

/**
 * Minimal styling
 */
add_action('wp_head', function(){
  ?>
  <style>
    .hp-rss-block { font-size: 14px; }
    .hp-rss-title { font-weight: 600; margin-bottom: 8px; }
    .hp-rss-list { list-style: none; margin: 0; padding: 0; }
    .hp-rss-item { margin: 0 0 10px 0; padding: 0 0 10px 0; border-bottom: 1px solid rgba(0,0,0,.08); }
    .hp-rss-link { text-decoration: none; }
    .hp-rss-meta { font-size: 12px; opacity: .75; margin-top: 2px; }
    .hp-rss-empty { opacity: .7; }
  </style>
  <?php
});

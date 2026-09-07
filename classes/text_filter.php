<?php
// This file is part of Moodle - http://moodle.org/

namespace filter_drmx5;

defined('MOODLE_INTERNAL') || die();

// The namespaced base class was introduced for the updated Filter API. This
// alias keeps the same plugin package usable on Moodle 4.4 and earlier.
if (class_exists('\core_filters\text_filter')) {
    class_alias('\core_filters\text_filter', 'filter_drmx5_base_text_filter');
} else {
    class_alias('\moodle_text_filter', 'filter_drmx5_base_text_filter');
}

/**
 * Replaces [zjget-player]URL[/zjget-player] with the ZJGet player markup.
 */
class text_filter extends \filter_drmx5_base_text_filter {
    /** @var bool Whether the player scripts have already been emitted. */
    private static $scriptsincluded = false;

    /**
     * Apply the DRM-X 5.0 player filter.
     *
     * @param string $text HTML to filter.
     * @param array $options Filter options.
     * @return string Filtered HTML.
     */
    public function filter($text, array $options = []) {
        if (!is_string($text) || stripos($text, '[zjget-player]') === false) {
            return $text;
        }

        global $PAGE;
        $editing = isset($PAGE)
            && is_object($PAGE)
            && method_exists($PAGE, 'user_is_editing')
            && $PAGE->user_is_editing();

        $filtered = preg_replace_callback(
            '~\[zjget-player\](.*?)\[/zjget-player\]~is',
            function ($matches) use ($editing) {
                // TinyMCE or Moodle's URL filter may replace the visible URL
                // with a filename and keep the full address only in an anchor's
                // href. Search the decoded HTML before removing its tags.
                $content = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (!preg_match('~https?://[^\s<>"\']+~iu', $content, $urlmatch)) {
                    return \html_writer::span(
                        get_string('invalidvideourl', 'filter_drmx5'),
                        'filter-drmx5-error'
                    );
                }
                $url = $urlmatch[0];

                // The ZJGet browser detection script creates a page-level
                // overlay in ordinary browsers. Do not initialize it while a
                // teacher is editing the Moodle course, because the overlay
                // can cover activity chooser and other editing dialogs.
                if ($editing) {
                    return \html_writer::div(
                        \html_writer::tag(
                            'strong',
                            get_string('editingplaceholder', 'filter_drmx5')
                        ) . \html_writer::tag('small', s($url), [
                            'class' => 'd-block text-muted',
                        ]),
                        'filter-drmx5-editing-placeholder alert alert-secondary'
                    );
                }

                // The nolink marker prevents a later Moodle URL filter from
                // converting the address back into an anchor whose text is
                // only the filename. ZJGet reads the element's text content.
                $urlcontent = \html_writer::span(s($url), 'nolink');
                $output = \html_writer::tag('div', $urlcontent, [
                    'id' => 'ZJGet_Video_URL',
                    'style' => 'display: none;',
                ]);

                if (!self::$scriptsincluded) {
                    self::$scriptsincluded = true;
                    $scripts = [
                        'https://www.zjget.com/assets/embed_js/embed_zjget.js',
                        'https://www.zjget.com/assets/videojs-8.23.3/video.min.js',
                        'https://www.zjget.com/assets/embed_js/zjget.js',
                    ];
                    foreach ($scripts as $script) {
                        $output .= \html_writer::tag('script', '', [
                            'type' => 'text/javascript',
                            'src' => $script,
                        ]);
                    }
                }

                return $output;
            },
            $text
        );

        return $filtered === null ? $text : $filtered;
    }
}

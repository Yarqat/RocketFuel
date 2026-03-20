<?php defined('ABSPATH') || exit; ?>

    </div>

    <div class="rfc-footer">
        <div class="rfc-footer-left">
            <?php
            printf(
                esc_html__('Built by %s', 'rocketfuel-cache'),
                '<a href="https://shahfahad.info" target="_blank" rel="noopener">Shah Fahad</a>'
            );
            ?>
        </div>
        <div class="rfc-footer-right">
            <a href="https://shahfahad.info/rocketfuel-cache/docs" target="_blank" rel="noopener"><?php esc_html_e('Documentation', 'rocketfuel-cache'); ?></a>
            <span class="rfc-footer-sep">|</span>
            <a href="https://wordpress.org/support/plugin/rocketfuel-cache/reviews/#new-post" target="_blank" rel="noopener"><?php esc_html_e('Rate Us', 'rocketfuel-cache'); ?></a>
            <span class="rfc-footer-sep">|</span>
            <span><?php printf(esc_html__('Version %s', 'rocketfuel-cache'), RFC_VERSION); ?></span>
        </div>
    </div>

</div>

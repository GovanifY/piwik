/*!
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
function initTopControls() {
    var $topControlsContainer = $('.top_controls'),
        left = 256;

    var siteSelector = $('.sites_selector_in_dashboard');
    if (siteSelector.length) {
        left = $(siteSelector.first()).outerWidth() + 30;
    }

    if ($topControlsContainer.length) {
        $('.piwikTopControl').each(function () {
            var $control = $(this);
            if ($control.css('display') == 'none') {
                return;
            }

            $control.css('left', left);
            $control.css('top', 18 + 'px');

            if (!$.contains($topControlsContainer[0], this)) {
                $control.detach().appendTo($topControlsContainer);
            }

            left += $control.outerWidth(true);
        });
    }
}
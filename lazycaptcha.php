<?php
/**
 * LazyCaptcha plugin for Joomla 4.x / 5.x
 *
 * @package     LazyCaptcha.Plugin
 * @subpackage  Captcha.lazycaptcha
 * @copyright   (C) 2026 LazyCaptcha contributors
 * @license     MIT
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

class PlgCaptchaLazycaptcha extends CMSPlugin
{
    /**
     * Load the plugin language file automatically.
     */
    protected $autoloadLanguage = true;

    /**
     * Initialize the captcha — no global script needed up front; the widget
     * loads itself asynchronously when we render it on a form.
     */
    public function onInit($id = 'lazycaptcha_1')
    {
        $baseUrl = rtrim($this->params->get('base_url', 'https://lazycaptcha.com'), '/');
        $scriptUrl = $baseUrl . '/api/captcha/v1/lazycaptcha.js';

        $doc = Factory::getApplication()->getDocument();

        // Only add the script once per page render.
        static $added = false;
        if (!$added) {
            $doc->addScript($scriptUrl, ['defer' => true, 'async' => true]);
            $added = true;
        }

        return true;
    }

    /**
     * Render the captcha element into the form.
     *
     * @param  string  $name   The field name (ignored; widget uses fixed name)
     * @param  string  $id     The DOM id for the widget container
     * @param  string  $class  Optional extra CSS classes
     * @return string          HTML to render
     */
    public function onDisplay($name = null, $id = 'lazycaptcha_1', $class = '')
    {
        $siteKey = trim((string) $this->params->get('site_key', ''));

        if ($siteKey === '') {
            return '<p class="alert alert-warning">'
                . Text::_('PLG_CAPTCHA_LAZYCAPTCHA_ERROR_NO_SITE_KEY')
                . '</p>';
        }

        $type = $this->params->get('type', 'auto');
        $theme = $this->params->get('theme', 'auto');
        $widget = $this->params->get('widget', 'standard');
        $width = trim((string) $this->params->get('width', ''));

        $classes = trim('lazycaptcha ' . $class);
        $widthAttr = $width !== ''
            ? ' data-width="' . htmlspecialchars($width, ENT_QUOTES, 'UTF-8') . '"'
            : '';

        return sprintf(
            '<div id="%s" class="%s" data-sitekey="%s" data-type="%s" data-theme="%s" data-widget="%s"%s></div>',
            htmlspecialchars($id, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($classes, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($siteKey, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($widget, ENT_QUOTES, 'UTF-8'),
            $widthAttr
        );
    }

    /**
     * Verify the captcha solution server-side.
     *
     * @param  string  $code  Reserved (ignored — Joomla passes whatever is in the field)
     * @return bool
     */
    public function onCheckAnswer($code = null)
    {
        $app = Factory::getApplication();
        $input = $app->input;

        // The widget injects a hidden input with this exact name; read it from POST.
        $token = $input->post->getString('lazycaptcha-token', '');

        if ($token === '') {
            $app->enqueueMessage(
                Text::_('PLG_CAPTCHA_LAZYCAPTCHA_ERROR_MISSING_TOKEN'),
                'error'
            );
            return false;
        }

        $secret = trim((string) $this->params->get('secret_key', ''));
        if ($secret === '') {
            $app->enqueueMessage(
                Text::_('PLG_CAPTCHA_LAZYCAPTCHA_ERROR_MISCONFIGURED'),
                'error'
            );
            return false;
        }

        $baseUrl = rtrim($this->params->get('base_url', 'https://lazycaptcha.com'), '/');
        $endpoint = $baseUrl . '/api/captcha/v1/verify';
        $timeout = max(1, (int) $this->params->get('timeout', 5));

        try {
            $http = HttpFactory::getHttp();
            $response = $http->post(
                $endpoint,
                json_encode([
                    'secret' => $secret,
                    'token' => $token,
                    'remote_ip' => $this->getClientIp(),
                ]),
                [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                $timeout
            );

            $body = json_decode((string) $response->body, true);

            if (!is_array($body) || empty($body['success'])) {
                $app->enqueueMessage(
                    Text::_('PLG_CAPTCHA_LAZYCAPTCHA_ERROR_FAILED'),
                    'error'
                );
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $app->enqueueMessage(
                Text::_('PLG_CAPTCHA_LAZYCAPTCHA_ERROR_CONNECTION'),
                'error'
            );
            return false;
        }
    }

    private function getClientIp(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }
        return $ip;
    }
}

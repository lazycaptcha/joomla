# LazyCaptcha for Joomla

Self-hostable CAPTCHA plugin for Joomla 4.x and 5.x. Protects any Joomla form that uses the built-in `JCaptcha` API — registration, contact, comments, and third-party forms like RSForm!Pro, Breezing Forms, Convert Forms, etc.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## Requirements

- Joomla 4.0+ or 5.0+
- PHP 8.1+
- A LazyCaptcha account ([lazycaptcha.com](https://lazycaptcha.com)) or self-hosted instance

## Installation

### Option A — from the zipped release

1. Download the latest `plg_captcha_lazycaptcha-x.y.z.zip` from the [Releases](../../releases) page
2. In Joomla admin, go to **System → Install → Extensions**
3. Drag & drop or select the ZIP file
4. Click **Upload & Install**

### Option B — from source

```bash
git clone https://github.com/yourusername/lazycaptcha-joomla.git
cd lazycaptcha-joomla
zip -r plg_captcha_lazycaptcha.zip . -x ".git/*" "README.md" ".github/*"
```

Then install the ZIP through the Joomla extension installer.

## Configuration

1. Go to **System → Manage → Plugins**
2. Search for "LazyCaptcha" and open it
3. Enter your **Site Key** and **Secret Key** from your LazyCaptcha dashboard
4. (Optional) Change **LazyCaptcha URL** if self-hosting — defaults to `https://lazycaptcha.com`
5. Pick a challenge type (`Auto` recommended)
6. Enable the plugin (set **Status** to Enabled)

## Activate on forms

1. Go to **System → Global Configuration → Site**
2. Under **Default Captcha**, select **LazyCaptcha**
3. Save

The plugin will now be used wherever Joomla's captcha system is invoked — user registration, contact forms, and any third-party extension that respects the `captcha` plugin group.

### Per-form override

In your own components or modules, request LazyCaptcha explicitly:

```php
use Joomla\CMS\Captcha\Captcha;

$captcha = Captcha::getInstance('lazycaptcha');
$captcha->initialise('my_form_captcha');
echo $captcha->display('captcha', 'my_form_captcha');
// ... later, on submit:
$passed = $captcha->checkAnswer(null);
```

## How it works

1. On form render, the plugin outputs a `<div class="lazycaptcha" data-sitekey="...">` and loads the LazyCaptcha widget script
2. The widget renders the challenge in an isolated Shadow DOM (no CSS conflicts with your Joomla template)
3. When solved, the widget injects a hidden `lazycaptcha-token` into the form
4. On submit, the plugin's `onCheckAnswer()` reads the token and verifies it server-to-server against `POST /api/captcha/v1/verify`
5. On success, Joomla lets the submission proceed; on failure, an error message is enqueued

## Troubleshooting

- **"site key not configured"** — you saved the plugin without a site key, or the plugin is disabled
- **Widget doesn't appear** — check browser console for errors; make sure the **Default Captcha** is set to LazyCaptcha in Global Configuration
- **Verification keeps failing** — confirm your secret key matches the one in your LazyCaptcha dashboard, and your Joomla server can reach the LazyCaptcha URL (whitelist outbound HTTPS if behind a firewall)
- **CSP headers break the widget** — add your LazyCaptcha host to `script-src` and `connect-src`

## License

[MIT](LICENSE)

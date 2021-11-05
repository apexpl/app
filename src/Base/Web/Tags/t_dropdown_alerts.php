<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Tags;

use Apex\Svc\{App, Convert};
use App\Webapp\Alerts\Alerts;
use Apex\Syrus\Parser\StackElement;
use Apex\Syrus\Render\Tags;
use Apex\Syrus\Interfaces\TagInterface;
use Apex\App\Attr\Inject;

/**
 * Renders a specific template tag.  Please see developer documentation for details.
 */
class t_dropdown_alerts implements TagInterface
{

    #[Inject(App::class)]
    private App $app;

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Alerts::class)]
    private Alerts $alerts;

    #[Inject(Tags::class)]
    private Tags $tags;

    /**
     * Render
     */
    public function render(string $html, StackElement $e):string
    {

        // Check if authenticated
        if (!$this->app->isAuth()) {
            return '';
        } elseif (null === ($user = $this->app->getUser())) {
            return '';
        }


        // Get alerts
        $rows = $this->alerts->list($this->app->getUser());

        // Go through alerts
        $html = '';
        foreach ($rows as $alert) {

            // Get sender'
            if (preg_match("/^(\w)\:(\d+)$/", $alert->sender) && $user = User::loadUuid($alert->sender)) {
                $sender = $user->getUsername();
            } else {
                $sender = $alert->sender;
            }

            $html .= $this->tags->getSnippet('dropdown.alert', $alert->contents, [
                'from' => $sender,
                'title' => $alert->title,
                'url' => $alert->url,
                'time' => $this->convert->lastSeen($alert->created_at->getTimestamp())
            ]);

        }

        // Return
        return $html;
    }

}



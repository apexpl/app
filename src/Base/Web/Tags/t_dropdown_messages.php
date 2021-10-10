<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Tags;

use Apex\Svc\{App, Convert};
use App\Webapp\Alerts\Messages;
use Apex\Syrus\Parser\StackElement;
use Apex\Syrus\Render\Tags;
use Apex\Syrus\Interfaces\TagInterface;

/**
 * Renders a specific template tag.  Please see developer documentation for details.
 */
class t_dropdown_messages implements TagInterface
{

    #[Inject(App::class)]
    private App $app;

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Messages::class)]
    private Messages $messages;

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
            return null;
        }

        // Get alerts
        $rows = $this->messages->list($this->app->getUser());

        // Go through alerts
        $html = '';
        foreach ($rows as $alert) {

            // Get sender'
            if (preg_match("/^(\w)\:(\d+)$/", $alert->sender) && $user = User::loadUuid($alert->sender)) {
                $sender = $user->getUsername();
            } else {
                $sender = $alert->sender;
            }

            $html .= $this->tags->getSnippet('dropdown.message', $alert->contents, [
                'from' => $sender,
                'badge' => $alert->badge,
                'title' => $alert->title,
                'url' => $alert->url,
                'time' => $this->convert->lastSeen($alert->created_at->getTimestamp())
            ]);

        }

        // Return
        return $html;
    }

}



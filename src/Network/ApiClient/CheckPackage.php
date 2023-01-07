<?php
declare(strict_types = 1);

namespace Apex\App\Network\ApiClient;

use Apex\Svc\Container;
use Apex\App\Network\NetworkClient;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Attr\Inject;
/**
 * Check package
 */
class CheckPackage
{

    #[Inject(NetworkClient::class)]
    private NetworkClient $nclient;

    /**
     * Process
     */
    public function process(LocalPackage $pkg, string $branch_name):void
    {

        // Set auth
        $this->nclient->setAuth($pkg->getLocalAccount());

        // Set JSON vars
        $request = [
            'alias' => $pkg->getAlias(), 
            'branch_name' => $branch_name
        ];

        // Send JSON
        $res = $this->nclient->post($pkg->getRepo(), 'repos/create_branch', $request);
    }

}



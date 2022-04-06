<?php
declare(strict_types=1);

namespace DachcomDigital\SocialDataFacebook\Connector;

use DachcomDigital\SocialData\Connector\AbstractConnectorDefinition;
use DachcomDigital\SocialData\Connector\ConnectorFeedConfigurationInterface;

class FacebookConnectorDefinition extends AbstractConnectorDefinition {
    
    public function getConnectorFeedConfiguration(): ConnectorFeedConfigurationInterface
    {
        return new FacebookConnectorFeedConfiguration();
    }
}

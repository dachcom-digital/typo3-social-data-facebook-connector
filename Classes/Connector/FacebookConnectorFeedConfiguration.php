<?php
declare(strict_types=1);

namespace DachcomDigital\SocialDataFacebook\Connector;

use DachcomDigital\SocialData\Connector\ConnectorFeedConfigurationInterface;
use DachcomDigital\SocialDataFacebook\FormEngine\Element\FacebookConnectorStatusElement;

final class FacebookConnectorFeedConfiguration implements ConnectorFeedConfigurationInterface
{
    public function getFlexFormFile(): string
    {
        return 'EXT:social_data_facebook/Configuration/FlexForms/FacebookConnectorConfiguration.xml';
    }
    
    public function getStatusFormElementClass(): string
    {
        return FacebookConnectorStatusElement::class;
    }
}

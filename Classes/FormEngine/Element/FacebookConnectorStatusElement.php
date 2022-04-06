<?php
declare(strict_types=1);

namespace DachcomDigital\SocialDataFacebook\FormEngine\Element;

use DachcomDigital\SocialData\Connector\ConnectorInterface;
use DachcomDigital\SocialData\FormEngine\Element\AbstractConnectorStatusElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

class FacebookConnectorStatusElement extends AbstractConnectorStatusElement
{
    
    public function render()
    {
        if (!$this->validateConnectorConfiguration()) {
            return ['html' => $this->renderStatusMessage('Missing connector configuration!', 'warning', [], 'actions-exclamation-triangle')];
        }
        
        $feedId = $this->data['databaseRow']['uid'];
        
        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $result = $this->mergeChildReturnIntoExistingResult($this->initializeResultArray(), $fieldInformationResult, false);
        
        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] = $fieldInformationHtml;
        $html[] = '<div class="form-wizards-wrap">';
        $html[] = '<div class="form-wizards-element">';
        if ($this->isConnectorConnected()) {
            $html[] = $this->renderStatusMessage('connected', 'success', ['id' => 'connector-status-message'], 'actions-check');
            $html[] = $this->renderButton(
                'Disconnect',
                'warning',
                [
                    'id' => 'facebook-connector-disconnect-btn',
                    'data-feed-id' => $feedId
                ],
                'actions-unlink'
            );
            $html[] = $this->renderButton(
                'Debug Access Token',
                'default',
                [
                    'id' => 'facebook-connector-debug-token-btn',
                    'data-feed-id' => $feedId
                ],
                'actions-question-circle'
            );
        } else {
            $html[] = $this->renderStatusMessage('not connected', 'warning', ['id' => 'connector-status-message'], 'actions-exclamation-triangle');
            $html[] = $this->renderButton(
                'Connect',
                'primary',
                [
                'id' => 'facebook-connector-connect-btn',
                'data-feed-id' => $feedId
                ],
                'actions-link'
            );
        }
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '</div>';
        $result['html'] = implode(LF, $html);
    
        $result['requireJsModules'][] = 'TYPO3/CMS/SocialDataFacebook/FacebookConnectorStatus';
        
        return $result;
    }
    
    protected function validateConnectorConfiguration(): bool
    {
        $connectorConfiguration = $this->getConnectorConfiguration();
        return !empty($connectorConfiguration) && !empty($connectorConfiguration['app_id'] && !empty($connectorConfiguration['app_secret']));
    }
}

<?php

namespace DachcomDigital\SocialDataFacebook\Controller;

use DachcomDigital\SocialData\Connector\ConnectorInterface;
use DachcomDigital\SocialData\Domain\Model\Feed;
use DachcomDigital\SocialData\Domain\Repository\FeedRepository;
use DachcomDigital\SocialData\Registry\ConnectorDefinitionRegistry;
use DachcomDigital\SocialDataFacebook\Client\FacebookClient;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Connector Registry
 *
 * implementing TYPO3's SingletonInterface is important to retrieve the same instance
 * via symfony container as well as with GeneralUtility::makeInstance.
 * Yes, this is awful.
 */
class FacebookConnectController implements SingletonInterface
{
    protected UserSessionManager $userSessionManager;
    protected PersistenceManagerInterface $persistenceManager;
    protected UriBuilder $uriBuilder;
    protected ConnectorDefinitionRegistry  $connectorRegistry;
    protected FeedRepository $feedRepository;
    
    public function __construct(
        PersistenceManagerInterface $persistenceManager,
        UriBuilder $uriBuilder,
        ConnectorDefinitionRegistry $connectorDefinitionRegistry,
        FeedRepository $feedRepository
    )
    {
        $this->userSessionManager = UserSessionManager::create('BE');
        $this->persistenceManager = $persistenceManager;
        $this->uriBuilder = $uriBuilder;
        $this->connectorRegistry = $connectorDefinitionRegistry;
        $this->feedRepository = $feedRepository;
    }
    
    public function connect(ServerRequestInterface $request): ResponseInterface
    {
        $feed = $this->feedRepository->findByUid($request->getQueryParams()['feed_id']);

        if (!$feed instanceof Feed) {
            return new Response('feed not found', 404);
        }
        
        $connectorConfiguration = $feed->getConnectorConfiguration();
        
        $facebookClient = new FacebookClient($connectorConfiguration);
        $redirectUri = $this->generateRedirectUri();
        $oauthProvider = $facebookClient->getOauthProvider($redirectUri);
        
        $authUrl = $oauthProvider->getAuthorizationUrl([
            'scope' => 'pages_show_list'
        ]);
        
        $session = $this->userSessionManager->createFromRequestOrAnonymous($request, BackendUserAuthentication::getCookieName());
        $session->set('FBLH_oauth_state', $oauthProvider->getState());
        $session->set('FBLH_feed_id', $feed->getUid());
        
        $this->userSessionManager->updateSession($session);
        
        return new RedirectResponse($authUrl);
    }
    
    public function connectCallback(ServerRequestInterface $request): ResponseInterface
    {
        $session = $this->userSessionManager->createFromRequestOrAnonymous($request, BackendUserAuthentication::getCookieName());
        
        if (empty($request->getQueryParams()['state']) || $request->getQueryParams()['state'] !== $session->get('FBLH_oauth_state')) {
            $content = [
                'error' => [
                    'reason' => 'general error',
                    'message' => 'invalid session data: missing state'
                ]
            ];
            return $this->buildConnectResponse($content, 400);
        }
        
        $feedId = $session->get('FBLH_feed_id');
        
        if (empty($feedId)) {
            $content = [
                'error' => [
                    'message' => 'invalid session data: missing feed id'
                ]
            ];
            return $this->buildConnectResponse($content, 400);
        }
        
        $feed = $this->feedRepository->findByUid($feedId);
        
        if (!$feed instanceof Feed) {
            $content = [
                'error' => [
                    'message' => 'feed not found'
                ]
            ];
            return $this->buildConnectResponse($content, 404);
        }
        
        $connectorConfiguration = $feed->getConnectorConfiguration();
        $facebookClient = new FacebookClient($connectorConfiguration);
        $redirectUri = $this->generateRedirectUri();
        $oauthProvider = $facebookClient->getOauthProvider($redirectUri);
        
        try {
            $defaultToken = $oauthProvider->getAccessToken('authorization_code', ['code' => $request->getQueryParams()['code']]);
        } catch (\Throwable $e) {
            $content = [
                'error' => [
                    'reason' => 'get access token error',
                    'message' => $e->getMessage()
                ]
            ];
            return $this->buildConnectResponse($content, 500);
        }
    
        if (!$defaultToken instanceof AccessToken) {
            $message = 'Could not generate access token';
            if (array_key_exists('error_message', $request->getQueryParams()) && !empty($request->getQueryParams()['error_message'])) {
                $message = $request->getQueryParams()['error_message'];
            }
            $content = [
                'error' => [
                    'reason' => 'invalid access token',
                    'message' => $message
                ]
            ];
            return $this->buildConnectResponse($content, 500);
        }
    
        try {
            $accessToken = $oauthProvider->getLongLivedAccessToken($defaultToken);
        } catch (\Throwable $e) {
            $content = [
                'error' => [
                    'reason' => 'long lived access token error',
                    'message' => $e->getMessage()
                ]
            ];
            return $this->buildConnectResponse($content, 500);
        }
    
        try {
            // @todo: really? Dispatch the /me/accounts request to make the user token finally ever lasting.
            $response = $facebookClient->get('/me/accounts?fields=access_token');
        } catch (\Throwable $e) {
            // we don't need to fail here.
            // in worst case this means only we don't have a never expiring token
        }
        
        try {
            $accessTokenMetadata = $facebookClient->get('/debug_token', ['input_token' => $accessToken->getToken()]);
        } catch (\Throwable $e) {
            $content = [
                'error' => [
                    'reason' => 'debug token fetch error',
                    'message' => $e->getMessage()
                ]
            ];
            return $this->buildConnectResponse($content, 500);
        }
        
        $expiresAt = null;
        if (is_array($accessTokenMetadata) && !empty($accessTokenMetadata['data']['expires_at'])) {
            $expiresAt = $accessTokenMetadata['data']['expires_at'];
        }
        
        $feed->setConnectorConfigurationValue('access_token', $accessToken);
        $feed->setConnectorStatus(ConnectorInterface::STATUS_CONNECTED);
        
        $this->feedRepository->update($feed);
        $this->persistenceManager->persistAll();
        
        $content = [
            'access_token' => $accessToken->getToken(),
            'expires_at' => $expiresAt
        ];
        
        return $this->buildConnectResponse($content);
    }
    
    public function disconnect(ServerRequestInterface $request): ResponseInterface
    {
        $feed = $this->feedRepository->findByUid($request->getQueryParams()['feed_id']);
        
        if (!$feed instanceof Feed) {
            return new JsonResponse(['error' => ['message' => 'feed not found']], 404);
        }
        
        $feed->setConnectorConfigurationValue('access_token', '');
        $feed->setConnectorStatus('');
        
        $this->feedRepository->update($feed);
        $this->persistenceManager->persistAll();
        
        return new JsonResponse(['success' => true]);
    }
    
    public function debugToken(ServerRequestInterface $request): ResponseInterface
    {
        $feed = $this->feedRepository->findByUid($request->getQueryParams()['feed_id']);
    
        if (!$feed instanceof Feed) {
            return new JsonResponse(['error' => ['message' => 'feed not found']], 404);
        }
    
        $connectorConfiguration = $feed->getConnectorConfiguration();
    
        $facebookClient = new FacebookClient($connectorConfiguration);
    
        try {
            $accessTokenMetadata = $facebookClient->get('/debug_token', ['input_token' => $connectorConfiguration['access_token']]);
        } catch (\Throwable $e) {
            $content = [
                'error' => [
                    'reason' => 'debug token fetch error',
                    'message' => $e->getMessage()
                ]
            ];
            return new JsonResponse($content, 500);
        }
        
        return new JsonResponse($accessTokenMetadata);
    }
    
    protected function buildConnectResponse(array $content, int $statusCode = 200): ResponseInterface
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:social_data/Resources/Private/Backend/Templates/ConnectCallback.html'));
        $view->assign('content', $content);
    
        return new HtmlResponse($view->render(), $statusCode);
    }
    
    protected function generateRedirectUri(): string
    {
        return $this->uriBuilder->buildUriFromRoute('socialdata_facebook_connect_callback', [],UriBuilder::ABSOLUTE_URL);
    }
}

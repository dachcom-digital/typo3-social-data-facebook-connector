define(
    [
        'TYPO3/CMS/Core/Event/RegularEvent',
        'TYPO3/CMS/Core/Ajax/AjaxRequest',
        'TYPO3/CMS/Backend/Severity',
        'TYPO3/CMS/Backend/Modal',
        'TYPO3/CMS/Backend/ActionButton/DeferredAction',
        'TYPO3/CMS/Backend/Element/SpinnerElement',
        'TYPO3/CMS/SocialData/ConnectWindow',
    ],
    function(RegularEvent, AjaxRequest, Severity, Modal, DeferredAction, SpinnerElement, ConnectWindow) {
        return new class {
            constructor() {

                let self = this;
                let facebookConnectButton = document.querySelector('#facebook-connector-connect-btn');
                let facebookDisconnectButton = document.querySelector('#facebook-connector-disconnect-btn');
                let debugTokenButton = document.querySelector('#facebook-connector-debug-token-btn');

                if (facebookConnectButton) {
                    let feedId = facebookConnectButton.dataset.feedId;
                    new RegularEvent('click', function (e) {
                        Modal.confirm(
                            'Facebook Connect Service',
                            'You have to create a connection to this application',
                            Severity.info,
                            [
                                {
                                    text: 'Cancel',
                                    btnClass: 'btn-default',
                                    trigger: function() {
                                        Modal.dismiss();
                                    }
                                },
                                {
                                    text: 'OK',
                                    btnClass: 'btn-primary',
                                    action: new DeferredAction(function() {
                                        let connectUrl = TYPO3.settings.ajaxUrls['socialdata_facebook_connect'] + '?feed_id=' + feedId;
                                        let connectWindow = new ConnectWindow(
                                            connectUrl,
                                            function(stateData) {
                                                self.stateHandler(stateData);
                                            },
                                            function(error) {
                                                self.stateHandler(error);
                                            }
                                        );
                                        connectWindow.open();
                                    }),
                                }
                            ]
                        );
                    }).bindTo(facebookConnectButton);
                }

                if (facebookDisconnectButton) {
                    let feedId = debugTokenButton.dataset.feedId;
                    new RegularEvent('click', function (e) {
                        Modal.confirm(
                            'Facebook Connect Service',
                            'Really disconnect?',
                            Severity.warning,
                            [
                                {
                                    text: 'Cancel',
                                    btnClass: 'btn-default',
                                    trigger: function() {
                                        Modal.dismiss();
                                    }
                                },
                                {
                                    text: 'Disconnect',
                                    btnClass: 'btn-warning',
                                    action: new DeferredAction(async function() {
                                        let disconnectUrl = TYPO3.settings.ajaxUrls['socialdata_facebook_disconnect'] + '?feed_id=' + feedId;
                                        let request = new AjaxRequest(disconnectUrl);
                                        await request.get().then(
                                            async function (response) {
                                                const data = await response.resolve();
                                                if (data && data.success) {
                                                    window.location.reload();
                                                } else {

                                                }
                                            }
                                        );
                                    })
                                }
                            ]
                        )
                    }).bindTo(facebookDisconnectButton);
                }

                if (debugTokenButton) {
                    let feedId = debugTokenButton.dataset.feedId;
                    new RegularEvent('click', function (e) {
                        let debugTokenUrl = TYPO3.settings.ajaxUrls['socialdata_facebook_debugtoken'] + '?feed_id=' + feedId;
                        let request = new AjaxRequest(debugTokenUrl);
                        request.get().then(
                            async function (response) {
                                const data = await response.resolve();
                                Modal.confirm(
                                    'Debug Token',
                                    JSON.stringify(data),
                                    Severity.info,
                                    [
                                        {
                                            text: 'OK',
                                            btnClass: 'btn-default',
                                            trigger: function() {
                                                Modal.dismiss();
                                            }
                                        },
                                    ]
                                );
                            },
                            function (error) {
                                console.error('Request failed: ' + error.status + ' ' + error.message);
                            }
                        );
                    }).bindTo(debugTokenButton)
                }
            }

            stateHandler(stateData) {
                let statusElement = document.querySelector('#connector-status-message');
                if (statusElement) {
                    statusElement.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning');
                    if (stateData.error) {
                        statusElement.classList.add('alert-danger');
                        statusElement.innerHTML = stateData.error.reason + ': ' + stateData.error.message;
                    } else {
                        statusElement.classList.add('alert-success');
                        statusElement.innerHTML = 'connection successful. reloading...';
                        window.location.reload();
                    }
                }
            }

        }
    }
);

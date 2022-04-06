<?php
defined('TYPO3') or die();

call_user_func(static function () {
    $GLOBALS['TCA']['tx_socialdata_domain_model_post']['ctrl']['typeicon_classes']['facebook'] = 'actions-brand-facebook';
});

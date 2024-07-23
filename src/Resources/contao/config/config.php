<?php

namespace Merconis\ThemeInstaller;

use Contao\System;
use Symfony\Component\HttpFoundation\Request;

if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))) {
	$GLOBALS['TL_CSS'][] = 'bundles/leadingsystemsmerconisthemeinstaller/be/css/style.css';
}

// API
$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('LeadingSystems\MerconisThemeInstallerBundle\API\APIGeneral', 'processRequest');

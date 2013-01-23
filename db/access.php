<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'report/mergeusers:view' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
        ),
    )
);



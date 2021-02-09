<?php

// The strings to be shown in the extension's list.
$_['heading_title'] = 'Special Timer';
$_['text_success'] = 'Success. You have modified module "Special Timer".';

/* I despise Opencart's method of providing views with language strings, so
   I extended it to be a less verbose and more reliable one.
   $_['domain']['key'] in here should become $domain_key in the view.*/

$_['common'] = array(
    'edit' => 'Edit Special Timer Module',
    'home' => 'Home',
    'extensions' => 'Extensions',
    'modules' => 'Modules',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'error' => 'ERROR: ',
    'timezone_warning' => 'NOTE: You must have a correct timezone on both your PHP engine and your database for the correct work of specials. The adjusting of the timezone is not the aim of this module.',
    'timezone_warning_close' => 'Close',
    'timezone_warning_close_never' => 'Close and never show again',
);

$_['form'] = array(
    'name' => 'Module name',
    'status' => 'Status:',
    'enabled' => 'Enabled',
    'disabled' => 'Disabled',
    'view' => 'View:',
    'textual' => 'Textual',
    'simple' => 'Simple',
    'layouts' => 'Show on Layouts:',
    'layouts_help' => 'suggested layouts are marked <b>in bold</b>',
    'featured_label' => 'Show in Feature module',
    'clock_label' => 'Clock:',
    'clock_browser_label' => 'Browser',
    'clock_browser_help' => 'Use the clock from the user\'s operating system.',
    'clock_server_label' => 'Server',
    'clock_server_help' => 'Push server time to the browser. It could help if the user has wrong time settings in the operating system, but this method has low accuracy and may confuse the user.',
    'clock_combo_label' => 'Combo',
    'clock_combo_help' => 'Use server time only if the difference with the browser time is very big.',
    'clock_critdiff' => 'Critical difference (sec):',
    'adv_setting' => 'advanced setting',
);

$_['error'] = array(
    'permission' => 'You have no permission to modify this module.',
    'name' => 'Name is required and must have from 1 to 200 valid characters.',
    'status' => 'Status is required and must be 0 or 1.',
    'view' => 'View is required and must be correct.',
    'layouts' => 'Layouts are required and must be correct.',
    'clock' => 'Clock is required and must be correct.',
    'clock--critdiff' => 'The critical difference must be positive and not huge integer.',
);

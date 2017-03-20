<?php

/**
 * Send emails via SMTP.
 */
add_action('phpmailer_init', 'custom_phpmailer_init');
function custom_phpmailer_init($phpmailer)
{
    $phpmailer->IsSMTP();
    $phpmailer->Host = 'smtp.example.sk';
    $phpmailer->SMTPDebug = 0;
    $phpmailer->SMTPAuth = true;
    $phpmailer->SMTPSecure = 'ssl';
    $phpmailer->Port = 465;
    $phpmailer->Username = 'php@example.sk';
    $phpmailer->Password = 'PassWordHere';

    return $phpmailer;
}

<?php

namespace com_ra_mailman;

defined('_JEXEC') or die;

class Mailhelper
{
    private $toolsHelper;

    public function __construct()
    {
        $this->toolsHelper = new \com_ra_mailman\ToolsHelper;
    }

    /**
     * Generates the responsive email header with text left-aligned and logo right-aligned
     * Uses flexbox for responsive layout that works on all screen sizes
     */
    public function buildEmailHeader($params)
    {
        $logo = '/images/com_ra_mailman/' . $params->get('logo_file');
        $logoAlign = $params->get('logo_align', 'right');
        $flexDirection = $logoAlign === 'left' ? 'row-reverse' : 'row';
        $textAlign = $logoAlign === 'left' ? 'right' : 'left';

        $header = '<div style="';
        $header .= 'display: flex; ';
        $header .= 'flex-direction: ' . $flexDirection . '; ';
        $header .= 'justify-content: space-between; ';
        $header .= 'align-items: center; ';
        $header .= 'gap: 20px; ';
        $header .= 'background: ' . $params->get('colour_header', 'rgba(20, 141, 168, 0.5)') . '; ';
        $header .= 'border-radius: 5%; ';
        $header .= 'padding: 20px; ';
        $header .= 'box-sizing: border-box; ';
        $header .= 'width: 100%; ';
        $header .= 'max-width: 100%; ';
        $header .= 'overflow: hidden; ';
        $header .= '">';

        $header .= '<div style="flex: 1 1 auto; text-align: ' . $textAlign . '; min-width: 0; overflow-wrap: break-word;">';
        $header .= $params->get('email_header');
        $header .= '</div>';

        if (file_exists(JPATH_ROOT . $logo)) {
            $image_data = file_get_contents(JPATH_ROOT . $logo);
            $encoded = base64_encode($image_data);
            $header .= '<a href="' . $params->get('website') . '" style="flex-shrink: 0; display: flex;">';
            $header .= '<img src="data:image/jpeg;base64,' . $encoded . '" ';
            $header .= 'style="height: ' . $params->get('height') . 'px; width: ' . $params->get('width') . 'px; display: block; max-width: 100%; height: auto;" ';
            $header .= 'alt="Logo">';
            $header .= '</a>';
        } else {
            Factory::getApplication()->enqueueMessage('Logo file "' . $logo . '" not found', 'warning');
        }

        $header .= '</div>';
        return $header;
    }

    /**
     * Generates the responsive email footer with text left-aligned and logo right-aligned
     * Uses flexbox for responsive layout that works on all screen sizes
     */
    public function buildEmailFooter($params)
    {
        $logo = '/images/com_ra_mailman/' . $params->get('logo_file');
        $logoAlign = $params->get('logo_align', 'right');
        $flexDirection = $logoAlign === 'left' ? 'row-reverse' : 'row';
        $textAlign = $logoAlign === 'left' ? 'right' : 'left';

        $footer = '<div style="';
        $footer .= 'display: flex; ';
        $footer .= 'flex-direction: ' . $flexDirection . '; ';
        $footer .= 'justify-content: space-between; ';
        $footer .= 'align-items: center; ';
        $footer .= 'gap: 20px; ';
        $footer .= 'background: ' . $params->get('colour_header', 'rgba(20, 141, 168, 0.5)') . '; ';
        $footer .= 'border-radius: 5%; ';
        $footer .= 'padding: 20px; ';
        $footer .= 'box-sizing: border-box; ';
        $footer .= 'width: 100%; ';
        $footer .= 'max-width: 100%; ';
        $footer .= 'overflow: hidden; ';
        $footer .= '">';

        $footer .= '<div style="flex: 1 1 auto; text-align: ' . $textAlign . '; min-width: 0; overflow-wrap: break-word;">';
        $footer .= $params->get('email_footer');
        $footer .= '</div>';

        if (file_exists(JPATH_ROOT . $logo)) {
            $image_data = file_get_contents(JPATH_ROOT . $logo);
            $encoded = base64_encode($image_data);
            $footer .= '<a href="' . $params->get('website') . '" style="flex-shrink: 0; display: flex;">';
            $footer .= '<img src="data:image/jpeg;base64,' . $encoded . '" ';
            $footer .= 'style="height: ' . $params->get('height') . 'px; width: ' . $params->get('width') . 'px; display: block; max-width: 100%; height: auto;" ';
            $footer .= 'alt="Logo">';
            $footer .= '</a>';
        } else {
            Factory::getApplication()->enqueueMessage('Logo file "' . $logo . '" not found', 'warning');
        }

        $footer .= '</div>';
        return $footer;
    }
}
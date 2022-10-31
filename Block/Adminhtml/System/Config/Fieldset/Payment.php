<?php

namespace Geidea\Payment\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Config\Model\Config;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Payment extends Fieldset
{    
    /**
     * @var Config
     */
    private $config;

    /**
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Session $authSession
     * @param FilterBuilder $filterBuilder
     * @param Js $jsHelper
     * @param Config $config
     * @param SecureHtmlRenderer $secureRenderer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        Config $config,
        SecureHtmlRenderer $secureRenderer,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $authSession,
            $jsHelper,
            $data,
            $secureRenderer
        );
        $this->config         = $config;
        $this->secureRenderer = $secureRenderer;
    }

    /**
     * @param AbstractElement $element
     */
    protected function _getFrontendClass($element)
    {
        return parent::_getFrontendClass($element) . ' with-button';
    }

    /**
     * @param AbstractElement $element
     */
    protected function _getHeaderTitleHtml($element)
    {
        $html = '<div class="config-heading" >';
        $htmlId = $element->getHtmlId();
        $html .= '<div class="button-container"><button type="button"' .
            ' disabled="disabled"' .
            ' class="button action-configure' .
            ' disabled' .
            '" id="' . $htmlId . '-head" >' .
            '<span class="state-closed">' . __(
                'Configure'
            ) . '</span><span class="state-opened">' . __(
                'Close'
            ) . '</span></button>';

        $html .= /* @noEscape */ $this->secureRenderer->renderEventListenerAsTag(
            'onclick',
            "geideaToggleSolution.call(this, '" . $htmlId . "', '" . $this->getUrl('adminhtml/*/state') .
            "');event.preventDefault();",
            'button#' . $htmlId . '-head'
        );

        $html .= '</div>';
        $html .= '<div class="heading"><strong>' . $element->getLegend() . '</strong>';

        if ($element->getComment()) {
            $html .= '<span class="heading-intro">' . $element->getComment() . '</span>';
        }
        $html .= '<div class="config-alt"></div>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * @param AbstractElement $element
     */
    protected function _getHeaderCommentHtml($element)
    {
        return '';
    }

    /**
     * @param AbstractElement $element
     */
    protected function _isCollapseState($element)
    {
        return false;
    }

    /**
     * @param AbstractElement $element
     */
    protected function _getExtraJs($element)
    {
        $script = "require(['jquery', 'prototype'], function(jQuery){
            window.geideaToggleSolution = function (id, url) {
                var doScroll = false;
                Fieldset.toggleCollapse(id, url);
                if ($(this).hasClassName(\"open\")) {
                    \$$(\".with-button button.button\").each(function(anotherButton) {
                        if (anotherButton != this && $(anotherButton).hasClassName(\"open\")) {
                            $(anotherButton).click();
                            doScroll = true;
                        }
                    }.bind(this));
                }
                if (doScroll) {
                    var pos = Element.cumulativeOffset($(this));
                    window.scrollTo(pos[0], pos[1] - 45);
                }
            }
        });";

        return $this->_jsHelper->getScript($script);
    }
}

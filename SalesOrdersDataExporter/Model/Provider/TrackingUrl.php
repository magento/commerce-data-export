<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */

declare(strict_types=1);

namespace Magento\SalesOrdersDataExporter\Model\Provider;

use Magento\Framework\Url\Encoder;
use Magento\Framework\UrlInterface;

/**
 * Class for getting tracking url.
 */
class TrackingUrl
{
    /**
     * @var UrlInterface
     */
    private $url;
    /**
     * @var Encoder
     */
    private $encoder;

    /**
     * @param UrlInterface $url
     * @param Encoder $encoder
     */
    public function __construct(
        UrlInterface $url,
        Encoder $encoder
    ) {
        $this->url = $url;
        $this->encoder = $encoder;
    }

    /**
     * Getting tracking url.
     *
     * @param array $values
     * @return array
     */
    public function get(array $values): array
    {
        foreach ($values as &$value) {
            /**
             * lightweight replacement of the original function
             * @see \Magento\Shipping\Helper\Data::_getTrackingUrl
             */
            $urlPart = "track_id:{$value['id']}:{$value['protect_code']}";

            $params = [
                '_scope' => $value['store_id'],
                '_nosid' => true,
                '_direct' => 'shipping/tracking/popup',
                '_query' => ['hash' => $this->encoder->encode($urlPart)]
            ];
            $value['trackingUrl'] = $this->url->getUrl('', $params);
        }

        return $values;
    }
}

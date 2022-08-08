<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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

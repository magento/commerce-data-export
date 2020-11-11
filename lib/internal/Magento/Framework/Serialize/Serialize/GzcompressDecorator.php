<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Serialize\Serializer;

use Magento\Framework\Serialize\SerializerInterface;

/**
 * Compress data.
 */
class GzcompressDecorator implements SerializerInterface
{
    /**
     * Json serializer.
     *
     * @var Json
     */
    private $serializer;

    /**
     * @param Json $serializer
     */
    public function __construct(Json $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function serialize($data)
    {
        return gzcompress($this->serializer->serialize($data), 9);
    }

    /**
     * @inheritDoc
     */
    public function unserialize($string)
    {
        return $this->serializer->unserialize(gzuncompress($string));
    }
}

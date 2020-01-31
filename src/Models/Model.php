<?php declare(strict_types=1);
namespace NAVIT\AzureAd\Models;

use NAVIT\AzureAd\Exceptions\InvalidArgumentException;
use GuzzleHttp\Psr7\Response;

abstract class Model {
    /**
     * Create an instance from an array
     *
     * @throws InvalidArgumentException
     * @return self
     */
    abstract public static function fromArray(array $data);

    /**
     * Create an instance from an API response
     *
     * @throws InvalidArgumentException
     */
    public static function fromApiResponse(Response $response) : self {
        return self::fromArray(json_decode($response->getBody()->getContents(), true));
    }
}
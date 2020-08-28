<?php declare(strict_types=1);
namespace NAVIT\AzureAd\Models;

use Psr\Http\Message\ResponseInterface;
use InvalidArgumentException;

abstract class Model {
    /**
     * Create an instance from an array
     *
     * @param array<string, mixed> $data
     * @throws InvalidArgumentException
     * @return self
     */
    abstract public static function fromArray(array $data);

    /**
     * Create an instance from an API response
     *
     * @throws InvalidArgumentException
     */
    public static function fromApiResponse(ResponseInterface $response) : self {
        /** @var array<string, mixed> */
        $data = json_decode($response->getBody()->getContents(), true);
        return static::fromArray($data);
    }
}
<?php declare(strict_types=1);
namespace NAVIT\AzureAd\Models;

use NAVIT\AzureAd\Exceptions\InvalidArgumentException;

class User {
    private $id;
    private $displayName;
    private $mail;

    public function __construct(string $id, string $displayName, string $mail) {
        $this->id          = $id;
        $this->displayName = $displayName;
        $this->mail        = $mail;
    }

    public function getId() : string {
        return $this->id;
    }

    public function getDisplayName() : string {
        return $this->displayName;
    }

    public function getMail() : string {
        return $this->mail;
    }

    public static function fromArray(array $data) : self {
        foreach (['id', 'displayName', 'mail'] as $required) {
            if (empty($data[$required])) {
                throw new InvalidArgumentException(sprintf('Missing data element: %s', $required));
            }
        }

        return new static(
            $data['id'],
            $data['displayName'],
            $data['mail']
        );
    }
}
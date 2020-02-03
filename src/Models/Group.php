<?php declare(strict_types=1);
namespace NAVIT\AzureAd\Models;

use InvalidArgumentException;

class Group extends Model {
    private $id;
    private $displayName;
    private $description;
    private $mail;

    public function __construct(string $id, string $displayName, string $description, string $mail) {
        $this->id          = $id;
        $this->displayName = $displayName;
        $this->description = $description;
        $this->mail        = $mail;
    }

    public function getId() : string {
        return $this->id;
    }

    public function getDisplayName() : string {
        return $this->displayName;
    }

    public function getDescription() : string {
        return $this->description;
    }

    public function getMail() : string {
        return $this->mail;
    }

    public static function fromArray(array $data) : self {
        foreach (['id', 'displayName', 'description', 'mail'] as $required) {
            if (empty($data[$required])) {
                throw new InvalidArgumentException(sprintf('Missing data element: %s', $required));
            }
        }

        return new static(
            $data['id'],
            $data['displayName'],
            $data['description'],
            $data['mail']
        );
    }
}